<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of countryinfo.php + include/country/* (society, economy, politics,
 * military, congress, requests, region/travel, law + law/*), cpcandidates.php and ejlaws.php.
 */
class CountryController extends GameController
{
    public const GODS = ['', 'Darkness', 'War', 'Work', 'Life', 'Chance', 'Peace'];

    /** country-{id}[-{go}].html */
    public function show(Request $request, int $id, ?string $go = null)
    {
        $go = $go ?: 'society';
        if ($go === 'cpcandidates') {
            return $this->cpCandidates($id);
        }

        $row = $this->countryRow($id);
        if (!$row) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('countryinfo');
        $ctx = $this->roles($row);
        $data = ['row' => $row, 'go' => $go, 'errors' => [], 'info' => []] + $ctx + $this->lawNames();
        $data['gods'] = self::GODS;

        $sub = match ($go) {
            'economy' => $this->economySection($request, $row, $data),
            'politics' => $this->politicsSection($row),
            'military' => $this->militarySection($row),
            'congress' => $this->congress($request, $row, $ctx, (int) $request->route('page', 1)),
            'requests' => $this->requests($request, $row, $ctx),
            default => $this->society($row),
        };
        if ($sub instanceof \Symfony\Component\HttpFoundation\Response) {
            return $sub;
        }
        $data = array_merge($data, $sub);
        $data['go'] = $go = $sub['go'] ?? $go;

        $barTitle = $this->lang->getstr('country_bartitle', 'countryinfo').' - '.ucfirst($go);
        $title = $go === 'congress'
            ? sprintf($this->lang->getstr('title_congress', 'title'), $this->lang->getstr($row['shortName'], 'country'))
            : sprintf($this->lang->getstr('title_country_info', 'title'), $this->lang->getstr($row['shortName'], 'country'));

        return $this->page('pages.country.show', $data, ['title' => $title, 'bar_title' => $barTitle, 'actiontype' => 'country']);
    }

    /** congress-{id}-{page}.html */
    public function congressPage(Request $request, int $id, int $page = 1)
    {
        $request->route()->setParameter('page', $page);

        return $this->show($request, $id, 'congress');
    }

    /** region-{id}.html */
    public function region(Request $request, int $id)
    {
        $reg = $this->database->row('SELECT * FROM region WHERE RegionID = ?', [$id]);
        if (!$reg) {
            return redirect('/index.html');
        }
        $row = $this->countryRow($reg['CountryID']);
        if (!$row) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('countryinfo');
        $this->lang->addPhrases('regions');
        $data = ['row' => $row, 'go' => 'region', 'errors' => [], 'gods' => self::GODS, 'regionID' => $id] + $this->roles($row) + $this->lawNames();

        $sub = $this->regionData($request, $reg, $row);
        if ($sub instanceof \Symfony\Component\HttpFoundation\Response) {
            return $sub;
        }

        return $this->page('pages.country.show', array_merge($data, $sub), [
            'title' => sprintf($this->lang->getstr('title_country_region', 'title'), $this->lang->getstr("region_$id", 'regions')),
            'bar_title' => 'Region info',
            'actiontype' => 'country',
        ]);
    }

    /** law-new-{what}.html and law-{id}[-{what}].html */
    public function law(Request $request, string $id, ?string $what = null)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('countryinfo');
        $citInfo = $this->citInfo;

        if ($id === 'new') {
            $row = $this->countryRow($citInfo['CountryID']);
            $law = null;
        } else {
            $law = $this->politics()->getLaw((int) $id);
            if (!$law) {
                return redirect('/index.html');
            }
            $row = $this->countryRow($law['CountryID']);
        }
        if (!$row) {
            return redirect('/index.html');
        }
        $ctx = $this->roles($row);
        $data = ['row' => $row, 'go' => 'law', 'id' => $id, 'errors' => [], 'law' => $law, 'what' => $what, 'gods' => self::GODS] + $ctx + $this->lawNames();

        if ($id === 'new') {
            $sub = $this->lawNew($request, $row, $ctx, (string) $what);
            $title = $this->lang->getstr('title_law_propose', 'title');
        } else {
            $sub = $this->lawView($request, $row, $ctx, $law, (string) $what);
            $title = $this->lang->getstr('title_law', 'title');
        }
        if ($sub instanceof \Symfony\Component\HttpFoundation\Response) {
            return $sub;
        }

        return $this->page('pages.country.show', array_merge($data, $sub), [
            'title' => $title,
            'bar_title' => $this->lang->getstr('country_bartitle', 'countryinfo').' - law',
            'actiontype' => 'country',
        ]);
    }

    /** laws.html */
    public function ejlaws()
    {
        return $this->page('pages.country.ejlaws', [], ['title' => $this->lang->getstr('title_laws', 'title'), 'bar_title' => 'eJahan Laws', 'actiontype' => 'laws']);
    }

    /* ------------------------------------------------------------------ */

    private function countryRow(int|string $id): ?array
    {
        $row = $this->database->row('SELECT country.*, stat_country.* FROM country LEFT JOIN stat_country ON country.CountryID = stat_country.CountryID WHERE country.CountryID = ?', [$id]);
        if (!$row) {
            return null;
        }
        if ($row['Hidden'] && !$this->session->isAdmin()) {
            return null;
        }
        $row['CountryID'] = (int) $id;

        return $row;
    }

    private function roles(array $row): array
    {
        $c = $this->citInfo;
        $me = $c['CitizenID'] ?? 0;

        return [
            'isCG' => $this->loggedIn() && ($c['cgCountryID'] ?? null) == $row['CountryID'],
            'isCP' => $me && $me == $row['cpID'],
            'isMoW' => $me && $me == $row['minister_war'],
            'isMoFA' => $me && $me == $row['minister_fa'],
            'isMoE' => $me && $me == $row['minister_e'],
            'countries' => $this->database->getCountries($this->session->isAdmin()),
            'rank' => $row['CountryID'] != 1 ? $this->ranks()->getCountryRank($row['CountryID']) : '--',
        ];
    }

    private function lawNames(): array
    {
        $l = $this->lang;
        $g = fn ($k) => $l->getstr($k, 'countryinfo');

        return [
            'names' => [
                'Tax' => $g('law_tax'), 'Donate' => $g('law_donate'), 'Issue' => $g('law_issue'), 'Impeach' => $g('law_impeach'),
                'Fee' => $g('law_fee'), 'Ministry' => $g('law_minister'), 'BuyClinic' => $g('law_clinic'), 'BuyMunic' => $g('law_munic'),
                'Alliance' => $g('law_alliance'), 'DeclareWar' => $g('law_war'), 'ProposePeace' => $g('law_peace'), 'Notrade' => $g('law_trdembargo'),
                'Notravel' => $g('law_trvembargo'), 'nFee' => $g('law_nfee'), 'Prefix' => $g('law_prefix'), 'WelMsg' => $g('law_welmsg'),
                'Warca' => $g('law_warca'), 'Inds' => $g('law_inds'),
            ],
            'stats' => [
                0 => $g('law_pending'),
                1 => '<font color="green">'.$g('law_accepted').'</font>',
                2 => '<font color="red">'.$g('law_rejected').'</font>',
                3 => '<font color="red">'.$g('law_removed').'</font>',
            ],
            'statsNoStyle' => [0 => $g('law_pending'), 1 => $g('law_accepted'), 2 => $g('law_rejected'), 3 => $g('law_removed')],
        ];
    }

    /* ---------------------------- society ----------------------------- */

    private function society(array $row): array
    {
        $id = $row['CountryID'];
        $db = $this->database;
        $langCol = in_array($this->lang->lang, config('ejahan.languages'), true) ? 'trans_'.$this->lang->lang : 'trans_en';

        $godCounts = array_fill(1, 6, 0);
        foreach ($db->rows('SELECT goddessType, COUNT(goddessType) AS Total FROM region WHERE CountryID = ? AND NOT(ISNULL(goddessType)) GROUP BY goddessType', [$id]) as $g) {
            if (isset($godCounts[(int) $g['goddessType']])) {
                $godCounts[(int) $g['goddessType']] = (int) $g['Total'];
            }
        }
        $regions = $db->rows("SELECT region.*, IFNULL(trans_strings.$langCol, IFNULL(trans_strings.trans_en, region.rName)) AS Name,
                Owner.Flag AS CFlag, Owner.cName AS CName, Orig.Flag AS OFlag, Orig.cName AS OName
            FROM region
            LEFT JOIN trans_strings ON trans_strings.phrase = CONCAT('region_', region.RegionID)
            LEFT JOIN country AS Owner ON Owner.CountryID = region.CountryID
            LEFT JOIN country AS Orig ON Orig.CountryID = region.oCountryID
            WHERE region.CountryID = ? OR oCountryID = ? ORDER BY Name", [$id, $id]);
        $totO = $db->count('SELECT * FROM region WHERE oCountryID = ?', [$id]);
        $totH = $db->count('SELECT * FROM region WHERE CountryID = ? AND oCountryID != ?', [$id, $id]);
        $totL = $db->count('SELECT * FROM region WHERE CountryID != ? AND oCountryID = ?', [$id, $id]);

        return [
            'society' => [
                'numMembers' => $db->getNumMembers(),
                'bornToday' => [$db->getTodayBorns($id), $db->getTodayBorns()],
                'bornYesterday' => [$db->getTodayBorns2($id), $db->getTodayBorns2()],
                'hibs' => [$db->getTodayHibs($id), $db->getTodayHibs()],
                'avgEP' => $db->getAvgEP(),
                'online' => [$db->getOnlineCitizensCount($id), $db->getOnlineCitizensCount()],
            ],
            'godCounts' => $godCounts,
            'regions' => $regions,
            'totO' => $totO, 'totH' => $totH, 'totL' => $totL, 'totC' => $totO + $totH - $totL,
        ];
    }

    /* ---------------------------- economy ----------------------------- */

    private function economySection(Request $request, array $row, array &$data): array
    {
        $id = $row['CountryID'];
        $db = $this->database;
        $errors = [];
        $info = [];
        $citInfo = $this->citInfo;

        if ($request->input('subdonate') && $this->loggedIn()) {
            $type = (int) $request->input('Type');
            $amount = trim((string) $request->input('Amount'));
            $have = $db->getCitizenMoney($citInfo['CitizenID'], $type);
            if ($type == 1 && !$citInfo['active']) {
                $errors[] = 'Cheating detected';
            } elseif ($amount === '' || !preg_match('/^[0-9.]+$/', $amount)) {
                $errors[] = 'The amount is not numeric.';
            } elseif ($have < $amount) {
                $errors[] = 'You have not enough money to donate this amount.';
            } elseif ($db->donate($type, (float) $amount, $type, $citInfo['CitizenID'], 'citizen', $id, 'country')) {
                $info[] = "Successfully donated to {$row['cName']}'s treasury!";
                $this->session->fillInfo(null, true);
            }
        }

        $myCurrencies = [];
        if ($this->loggedIn()) {
            foreach ($db->rows('SELECT CurID FROM citizen_money WHERE CitID = ?', [$citInfo['CitizenID']]) as $r) {
                if ($r['CurID'] != 1 || $citInfo['active']) {
                    $myCurrencies[$r['CurID']] = $db->getCurrency($r['CurID']);
                }
            }
        }
        $inds = array_filter([$row['impind1'], $row['impind2'], $row['impind3']]);
        $impInds = $inds ? $db->rows('SELECT * FROM industry WHERE IndustryID IN ('.implode(',', array_fill(0, count($inds), '?')).')', array_values($inds)) : [];

        return [
            'errors' => $errors, 'info' => $info,
            'myCurrencies' => $myCurrencies,
            'accounts' => $db->getCountryAccounts($id),
            'embargoes' => $this->eco()->getEmbargoes($id, 'trade') ?: [],
            'taxes' => $db->getIndustryTax($id),
            'impInds' => $impInds,
        ];
    }

    /* ---------------------------- politics ---------------------------- */

    private function politicsSection(array $row): array
    {
        $id = $row['CountryID'];
        $db = $this->database;
        $cabinet = [];
        foreach (['minister_e' => 'Minister of economy', 'minister_fa' => 'Minister of foreign affairs', 'minister_war' => 'Minister of war'] as $col => $title) {
            if ($row[$col] && ($cit = $db->getUserInfoFromID($row[$col]))) {
                $cabinet[] = ['cit' => $cit, 'title' => $title];
            }
        }

        return [
            'neighbors' => $this->neighborCountries($id),
            'president' => $row['cpID'] ? $db->getUserInfoFromID($row['cpID']) : null,
            'cabinet' => $cabinet,
            'cgSeats' => $this->politics()->getCountryCGMembers($id),
        ];
    }

    private function neighborCountries(int|string $id): array
    {
        return $this->database->rows('SELECT Country2.* FROM country AS Country1
            RIGHT JOIN region AS reg1 ON reg1.CountryID = Country1.CountryID
            RIGHT JOIN neighbors ON neighbors.Region1 = reg1.RegionID
            LEFT JOIN region AS reg2 ON reg2.RegionID = neighbors.Region2
            LEFT JOIN country AS Country2 ON reg2.CountryID = Country2.CountryID
            WHERE Country1.CountryID = ? AND Country2.CountryID != ? GROUP BY Country2.cName ORDER BY Country2.cName', [$id, $id]);
    }

    /* ---------------------------- military ---------------------------- */

    private function militarySection(array $row): array
    {
        $id = $row['CountryID'];
        $wars = $this->database->rows("SELECT wars.*, Attacker.cName AS attName, Attacker.Flag AS attFlag, Defender.cName AS defName, Defender.Flag AS defFlag
            FROM wars LEFT JOIN country AS Attacker ON Attacker.CountryID = wars.Attacker
            JOIN country AS Defender ON Defender.CountryID = wars.Defender
            WHERE (Attacker = ? OR Defender = ?) AND Type = 'war' AND End = '0' ORDER BY Start DESC", [$id, $id]);
        foreach ($wars as &$w) {
            $w['revoltReg'] = $w['Type'] === 'revolt' ? $this->war()->getRegion($w['warID']) : null;
        }

        return ['wars' => $wars, 'allies' => $this->war()->getAllies($id)];
    }

    /* ---------------------------- congress ---------------------------- */

    private function congress(Request $request, array $row, array $ctx, int $page)
    {
        $id = $row['CountryID'];
        $citInfo = $this->citInfo;
        if ($request->input('subresign') && $this->loggedIn() && $ctx['isCG']) {
            $this->politics()->resignCG($citInfo['CitizenID'], $citInfo['CountryID']);
            $this->session->fillInfo(null, true);

            return redirect($request->getRequestUri());
        }
        $page = max(1, $page);
        $count = 10;
        $start = ($page - 1) * $count;

        $uPStats = [3, 3];
        $numProposes = 0;
        if ($this->loggedIn() && ($ctx['isCG'] || $ctx['isCP'] || $ctx['isMoFA'] || $ctx['isMoE'] || $ctx['isMoW'])) {
            $uProps = $this->politics()->getLawsBy($citInfo['CitizenID']);
            $numProposes = count($uProps);
            foreach (array_slice($uProps, 0, 2) as $i => $p) {
                $uPStats[$i] = (int) $p['Status'];
            }
        }

        return [
            'page' => $page, 'count' => $count, 'start' => $start,
            'uStats' => ['gray', 'lime', 'red', 'white'],
            'uPStats' => $uPStats,
            'numProposes' => $numProposes,
            'laws' => $this->politics()->getLaws($id, $start, $count),
            'lawCount' => $this->politics()->getLaws($id, -1),
        ];
    }

    /* ---------------------------- requests ---------------------------- */

    private function requests(Request $request, array $row, array $ctx)
    {
        $id = $row['CountryID'];
        if ($request->input('subapprove') && $ctx['isMoFA']) {
            $cID = (int) $request->input('cID');
            $toID = (int) $request->input('toID');
            if ($request->input('token') === md5($cID.$toID.'Chang3 Nati0n')) {
                $this->database->exec('UPDATE citizens SET nationality = ? WHERE CitizenID = ?', [$toID, $cID]);
                $this->database->exec('UPDATE log_nchange SET approved = ? WHERE CitizenID = ? AND nNation = ? AND approved = 0', [$this->citInfo['CitizenID'], $cID, $toID]);
                $this->database->sendNote($cID, '', 'The minister of Foreign Affairs approved your request and you are the national member of this country!');
            }

            return redirect($request->getRequestUri());
        }

        return ['requestsList' => $this->database->rows('SELECT log_nchange.*, citizens.name, citizens.avatar, oldC.Flag AS OFlag, appr.name AS approver
            FROM log_nchange JOIN citizens ON log_nchange.CitizenID = citizens.CitizenID
            JOIN country AS oldC ON oldC.CountryID = log_nchange.oNation
            LEFT JOIN citizens AS appr ON appr.CitizenID = log_nchange.approved
            WHERE nNation = ? ORDER BY timestamp DESC', [$id])];
    }

    /* ---------------------------- region + travel --------------------- */

    private function regionData(Request $request, array $reg, array $row)
    {
        $db = $this->database;
        $citInfo = $this->citInfo;
        $logged = $this->loggedIn();
        $rID = $reg['RegionID'];
        $cID = $citInfo['CitizenID'] ?? 0;
        $errors = [];
        $numRegs = $db->row('SELECT SUM(IF(oCountryID = CountryID, 1, 0)) AS Remain, SUM(IF(oCountryID != CountryID, 1, 1)) AS Total FROM region WHERE oCountryID = ?', [$reg['oCountryID']]);
        $C = 1 + (($numRegs['Total'] ?? 0) ? $numRegs['Remain'] / $numRegs['Total'] : 0);
        $RWPrice = round((30 + $reg['stat_value']) * $C, 2);

        if ($logged && $request->input('subRevolt')) {
            if ($request->input('token') === md5($cID.'l w@nnA $tar+ @ rev0lt'.$reg['stat_pop'].$rID)) {
                if ($db->getCitizenMoney($cID, 1) < $RWPrice) {
                    $errors[] = 'You have not enough money to start a revolt here';
                } else {
                    $r = $this->war()->declareWar($cID, $reg['CountryID'], 'revolt', $rID);
                    if ($r) {
                        $db->addMoney(1, -$RWPrice, $cID);
                    }

                    return redirect($this->vars->getURL('wars'));
                }
            }
        }

        $travel = null;
        if ($logged && $reg['RegionID'] != $citInfo['regionID']) {
            $t = $this->travel($request, $reg, $row);
            if ($t instanceof \Symfony\Component\HttpFoundation\Response) {
                return $t;
            }
            $travel = $t;
            $errors = array_merge($errors, $travel['errors']);
        }

        $oC = $db->row('SELECT * FROM country WHERE CountryID = ?', [$reg['oCountryID']]);
        $neighbors = $db->rows('SELECT neighbors.*, region.RegionID, country.CountryID, country.shortName, country.Flag
            FROM neighbors JOIN region ON region.RegionID = neighbors.Region2 JOIN country ON country.CountryID = region.CountryID
            WHERE neighbors.Region1 = ?', [$rID]);

        return ['reg' => $reg, 'RWPrice' => $RWPrice, 'travel' => $travel, 'origCountry' => $oC, 'neighbors' => $neighbors, 'errors' => $errors];
    }

    /** include/country/travel.php: handles subtravel / subtravel2 and returns the form data. */
    private function travel(Request $request, array $reg, array $row): array|\Symfony\Component\HttpFoundation\Response
    {
        $db = $this->database;
        $citInfo = $this->citInfo;
        $errors = [];
        $fix = null;
        $fromCoun = $citInfo['CountryID'];
        $fromReg = $citInfo['RegionID'];
        $toCoun = $reg['CountryID'];
        $toReg = $reg['RegionID'];
        $time = time();

        if ($citInfo['CountryID'] == $toCoun) {
            $price = 25;
        } elseif ($this->eco()->getEmbargoes($fromCoun, 'travel', $toCoun) || $this->war()->haveWar($fromCoun, $toCoun)) {
            $price = 75;
        } else {
            $price = 50;
        }

        $commonCheck = function () use (&$errors, &$fix, $toCoun, $fromCoun, $citInfo, $db) {
            if ($toCoun != $fromCoun && $db->isWorker($citInfo['CitizenID'])) {
                $errors[] = 'You must resign from your company.';
                $fix = $this->vars->getURL('company');
            } elseif ($toCoun != $fromCoun && $citInfo['CountryID'] == $citInfo['cgCountryID']) {
                $errors[] = 'You are a congress member, so you cannot move to another country until the end of your period.';
                $fix = $this->vars->getURL('congress', $citInfo['CountryID']);
            } elseif ($toCoun != $fromCoun && $citInfo['PartyID']) {
                $errors[] = 'You must resign from your party.';
                $fix = $this->vars->getURL('party');
            }

            return !$errors;
        };

        $actTicket = (int) $request->input('ticket');
        if ($request->input('subtravel')) {
            $tick = $db->row("SELECT * FROM inventory WHERE Owner = ? AND Usable = '1' AND Type = '2' AND Stars = ?", [$citInfo['CitizenID'], $actTicket]);
            if ($db->isHiddenCountry($toCoun) && !$this->session->isAdmin()) {
                return redirect('/index.html');
            }
            if (!$tick) {
                $errors[] = 'Cheating ?! You have not a ticket with this quality.';
            } elseif ($citInfo['travel_due'] >= $time) {
                $errors[] = 'You have just travelled a few time ago and you need to rest '.$this->session->getDiffF($citInfo['travel_due'], $time, 0).' to be able to travel again.';
            } elseif (in_array($toReg, [342, 343])) {
                $errors[] = 'You cannot travel to this region.';
            } elseif ($citInfo['wellness'] < 5 && !$this->isCA()) {
                $errors[] = 'Your wellness must be at least 5 to travel.';
            } elseif ($fromReg == $toReg) {
                $errors[] = 'You cannot travel to your living region.';
            } elseif ($toCoun != $fromCoun && $actTicket == 1) {
                $errors[] = 'To go outside of a country, you must use a ticket with 2 stars or more.';
            } elseif ($this->war()->haveWar($fromCoun, $toCoun) && $actTicket != 5) {
                $errors[] = 'You cannot go to this country directly according to an active war. You can use a 5-star ticket.';
            } elseif ($this->eco()->getEmbargoes($fromCoun, 'travel', $toCoun) && $actTicket != 5) {
                $errors[] = 'You cannot go to this country directly according to a travel embargo. You can use a 5-star ticket.';
            } elseif ($commonCheck()) {
                $db->exec("UPDATE inventory SET Usable = '0' WHERE pID = ?", [$tick['pID']]);
                $db->updateUserFieldID($citInfo['CitizenID'], 'regionID', $toReg);
                $inside = [1 => -2, 2 => 2, 3 => -2, 4 => 2, 5 => 5];
                $outside = [2 => -5, 3 => -3, 4 => 1, 5 => 5];
                $wChange = $toCoun == $fromCoun ? ($inside[$actTicket] ?? 0) : ($outside[$actTicket] ?? 0);
                $db->updateUserFieldID($citInfo['CitizenID'], 'wellness', min(100, $citInfo['wellness'] + $wChange));
                $db->updateUserFieldID($citInfo['CitizenID'], 'travel_due', $time + ((6 - $actTicket) * 3600));
                $gds = min((int) ($citInfo['gd_love'] ?? 0), 10);
                $gdmin = [1, 0.95, 0.9, 0.85, 0.8, 0.75, 0.7, 0.65, 0.6, 0.55, 0.5];
                $db->addOcc($citInfo['CitizenID'], (30 - ($actTicket * 5)) * 60 * $gdmin[$gds]);
                $this->session->fillInfo(null, true);

                return redirect($request->getRequestUri());
            }
        }

        if ($request->input('subtravel2')) {
            if ($db->isHiddenCountry($toCoun) && !$this->session->isAdmin()) {
                return redirect('/index.html');
            }
            $citMoney = $db->getCitizenMoney($citInfo['CitizenID']);
            if (($citMoney[$fromCoun] ?? 0) < $price) {
                $errors[] = "You don't have enough money to travel to this region. You need $price local currency to be able to travel.";
            } elseif ($commonCheck()) {
                $db->transferMoney($fromCoun, $price, $citInfo['CitizenID'], 'citizen', 1, 'citizen', 1, "> Travelled to region # $fromReg <");
                $db->updateUserFieldID($citInfo['CitizenID'], 'regionID', $toReg);
                $this->session->fillInfo(null, true);

                return redirect($request->getRequestUri());
            }
        }

        $tickets = $db->rows("SELECT * FROM inventory WHERE Owner = ? AND Type = '2' AND Usable = '1' GROUP BY Stars ORDER BY Stars DESC", [$citInfo['CitizenID']]);

        return ['errors' => $errors, 'fix' => $fix, 'price' => $price, 'tickets' => $tickets, 'actTicket' => $actTicket, 'time' => $time];
    }

    /* ---------------------------- cp candidates ----------------------- */

    private function cpCandidates(int $coun)
    {
        $db = $this->database;
        $cName = $db->getCountryC($coun);
        $row = $this->countryRow($coun);
        if (!$cName || !$row) {
            return redirect($this->vars->getURL('online', 1));
        }
        $cands = $db->rows('SELECT citizens.*, party.*, COUNT(party_members.CitizenID) AS POP
            FROM party JOIN citizens ON party.cpProposed = citizens.CitizenID
            JOIN party_members ON party.pID = party_members.PartyID
            WHERE party.CountryID = ? GROUP BY party_members.PartyID ORDER BY POP DESC LIMIT 0, 5', [$coun]);

        return $this->page('pages.country.cpcandidates', [
            'coun' => $coun, 'cName' => $cName,
            'flag' => $db->getCountryFlagC($coun, $this->vars->getImgLoc('CountryFlag')),
            'allCountries' => $db->rows('SELECT * FROM country ORDER BY cName'),
            'cands' => $cands,
        ], [
            'title' => sprintf($this->lang->getstr('title_country_cpcandidates', 'title'), $this->lang->getstr($row['shortName'], 'country')),
            'bar_title' => 'Country Presidency Candidates',
            'actiontype' => 'country',
        ]);
    }

    /* ---------------------------- laws -------------------------------- */

    private function lawView(Request $request, array $row, array $ctx, array $law, string $what)
    {
        $citInfo = $this->citInfo;
        $pol = $this->politics();
        $haveVoteRights = ($ctx['isCG'] || $ctx['isCP']) && !$pol->isVoted($citInfo['CitizenID'], $law['lawID']) && $law['dTime'] > time() && $law['Status'] == 0;

        if ($request->input('sublaw')) {
            $lawID = (int) $request->input('lawID');
            $vote = strtoupper($what ?: (string) $request->input('what'));
            if ($request->input('token') !== md5($lawID.$citInfo['CitizenID'].'k3y4 l@w v0t1nj'.$lawID) || !in_array($vote, ['YES', 'NO'])) {
                return redirect('/index.html');
            }
            if (!$haveVoteRights || $lawID != $law['lawID']) {
                return redirect($this->vars->getURL('law', $law['lawID']));
            }
            $pol->setVote($citInfo['CitizenID'], $lawID, $vote);

            return redirect($this->vars->getURL('law', $lawID));
        }

        $db = $this->database;
        $param = $law['Params'];
        $p = explode(',', (string) $param);
        $detail = ['type' => $law['Type'], 'p' => $p, 'raw' => $param];
        switch ($law['Type']) {
            case 'Tax':
                $detail['industry'] = $db->getIndustry($p[0]);
                break;
            case 'Fee':
            case 'Issue':
            case 'nFee':
                $detail['cur'] = $db->getCurrency($db->getCurrencyID($law['CountryID']));
                break;
            case 'Donate':
                $detail['cur'] = $db->getCurrency($p[1] ?? 0);
                break;
            case 'Inds':
                $ids = array_filter(array_map('intval', $p));
                $detail['inds'] = $ids ? $db->rows('SELECT iName FROM industry WHERE IndustryID IN ('.implode(',', array_fill(0, count($ids), '?')).')', array_values($ids)) : [];
                break;
            case 'BuyClinic':
            case 'BuyMunic':
                $detail['cur'] = $db->getCurrency($row['CountryID']);
                break;
            case 'Alliance':
                [$cName, $cID, $dur, $price, $supLaw] = array_pad($p, 5, null);
                if (!$price) {
                    $supLaw = $dur;
                    $dur = 30;
                }
                $detail += $supLaw
                    ? ['coun' => $cName, 'counID' => $cID, 'tarName' => $law['cName'], 'tarID' => $law['CountryID']]
                    : ['coun' => $law['cName'], 'counID' => $law['CountryID'], 'tarName' => $cName, 'tarID' => $cID];
                $detail['dur'] = $dur;
                $detail['supLaw'] = $supLaw;
                break;
            case 'ProposePeace':
                [$price, $cName, $cID, $supLaw] = array_pad($p, 4, null);
                $detail += $supLaw
                    ? ['coun' => $cName, 'counID' => $cID, 'tarName' => $law['cName'], 'tarID' => $law['CountryID']]
                    : ['coun' => $law['cName'], 'counID' => $law['CountryID'], 'tarName' => $cName, 'tarID' => $cID];
                $detail['price'] = $price;
                $detail['supLaw'] = $supLaw;
                break;
        }

        return [
            'haveVoteRights' => $haveVoteRights,
            'lawVotes' => $pol->getVotes($law['lawID']),
            'voteToken' => md5($law['lawID'].($citInfo['CitizenID'] ?? '').'k3y4 l@w v0t1nj'.$law['lawID']),
            'detail' => $detail,
        ];
    }

    private function lawNew(Request $request, array $row, array $ctx, string $what)
    {
        $citInfo = $this->citInfo;
        $pol = $this->politics();
        $isCP = $ctx['isCP'];
        $isMoFA = $ctx['isMoFA'];
        $isMoW = $ctx['isMoW'];
        $isMoE = $ctx['isMoE'];

        if ($citInfo['cgCountryID'] != $row['CountryID'] && !$isCP && !$isMoFA && !$isMoW && !$isMoE) {
            return redirect('/index.html');
        }
        $numProps = count($pol->getLawsBy($citInfo['CitizenID']));
        if ($numProps >= 2 && !$isCP && (!$isMoFA && $what !== 'nfee')) {
            return ['lawForm' => null, 'errors' => ['You can propose a maximum of 2 laws in each 1 month period']];
        }
        $allowed = match ($what) {
            'fee', 'tax', 'iss', 'don', 'imp' => true,
            'ministry', 'notra', 'notrd', 'ally', 'decwar', 'ppeace', 'buycli', 'buymun', 'welmsg', 'prefix' => $isCP,
            'nfee' => $isMoFA,
            'warca' => $isMoW,
            'inds' => $isMoE,
            default => false,
        };
        if (!$allowed) {
            return redirect('/index.html');
        }

        $r = $this->handleLawForm($request, $row, $what);
        if ($r instanceof \Symfony\Component\HttpFoundation\Response) {
            return $r;
        }

        return ['lawForm' => $what, 'errors' => $r['errors'], 'form' => $r];
    }

    /** All include/country/law/*.php submit handlers + form data. */
    private function handleLawForm(Request $request, array $row, string $what)
    {
        $db = $this->database;
        $citInfo = $this->citInfo;
        $pol = $this->politics();
        $me = $citInfo['CitizenID'];
        $myC = $citInfo['CountryID'];
        $errors = [];
        $deb = (string) $request->input('Debate', '');
        $debNorm = function (bool $always) use ($deb) {
            if (($always || $deb !== '') && substr($deb, 0, 7) !== 'http://') {
                return 'http://'.$deb;
            }

            return $deb;
        };
        $done = fn () => redirect($this->vars->getURL('congress', $myC));
        $inProcess = fn (string $type) => (($pol->getLastLaw($myC, $type)['dTime'] ?? 0) > time());
        $tok = fn (string $s) => md5($s);
        $form = ['errors' => &$errors, 'what' => $what];
        $cur = $db->getCurrency($db->getCurrencyID($myC));
        $form['cur'] = $cur;

        switch ($what) {
            case 'tax':
                $form['token'] = $tok('tax'.'subtax'.'tax'.$myC.$me.'k3yy4 t@x 1@w');
                $form['industries'] = $db->rows('SELECT * FROM industry');
                if ($request->input('subtax')) {
                    $ind = (int) $request->input('Industry');
                    $tax = $db->getIndustryTax($myC, $ind, 1);
                    $param = implode(',', [$ind, (int) $request->input('Income'), (int) $request->input('Import'), (int) $request->input('VAT'), $tax['Income'] ?? 0, $tax['Import'] ?? 0, $tax['VAT'] ?? 0]);
                    if ($inProcess('Tax')) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } else {
                        $pol->addLaw($me, $myC, 'Tax', $param, $debNorm(true));

                        return $done();
                    }
                }
                break;

            case 'fee':
            case 'nfee':
                $col = $what === 'fee' ? 'cFee' : 'nFee';
                $type = $what === 'fee' ? 'Fee' : 'nFee';
                $form['oFee'] = $oFee = $row[$col];
                $form['token'] = $what === 'fee'
                    ? $tok('fee'.'subfee'.$oFee.$myC.$me.'k3yy4 f33 1@w')
                    : $tok('nfee'.'subnfee'.$oFee.$myC.$me.'k3yy4 nf33 1@w');
                if ($request->input('subfee')) {
                    $nFee = trim((string) $request->input('nFee'));
                    $oFeeIn = $request->input('oFee');
                    $sentTok = $what === 'fee'
                        ? $tok('fee'.'subfee'.$oFeeIn.$myC.$me.'k3yy4 f33 1@w')
                        : $tok('nfee'.'subnfee'.$oFeeIn.$myC.$me.'k3yy4 nf33 1@w');
                    if (!preg_match('/^[0-9]+$/', $nFee)) {
                        return redirect('/index.html');
                    }
                    if ($inProcess($type)) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $sentTok) {
                        $errors[] = 'Cheating detected...';
                    } else {
                        $pol->addLaw($me, $myC, $type, "$oFeeIn,$nFee", $debNorm(false));

                        return $done();
                    }
                }
                break;

            case 'don':
                $form['token'] = $tok('don'.'subdon'.$myC.$me.'k3yy4 |>0|\| 1@w');
                $form['accounts'] = $db->getCountryAccounts($row['CountryID']);
                if ($request->input('subdon')) {
                    $target = (int) $request->input('Target');
                    $amount = trim((string) $request->input('Amount'));
                    $curID = (int) $request->input('Cur');
                    if (!preg_match('/^[0-9]+$/', $amount)) {
                        return redirect('/index.html');
                    }
                    $tID = $db->getUserInfoFromID($target);
                    $cAcc = $db->getCountryAccount($myC, $curID);
                    if ($request->input('token') !== $form['token']) {
                        return redirect('/index.html');
                    } elseif ($amount < 1 || $amount > ($cAcc['Amount'] ?? 0)) {
                        $errors[] = 'Invalid amount entered. Minumum is 1 and maximum is '.($cAcc['Amount'] ?? 0).' for this currency';
                    } elseif (!$tID || $tID['accType'] === 'citizen') {
                        $errors[] = 'You must specify a Co-Account ID, not a citizen account';
                    } else {
                        $db->transferMoney($curID, (float) $amount, $myC, 'country', 1, 'citizen');
                        $pol->addLaw($me, $myC, 'Donate', "$amount,$curID,$target,{$tID['name']}", $debNorm(false));

                        return $done();
                    }
                }
                break;

            case 'iss':
                $oTala = $db->getCountryAccount($myC, 1)['Amount'] ?? 0;
                $form['oTala'] = $oTala;
                $form['token'] = $tok('iss'.'subiss'.$oTala.$myC.$me.'k3yy4 1$$ 1@w');
                if ($request->input('subiss')) {
                    $cTala = $request->input('cTala');
                    $amount = trim((string) $request->input('Amount'));
                    if (!preg_match('/^[0-9]+$/', $amount)) {
                        return redirect('/index.html');
                    }
                    $price = ($amount * 0.002) + 15;
                    $cAcc = $db->getCountryAccount($myC, 1);
                    if ($inProcess('Issue')) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $tok('iss'.'subiss'.$cTala.$myC.$me.'k3yy4 1$$ 1@w')) {
                        $errors[] = 'Cheating detected...';
                    } elseif ($amount < 1 || $price > ($cAcc['Amount'] ?? 0)) {
                        $errors[] = 'Invalid amount entered. Maximum is '.($cAcc['Amount'] ?? 0);
                    } else {
                        $db->transferMoney(1, $price, $myC, 'country', 1, 'citizen');
                        $pol->addLaw($me, $myC, 'Issue', (string) $amount, $debNorm(false));

                        return $done();
                    }
                }
                break;

            case 'imp':
                $form['token'] = $tok('imp'.'subimp'.$myC.$me.'k3yy4 1mp 1@w');
                if ($request->input('subimp')) {
                    if ((($pol->getLastLaw($myC, 'Impeach')['dTime'] ?? 0) > time() - (7 * 86400))) {
                        $errors[] = 'Another impeachment found later than a week ago...';
                    } elseif ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } else {
                        $pol->addLaw($me, $myC, 'Impeach', '', $deb);

                        return $done();
                    }
                }
                break;

            case 'ministry':
                $form['token'] = $tok('minis'.'subministry'.'minis'.$myC.$me.'k3yy4 mlnl$tri');
                if ($request->input('subministry')) {
                    $profile = (int) $request->input('profile');
                    $ministry = (string) $request->input('ministry');
                    $cit = $db->getUserInfoFromID($profile);
                    if ($request->input('token') !== $form['token'] || !in_array($ministry, ['War', 'FA', 'E'])) {
                        $errors[] = 'Cheating detected...';
                    } elseif (!$cit || $cit['accType'] !== 'citizen') {
                        $errors[] = 'You must specify a citizen profile ID...';
                    } elseif ($cit['CountryID'] != $myC) {
                        $errors[] = 'The specified citizen must be in your home country...';
                    } elseif ($cit['nationality'] != $myC) {
                        $errors[] = "The specified citizen must have your home country's nationality...";
                    } else {
                        $pol->addLaw($me, $myC, 'Ministry', "$profile,{$cit['name']},$ministry", $debNorm(true));

                        return $done();
                    }
                }
                break;

            case 'buycli':
            case 'buymun':
                $indID = $what === 'buycli' ? 9 : 10;
                $form['token'] = $tok('buy'.'subbuy'.$myC.$me.'k3yy4 l3vy 1@w');
                $form['regions'] = $db->rows('SELECT * FROM region WHERE CountryID = ? ORDER BY rName', [$row['CountryID']]);
                if ($request->input('subbuy')) {
                    $compID = (int) $request->input('CompanyID');
                    $regID = (int) $request->input('Region');
                    $curID = $db->getCurrencyID($row['CountryID']);
                    $offers = $db->getMarketOffers($indID, '', $row['CountryID'], $compID);
                    $comp = $offers[0] ?? null;
                    $reg = $db->row('SELECT * FROM region WHERE RegionID = ?', [$regID]);
                    $cAcc = $db->getCountryAccount($myC, $curID);
                    if ($request->input('token') !== $form['token']) {
                        return redirect('/index.html');
                    } elseif (!$comp || !$reg) {
                        $errors[] = 'Invalid company ID. The '.($what === 'buycli' ? 'clinic' : 'municipality')." company must have at least one product in your country's market";
                    } elseif (($cAcc['Amount'] ?? 0) < $comp['Price']) {
                        $errors[] = 'The price of '.($what === 'buycli' ? 'clinic' : 'municipality').' is too high!';
                    } elseif ($what === 'buymun' && $reg['Munic'] >= $comp['Stars']) {
                        $errors[] = 'The selected region has a better municipality than your selected municipality!';
                    } else {
                        $param = "{$comp['Stars']},{$comp['Price']},{$comp['CompanyID']},{$comp['Name']},{$reg['RegionID']},{$reg['rName']}";
                        $db->transferMoney($curID, $comp['Price'], $myC, 'country', 1, 'citizen');
                        $db->setCompanyOffer($comp['OfferID'], $compID, $comp['Quality'], $comp['CountryID'], $comp['Stock'] - 1, $comp['Price'], 1);
                        $pol->addLaw($me, $myC, $what === 'buycli' ? 'BuyClinic' : 'BuyMunic', $param, $debNorm(false));

                        return $done();
                    }
                }
                break;

            case 'ally':
                $form['token'] = $tok('ally'.'subally'.'ally'.$myC.$me.'k3yy4 @l1y');
                $form['newCountry'] = ($citInfo['noWar'] ?? 0) >= $db->today;
                $form['countries'] = array_values(array_filter(
                    $db->rows('SELECT country.* FROM country JOIN region ON region.CountryID = country.CountryID WHERE country.CountryID != ? GROUP BY region.CountryID HAVING COUNT(region.CountryID > 0) ORDER BY cName', [$myC]),
                    fn ($c) => !$this->war()->haveWar($row['CountryID'], $c['CountryID'])
                ));
                if ($request->input('subally')) {
                    $peri = (int) $request->input('period');
                    $coun = $db->getCountryRec((int) $request->input('Country'));
                    [$dur, $price] = match ($peri) { 1 => [7, 10], 3 => [90, 70], 4 => [180, 130], default => [30, 25] };
                    $cAcc = $db->getCountryAccount($myC, 1)['Amount'] ?? 0;
                    $tAcc = $coun ? ($db->getCountryAccount($coun['CountryID'], 1)['Amount'] ?? 0) : 0;
                    $myNew = ($citInfo['noWar'] ?? 0) >= $db->today && $dur <= 30;
                    $tNew = $coun && $coun['noWar'] >= $db->today && $dur <= 30;
                    if ($request->input('token') !== $form['token'] || !($peri > 0 && $peri < 5) || !$coun) {
                        $errors[] = 'Cheating detected...';
                    } elseif ($this->war()->haveWar($row['CountryID'], $coun['CountryID'])) {
                        $errors[] = 'You cannot propose alliance with a country with a war with you';
                    } elseif ($this->war()->isAlly($row['CountryID'], $coun['CountryID']) && (($citInfo['noWar'] ?? 0) >= $db->today || $coun['noWar'] >= $db->today)) {
                        $errors[] = 'New countries cannot extend the period of alliance';
                    } elseif ($cAcc < $price && !$myNew) {
                        $errors[] = "You have not enough Tala in your country's treasury";
                    } elseif ($tAcc < $price && !$tNew) {
                        $errors[] = "The target country has not enough Tala in its country's treasury";
                    } else {
                        $time = time();
                        if (!$myNew) {
                            $db->transferMoney(1, $price, $myC, 'country', 1, 'citizen', 1, '> Signing alliance <');
                        }
                        if (!$tNew) {
                            $db->transferMoney(1, $price, $coun['CountryID'], 'country', 1, 'citizen', 1, '> Signing alliance <');
                        }
                        $d = $debNorm(true);
                        $lawID = $pol->addLaw($me, $myC, 'Alliance', "{$coun['cName']},{$coun['CountryID']},$dur,$price,0", $d, $time);
                        $pol->addLaw($me, $coun['CountryID'], 'Alliance', "{$citInfo['cName']},$myC,$dur,$price,$lawID", $d, $time + 1);

                        return $done();
                    }
                }
                break;

            case 'decwar':
                $form['token'] = $tok('war'.'subwar'.'war'.$myC.$me.'k3yy4 $t@rt \/\/@r');
                $form['countries'] = array_values(array_filter(
                    $this->neighborCountries($myC),
                    fn ($c) => !$this->war()->haveWar($row['CountryID'], $c['CountryID']) && !$this->war()->isAlly($row['CountryID'], $c['CountryID'])
                ));
                if ($request->input('substwar')) {
                    $coun = (int) $request->input('Country');
                    $cName = $db->getCountryC($coun);
                    $cAcc = $db->getCountryAccount($myC, 1)['Amount'] ?? 0;
                    $price = $this->war()->getWDPrice($coun, $myC);
                    if ($inProcess('DeclareWar')) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } elseif ($this->war()->isAlly($row['CountryID'], $coun)) {
                        $errors[] = 'You cannot declare war to your ally';
                    } elseif ($this->war()->haveWar($row['CountryID'], $coun)) {
                        $errors[] = 'You have a war with this country already';
                    } elseif ($cAcc < $price) {
                        $errors[] = "You have not enough Tala in your country's treasury";
                    } else {
                        $db->transferMoney(1, $price, $myC, 'country', 1, 'citizen');
                        $pol->addLaw($me, $myC, 'DeclareWar', "$cName,$coun", $debNorm(true));
                        $note = 'The president of <a href="'.$this->vars->getURL('country', $myC).'">'.$citInfo['cName'].'</a> '
                            .'wants to declare war to your country and the law is currently in their congress. Hurry up and prepare for war!';
                        foreach ($db->rows('SELECT cgCitizenID FROM congressmen WHERE cgCountryID = ?', [$coun]) as $cg) {
                            $db->sendNote($cg['cgCitizenID'], '', $note);
                        }

                        return $done();
                    }
                }
                break;

            case 'ppeace':
                $form['token'] = $tok('pce'.'subpce'.'pce'.$myC.$me.'k3yy4 end \/\/@r');
                $form['countries'] = array_values(array_filter(
                    $db->rows('SELECT * FROM country ORDER BY cName'),
                    fn ($c) => $this->war()->haveWar($row['CountryID'], $c['CountryID'])
                ));
                if ($request->input('subpce')) {
                    $coun = (int) $request->input('Country');
                    $price = (float) $request->input('price');
                    $cName = $db->getCountryC($coun);
                    $cAcc = $db->getCountryAccount($myC, 1)['Amount'] ?? 0;
                    if ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } elseif ($price < 0) {
                        $errors[] = 'Invalid price entered...';
                    } elseif (!$this->war()->haveWar($row['CountryID'], $coun)) {
                        $errors[] = 'You have no war with this country...';
                    } elseif ($cAcc < $price) {
                        $errors[] = "You have not enough Tala in your country's treasury";
                    } else {
                        $time = time();
                        $d = $debNorm(true);
                        $db->transferMoney(1, $price, $myC, 'country', 1, 'citizen');
                        $lawID = $pol->addLaw($me, $myC, 'ProposePeace', "$price,$cName,$coun,0", $d, $time);
                        $pol->addLaw($me, $coun, 'ProposePeace', "$price,{$citInfo['cName']},$myC,$lawID", $d, $time + 1);

                        return $done();
                    }
                }
                break;

            case 'notra':
            case 'notrd':
                $type = $what === 'notra' ? 'Notravel' : 'Notrade';
                $form['token'] = $what === 'notra'
                    ? $tok('tra'.'subtra'.'tra'.$myC.$me.'k3yy4 tr@\/31 3^^|3@rg0')
                    : $tok('trd'.'subtrd'.'trd'.$myC.$me.'k3yy4 tr@d3 3^^|3@rg0');
                $form['countries'] = array_values(array_filter(
                    $db->rows('SELECT * FROM country WHERE CountryID != ? ORDER BY cName', [$row['CountryID']]),
                    fn ($c) => !$this->eco()->getEmbargoes($row['CountryID'], 'travel', $c['CountryID'])
                ));
                if ($request->input('subnotrd')) {
                    $coun = (int) $request->input('Country');
                    $cName = $db->getCountryC($coun);
                    if ($inProcess($type)) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } else {
                        $pol->addLaw($me, $myC, $type, "$cName,$coun", $debNorm(true));

                        return $done();
                    }
                }
                break;

            case 'prefix':
                $form['token'] = $tok('prefix'.'subpref'.$row['Prefix'].$myC.$me.'k3yy4 pr3flx 1@w');
                if ($request->input('subpref')) {
                    $oPref = (string) $request->input('oPref');
                    $nPref = strip_tags((string) $request->input('newPref'));
                    $cAcc = $db->getCountryAccount($myC, 1)['Amount'] ?? 0;
                    if ($inProcess('Prefix')) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $tok('prefix'.'subpref'.$oPref.$myC.$me.'k3yy4 pr3flx 1@w')) {
                        $errors[] = 'Cheating detected...';
                    } elseif ($cAcc < 5) {
                        $errors[] = "You have not enough Tala in your country's treasury";
                    } else {
                        $db->transferMoney(1, 5, $myC, 'country', 1, 'citizen');
                        $pol->addLaw($me, $myC, 'Prefix', "$oPref,$nPref", $debNorm(false));

                        return $done();
                    }
                }
                break;

            case 'welmsg':
                $form['token'] = $tok('welmsg'.'subwelmsg'.$myC.$me.'k3yy4 w3lm$g 1@w');
                if ($request->input('subwelmsg')) {
                    $nMsg = (string) $request->input('newmsg');
                    if ($inProcess('WelMsg')) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } else {
                        $pol->addLaw($me, $myC, 'WelMsg', $nMsg, $debNorm(false));

                        return $done();
                    }
                }
                break;

            case 'warca':
                $form['token'] = $tok('warca'.'subwarca'.'warca'.$myC.$me.'k3yy4 w@rc@');
                if ($request->input('subministry')) {
                    $profile = (int) $request->input('profile');
                    $cit = $db->getUserInfoFromID($profile);
                    if ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } elseif (!$cit || $cit['accType'] !== 'co-account') {
                        $errors[] = 'You must specify a co-account profile ID...';
                    } elseif ($cit['accOwner'] != $me) {
                        $errors[] = 'The specified co-account must be yours...';
                    } elseif ($cit['CountryID'] != $myC) {
                        $errors[] = 'The specified co-account must be in your home country...';
                    } else {
                        $pol->addLaw($me, $myC, 'Warca', "$profile,{$cit['name']},", $debNorm(true));
                        $db->updateUserFieldID($profile, 'active', 0);

                        return $done();
                    }
                }
                break;

            case 'inds':
                $form['token'] = $tok('inds'.'subinds'.$myC.$me.'k3yy4 Ind$ 1@w');
                $form['industries'] = $db->rows("SELECT * FROM industry WHERE Hidden = '0'");
                if ($request->input('subinds')) {
                    $inds = array_map('intval', (array) $request->input('inds', []));
                    if ($inProcess('Inds')) {
                        $errors[] = 'Another law of this type is currently in process...';
                    } elseif ($request->input('token') !== $form['token']) {
                        $errors[] = 'Cheating detected...';
                    } elseif (count($inds) > 3) {
                        $errors[] = 'You can select max. 3 industries';
                    } else {
                        $pol->addLaw($me, $myC, 'Inds', implode(',', $inds), $debNorm(false));

                        return $done();
                    }
                }
                break;
        }

        return $form;
    }
}
