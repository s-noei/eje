<?php

namespace App\Http\Controllers\Api;

use App\Game\Support\Constants;
use App\Http\Controllers\AjaxController;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    /** GET /api/v1/home — everything the dashboard needs in one call. */
    public function index()
    {
        $c = $this->cit(true);
        $citID = $this->citID();
        $today = $this->database->today;
        $now = $this->session->getTodayArray();
        $cid = (int) $c['CountryID'];
        $isCA = $c['accType'] !== 'citizen';

        $battles = $this->database->rows("SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName
            FROM battles JOIN region ON region.RegionID = battles.regionID
            LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
            JOIN country AS defender ON defender.CountryID = battles.Defender
            WHERE (Attacker = ? OR Defender = ? OR ally_att LIKE ? OR ally_def LIKE ?) AND Result = ''", [$cid, $cid, "% $cid %", "% $cid %"]);

        $unitBattle = null;
        if ((int) $c['military_unit'] && !$isCA) {
            $unit = $this->database->row('SELECT * FROM military_unit WHERE mID = ?', [$c['military_unit']]);
            if ($unit && $unit['mBattleID']) {
                $ub = $this->database->row("SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName FROM battles
                    JOIN region ON region.RegionID = battles.regionID LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
                    JOIN country AS defender ON defender.CountryID = battles.Defender WHERE battleID = ? AND Result = ''", [$unit['mBattleID']]);
                $unitBattle = $ub ? $this->battlePayload($ub) : null;
            }
        }

        $vote = null;
        $days = ['cg' => Constants::ELECTIONS_CG_DAY, 'cp' => Constants::ELECTIONS_CP_DAY, 'pp' => Constants::ELECTIONS_PP_DAY];
        if (!$isCA && in_array((int) $now['Day'], $days, true)) {
            $type = array_search((int) $now['Day'], $days, true);
            $vote = ['type' => $type, 'url' => url($this->vars->getURL('elections', $type, $cid, $type === 'cg' ? $c['regionID'] : ($type === 'pp' ? ($c['PartyID'] ?: 0) : ''), $now['Year'], $now['Month']))];
        }

        $ajax = app(AjaxController::class);
        $this->lang->addPhrases('regions');
        $this->lang->addPhrases('events');
        $events = fn ($country) => array_map(fn ($e) => ['title' => $e['title'], 'icon' => url($e['icon']), 'link' => url($e['link'])],
            array_map([$ajax, 'describeEvent'], $this->database->rows('SELECT events.*, Country1.cName AS Country1Name, Country1.shortName AS Country1SN, Country1.CountryID AS Country1ID,
                Country2.cName AS Country2Name, Country2.shortName AS Country2SN, Country2.CountryID AS Country2ID, region.rName AS RegionName, region.RegionID
                FROM events LEFT JOIN country AS Country1 ON Country1.CountryID = events.Country1 LEFT JOIN country AS Country2 ON Country2.CountryID = events.Country2
                LEFT JOIN battles ON battles.battleID = events.refID LEFT JOIN region ON battles.regionID = region.RegionID'
                .($country ? ' WHERE (Country1 = ? OR Country2 = ?)' : '').' ORDER BY timestamp DESC LIMIT 5', $country ? [$country, $country] : [])));

        return $this->ok([
            'citizen' => $this->citizenPayload($c, true),
            'day' => $today,
            'quests' => [
                'dailyReward' => $c['LastDaily'] != $today && !$isCA && $c['LastTrained'] == $today,
                'train' => $c['LastTrained'] != $today && !$isCA,
                'work' => $c['LastWorked'] < $today && !$isCA && $this->database->isWorker($citID),
                'explore' => $c['LastExplored'] != $today && !$isCA && $c['puberty'] > 0,
                'vote' => $vote,
                'unitBattle' => $unitBattle,
            ],
            'battles' => array_map([$this, 'battlePayload'], $battles),
            'events' => ['local' => $events($cid), 'international' => $events(0)],
            'news' => [
                'top' => array_map([$this, 'articlePayload'], $this->database->getTRNewsL($cid)),
                'latest' => array_map([$this, 'articlePayload'], $this->database->getLatestNews($cid)),
                'international' => array_map([$this, 'articlePayload'], $this->database->getTRNewsI($cid)),
                'subscriptions' => $isCA ? [] : array_map([$this, 'articlePayload'], $this->database->getTRNewsS($citID)),
            ],
            'around' => array_map(fn ($a) => $this->articlePayload($a) + ['isNew' => $a['timestamp'] > time() - 2 * 86400], array_slice($this->database->getNPAdminArticles(), 0, 3)),
            'newPM' => $this->database->getNewMSGs($citID),
            'newNotes' => $this->database->getNewNotes($citID),
        ]);
    }

    /** POST /api/v1/daily-reward */
    public function dailyReward()
    {
        $c = $this->cit(true);
        $citID = $this->citID();
        $today = $this->database->today;
        if (!($c['LastDaily'] != $today && $c['accType'] === 'citizen' && $c['LastTrained'] == $today)) {
            return $this->fail('Daily reward is not available.');
        }
        if ($this->database->getCitizenFreeIS($citID) < 1) {
            return $this->fail('Your inventory is full.');
        }
        $this->database->updateUserFieldID($citID, 'LastDaily', $today);
        $this->database->addEP($citID, 5, 'Receive from daily tasks');
        $this->database->createProduct(1, 5, $citID);

        return $this->ok(['citizen' => $this->citizenPayload($this->cit(true), true), 'reward' => ['ep' => 5, 'food' => ['stars' => 5, 'amount' => 1]]]);
    }
}
