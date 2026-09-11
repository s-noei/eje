<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of military-unit.php / military-unitinfo.php.
 */
class MilitaryUnitController extends GameController
{
    private const BATTLE_SQL = 'SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName
        FROM battles
        JOIN region ON region.RegionID = battles.regionID
        LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
        JOIN country AS defender ON defender.CountryID = battles.Defender';

    /** military-unit.php */
    public function index()
    {
        if (!$this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        if ($this->citInfo['military_unit'] > 0) {
            return redirect($this->vars->getURL('military-unit', $this->citInfo['military_unit']));
        }

        $units = $this->database->rows('SELECT * FROM military_unit WHERE mCountryID = ? ORDER BY mMembers DESC', [$this->citInfo['nationality']]);

        return $this->page('pages.military.unit', compact('units'), [
            'title' => $this->lang->getstr('title_military_unit', 'title'),
            'bar_title' => 'Military unit',
            'actiontype' => 'military-unit',
        ]);
    }

    /** military-unitinfo.php */
    public function show(Request $request, int $mID)
    {
        if (!$this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $unit = $this->database->row('SELECT military_unit.*, country.CountryID, country.cName, country.Flag FROM military_unit, country WHERE mID = ? AND military_unit.mCountryID = country.CountryID', [$mID]);
        if (!$unit) {
            return redirect('/index.html');
        }

        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'];
        $errors = [];
        $isCC = $me == $unit['mCommander'] || $me == $unit['mCaptain'];
        $back = fn () => redirect($request->getRequestUri());

        if ($request->isMethod('post')) {
            if ($request->has('Join')) {
                if ($citInfo['nationality'] != $unit['mCountryID']) {
                    $errors[] = 'Sorry, but you are not a national member of this country.';
                } elseif ($citInfo['military_unit'] != 0) {
                    $errors[] = "As it seems, you're already a military unit member.";
                } else {
                    $this->database->updateUserFieldID($me, 'military_unit', $mID);
                    $this->database->updateMilitaryUnitField($mID, 'mMembers', $unit['mMembers'] + 1);
                    $this->session->fillInfo(null, true);

                    return $back();
                }
            }

            if ($request->has('Resign')) {
                if ($citInfo['military_unit'] != $mID) {
                    $errors[] = 'Something is wrong.';
                } elseif ($me == $unit['mCommander']) {
                    $errors[] = 'Commander can not leave the military unit.';
                } else {
                    $this->database->updateUserFieldID($me, 'military_unit', 0);
                    $this->database->updateMilitaryUnitField($mID, 'mMembers', $unit['mMembers'] - 1);
                    if ($me == $unit['mCaptain']) {
                        $this->database->updateMilitaryUnitField($mID, 'mCaptain', 0);
                    }
                    $this->session->fillInfo(null, true);

                    return $back();
                }
            }

            if ($request->filled('Edit')) {
                $name = strip_tags((string) $request->input('mName'));
                $text = strip_tags((string) $request->input('mText'));
                if (strlen($name) < 3 || strlen($name) > 30 || !preg_match('#^[a-zA-Z0-9\s]+$#', $name)) {
                    $errors[] = 'A military unit name is incorrect.';
                } elseif (strlen($text) > 200) {
                    $errors[] = 'Text is incorrect.';
                } elseif ($me != $unit['mCommander']) {
                    $errors[] = 'Something is wrong.';
                } else {
                    $this->database->updateMilitaryUnitField($mID, 'mName', $name);
                    $this->database->updateMilitaryUnitField($mID, 'mText', $text);
                    $logo = $request->file('mLogo');
                    if ($logo && $logo->isValid() && in_array($logo->getMimeType(), ['image/jpeg', 'image/pjpeg']) && $logo->getSize() < 51200) {
                        $fName = md5($mID.'MiLiTaRyUnIt').'.jpg';
                        $logo->move(public_path('uploads/avatars/military-unit'), $fName);
                        $this->database->updateMilitaryUnitField($mID, 'mLogo', $fName);
                    }

                    return $back();
                }
            }

            if ($request->has('ResignCC')) {
                $memberID = (int) $request->input('MemberID');
                if ($citInfo['military_unit'] != $mID || !$isCC) {
                    $errors[] = 'Something is wrong.';
                } elseif ($memberID == $unit['mCommander']) {
                    $errors[] = 'Commander can not leave the military unit.';
                } else {
                    $citMU = $this->database->getUserInfoFromID($memberID);
                    if (!$citMU || $citMU['military_unit'] != $mID) {
                        $errors[] = 'Something is wrong.';
                    } else {
                        $this->database->updateUserFieldID($memberID, 'military_unit', 0);
                        $this->database->updateMilitaryUnitField($mID, 'mMembers', $unit['mMembers'] - 1);
                        if ($memberID == $unit['mCaptain']) {
                            $this->database->updateMilitaryUnitField($mID, 'mCaptain', 0);
                        }

                        return $back();
                    }
                }
            }

            if ($request->has('CaptainCC')) {
                $memberID = (int) $request->input('MemberID');
                if ($citInfo['military_unit'] != $mID || $me != $unit['mCommander']) {
                    $errors[] = 'Something is wrong.';
                } else {
                    $citMU = $this->database->getUserInfoFromID($memberID);
                    if (!$citMU || $citMU['military_unit'] != $mID) {
                        $errors[] = 'Something is wrong.';
                    } elseif ((int) $citMU['mRank'] < \App\Game\Support\Constants::RANK_MIN_CAPTAIN) {
                        $errors[] = 'A captain needs the military rank '.\App\Game\Support\Constants::MILI_RANKS[\App\Game\Support\Constants::RANK_MIN_CAPTAIN].' or higher.';
                    } else {
                        $this->database->updateMilitaryUnitField($mID, 'mCaptain', $memberID);

                        return $back();
                    }
                }
            }

            if ($request->has('addBattle')) {
                $battleID = (int) $request->input('battleID');
                if ($citInfo['military_unit'] != $mID || !$isCC) {
                    $errors[] = 'Something is wrong.';
                } elseif ($this->database->row(self::BATTLE_SQL.' WHERE battleID = ?', [$battleID])) {
                    $this->database->updateMilitaryUnitField($mID, 'mBattleID', $battleID);
                    $this->database->updateMilitaryUnitField($mID, 'timestamp', time());

                    return $back();
                }
            }
        }

        $commander = $this->database->getUserInfoFromID($unit['mCommander']) ?: [];
        $captain = $unit['mCaptain'] ? ($this->database->getUserInfoFromID($unit['mCaptain']) ?: null) : null;
        $order = $this->database->row(self::BATTLE_SQL.' WHERE battleID = ?', [$unit['mBattleID']]);
        $activeBattles = $isCC ? $this->database->rows(self::BATTLE_SQL." WHERE Result = ''") : [];
        $stats = $this->database->rows('SELECT citizens.CitizenID, citizens.name, citizens.military_unit, citizens.Avatar, citizens.ep, citizens.puberty, citizens.mSP, citizens.mSkill, SUM(fights.damage) AS UNITDMG, COUNT(fights.damage) AS UNITFIGHT
            FROM citizens LEFT JOIN fights ON citizens.CitizenID = fights.fighterID
            WHERE citizens.military_unit = ? AND fights.battleID = ? GROUP BY fights.fighterID ORDER BY UNITDMG DESC', [$mID, $unit['mBattleID']]);
        $members = $this->database->rows('SELECT * FROM citizens WHERE military_unit = ? ORDER BY ep DESC', [$mID]);

        return $this->page('pages.military.unitinfo', compact('unit', 'errors', 'commander', 'captain', 'order', 'activeBattles', 'stats', 'members', 'isCC'), [
            'title' => $this->lang->getstr('title_military_unit_info', 'title'),
            'bar_title' => 'Military unit',
            'actiontype' => 'military-unitinfo',
        ]);
    }
}
