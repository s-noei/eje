<?php

namespace App\Http\Controllers;

use App\Game\Support\Constants;
use Illuminate\Http\Request;

/**
 * Port of army.php (+ train_report), battlefield.php (+ include/war/*), wars.php, warinfo.php.
 */
class WarController extends GameController
{
    /* ------------------------------------------------------------------ army */
    public function army(Request $request)
    {
        if (! $this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('army');
        $cit = $this->citInfo;
        $citID = $this->citID();
        $today = $this->database->getToday();
        $layout = ['title' => $this->lang->getstr('title_army', 'title'), 'bar_title' => $this->lang->getstr('army_bar_title', 'army'), 'ambient' => 'army', 'actiontype' => 'army'];

        $gl = min((int) ($cit['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $wMul = $gdpercs[$gl];
        $wChange = function (int $type) use ($wMul) {
            $c = Constants::TRAIN_WELLNESS[$type];
            $c -= abs(round($c * $wMul));

            return -$c;
        };
        $acc = $this->accesses();
        $msg = null;
        $trainednow = 0;

        if ($request->isMethod('post') && $request->input('submsg') && $acc['is_war_minister']) {
            $this->database->exec('UPDATE country SET army_message = ? WHERE CountryID = ?', [(string) $request->input('armymsg'), $cit['CountryID']]);

            return redirect($request->getRequestUri());
        }
        if ($request->isMethod('post') && in_array((int) $request->input('train'), [Constants::TRAIN_WEIGHTS, Constants::TRAIN_CARDIO], true)) {
            $ttype = (int) $request->input('train');
            if ($cit['LastTrained'] == $today) {
                $msg = '<h3 class=errHandle>'.$this->lang->getstr('error_trained_today', 'msgs').'</h3>';
            } elseif ($cit['wellness'] <= Constants::TRAIN_WELLNESS[$ttype]) {
                $msg = '<h3 class=errHandle>'.$this->lang->getstr('error_low_wellness', 'msgs').'</h3>';
            } else {
                $this->database->doTrain($cit, $ttype);
                $trainednow = 1;
                $this->session->fillInfo(null, true);
                $cit = $this->citInfo = $this->session->userinfo;
            }
        }
        $trained = $cit['LastTrained'] == $today;
        $report = $trained ? $this->trainReport($citID) : null;
        $battles = $this->database->rows("SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName FROM battles
            JOIN region ON region.RegionID = battles.regionID LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
            JOIN country AS defender ON defender.CountryID = battles.Defender
            WHERE (Attacker = ? OR Defender = ? OR ally_att LIKE ? OR ally_def LIKE ?) AND Result = ''",
            [$cit['CountryID'], $cit['CountryID'], "% {$cit['CountryID']} %", "% {$cit['CountryID']} %"]);

        return $this->page('pages.war.army', [
            'msg' => $msg, 'trained' => $trained, 'trainednow' => $trainednow, 'report' => $report, 'battles' => $battles,
            'wChange' => [Constants::TRAIN_WEIGHTS => $wChange(Constants::TRAIN_WEIGHTS), Constants::TRAIN_CARDIO => $wChange(Constants::TRAIN_CARDIO)],
            'shape' => $this->shape($cit),
            'cit' => $cit,
        ], $layout);
    }

    private function trainReport(int $citID): ?array
    {
        $rep = $this->database->row('SELECT * FROM log_training WHERE CitizenID = ? AND Day = ?', [$citID, $this->database->today]);
        if (! $rep) {
            return null;
        }
        $rep['ep2'] = 1;
        $ex = explode('|', (string) $rep['wellness']) + [0, 0, 0];
        [$rep['wellness'], $rep['wellness2'], $rep['wellness3']] = $ex;
        $ex = explode('|', (string) $rep['skill']) + [0, 0, 0];
        [$rep['strength'], $rep['stamina'], $rep['streak']] = array_map('intval', $ex);
        if ($rep['stamina'] > Constants::SHAPE_MAX) { // row written by the old skill-point training
            [$rep['strength'], $rep['stamina'], $rep['streak']] = [(int) $this->citInfo['strength'], (int) $this->citInfo['stamina'], (int) $this->citInfo['train_streak']];
        }
        $rep['type'] = min((int) $rep['type'], Constants::TRAIN_CARDIO);

        return $rep;
    }

    /** Body shape summary for the army page / API. */
    public static function shape(array $cit): array
    {
        $strength = (int) ($cit['strength'] ?? 0);
        $stamina = (int) ($cit['stamina'] ?? 0);
        $stage = (int) floor(($strength + $stamina) / 2);

        return [
            'strength' => $strength, 'stamina' => $stamina, 'streak' => (int) ($cit['train_streak'] ?? 0), 'max' => Constants::SHAPE_MAX,
            'stage' => $stage, 'name' => Constants::SHAPE_NAMES[$stage] ?? '', 'names' => Constants::SHAPE_NAMES,
            'damage' => Constants::shapeDamage($strength), 'fightCost' => Constants::fightWellnessCost($stamina),
            'trainedToday' => ($cit['LastTrained'] ?? 0) == app(\App\Game\Services\GameDatabase::class)->today,
        ];
    }

    /* ------------------------------------------------------------ battlefield */
    public function battle(Request $request, int $id, ?string $for = null)
    {
        $this->lang->addPhrases('regions');
        $rWar = $this->war()->getBattleInfo($id);
        if (! $rWar) {
            return redirect($this->vars->getURL('home'));
        }
        if ($for && ! in_array($for, ['att', 'def'], true)) {
            return redirect($this->vars->getURL('battle', $id));
        }
        if ($rWar['Type'] !== 'revolt' && $for) {
            return redirect($this->vars->getURL('battle', $id));
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $allyDef = $this->session->getStr2Array($rWar['ally_def']);
        $allyAtt = $this->session->getStr2Array($rWar['ally_att']);
        $inDef = $this->loggedIn() && (in_array($cit['CountryID'], $allyDef) || (int) $cit['CountryID'] === (int) $rWar['Defender']);
        $inAtt = $this->loggedIn() && (in_array($cit['CountryID'], $allyAtt) || (int) $cit['CountryID'] === (int) $rWar['Attacker']);

        $errbat = null;
        if (! $rWar['Result'] && $rWar['End'] > time()) {
            if (! $this->loggedIn()) {
                $errbat = 'Log into game first';
            } elseif ($this->isCA()) {
                $errbat = 'Co-Accounts are not allowed to join a battle';
            } elseif ($cit['puberty'] < 0) {
                $errbat = 'You can fight when you are at level 2 (5 EP)';
            } elseif ($cit['occDue'] >= time()) {
                $errbat = 'You are occupied';
            } elseif (! $inDef && ! $inAtt) {
                $errbat = 'Your country is not involved into this war';
            }
        }
        if ($rWar['End_P1'] <= time()) {
            $rWar['phase'] = ($rWar['wall'] > $rWar['extra'] && $rWar['wall'] <= $rWar['sPoint']) ? 2 : 3;
        } else {
            $rWar['phase'] = 1;
        }
        $isCPatt = $this->loggedIn() && $this->politics()->isCP($citID, $rWar['attID']);
        $isCPdef = $this->loggedIn() && $this->politics()->isCP($citID, $rWar['defID']);
        if ($request->input('subretreat') && ($isCPatt || $isCPdef) && ! $rWar['Result'] && $this->war()->canRetreat($rWar, $cit['CountryID'])) {
            if ($this->politics()->isCP($citID, $rWar['Defender'])) {
                $this->war()->battleConquer($rWar['battleID'], 'defreat');
            } elseif ($rWar['Type'] !== 'revolt') {
                $this->war()->battleSecure($rWar['battleID'], 'attreat');
            }

            return redirect($request->getRequestUri());
        }
        $acc = $this->accesses();
        $rageMsg = null;
        if ($request->input('subhitrage') && $this->loggedIn() && (int) $rWar['Attacker'] === (int) $cit['CountryID'] && $acc['is_war_minister']) {
            $rageMsg = $this->shootRage($request, $rWar, $cit);
            if ($rageMsg === true) {
                return redirect($request->getRequestUri());
            }
        }
        $allies = fn (array $ids) => array_values(array_filter(array_map(fn ($i) => $this->database->getCountryRec((int) $i), $ids)));
        $maxcli = $rWar['Clinic'] ? 10 : 5;
        $weapStar = $this->loggedIn() ? (int) $this->database->value('SELECT Stars FROM inventory WHERE Usable = 1 AND Owner = ? AND Type = 4 ORDER BY Stars DESC LIMIT 1', [$citID], 0) : 0;
        $myInf = $this->loggedIn() ? (float) $this->database->value('SELECT SUM(ABS(damage)) dmg FROM fights WHERE battleID = ? AND fighterID = ?', [$rWar['battleID'], $citID], 0) : 0;

        $data = [
            'rWar' => $rWar, 'for' => $for ?? '', 'errbat' => $errbat, 'inDef' => $inDef, 'inAtt' => $inAtt,
            'alliesAtt' => $allies($allyAtt), 'alliesDef' => $allies($allyDef),
            'revoltReg' => $rWar['Type'] === 'revolt' ? $this->war()->getRegion($rWar['warID']) : null,
            'canRetreat' => (($isCPatt && $rWar['Type'] !== 'revolt') || $isCPdef) && $this->loggedIn() && ! $rWar['Result'] && $this->war()->canRetreat($rWar, $cit['CountryID'] ?? 0),
            'maxcli' => $maxcli, 'weapStar' => $weapStar, 'myInf' => $myInf,
            'cliToken' => md5($citID.$maxcli.$this->database->today.'clInic Uz'),
            'wpCost' => (floor((int) ($cit['LastDayWP'] ?? 0) / 5) + 1) * 0.5,
            'rageMsg' => $rageMsg,
            'canRage' => $this->loggedIn() && (int) $rWar['Attacker'] === (int) $cit['CountryID'] && $acc['is_war_minister'],
        ];
        if ($data['canRage']) {
            $data['rages'] = $this->database->rows('SELECT * FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = 11 GROUP BY Stars', [$cit['nca_war']]);
            $data['rageRegions'] = $this->database->rows("SELECT region.RegionID, rName, stat_pop, ROUND(AVG(citizens.wellness), 2) AS avgWell FROM region
                JOIN citizens ON region.RegionID = citizens.regionID WHERE CountryID = ? AND citizens.wellness > 0 AND citizens.ban_due != 'PERMANENTLY' GROUP BY RegionID ORDER BY rName", [$rWar['Defender']]);
        }
        $title = $this->lang->getstr('title_battle_head', 'title').':'.sprintf($this->lang->getstr('title_battle_tail', 'title'),
            $this->lang->getstr($rWar['defSName'], 'country'), $this->lang->getstr('region_'.$rWar['regionID'], 'regions'),
            $rWar['Type'] === 'war' ? $this->lang->getstr($rWar['attSName'], 'country') : $this->lang->getstr('title_battle_rforce', 'title'));

        return $this->page('pages.war.battlefield', $data, ['title' => $title, 'bar_title' => 'Battlefield', 'coltype' => 1, 'ambient' => 'war', 'actiontype' => 'battle']);
    }

    /** Port of include/war/shoot_rage.php. Returns true on success or an error HTML string. */
    private function shootRage(Request $request, array $rWar, array $cit): string|bool
    {
        $rageID = (int) $request->input('rageID');
        $regID = (int) $request->input('regID');
        if (! $rageID || ! $regID) {
            return '<h3 class="errHandle">Please select a rage/region</h3>';
        }
        $rage = $this->database->row('SELECT * FROM inventory WHERE Usable = 1 AND Type = 11 AND Owner = ? AND pID = ?', [$cit['nca_war'], $rageID]);
        if (! $rage) {
            return '<h3 class="errHandle">Rage error!</h3>';
        }
        $reg = $this->database->row('SELECT * FROM region WHERE RegionID = ? AND CountryID = ?', [$regID, $rWar['Defender']]);
        if (! $reg) {
            return '<h3 class="errHandle">Region error!</h3>';
        }
        $s = (int) $rage['Stars'];
        $rgWell = ($s + 1) * 10 + ($s > 3 ? (($s - 3) * 10) : 0);
        $rgPois = ($s + 1) + ($s > 4 ? 1 : 0);
        $rgClinic = $s > 3 ? ($s - 3) : 0;
        $this->database->exec("UPDATE citizens SET wellness = IF(wellness > ?, wellness - ?, IF(wellness > 10, 10, wellness)), poisoning = poisoning + ? WHERE regionID = ? AND ban_due != 'PERMANENTLY'",
            [$rgWell + 10, $rgWell, $rgPois, $reg['RegionID']]);
        $this->database->exec('UPDATE inventory SET Usable = 0 WHERE pID = ?', [$rageID]);
        $rgClinic = min($rgClinic, (int) $reg['Clinic']);
        $this->database->exec('UPDATE region SET Clinic = Clinic - ? WHERE RegionID = ?', [$rgClinic, $regID]);
        $cp = $this->database->value('SELECT cpID FROM country WHERE CountryID = ?', [$rWar['Defender']]);
        if ($cp) {
            $this->database->sendNote($cp, 'rageshot', "{$rWar['Attacker']}|{$rWar['attSName']}|{$s}|{$reg['RegionID']}");
        }
        $this->database->exec("INSERT INTO events (Type, Country1, Country2, refID, timestamp) VALUES ('rage', ?, ?, ?, ?)", [$rWar['Attacker'], $rWar['Defender'], "{$s}|{$regID}|{$rWar['battleID']}", time()]);
        $this->database->exec('INSERT INTO log_rageshots (quality, battleID, attacker, toCoun, toReg, timestamp) VALUES (?, ?, ?, ?, ?, ?)', [$s, $rWar['battleID'], $rWar['Attacker'], $rWar['Defender'], $regID, time()]);

        return true;
    }

    /* ------------------------------------------------------------------- wars */
    public function wars($inv = null, $act = null, $type = null, $page = null)
    {
        $inv = (int) $inv;
        $act = in_array($act, ['act', 'end', 'all'], true) ? $act : 'all';
        $type = in_array($type, ['war', 'rev', 'all'], true) ? $type : 'all';
        $page = max(1, (int) ($page ?: 1));
        $start = ($page - 1) * 10;
        $sql = "SELECT wars.*, Attacker.cName AS attName, Attacker.Flag AS attFlag, Defender.cName AS defName, Defender.Flag AS defFlag,
                AttackerC.CitizenID AS StarterID, AttackerC.name AS Starter, AttackerC.Avatar AS StarterAvatar
            FROM wars LEFT JOIN country AS Attacker ON (Attacker.CountryID = wars.Attacker AND wars.Type = 'war')
            LEFT JOIN citizens AS AttackerC ON (AttackerC.CitizenID = wars.Attacker AND wars.Type = 'revolt')
            JOIN country AS Defender ON Defender.CountryID = wars.Defender";
        $where = [];
        $b = [];
        if ($inv) {
            $where[] = '(Attacker = ? OR Defender = ? OR (ally_def LIKE ? AND (allies >= 2)) OR (ally_att LIKE ? AND (allies = 1 OR allies = 3)))';
            array_push($b, $inv, $inv, "% {$inv} %", "% {$inv} %");
        }
        if ($act === 'act') {
            $where[] = "End = '0'";
        } elseif ($act === 'end') {
            $where[] = "End > '0'";
        }
        if ($type === 'war') {
            $where[] = "Type = 'war'";
        } elseif ($type === 'rev') {
            $where[] = "Type = 'revolt'";
        }
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        $sql .= ' ORDER BY End, Start DESC';
        $num = $this->database->count($sql, $b);
        $rows = [];
        foreach ($this->database->rows($sql." LIMIT {$start}, 10", $b) as $w) {
            $w['alliesAtt'] = array_values(array_filter(array_map(fn ($i) => $this->database->getCountryRec((int) $i), $this->session->getStr2Array($w['ally_att']))));
            $w['alliesDef'] = array_values(array_filter(array_map(fn ($i) => $this->database->getCountryRec((int) $i), $this->session->getStr2Array($w['ally_def']))));
            $w['revoltReg'] = $w['Type'] === 'revolt' ? $this->war()->getRegion($w['warID']) : null;
            $rows[] = $w;
        }
        $coun = $inv ? $this->database->getCountryRec($inv) : null;

        return $this->page('pages.war.wars', [
            'inv' => $inv, 'act' => $act, 'type' => $type, 'page' => $page, 'start' => $start, 'num' => $num, 'rows' => $rows, 'coun' => $coun,
            'countries' => $this->database->rows('SELECT * FROM country ORDER BY cName'),
        ], ['title' => $this->lang->getstr('title_wars', 'title'), 'bar_title' => 'War list', 'ambient' => 'war', 'actiontype' => 'wars']);
    }

    /* ---------------------------------------------------------------- warinfo */
    public function warInfo(Request $request, int $warID, ?string $go = null)
    {
        $rWar = $this->database->row('SELECT wars.*, Attacker.cName AS attName, Attacker.Flag AS attFlag, Defender.cName AS defName, Defender.Flag AS defFlag
            FROM wars LEFT JOIN country AS Attacker ON Attacker.CountryID = wars.Attacker JOIN country AS Defender ON Defender.CountryID = wars.Defender WHERE warID = ?', [$warID]);
        if (! $rWar) {
            return redirect($this->vars->getURL('home'));
        }
        if ($rWar['Type'] === 'revolt') {
            $bat = $this->database->row('SELECT battleID FROM battles WHERE warID = ?', [$warID]);
            if ($bat) {
                return redirect($this->vars->getURL('battle', $bat['battleID']));
            }
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $msg = null;
        if ($this->loggedIn() && $request->input('substart') && count($this->war()->getActiveBattles($warID)) < 2) {
            $regID = (int) $request->input('regID');
            $trust = (string) $request->input('Trust');
            $ttokn = md5($regID.$trust.'RegTru$t');
            $counter = null;
            if ($this->politics()->isCP($citID, $rWar['Attacker'])) {
                $counter = '';
            } elseif ($this->politics()->isCP($citID, $rWar['Defender'])) {
                $counter = 'counter';
            }
            if ($counter !== null && hash_equals($ttokn, (string) $request->input('token'))) {
                $reg = $this->database->getRegionRec($regID);
                $counMoney = $this->database->getCountryAccount($cit['CountryID'], 1);
                $price = (float) ($reg['stat_value'] ?? $trust);
                if (! $reg || ($counMoney['Amount'] ?? 0) < $price) {
                    $msg = '<center>Your country has not enough money in treasury</center>';
                } else {
                    $this->database->addMoney(1, -$price, $cit['CountryID'], 'country', 1);
                    $this->war()->attackRegion($warID, $regID, $counter ?: 'reaction');

                    return redirect($request->getRequestUri());
                }
            }
        }
        $totFights = (int) $this->database->value('SELECT COUNT(*) AS TotFights FROM fights JOIN battles ON battles.battleID = fights.battleID WHERE battles.warID = ?', [$warID], 0);
        $data = ['rWar' => $rWar, 'warID' => $warID, 'go' => $go ?: '', 'totFights' => $totFights, 'msg' => $msg];
        if ($go === 'finished') {
            $data['battles'] = $this->database->rows("SELECT battles.*, Defender.cName AS defName, Defender.Flag AS defFlag, region.rName FROM wars
                JOIN battles ON battles.warID = wars.warID JOIN country AS Defender ON Defender.CountryID = battles.Defender
                JOIN region ON region.RegionID = battles.regionID WHERE wars.warID = ? AND battles.Result != '' ORDER BY Start DESC", [$warID]);
        } elseif ($go === 'details') {
            $data['details'] = $this->database->rows('SELECT fights.*, country.*, COUNT(damage) AS totFight, SUM(ABS(damage)) AS totForce FROM fights
                JOIN country ON country.CountryID = fights.CountryID JOIN battles ON fights.battleID = battles.battleID
                WHERE warID = ? GROUP BY fights.CountryID ORDER BY totForce DESC', [$warID]);
        } else {
            $data['battles'] = $this->database->rows("SELECT battles.*, wars.defPoints, Defender.cName AS defName, Defender.Flag AS defFlag, region.rName FROM wars
                JOIN battles ON battles.warID = wars.warID JOIN country AS Defender ON Defender.CountryID = battles.Defender
                JOIN region ON region.RegionID = battles.regionID WHERE wars.warID = ? AND battles.Result = ''", [$warID]);
            $data += $this->startBattleData($rWar, $data['battles']);
        }

        return $this->page('pages.war.warinfo', $data, ['title' => $this->lang->getstr('title_war', 'title'), 'bar_title' => 'War info', 'ambient' => 'war', 'actiontype' => 'war']);
    }

    /** Port of include/war/start_battle.php — which regions a CP may attack now. */
    private function startBattleData(array $rWar, array $activeBattles): array
    {
        $out = ['seedMode' => null, 'seedRegions' => [], 'seedBlocked' => false, 'winDue' => null];
        if (! $this->loggedIn()) {
            return $out;
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $canAttack = ! $rWar['End'] && ((int) $rWar['winID'] === (int) $cit['CountryID'] || $rWar['winDue'] < time());
        $bBatts = array_column($activeBattles, 'regionID');
        $show = count($activeBattles) < 1;
        if (count($activeBattles) === 1) {
            $b = $activeBattles[0];
            $show = (time() - $b['Start'] > 23 * 3600) && ($b['wall'] < ($b['sPoint'] / 4));
        }
        if (! $show) {
            return $out;
        }
        $side = null;
        if ($this->politics()->isCP($citID, $rWar['Attacker']) && $canAttack) {
            $side = ['from' => $rWar['Attacker'], 'to' => $rWar['Defender']];
        } elseif ($this->politics()->isCP($citID, $rWar['Defender']) && $canAttack) {
            $side = ['from' => $rWar['Defender'], 'to' => $rWar['Attacker']];
        } elseif (($this->politics()->isCP($citID, $rWar['Attacker']) || $this->politics()->isCP($citID, $rWar['Defender'])) && ! $rWar['End'] && (int) $rWar['winID'] !== (int) $cit['CountryID'] && $rWar['winDue'] >= time()) {
            $out['winDue'] = $rWar['winDue'];

            return $out;
        }
        if (! $side) {
            return $out;
        }
        $regs = $this->database->rows('SELECT reg2.* FROM country AS Country1 RIGHT JOIN region AS reg1 ON reg1.CountryID = Country1.CountryID
            RIGHT JOIN neighbors ON neighbors.Region1 = reg1.RegionID LEFT JOIN region AS reg2 ON reg2.RegionID = neighbors.Region2
            LEFT JOIN country AS Country2 ON reg2.CountryID = Country2.CountryID
            WHERE Country1.CountryID = ? AND Country2.CountryID = ? GROUP BY reg2.rName ORDER BY Country2.cName', [$side['from'], $side['to']]);
        if (! $regs) {
            return $out;
        }
        $out['seedMode'] = 'list';
        $busy = $this->database->count("SELECT battleID FROM battles WHERE Defender = ? AND battle_type = 'battle' AND Result = ''", [$side['from']]);
        if ($busy) {
            $out['seedBlocked'] = true;

            return $out;
        }
        foreach ($regs as $reg) {
            if (in_array($reg['RegionID'], $bBatts)) {
                continue;
            }
            $w = $this->war()->getWall($reg);
            $reg['wallText'] = "{$w['wall']}/{$w['sp']}";
            $out['seedRegions'][] = $reg;
        }

        return $out;
    }
}
