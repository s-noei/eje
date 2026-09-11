<?php

namespace App\Http\Controllers;

use App\Game\Support\Constants;
use Illuminate\Http\Request;

/**
 * Port of main.php (logged in home) and include/main-out.php (guest home).
 */
class HomeController extends GameController
{
    public function index(Request $request)
    {
        $layout = ['title' => $this->lang->getstr('title_home', 'title') ?: 'eJahan', 'coltype' => 3, 'actiontype' => 'home', 'ambient' => 'default'];
        if (! $this->loggedIn()) {
            $this->lang->addPhrases('main_out');

            return $this->page('pages.home-out', [
                'news' => $this->database->rows("SELECT * FROM articles WHERE npID = '1' AND Deleted != '1' ORDER BY timestamp DESC LIMIT 3"),
                'topEP' => $this->ranks()->rankCountries(1, 0, 8),
                'topPop' => $this->ranks()->rankCountries(2, 0, 8),
            ], $layout + ['ambient' => 'main-out', 'coltype' => 1]);
        }

        $this->lang->addPhrases('home');
        $this->lang->addPhrases('tasks');
        $cit = $this->citInfo;
        $citID = $this->citID();
        $today = $this->database->getToday();
        $layout['bar_title'] = $this->lang->getstr('home_title', 'home');

        // Daily reward
        $canDaily = $cit['LastDaily'] != $today && $cit['accType'] === 'citizen' && $cit['LastTrained'] == $today;
        $dailyError = null;
        if ($request->input('DailyReward') && $canDaily) {
            if ($this->database->getCitizenFreeIS($citID) < 1) {
                $dailyError = '<h3 class=errHandle>Your inventory is full.</h3>';
            } else {
                $this->database->updateUserFieldID($citID, 'LastDaily', $today);
                $this->database->addEP($citID, 5, 'Receive from daily tasks');
                $this->database->createProduct(1, 5, $citID);

                return redirect('/index.html');
            }
        }

        // Military unit battle
        $unitBattle = null;
        if ((int) $cit['military_unit'] !== 0 && $cit['accType'] === 'citizen') {
            $unit = $this->database->row('SELECT * FROM military_unit WHERE mID = ?', [$cit['military_unit']]);
            if ($unit) {
                $unitBattle = $this->database->row("SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName
                    FROM battles JOIN region ON region.RegionID = battles.regionID
                    LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
                    JOIN country AS defender ON defender.CountryID = battles.Defender
                    WHERE battleID = ? AND Result = ''", [$unit['mBattleID']]);
            }
        }

        $battles = $this->database->rows("SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName
            FROM battles JOIN region ON region.RegionID = battles.regionID
            LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
            JOIN country AS defender ON defender.CountryID = battles.Defender
            WHERE (Attacker = ? OR Defender = ? OR ally_att LIKE ? OR ally_def LIKE ?) AND Result = ''",
            [$cit['CountryID'], $cit['CountryID'], "% {$cit['CountryID']} %", "% {$cit['CountryID']} %"]);

        return $this->page('pages.home', [
            'canDaily' => $canDaily,
            'dailyError' => $dailyError,
            'unitBattle' => $unitBattle,
            'battles' => $battles,
            'latest' => $this->database->getLatestNews($cit['CountryID']),
            'topL' => $this->database->getTRNewsL($cit['CountryID']),
            'topI' => $this->database->getTRNewsI($cit['CountryID']),
            'subs' => $this->isCA() ? [] : $this->database->getTRNewsS($citID),
            'adminNews' => array_slice($this->database->getNPAdminArticles(), 0, 3),
            'electionDays' => ['cg' => Constants::ELECTIONS_CG_DAY, 'cp' => Constants::ELECTIONS_CP_DAY, 'pp' => Constants::ELECTIONS_PP_DAY],
        ], $layout);
    }
}
