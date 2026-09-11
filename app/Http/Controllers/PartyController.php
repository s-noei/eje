<?php

namespace App\Http\Controllers;

use App\Game\Support\Constants;
use Illuminate\Http\Request;

/**
 * Port of party.php / partyinfo.php + include/party/{members,ppCandidates,cgCandidates}.php.
 */
class PartyController extends GameController
{
    /** party.php */
    public function index()
    {
        if (!$this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $c = $this->citInfo;
        if ($c['PartyID']) {
            return redirect($this->vars->getURL('party', $c['PartyID']));
        }

        return $this->page('pages.party.index', [
            'notNational' => $c['nationality'] != $c['CountryID'],
        ], ['title' => $this->lang->getstr('title_party', 'title'), 'bar_title' => 'Your political statement', 'actiontype' => 'party']);
    }

    /** partyinfo.php: party-{id}[-{go}[-{id2}]].html */
    public function show(Request $request, int $pID, ?string $go = null, ?int $id2 = null)
    {
        $db = $this->database;
        $row = $db->row('SELECT * FROM party WHERE pID = ?', [$pID]);
        if (!$row) {
            return redirect('/index.html');
        }
        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'] ?? 0;
        $logged = $this->loggedIn();
        $now = $this->session->getTodayArray();
        $day = $now['Day'];
        $errors = [];
        $isPP = $logged && $me == $row['PP'];
        $isPPCandidate = $logged ? $db->isPPCandidate($me) : 0;
        $isCGCandidate = $logged ? $db->isCGCandidate($me) : 0;
        $back = fn () => redirect($request->getRequestUri());
        $pol = $this->politics();

        if ($logged && $request->isMethod('post')) {
            if ($request->has('Join')) {
                if ($citInfo['nationality'] != $citInfo['CountryID']) {
                    $errors[] = 'Sorry, but you are not a national member of this country. Please change your nationality in order to do political actions here.';
                } elseif ($citInfo['PartyID']) {
                    $errors[] = "As it seems, you're already a party member...";
                } else {
                    $pol->joinParty($pID, $me);
                    $this->session->fillInfo(null, true);

                    return $back();
                }
            }
            if ($request->has('Resign')) {
                if ($isPP) {
                    $errors[] = 'First resign party presidency...';
                } elseif ($isCGCandidate) {
                    $errors[] = 'First resign congress candidacy...';
                } elseif ($isPPCandidate) {
                    $errors[] = 'First resign Party Presidency candidacy...';
                } elseif ($citInfo['PartyID'] == $pID) {
                    if ($row['cpProposed'] == $me) {
                        if ($row['PP'] != $me) {
                            $db->sendNote($row['PP'], '', 'We inform that your proposed CP has been resigned from party. Please propose a new CP until the elections.');
                        }
                        $db->updatePartyField($pID, 'cpProposed', '');
                    }
                    if ($row['coPP'] == $me) {
                        $db->sendNote($row['PP'], '', 'We inform that your selected co-PP has been resigned from party. Please select a new co-PP.');
                        $db->updatePartyField($pID, 'coPP', '');
                    }
                    $this->elections()->resignParty($pID, $me);
                    $this->session->fillInfo(null, true);

                    return $back();
                }
            }
            if ($request->has('resignPP')) {
                if (!$isPP) {
                    $errors[] = 'You are not the party president of this party';
                } else {
                    if ($row['coPP']) {
                        $nextPP = $row['coPP'];
                    } else {
                        $members = $db->getPartyMembers($pID);
                        $next = $members[0] ?? null;
                        if ($next && $next['CitizenID'] == $me) {
                            $next = $members[1] ?? null;
                        }
                        $nextPP = $next['CitizenID'] ?? 0;
                    }
                    $pol->setPP($pID, $nextPP);

                    return $back();
                }
            }
            if ($request->filled('candidate')) {
                if ($citInfo['puberty'] < 6) {
                    $errors[] = sprintf($this->lang->getstr('error_puberty', 'msgs'), $this->lang->getstr('puberty_6'), 250);
                } else {
                    $r = $this->candidacy($request, $row, $isPPCandidate, $isCGCandidate, $errors);
                    if ($r) {
                        return $r;
                    }
                }
            }
            if ($isPP && $request->has('editOk')) {
                $db->updatePartyField($pID, 'pName', strip_tags((string) $request->input('pName')));
                $logo = $request->file('partyLogo');
                if ($logo) {
                    if ($logo->isValid() && in_array($logo->getMimeType(), ['image/jpeg', 'image/pjpeg']) && $logo->getSize() < 51200) {
                        $fName = md5($pID.'PaRtY').'.jpg';
                        $logo->move(public_path('uploads/avatars/party'), $fName);
                        $db->updatePartyField($pID, 'pLogo', $fName);
                    } else {
                        $errors[] = 'The file you specified is not correct.';
                    }
                }
                if (!$errors) {
                    return $back();
                }
            }
            if ($isPP && $request->filled('cpPropID')) {
                $cpID = (int) $request->input('cpPropID');
                $tokOk = $request->input('token') === md5($cpID.'thisis @ 30p c@ndid@+e');
                if ($request->has('cpprp')) {
                    if (!$tokOk) {
                        $errors[] = 'Cheating found';
                    } elseif (!$pol->isPartyMember($cpID, $pID)) {
                        $errors[] = 'The candidate proposed is not a member of this party';
                    } else {
                        if ($cpID != $row['cpProposed']) {
                            if ($row['cpProposed'] && $row['cpProposed'] != $row['PP']) {
                                $db->sendNote($row['cpProposed'], '', "Unfortunately, your party president has been decided to propose somebody else as the party's country presidency candidate.");
                            }
                            $db->updatePartyField($pID, 'cpProposed', $cpID);
                            if ($row['PP'] != $cpID) {
                                $db->sendNote($cpID, '', "Your party president has been decided to propose you as the party's country presidency candidate. You can click <a href=\"".$this->vars->getURL('country', $row['CountryID'], 'cpcandidates').'">here</a> to see your opponents');
                            }
                        }

                        return redirect($this->vars->getURL('party', $pID));
                    }
                }
                if ($request->has('ppsup')) {
                    if (!$tokOk) {
                        $errors[] = 'Cheating found';
                    } elseif ($row['PP'] == $cpID) {
                        $errors[] = 'You cannot select yourself as a VP';
                    } elseif (!$pol->isPartyMember($cpID, $pID)) {
                        $errors[] = 'The selected citizen is not a member of this party';
                    } else {
                        if ($cpID != $row['coPP']) {
                            if ($row['coPP']) {
                                $db->sendNote($row['coPP'], '', "Unfortunately, your party president has been decided to select somebody else as the party's vice president.");
                            }
                            $db->updatePartyField($pID, 'coPP', $cpID);
                            $db->sendNote($cpID, '', "Your party president has been decided to select you as the party's vice president. If the party president resignes from party, you'll be the next party president");
                        }

                        return redirect($this->vars->getURL('party', $pID));
                    }
                }
            }
            if ($go === 'cgCandidates' && $request->input('subChange')) {
                $uID = (int) $request->input('uID');
                $new = (float) $request->input('newOrder');
                if ($request->input('token') !== md5($uID.'k3y4 chanjing 0rd3r')) {
                    $errors[] = 'Cheating?!';
                } elseif ($new < 0) {
                    $errors[] = 'Use only numbers';
                } elseif ($isPP) {
                    $pol->setCGCandidateOrder($pID, $uID, $new);

                    return $back();
                }
            }
        }

        $country = $db->row('SELECT cName, Flag, CountryID FROM country WHERE CountryID = ?', [$row['CountryID']]) ?: ['cName' => 'Error!', 'Flag' => '', 'CountryID' => 0];
        $data = [
            'row' => $row, 'errors' => $errors, 'isPP' => $isPP, 'isPPCandidate' => $isPPCandidate, 'isCGCandidate' => $isCGCandidate,
            'country' => $country, 'pp' => $row['PP'] ? $db->getUserInfoFromID($row['PP']) : null, 'day' => $day, 'go' => $go,
            'editForm' => $isPP && $request->has('editParty'),
        ];
        $title = $this->lang->getstr('title_party_info', 'title');
        $bar = 'Party Info';

        switch ($go) {
            case 'members':
                $data['members'] = $db->getPartyMembers($pID);
                $title = $this->lang->getstr('title_party_members', 'title');
                $bar = 'Party members';
                break;
            case 'ppCandidates':
                $data['ppCandidates'] = $db->getPartyCandidates($pID);
                $title = $this->lang->getstr('title_party_members', 'title');
                break;
            case 'cgCandidates':
                $regID = (int) $id2;
                $regions = $db->rows('SELECT * FROM region WHERE CountryID = ? ORDER BY rName', [$row['CountryID']]);
                if ($regID && !array_filter($regions, fn ($r) => $r['RegionID'] == $regID)) {
                    return redirect($this->vars->getURL('party', $pID, 'cgCandidates'));
                }
                $qual = $pol->getCountryCGQualifies($row['CountryID']);
                $reg = $regID ? ($db->row('SELECT stat_pop FROM region WHERE RegionID = ?', [$regID]) ?: ['stat_pop' => 0]) : ['stat_pop' => 0];
                if (empty($qual['xC'])) {
                    $cgCount = $reg['stat_pop'] > 100 ? $qual['xP'] : ($reg['stat_pop'] > 10 ? $qual['xN'] : 2);
                } else {
                    $cgCount = $qual['xC'];
                }
                $data += ['regID' => $regID, 'regions' => $regions, 'cgCount' => (int) $cgCount, 'cgCands' => $db->getCongressCandidates($pID, $regID ?: '', 1)];
                $title = $this->lang->getstr('title_party_members', 'title');
                $bar .= ' - Congress candidates';
                break;
            default:
                $go = null;
                $data['go'] = null;
                $acc = $db->row("SELECT ROUND(IFNULL(Amount, 0), 2) AS Amount FROM party_money WHERE PartyID = ? AND CurID = '1' LIMIT 1", [$pID]);
                $eor = (int) $row['eOrient'];
                $eOr = (($eor > 1 && $eor < 5) ? 'Center' : 'Far').($eor > 3 ? '-Right' : '-Left');
                $sOr = ['', 'Totalitarian', 'Authoritarian', 'Libertarian', 'Anarchist'];
                $el = $this->elections();
                $lastPP = $el->getLastElection('PP');
                $lastCG = $el->getLastElection('CG');
                $lastCP = $el->getLastElection('CP');
                $mk = fn ($type, $last, $p2) => !empty($last['timestamp'])
                    ? $this->vars->getURL('elections', $type, $row['CountryID'], $p2, date('Y', $last['timestamp']), date('m', $last['timestamp']))
                    : 'javascript:;';
                $data += [
                    'amount' => $acc['Amount'] ?? '0.00',
                    'orientation' => "$eOr, ".($sOr[(int) $row['sOrient']] ?? ''),
                    'memberCount' => count($db->getPartyMembers($pID)),
                    'isMember' => $logged ? $db->isMember($me, $pID) : 0,
                    'coPP' => $row['coPP'] ? $db->getUserInfoFromID($row['coPP']) : null,
                    'cpProposed' => $row['cpProposed'] ? $db->getUserInfoFromID($row['cpProposed']) : null,
                    'cp' => $pol->getCP($row['CountryID'], 1),
                    'lastPPLink' => $mk('pp', $lastPP, $pID),
                    'lastCGLink' => $mk('cg', $lastCG, 0),
                    'lastCPLink' => $mk('cp', $lastCP, ''),
                    'nextPP' => $el->getNextElection('PP'),
                    'nextCG' => $el->getNextElection('CG'),
                    'nextCP' => $el->getNextElection('CP'),
                    'ppCandCount' => count($db->getPartyCandidates($pID)),
                    'cgCandCount' => count($db->getCongressCandidates($pID)),
                    'cgSeats' => $pol->getPartyCGMembers($pID, 0),
                    'cgGained' => $pol->getPartyCGMembers($pID, 1),
                    'now' => $now,
                    'CG_SORT_DAY' => Constants::CG_SORT_DAY,
                    'CG_PROPOSE_START' => Constants::CG_PROPOSE_START,
                    'CG_PROPOSE_DUE' => Constants::CG_PROPOSE_DUE,
                ];
        }

        return $this->page('pages.party.show', $data, ['title' => $title, 'bar_title' => $bar, 'actiontype' => 'partyinfo']);
    }

    /** "candidate" POST: pp/cg be/re. Returns a redirect or null (errors appended). */
    private function candidacy(Request $request, array $row, int $isPPCandidate, int $isCGCandidate, array &$errors)
    {
        $db = $this->database;
        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'];
        $do = (string) $request->input('do', '');
        $what = $request->input('candidate');
        $pol = $this->politics();
        $back = fn () => redirect($request->getRequestUri());

        if ($citInfo['nationality'] != $row['CountryID']) {
            $errors[] = "Your nationality differs to the party's country.";

            return null;
        }
        if ($citInfo['PartyID'] != $row['pID']) {
            $errors[] = $do === 're' ? 'Are you cheating?! You can only resign candidacy in your own party!' : 'Are you cheating?! You can only be a candidate in your own party!';

            return null;
        }
        if ($what === 'pp') {
            if ($do === 'be') {
                $row2 = $db->row('SELECT * FROM party_members WHERE PartyID = ? AND CitizenID = ?', [$row['pID'], $me]);
                $citMoney = $db->getCitizenMoney($me);
                if ($row2 && $row2['timestamp'] > (time() - (24 * 3 * 3600))) {
                    $errors[] = 'Your political age in this party is not 3 days yet! You can become a candidate in this party '.$this->session->getDiffF($row2['timestamp'] + (24 * 3 * 3600)).'.';
                } elseif ($isPPCandidate) {
                    $errors[] = 'You became a candidate already!';
                } elseif (($citMoney[1] ?? 0) < 1) {
                    $errors[] = 'You have not enough money to become a PP candidate.';
                } else {
                    $pol->beCandidatePP($me, $row['pID']);

                    return $back();
                }
            } elseif ($do === 're') {
                if (!$isPPCandidate) {
                    $errors[] = 'You are not a candidate!';
                } else {
                    $pol->resignCandidatePP($me, $row['pID']);

                    return $back();
                }
            }
        } elseif ($what === 'cg') {
            if ($do === 'be') {
                if ($isCGCandidate) {
                    $errors[] = 'You became a candidate already!';
                } else {
                    $pol->beCandidateCG($citInfo, $row['pID'], (string) $request->input('docURL', ''));

                    return $back();
                }
            } elseif ($do === 're') {
                if (!$isCGCandidate) {
                    $errors[] = 'You are not a candidate!';
                } else {
                    $pol->resignCandidateCG($me, $row['pID']);

                    return $back();
                }
            }
        }

        return null;
    }
}
