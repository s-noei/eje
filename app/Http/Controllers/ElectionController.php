<?php

namespace App\Http\Controllers;

use App\Game\Support\Constants;
use Illuminate\Http\Request;

/**
 * Port of election.php + include/elections/{pp,cp,cg,cg-shuffle,cg-results}.php.
 */
class ElectionController extends GameController
{
    /**
     * elections.html, election-pp-{country}-{party}-{year}-{month}.html,
     * election-cg-{country}-{region}-{year}-{month}.html, election-cp-{country}-{year}-{month}.html
     */
    public function index(Request $request, string $what = '', int $country = 0, int $p3 = 0, int $year = 0, int $month = 0)
    {
        $db = $this->database;
        $now = $this->session->getTodayArray();
        if ($what === 'cp') {
            // cp URL has no party/region segment: shift the positional params.
            $month = $year;
            $year = $p3;
            $p3 = 0;
        }
        $party = $what === 'pp' ? $p3 : 0;
        $region = $what === 'cg' ? $p3 : 0;
        $year = $year ?: (int) $now['Year'];
        $month = $month ?: (int) $now['Month'];
        if (!in_array($what, ['cg', 'cp', 'pp', ''], true)) {
            $what = '';
        }
        if ($country && $db->isHiddenCountry($country) && !$this->session->isAdmin()) {
            return redirect('/index.html');
        }

        $citInfo = $this->citInfo;
        $logged = $this->loggedIn();
        $errors = [];
        $isAdmin = $this->session->isAdmin();
        $el = $this->elections();

        if ($logged && $what && $request->input('subvote') && $citInfo['puberty'] >= 3) {
            $eID = (int) $request->input('eID');
            $cID = (int) $request->input('cID');
            $eType = (string) $request->input('eType');
            $vID = $citInfo['CitizenID'];
            if ($request->input('token') !== md5($eID.$eType.$vID.$eType.$cID)) {
                $errors[] = 'Cheating?';
            } else {
                match ($what) {
                    'pp' => $el->setVote($eID, $vID, $cID),
                    'cp' => $el->setVote($eID, $vID, $cID, strtolower($eType)),
                    'cg' => $el->setVote($eID, $vID, $cID, 'cg', $citInfo['CountryID']),
                };

                return redirect($request->getRequestUri());
            }
        }

        $data = [
            'what' => $what ?: 'select', 'country' => $country, 'party' => $party, 'region' => $region, 'year' => $year, 'month' => $month,
            'errors' => $errors, 'now' => $now, 'isAdmin' => $isAdmin,
            'countries' => $db->getCountries($isAdmin),
            'dates' => $el->getElectionList($what),
        ];
        if ($country) {
            $coun = $db->getCountryRec($country);
            $data['cName'] = $coun['cName'] ?? 'Select';
            $data['flag'] = $coun ? $this->vars->getImgLoc('CountryFlag').$coun['Flag'].'.gif' : '/images/elections/select.jpg';
        } else {
            $data['cName'] = 'Select';
            $data['flag'] = '/images/elections/select.jpg';
        }

        if ($what === 'pp') {
            $pLogo = '/images/elections/select.jpg';
            if ($party) {
                $p = $db->row('SELECT * FROM party WHERE pID = ? AND CountryID = ?', [$party, $country]);
                if (!$p) {
                    $party = $data['party'] = 0;
                } else {
                    $pLogo = $this->vars->getImgLoc('PartyLogo').$p['pLogo'];
                }
            }
            $data['pLogo'] = $pLogo;
            $data['parties'] = $el->getPartyList($country);
            $shuff = ($now['Day'] == Constants::ELECTIONS_PP_DAY && !$isAdmin) ? '1' : '';
            $data['voting'] = (bool) $shuff;
            $data['rows'] = $el->getElectionPP($country, $party, $year, $month, $shuff);
            $data['voted'] = fn ($eID) => $logged ? $el->isVoted($citInfo['CitizenID'], $eID) : 0;
        } elseif ($what === 'cp') {
            $shuff = ($now['Day'] == Constants::ELECTIONS_CP_DAY && !$isAdmin) ? '1' : '';
            $data['voting'] = (bool) $shuff;
            $data['rows'] = $el->getElectionCP($country, $year, $month, $shuff);
            $data['voted'] = fn ($eID) => $logged ? $el->isVoted($citInfo['CitizenID'], $eID, 'cp') : 0;
        } elseif ($what === 'cg') {
            if ($country) {
                $data['regions'] = $db->rows('SELECT RegionID, rName FROM region WHERE CountryID = ? ORDER BY rName', [$country]);
            }
            // Legacy cg.php forces the shuffle (voting) view for everyone but admins.
            $shuff = !$isAdmin;
            $data['voting'] = $shuff;
            if ($shuff) {
                $data['rows'] = $el->getElectionCG($country, $region, $year, $month, 1);
                $data['voted'] = fn ($eID) => $logged ? $el->isVoted($citInfo['CitizenID'], $eID, 'cg') : 0;
            } else {
                $groups = [];
                if (!$region) {
                    $groups['Populous regions'] = $el->getElectionCG($country, $region, $year, $month, 0, 1);
                    $groups['Normal regions'] = $el->getElectionCG($country, $region, $year, $month, 0, 2);
                    $groups['Desert regions'] = $el->getElectionCG($country, $region, $year, $month, 0, 3);
                } else {
                    $groups['Qualified'] = $el->getElectionCG($country, $region, $year, $month, 0, 'qual');
                }
                $groups['Wildcard'] = $el->getElectionCG($country, $region, $year, $month, 0, 4);
                $groups['Not qualified'] = $el->getElectionCG($country, $region, $year, $month);
                $data['groups'] = $groups;
            }
        }

        return $this->page('pages.elections.index', $data, ['title' => $this->lang->getstr('title_elections', 'title'), 'bar_title' => 'Election Info', 'actiontype' => 'elections']);
    }
}
