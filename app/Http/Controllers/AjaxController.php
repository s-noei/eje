<?php

namespace App\Http\Controllers;

use App\Game\Support\Constants;
use Illuminate\Http\Request;

/**
 * Port of include/self/*.php and include/ajFunc.php — the JSON/text endpoints
 * called by the legacy JavaScript (jQuery 1.3.2).
 */
class AjaxController extends GameController
{
    private function json(array|object $data)
    {
        return response()->json($data)->header('Cache-Control', 'no-store, no-cache, must-revalidate')->header('Pragma', 'no-cache');
    }

    /** regions-{id}[-{type}].html — regions of a country for select boxes. */
    public function regions(int $id, int $type = 0)
    {
        $lang = in_array($this->lang->lang, config('ejahan.languages'), true) ? $this->lang->lang : 'en';
        if ($type) {
            $rows = $this->database->rows("SELECT IFNULL(trans_reg.trans_{$lang}, IFNULL(trans_reg.trans_en, region.rName)) AS RegionName,
                    RegionID, Owner.CountryID AS OwnerID, Current.CountryID AS CountryID, Owner.cName AS OwnerName,
                    IFNULL(trans_coun.trans_{$lang}, IFNULL(trans_coun.trans_en, Current.cName)) AS CountryName
                FROM region JOIN country AS Owner ON region.oCountryID = Owner.CountryID
                JOIN country AS Current ON region.CountryID = Current.CountryID
                JOIN trans_strings trans_reg ON concat('region_', region.RegionID) = trans_reg.phrase
                JOIN trans_strings trans_coun ON Current.shortName = trans_coun.phrase
                WHERE Owner.CountryID = ? ORDER BY RegionName", [$id]);
        } else {
            $rows = $this->database->rows('SELECT rName AS RegionName, RegionID, Current.CountryID AS CountryID, Current.cName AS CountryName
                FROM region JOIN country AS Current ON region.CountryID = Current.CountryID WHERE Current.CountryID = ? ORDER BY RegionName', [$id]);
        }
        $flag = $this->database->value('SELECT Flag FROM country WHERE CountryID = ?', [$id]);

        return $this->json(['region' => $rows, 'CountryFlag' => $flag ? "/images/flags/l/{$flag}.gif" : '']);
    }

    /** region-{id}.gif — region thumbnail coloured with the owner's map colour. */
    public function regionImage(int $id)
    {
        $file = public_path("include/map/regions/{$id}.gif");
        if (! is_file($file) || ! function_exists('imagecreatefromgif')) {
            abort(404);
        }
        $im = imagecreatefromgif($file);
        $row = $this->database->row('SELECT map_colors.* FROM map_colors JOIN region ON region.CountryID = map_colors.CountryID WHERE RegionID = ?', [$id]);
        if ($row) {
            $index = imagecolorclosest($im, 255, 255, 255);
            imagecolorset($im, $index, (int) $row['r'], (int) $row['g'], (int) $row['b']);
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $r = $w / $h;
        [$nw, $nh] = (90 / 45 > $r) ? [45 * $r, 45] : [90, 90 / $r];
        $dst = imagecreatetruecolor((int) $nw, (int) $nh);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, (int) $nw, (int) $nh, $w, $h);
        ob_start();
        imagegif($dst);
        $out = ob_get_clean();
        imagedestroy($im);
        imagedestroy($dst);

        return response($out, 200, ['Content-Type' => 'image/gif']);
    }

    /** juice-{id}-{token}.html — drink juice on self or a friend. */
    public function juice(int $id, string $token)
    {
        if (! $this->loggedIn() || ! $id || ! hash_equals(md5($id.$this->citID().config('ejahan.salts.juice')), $token)) {
            return response('');
        }
        $fr = $this->citID();
        $citInfo = $this->database->getUserInfoFromID($fr);
        $toInfo = $this->database->getUserInfoFromID($id);
        if (! $toInfo) {
            return response('');
        }
        $out = ['self' => $fr === (int) $toInfo['CitizenID']];
        $juices = $this->database->rows("SELECT * FROM inventory WHERE Type = '3' AND Owner = ? AND Usable = '1' ORDER BY Stars ASC", [$fr]);
        if (! $juices) {
            $out['result'] = 'nojuice';
            $out['url'] = $this->vars->getURL('market', 3, 0, $citInfo['CountryID']);

            return $this->json($out);
        }
        $lhp = (int) $toInfo['LastJuiceWellness'];
        $hp = (float) $toInfo['wellness'];
        $qhp = 0;
        $used = [];
        foreach ($juices as $drink) {
            if ($lhp + $drink['Stars'] > 200) {
                $out['result'] = 'finish';
            } elseif ($hp >= 100) {
                $out['result'] = 'wfull';
            } else {
                $hp += $drink['Stars'];
                $lhp += $drink['Stars'];
                $qhp += $drink['Stars'];
                $used[] = (int) $drink['pID'];
            }
        }
        if ($used) {
            $this->database->exec("UPDATE inventory SET Usable = '0' WHERE pID IN (".implode(',', $used).')');
        }
        $hp = min(100, $hp);
        $lhp = min(200, $lhp);
        $this->database->updateUserFieldID($id, 'wellness', $hp);
        $this->database->exec('UPDATE citizens SET LastJuiceWellness = ? WHERE CitizenID = ?', [$lhp, $id]);
        $out += [
            'jremain' => $this->database->count('SELECT pID FROM inventory WHERE Owner = ? AND Type = 3 AND Usable = 1', [$fr]) ? 1 : 0,
            'result' => 'done', 'wellness' => $hp, 'quality' => $qhp, 'fight' => $hp >= 20,
            'remain' => 200 - $lhp, 'wremain' => 200 - $lhp, 'aremain' => 9999,
        ];

        return $this->json($out);
    }

    /** friendship-{do}-{id}-{token}.html */
    public function friendAction(string $do, int $id, string $token)
    {
        if (! $this->loggedIn()) {
            return response('Logged out!');
        }
        $me = $this->database->getUserInfoFromID($this->citID(), 1);
        $other = $this->database->getUserInfoFromID($id, 1);
        $isCA = ($me['accType'] ?? '') !== 'citizen' || ($other['accType'] ?? '') !== 'citizen';
        $result = 'err';
        if ($id && ! $isCA && hash_equals(md5($id.config('ejahan.salts.friend')), $token)) {
            $r = $do === 'accept' ? $this->friendship()->acceptFriend($this->citID(), $id)
                : ($do === 'reject' ? $this->friendship()->rejectFriend($this->citID(), $id) : 0);
            $result = $r ? 'done' : 'dup';
        }

        return $this->json(['result' => $result]);
    }

    /** getevents-{id}.html — latest military events (id = country or 0). */
    public function events(int $id)
    {
        $this->lang->addPhrases('regions');
        $this->lang->addPhrases('events');
        $sql = 'SELECT events.*, Country1.cName AS Country1Name, Country1.shortName AS Country1SN, Country1.CountryID AS Country1ID,
                Country2.cName AS Country2Name, Country2.shortName AS Country2SN, Country2.CountryID AS Country2ID, region.rName AS RegionName, region.RegionID
            FROM events LEFT JOIN country AS Country1 ON Country1.CountryID = events.Country1
            LEFT JOIN country AS Country2 ON Country2.CountryID = events.Country2
            LEFT JOIN battles ON battles.battleID = events.refID
            LEFT JOIN region ON battles.regionID = region.RegionID';
        $b = [];
        if ($id) {
            $sql .= ' WHERE (Country1 = ? OR Country2 = ?)';
            $b = [$id, $id];
        }
        $events = $this->database->rows($sql.' ORDER BY timestamp DESC LIMIT 5', $b);
        if (! $events) {
            return $this->json(['noeve' => $this->lang->getstr('no_mili_events', 'events')]);
        }
        $out = [];
        foreach ($events as $event) {
            $out[] = $this->describeEvent($event);
        }

        return $this->json(['event' => $out]);
    }

    /** Builds {id, icon, title, link} for an events row (shared with the media centre). */
    public function describeEvent(array $event): array
    {
        $lang = $this->lang;
        $c = fn ($sn) => $lang->getstr($sn, 'country');
        $ref = 'battle';
        $refID = $event['refID'];
        switch ($event['Type']) {
            case 'att':
                if ($event['RegionName']) {
                    $desc = $lang->getstr('events_att', 'events');
                } else {
                    $ref = 'war';
                    $desc = $lang->getstr('att', 'events');
                }
                break;
            case 'dec':
                $ref = 'law';
                $desc = $lang->getstr('dec', 'events');
                break;
            case 'pce':
                $ref = 'law';
                $desc = $lang->getstr('pce', 'events');
                break;
            case 'conq':
                $desc = $lang->getstr('conq', 'events');
                break;
            case 'secu':
                $desc = $lang->getstr('secu', 'events');
                break;
            case 'revolt':
                $desc = $lang->getstr('events_revolt', 'events');
                break;
            case 'rage':
                $ref = 'region';
                $desc = $lang->getstr('events_rageshot', 'events');
                $ex = explode('|', (string) $event['refID']);
                $event['Quality'] = $ex[0] ?? '';
                $refID = $ex[1] ?? '';
                break;
            default:
                $desc = '%s %s';
        }
        $force = $lang->getstr('events_revolt_force', 'events');
        if (in_array($event['Type'], ['revolt', 'conq', 'secu'], true)) {
            $tit = @sprintf($desc, $lang->getstr('region_'.$event['RegionID'], 'regions'), $event['Country1Name'] ? $c($event['Country1SN']) : $force, $event['Country2Name'] ? $c($event['Country2SN']) : $force);
        } elseif ($event['Type'] === 'att' && $event['RegionName']) {
            $tit = @sprintf($desc, $c($event['Country1SN']), $lang->getstr('region_'.$event['RegionID'], 'regions'), $c($event['Country2SN']));
        } elseif ($event['Type'] === 'rage') {
            $tit = @sprintf($desc, $c($event['Country1SN']), $event['Quality'], $lang->getstr('region_'.$refID, 'regions'), $c($event['Country2SN']));
        } else {
            $tit = @sprintf($desc, $c($event['Country1SN']), $c($event['Country2SN']));
        }

        return ['id' => $event['eventID'], 'icon' => '/images/media/'.$event['Type'].'-s.jpg', 'title' => $tit ?: $desc, 'link' => $this->vars->getURL($ref, $refID)];
    }

    /** tasks-{what}.html — the to-do balloons on the left sidebar. */
    public function tasks(string $what)
    {
        if (! $this->loggedIn()) {
            return response('');
        }
        $cit = $this->database->getUserInfoFromID($this->citID());
        $this->lang->addPhrases('tasks');
        $t = fn ($k) => $this->lang->getstr($k, 'tasks');
        $todo = [];
        $add = function ($title, $pic, $link, $desc, $skip = 1, $main = 1, $time = 0) use (&$todo) {
            $todo[] = compact('title', 'pic', 'link', 'desc', 'skip', 'main', 'time');
        };
        $today = $this->database->getToday();
        $now = time();

        $pm = $this->database->row('SELECT pmID FROM pm WHERE fromID = 1 AND toID = ? AND isRead = 0', [$cit['CitizenID']]);
        if ($pm) {
            $add($t('readpm_title'), 'welcome', $this->vars->getURL('mail', 'view', $pm['pmID']), $t('readpm_desc'), 1, 0);
        }
        if ($cit['joined'] != $today) {
            if ($cit['dmg_booster'] > $now) {
                $add('Damage Booster', 'damge-booster', $this->vars->getURL('special'), 'Damage Booster for 20 minutas.', 1, 0, $cit['dmg_booster']);
            }
            if ($cit['gold_pack'] > $now) {
                if ($cit['gold_pack'] - $now < 86400) {
                    $add('Gold Pack (30 Days)', 'gold-pack', $this->vars->getURL('special'), 'Enables all game functions for 30 days.', 1, 0, $cit['gold_pack']);
                }
            } elseif ($this->database->count("SELECT pID FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = '13' AND Stars = '5' LIMIT 1", [$cit['CitizenID']])) {
                $add('Gold Pack (30 Days)', 'gold-pack', $this->vars->getURL('items'), 'Enables all game functions for 30 days.', 1, 0);
            } else {
                $add('Buy Gold Pack', 'gold-pack', $this->vars->getURL('special'), 'Enables all game functions for 30 days.', 1, 0);
            }
            $timeGP = ($cit['gold_pack'] > $now) ? 5 : 10;
            $cons = $this->database->row('SELECT * FROM log_consume WHERE citID = ? ORDER BY timestamp DESC LIMIT 1', [$cit['CitizenID']]);
            $consumeLink = $this->vars->getURL('profile', $cit['CitizenID'], 'consume');
            if (! $cons || $cons['timestamp'] < $now - $timeGP * 60) {
                $add($t('usefood_title'), 'food', $consumeLink, $t('usefood_desc'), 1, 0);
            } else {
                $add($t('usefood_title'), 'food', $consumeLink, $t('usefood_desc'), 1, 0, $cons['timestamp'] + $timeGP * 60);
            }
            if ($cit['LastExplored'] < $today) {
                $add($t('explore_title'), 'explore', $this->vars->getURL('mines'), $t('explore_desc'), 1, 1);
            }
        }
        if ($this->database->isWorker($cit['CitizenID'])) {
            if ($cit['LastWorked'] < $today) {
                $add($t('workplace_title'), 'work', $this->vars->getURL('company'), $t('workplace_desc'), 1, 1);
            }
        } elseif (! $this->database->isManager($cit['CitizenID'])) {
            $add($t('getjob_title'), 'job', $this->vars->getURL('jobs'), $t('getjob_desc'), 1, 0);
        }
        if ($cit['LastTrained'] < $today) {
            $add($t('army_title'), 'train', $this->vars->getURL('army'), $t('army_desc'), 1, 1);
        }
        if ($cit['nationality'] != $cit['CountryID']) {
            $add($t('nationality_title'), 'nationality', $this->vars->getURL('profile', $cit['CitizenID'], 'nationality'), $t('nationality_desc'), 1, 0);
        } elseif (! $this->database->getCitizenParty($cit['CitizenID'])) {
            $add($t('jparty_title'), 'party', $this->vars->getURL('party'), $t('jparty_desc'), 1, 0);
        }
        $nowA = $this->session->getTodayArray();
        if ($nowA['Day'] == Constants::ELECTIONS_CG_DAY) {
            $add($t('vote_title'), 'vote', $this->vars->getURL('elections', 'cg', $cit['CountryID'], '', $nowA['Year'], $nowA['Month']), sprintf($t('vote_desc'), $t('vote_cg_desc')), 1, 0);
        } elseif ($nowA['Day'] == Constants::ELECTIONS_CP_DAY) {
            $elec = $this->elections()->getLastElection('CP');
            if (! $this->elections()->isVoted($cit['CitizenID'], $elec['eID'] ?? 0, 'cp')) {
                $add($t('vote_title'), 'vote', $this->vars->getURL('elections', 'cp', $cit['CountryID'], '', $nowA['Year'], $nowA['Month']), sprintf($t('vote_desc'), $t('vote_cp_desc')), 1, 0);
            }
        } elseif ($nowA['Day'] == Constants::ELECTIONS_PP_DAY) {
            $add($t('vote_title'), 'vote', $this->vars->getURL('elections', 'pp', $cit['CountryID'], $cit['PartyID'] ?? 0, $nowA['Year'], $nowA['Month']), sprintf($t('vote_desc'), $t('vote_pp_desc')), 1, 0);
        }

        if ($what === 'next') {
            $this->database->updateUserFieldID($cit['CitizenID'], 'activeTask', $cit['activeTask'] + 1);
        }
        $tasks = [];
        $check = 0;
        foreach ($todo as $task) {
            if ($cit['occDue'] >= $now && $task['main']) {
                if (! $check) {
                    $tasks[] = ['title' => $t('lookaround_title'), 'pic' => 'mcenter', 'link' => $this->vars->getURL('media'), 'desc' => $t('lookaround_desc'), 'skip' => 0];
                    $check = 1;
                }
            } else {
                $tasks[] = ['title' => $task['title'], 'pic' => $task['pic'], 'link' => $task['link'], 'desc' => $task['desc'], 'skip' => $task['skip'], 'time' => $task['time'] ? $task['time'] - $now : 0];
            }
        }

        return $this->json(['tasks' => $tasks]);
    }

    /** clinic-{token}.html — use a regional clinic in battle. */
    public function clinic(string $token)
    {
        if (! $this->loggedIn()) {
            return response('');
        }
        $citID = $this->citID();
        $today = $this->database->today;
        $max = hash_equals(md5($citID.'5'.$today.'clInic Uz'), $token) ? 5 : (hash_equals(md5($citID.'10'.$today.'clInic Uz'), $token) ? 10 : 0);
        if (! $max) {
            return response('');
        }
        $cit = $this->database->getUserInfoFromID($citID);
        $numfights = $this->database->count('SELECT fightID FROM fights WHERE timestamp >= ? AND fighterID = ?', [$this->database->todayts, $citID]);
        $reg = $this->database->row('SELECT * FROM region WHERE Clinic > 0 AND CountryID = ? ORDER BY RAND() LIMIT 1', [$cit['CountryID']]);
        if (! $reg) {
            $out = ['result' => 'noclinic', 'canuse' => 0];
        } elseif ($cit['LastDayClinic'] >= $max) {
            $out = ['result' => 'max', 'canuse' => 0];
        } elseif ($cit['LastDayClinic'] >= $numfights && $today < 727) {
            $out = ['result' => 'nofight', 'canuse' => 0];
        } elseif ($cit['wellness'] >= 100) {
            $out = ['result' => 'wfull', 'canuse' => 0];
        } else {
            $this->database->exec('UPDATE region SET Clinic = Clinic - 1 WHERE RegionID = ?', [$reg['RegionID']]);
            $wChange = ($cit['wellness'] + 10 <= 100) ? 10 : (100 - $cit['wellness']);
            $this->database->exec('UPDATE citizens SET LastDayClinic = LastDayClinic + 1, wellness = wellness + ? WHERE CitizenID = ?', [$wChange, $citID]);
            $used = $cit['LastDayClinic'] + 1;
            $out = ['result' => 'done', 'wellness' => $cit['wellness'] + $wChange, 'remain' => $max - $used, 'fight' => ($cit['wellness'] + $wChange) >= 20, 'canuse' => $max > $used];
        }

        return $this->json($out);
    }

    /** getweap-{q}.html — next available weapon quality above q. */
    public function getWeapon(int $q)
    {
        if ($q >= 5 || ! $this->loggedIn()) {
            return response('0');
        }
        $stars = $this->database->value('SELECT Stars FROM inventory WHERE Owner = ? AND Usable = 1 AND Stars > ? AND Type = 4 ORDER BY Stars LIMIT 1', [$this->citID(), $q]);

        return response((string) ($stars ?: '0'));
    }

    /** buywp.html — buy a wellness pack with Tala. */
    public function buyWellnessPack()
    {
        if (! $this->loggedIn()) {
            return response('');
        }
        $cit = $this->database->getUserInfoFromID($this->citID(), 1);
        $cAcc = $this->database->getCitizenMoney($cit['CitizenID'], 1);
        $maxWP = ($cit['proExpire'] >= time()) ? 80 : 60;
        $bought = (int) $cit['LastDayWP'];
        $remain = max(0, $maxWP - $bought);
        $cost = (floor(min($bought, 59) / 15) + 1) * 0.5;
        if ((int) $cit['wellness'] === 100) {
            $out = ['result' => 'wellness'];
        } elseif ($cAcc < $cost) {
            $out = ['result' => 'money'];
        } elseif ($remain <= 0) {
            $out = ['result' => 'finished'];
        } else {
            $remain--;
            $this->database->addMoney(1, -$cost, $cit['CitizenID']);
            $wellness = min(100, $cit['wellness'] + 10);
            $this->database->updateUserFieldID($cit['CitizenID'], 'wellness', $wellness);
            $this->database->updateUserFieldID($cit['CitizenID'], 'LastDayWP', $bought + 1);
            $out = ['result' => 'done', 'wellness' => $wellness, 'fight' => $wellness >= 20, 'remain' => $remain, 'money' => round($cAcc - $cost, 2), 'cost' => $cost];
        }

        return $this->json($out);
    }

    /** getCBresult.html — open a chancebox egg. */
    public function chanceboxResult(Request $request)
    {
        if (! $this->loggedIn()) {
            return response('rest');
        }
        $this->lang->addPhrases('chancebox');
        $citID = $this->citID();
        $cbID = (int) $request->input('cbID');
        $number = (int) $request->input('number');
        $token = (string) $request->input('token');
        if ((string) crc32($cbID.$citID.'k3y4 chance egg') !== $token) {
            return response('rest');
        }
        if (! $this->database->count('SELECT LastCBsOpened FROM citizens WHERE CitizenID = ? AND LastCBsOpened < 3', [$citID])) {
            return response('rest');
        }
        $cb = $this->database->row('SELECT * FROM chanceboxes WHERE toID = ? AND cbID = ? AND ISNULL(result)', [$citID, $cbID]);
        if (! $cb || $number > 7 || $number < 1) {
            return response('rest');
        }
        $prizes = ['tala', 'ep', 'wsp', 'msp', 'pro', 'tala'];
        $muls = [0.15, 1, 2, 1, 0.25, 0.1];
        $i = ($number === 6 && $cb['canGetTala']) ? 5 : (int) $cb['expResult'] - 1;
        $i = max(0, min(5, $i));
        $amount = $muls[$i] * $cb['points'];
        switch ($i) {
            case 0:
            case 5:
                $this->database->addMoney(1, $amount, $citID, 'citizen', 1, 'Receive from chancebox');
                break;
            case 1:
                $this->database->addEP($citID, (int) round($amount), 'Receive from chancebox');
                break;
            case 2:
                $cit = $this->database->getUserInfoFromID($citID, 1);
                $wSP = $cit['wSP'] + round($amount);
                $this->database->updateUserFieldID($citID, 'wSP', $wSP);
                if ((Constants::SP_CPS[$cit['wSkill'] + 1] ?? PHP_INT_MAX) < $wSP) {
                    $this->database->updateUserFieldID($citID, 'wSkill', $cit['wSkill'] + 1);
                }
                break;
            case 3:
                $cit = $this->database->getUserInfoFromID($citID, 1);
                $mSP = $cit['mSP'] + round($amount);
                $this->database->updateUserFieldID($citID, 'mSP', $mSP);
                if ((Constants::SP_CPS[$cit['mSkill'] + 1] ?? PHP_INT_MAX) < $mSP) {
                    $this->database->updateUserFieldID($citID, 'mSkill', $cit['mSkill'] + 1);
                }
                if (($mSP - round($amount)) % 7500 > $mSP % 7500) {
                    $this->database->addMedal($citID, 'is');
                    $this->database->sendNote($citID, '', 'You passed 7500 military skill points and you received an imperishable soldier trophy!');
                }
                break;
            case 4:
                $this->pays()->extendAcc($citID, 'pro', $amount / 30);
                break;
        }
        $this->database->exec('UPDATE chanceboxes SET result = ?, prize = ? WHERE cbID = ?', [$i + 1, $amount, $cbID]);
        $this->database->exec('UPDATE citizens SET LastCBsOpened = LastCBsOpened + 1 WHERE CitizenID = ?', [$citID]);
        $this->database->sendNote($citID, 'cb_won', $prizes[$i].'|'.$amount);
        $prize = $i ? sprintf($this->lang->getstr('cb_prize_'.$prizes[$i], 'chancebox'), $amount) : $amount.' <img src="/images/tala.gif" align="absmiddle">';

        return response($prize);
    }

    /** getchat-{id}[-{pr}].html */
    public function getChat(int $id = 0, int $pr = 0)
    {
        $rows = $pr ? array_filter([$this->chat()->getAnnounce()]) : $this->chat()->getChat($id);

        return $this->json(['chat' => array_values($rows)]);
    }

    /** addchat.html (POST message) or addchat-{message}.html */
    public function addChat(Request $request, ?string $message = null)
    {
        $message = strip_tags((string) ($message ?? $request->input('message', '')));
        if ($message === '') {
            return response('');
        }
        if (! $this->loggedIn()) {
            return response('<font color="red">Log into game first...</font>');
        }
        $citID = $this->citID();
        if ($this->database->usernameBanned($citID)) {
            return response('<font color="red">You are banned...</font>');
        }
        $message = $this->session->addSmileys($message, 16);
        $message = str_ireplace(['fuck', 'gay', 'dick', 'fag', 'whore', 'asshole'], '****', $message);
        if (stripos($message, 'e-sim') !== false) {
            return response('');
        }
        $cit = $this->database->getUserInfoFromID($citID, 0);
        $header = substr($message, 1, 4);
        $body = substr($message, 6);
        if ($header === 'ance') {
            if ($this->session->isAdmin()) {
                $this->chat()->addMessage($cit, $this->vars->convert_urls($body), 9);
            }

            return response('<font color="lime">Your message has been successfully sent!</font>');
        }
        if ($header === 'join') {
            return response('');
        }
        $num = $this->database->count('SELECT chatID FROM chat_messages WHERE timestamp >= ? AND citID = ?', [time() - 86400, $citID]) + 1;
        $max = $cit['active'] ? 50 : 5;
        if ($num > $max) {
            return response('<font color="red">Ooops! You have reached your limit for sending today...</font>');
        }
        $last2 = $this->database->rows('SELECT citID FROM chat_messages ORDER BY timestamp DESC LIMIT 2');
        if (count($last2) === 2 && (int) $last2[0]['citID'] === $citID && (int) $last2[1]['citID'] === $citID) {
            return response('<font color="red">You cannot send 3 messages in a row...</font>');
        }
        $recent = $this->database->row('SELECT timestamp FROM chat_messages WHERE timestamp >= ? AND citID = ? ORDER BY timestamp DESC', [time() - 10, $citID]);
        if ($recent) {
            return response('<font color="red">You must wait '.(10 - (time() - $recent['timestamp'])).' secs...</font>');
        }
        $this->chat()->addMessage($cit, $this->vars->convert_urls($message));

        return response('<font color="lime">Your message has been successfully sent ('.($max - $num).' left)!</font>');
    }

    /** heroes-{battleID}.html */
    public function heroes(int $battleID)
    {
        $att = array_values(array_filter($this->database->rows('SELECT citizens.CitizenID, citizens.Avatar, citizens.name, SUM(fights.damage) AS advance FROM fights
            JOIN citizens ON fights.fighterID = citizens.CitizenID WHERE fights.battleID = ? GROUP BY fighterID ORDER BY advance LIMIT 3', [$battleID]), fn ($o) => $o['advance'] < 0));
        $def = array_values(array_filter($this->database->rows('SELECT citizens.CitizenID, citizens.Avatar, citizens.name, SUM(fights.damage) AS advance FROM fights
            JOIN citizens ON fights.fighterID = citizens.CitizenID WHERE fights.battleID = ? GROUP BY fighterID ORDER BY advance DESC LIMIT 3', [$battleID]), fn ($o) => $o['advance'] > 0));

        return $this->json(['attacker' => $att, 'defender' => $def]);
    }

    /** ajaxstats-{batID}.html */
    public function battleStats(int $battleID)
    {
        $q = fn ($cmp, $order) => "SELECT fighterID id, name, COUNT(damage) AS fights, SUM(damage) As totadv, ROUND(SUM(damage)/COUNT(damage), 2) AS avgadv
            FROM fights JOIN citizens ON citizens.CitizenID = fights.fighterID WHERE damage {$cmp} 0 AND battleID = ? GROUP BY fighterID ORDER BY totadv {$order} LIMIT 20";
        $qn = fn ($cmp, $order) => "SELECT fighterID id, cName name, COUNT(damage) AS fights, SUM(damage) As totadv, ROUND(SUM(damage)/COUNT(damage), 2) AS avgadv
            FROM fights JOIN citizens ON citizens.CitizenID = fights.fighterID JOIN country ON country.CountryID = citizens.nationality
            WHERE damage {$cmp} 0 AND battleID = ? GROUP BY cName ORDER BY totadv {$order} LIMIT 20";

        return $this->json([
            'att20' => $this->database->rows($q('<', ''), [$battleID]),
            'def20' => $this->database->rows($q('>', 'DESC'), [$battleID]),
            'attN' => $this->database->rows($qn('<', ''), [$battleID]),
            'defN' => $this->database->rows($qn('>', 'DESC'), [$battleID]),
        ]);
    }

    /** battle-{id}-log[-{num}].html */
    public function battleLog(int $battleID, int $num = 0)
    {
        $wname = ['bare hands', 'handgun', 'grenade', 'RPG'];
        $limit = $num ? ' LIMIT '.(int) $num : '';
        $q = fn ($cmp) => "SELECT citizens.CitizenID, citizens.Avatar, citizens.name, fights.damage AS advance, fights.fightID, fights.weapon FROM fights
            JOIN citizens ON fights.fighterID = citizens.CitizenID WHERE fights.battleID = ? AND damage {$cmp} 0 ORDER BY fights.timestamp DESC{$limit}";
        $map = fn ($rows) => array_map(function ($o) use ($wname) {
            $o['weapon'] = $wname[(int) $o['weapon']] ?? $o['weapon'];

            return $o;
        }, $rows);
        $battle = $this->war()->getBattleInfo($battleID);
        if (! $battle) {
            abort(404);
        }
        if ($battle['End_P1'] <= time()) {
            $phase = ($battle['wall'] > $battle['extra'] && $battle['wall'] <= $battle['sPoint']) ? 2 : 3;
        } else {
            $phase = 1;
        }
        $status = in_array($battle['Result'], ['conq', 'defreat'], true) ? '1' : (in_array($battle['Result'], ['secu', 'attreat'], true) ? '2' : '0');

        return $this->json([
            'attList' => $map($this->database->rows($q('<'), [$battleID])),
            'defList' => $map($this->database->rows($q('>'), [$battleID])),
            'dForce' => $battle['wall'], 'End' => $battle['End'], 'Phase' => $phase, 'Status' => $status,
        ]);
    }

    /** ajaxfight-{batID}-{weap}-{for}-{token}.html */
    public function fight(int $battleID, int $weap, string $for, string $token)
    {
        if (! $this->loggedIn()) {
            return response('');
        }
        $cit = $this->database->getUserInfoFromID($this->citID());
        $bat = $this->war()->getBattleInfo($battleID);
        if (! $bat || ! hash_equals(md5($cit['CitizenID'].$for.$bat['battleID'].'k3yy4 f1+lng'), $token)) {
            return response('');
        }
        $isCA = $cit['accType'] !== 'citizen';
        $allyDef = $this->session->getStr2Array($bat['ally_def']);
        $allyAtt = $this->session->getStr2Array($bat['ally_att']);
        $inDef = in_array($cit['CountryID'], $allyDef) || (int) $cit['CountryID'] === (int) $bat['Defender'];
        $inAtt = in_array($cit['CountryID'], $allyAtt) || (int) $cit['CountryID'] === (int) $bat['Attacker'];
        $fail = null;
        if ($isCA) {
            $fail = 'ca';
        } elseif ($bat['End'] <= time()) {
            $fail = 'end';
        } elseif ($cit['puberty'] < 0) {
            $fail = 'pub';
        } elseif ($cit['occDue'] >= time()) {
            $fail = 'occ';
        } elseif (! $inDef && ! $inAtt) {
            $fail = 'inv';
        } elseif ($cit['wellness'] < 20) {
            $fail = 'well';
        } elseif ($bat['Result']) {
            $fail = 'res';
        }
        if ($fail) {
            return $this->json(['result' => $fail, 'fight' => 0]);
        }
        $msg = $this->war()->fight($cit, $bat, $weap, $for);
        $d = $msg['Damage'];
        $rTit = $d < 50 ? 'Good' : ($d < 100 ? 'Charming' : ($d < 200 ? 'Elegant' : ($d < 350 ? 'Terrific' : ($d < 500 ? 'Excellent' : 'Epic'))));
        $maxQ = ($bat['Clinic'] && $inDef) ? 10 : 5;
        $my = (float) $this->database->value('SELECT SUM(ABS(damage)) dmg FROM fights WHERE battleID = ? AND fighterID = ?', [$bat['battleID'], $cit['CitizenID']], 0);

        return $this->json([
            'result' => 'done', 'force' => $d, 'pref' => $rTit, 'wellness' => $msg['Well'], 'wellinf' => $msg['Well-inf'],
            'skill' => $msg['Skill'], 'mrank' => $msg['mRank'], 'rank' => $msg['mRankNum'], 'ep' => $msg['EP'], 'weapon' => $msg['Weapon'],
            'maxq' => $msg['MaxQ'], 'totforce' => $msg['totForce'], 'fight' => $msg['Well'] >= 20,
            'clinic' => ($cit['LastDayClinic'] < $maxQ) ? 1 : 0, 'myforce' => $my,
        ]);
    }

    /** include/ajFunc.php?q=... — small text helpers used by the old ajax.js. */
    public function ajFunc(Request $request)
    {
        $out = '';
        switch ($request->input('q')) {
            case 'getR':
                $r = $this->database->getRegion((int) $request->input('p'));
                $out = $r ? e($request->input('suf', '')).' <font color=green>'.e($r).'</font>' : '<font color=red>Invalid region!</font>';
                break;
            case 'getWDPrice':
                $out = (string) $this->war()->getWDPrice((int) $request->input('p'), (int) $request->input('q2'));
                break;
            case 'getRegions2':
                $rows = $this->database->rows('SELECT region.*, Owner.cName FROM region LEFT JOIN country AS Owner ON (region.CountryID = Owner.CountryID AND region.CountryID != region.oCountryID)
                    WHERE oCountryID = ? ORDER BY rName', [(int) $request->input('p')]);
                $out = '<select size="1" class="style" name="RegionID"><option value=\'0\'>   --- SELECT ---   </option>';
                foreach ($rows as $row) {
                    $out .= "<option value='{$row['RegionID']}'>".e($row['rName']).($row['cName'] ? ' <font color="red"> (conquered by '.e($row['cName']).')</font>' : '').'</option>';
                }
                break;
            case 'getPrice':
                $tax = $this->database->getIndustryTax((int) $request->input('coun'), (int) $request->input('ind'), 1) ?? ['VAT' => 0, 'Import' => 0];
                $p = (float) $request->input('p');
                $out = (string) round($p + (($p * $tax['VAT']) + ($p * $tax['Import'] * (float) $request->input('type'))) / 100, 2);
                break;
            case 'isNeibor':
                $out = $this->database->isNeighbor((int) $request->input('p'), (int) $request->input('p2')) ? '<font color=red>Not Available!</font>' : '<font color=green>Free!</font>';
                break;
            case 'vote':
                $p = (int) $request->input('p');
                $u = (int) $request->input('u');
                $tt = md5($p.$u.config('ejahan.salts.vote'));
                $out = (hash_equals($tt, (string) $request->input('token')) && $this->loggedIn() && $u === $this->citID())
                    ? (string) $this->database->setVote($p, $u, $request->input('v'), (string) $request->input('r', ''))
                    : '<font color="red">E</font>';
                break;
            case 'cmvote':
                $p = (int) $request->input('p');
                $u = (int) $request->input('u');
                $v = (string) $request->input('v');
                $tt = md5($p.$u.$v.config('ejahan.salts.cmvote'));
                $out = (hash_equals($tt, (string) $request->input('token')) && $this->loggedIn() && $u === $this->citID() && in_array($v, ['1', '-1'], true))
                    ? (string) $this->database->setCMVote($p, $u, $v)
                    : '<font color="red">E</font>';
                break;
        }

        return response($out);
    }
}
