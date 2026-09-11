<?php

namespace App\Game\Cron;

use App\Game\Services\GameDatabase;
use App\Game\Services\Military;
use App\Game\Services\Rankings;
use App\Game\Support\Url;

/**
 * Port of include/cron/{init,minly,hourly,daily,battles,lottery,stat,invites}.php.
 * Each method is idempotent per period through the legacy `cronlog` table where the original was.
 */
class GameCron
{
    public function __construct(protected GameDatabase $database, protected Military $war, protected Rankings $ranks, protected Url $url,
        protected LawProcessor $laws, protected ElectionProcessor $elections)
    {
    }

    /** minly.php: laws, battles, company sales, inactive cleanup. */
    public function minutely(): array
    {
        $db = $this->database;
        $log = ['laws' => $this->laws->run(), 'battles' => $this->battles()];
        $sold = 0;
        foreach ($db->rows("SELECT * FROM company WHERE sale_due < ? AND sale_due > '0'", [time()]) as $comp) {
            $db->transferCompany($comp);
            $sold++;
        }
        $log['companies_sold'] = $sold;
        $db->removeInactiveUsers();
        $db->removeInactiveGuests();

        return $log;
    }

    /** battles.php: close battles whose time is over. */
    public function battles(): int
    {
        $n = 0;
        foreach ($this->database->rows("SELECT * FROM battles WHERE Result = '' AND End < ?", [time()]) as $bat) {
            if ($bat['wall'] < $bat['extra']) {
                $this->war->battleConquer($bat['battleID']);
            } else {
                $this->war->battleSecure($bat['battleID']);
            }
            $n++;
        }

        return $n;
    }

    /** init.php hourly part (hourly.php is empty in legacy; only the cronlog record is kept). */
    public function hourly(): bool
    {
        $db = $this->database;
        $today = $db->today;
        $hour = date('H');
        if ($db->count("SELECT * FROM cronlog WHERE Type = 'hourly' AND Day = ? AND Hour = ?", [$today, $hour])) {
            return false;
        }
        $db->exec("INSERT INTO cronlog (Type, Day, Hour, timestamp, Report) VALUES ('hourly', ?, ?, ?, 'Operation completed successfully!')", [$today, $hour, time()]);

        return true;
    }

    /** daily.php (+ cronelec.php + lottery). Returns false when already run today. */
    public function daily(bool $force = false): array|false
    {
        $db = $this->database;
        $today = $db->today;
        if (!$force && $db->count("SELECT * FROM cronlog WHERE Type = 'daily' AND Day = ?", [$today])) {
            return false;
        }
        $log = [];
        $db->exec('UPDATE citizens SET LastDayWP = 0, LastDayClinic = 0, LastCBsOpened = 0');
        $log[] = 'Body shape decayed for '.$db->decayBodyShape($today).' citizens who skipped training';
        foreach ($db->rows('SELECT CitizenID FROM citizens WHERE regionID = 342') as $row) {
            $db->createProduct(1, 5, $row['CitizenID']);
        }
        $db->exec('UPDATE citizens SET wellness = IF(wellness > 2, wellness - 2, 0.01), poisoning = poisoning - 1 WHERE poisoning > 0');
        $db->exec('DELETE FROM notes WHERE timestamp < ?', [time() - 432000]);
        if ((int) ($db->setting['count_hw'] ?? 0)) {
            $db->exec('UPDATE citizens SET rowWorked = 0 WHERE LastWorked < ?', [$today - 1]);
        }
        $db->exec('UPDATE citizens SET LastJuiceAmount = 0, LastJuiceWellness = 0');
        $db->exec('DELETE FROM embargo WHERE Expire <= ?', [$today]);
        $db->exec('DELETE FROM alliances WHERE Expire <= ?', [$today]);
        $log['elections'] = $this->elections->run();
        $log['lottery'] = $this->lottery();
        $db->exec("INSERT INTO cronlog (Type, Day, timestamp, Report) VALUES ('daily', ?, ?, 'Operation completed successfully!')", [$today, time()]);
        $time = time();
        $db->exec('UPDATE inventory SET Expires = ? WHERE Type = 8 AND Usable = 1 AND Expires = 0', [$time + 2592000]);
        $db->exec('UPDATE inventory SET Expires = Expires + 86400 WHERE Type = 8 AND Usable = 2 AND Expires != 0');
        $db->exec('UPDATE inventory SET Usable = 0 WHERE Type = 8 AND Usable = 1 AND Expires != 0 AND Expires <= ?', [$time]);
        $db->exec('DELETE FROM inventory WHERE Usable = 0');

        return $log;
    }

    /** lottery1346795ejahan.php: draw 5 winners and hand out chance boxes. */
    public function lottery(): array
    {
        $db = $this->database;
        $day = $db->today;
        $tickets = $db->rows('SELECT * FROM lottery_tickets');
        if (!$tickets) {
            return [];
        }
        $persons = count(array_unique(array_column($tickets, 'buyerID')));
        $prize = [0, 100, 50, 20, 10, 5];
        $db->exec('INSERT INTO lottery_stats (lottery_day, lottery_tickets, lottery_citizens, lottery_winners) VALUES (?, ?, ?, 5)', [$day, count($tickets), $persons]);
        $note = "Congratulations! You're one of our winners in the lottery of today! Check <a href=\"".$this->url->getURL('lottery', 'results').'">here</a>.';
        $winners = [];
        $pool = $tickets;
        for ($i = 1; $i <= 5 && $pool; $i++) {
            $winner = $pool[array_rand($pool)];
            $tala = $prize[$i];
            $db->exec('INSERT INTO lottery_winners (ticket, winnerID, Day, Prize) VALUES (?, ?, ?, ?)', [$winner['ticketID'], $winner['buyerID'], $day, "Chancebox with $tala points"]);
            $db->addCB($winner['buyerID'], 'lottery', $tala);
            $db->sendNote($winner['buyerID'], '', $note);
            $winners[] = $winner['buyerID'];
            $pool = array_values(array_filter($pool, fn ($t) => $t['buyerID'] != $winner['buyerID']));
        }
        $db->exec('TRUNCATE lottery_tickets');

        return $winners;
    }

    /** invites.php: recycle unused invites older than two weeks. */
    public function recycleInvites(): int
    {
        $db = $this->database;
        $n = 0;
        foreach ($db->rows("SELECT * FROM invites WHERE sended > '0' AND sended < ? AND toID = '0' AND byID != '1'", [time() - 14 * 86400]) as $row) {
            $db->exec("UPDATE invites SET emailID = '', sended = '0', inviteID = ? WHERE inviteID = ?", [md5(random_bytes(16)), $row['inviteID']]);
            $n++;
        }

        return $n;
    }

    /** stat.php: region values, citizen ranks and country stats. */
    public function stats(): array
    {
        $db = $this->database;
        $today = $db->today;
        $regions = 0;
        $gdsMul = [1, 1.1, 1.14, 1.17, 1.19, 1.2, 1.21, 1.22, 1.23, 1.24, 1.25];
        foreach ($db->rows('SELECT RegionID, Clinic, Munic, goddessType, CountryID, oCountryID, COUNT(neighbors.Region2) AS Neighbors FROM region JOIN neighbors ON neighbors.Region1 = region.RegionID GROUP BY RegionID') as $reg) {
            $pop = $db->count("SELECT regionID FROM citizens WHERE regionID = ? AND wellness > '0' AND ban_due != 'PERMANENTLY' AND accType = 'citizen'", [$reg['RegionID']]);
            $gd = min(10, $db->count('SELECT RegionID FROM region WHERE CountryID = ? AND goddessType = 6 AND goddessDeath >= ?', [$reg['CountryID'], $today]));
            $value = round((10 + $reg['Clinic'] / 12500 + $reg['Munic'] * 10 + $pop * 0.02 + ($reg['goddessType'] ? 80 : 0))
                * (($reg['CountryID'] == $reg['oCountryID']) ? 1.5 : 1) * ($reg['Neighbors'] * 0.05 + 1) * $gdsMul[$gd], 2);
            $comps = $db->count('SELECT CompanyID FROM company WHERE RegionID = ? AND ManagerID > 0', [$reg['RegionID']]);
            $C = $comps <= 75 ? 0 : $comps - 75;
            $P = $pop <= 250 ? 0 : $pop - 250;
            $cf = max(0.01, round(1 - (($C + $P * 0.02) / 100) + ($reg['Munic'] / 5), 2));
            $db->exec('UPDATE region SET stat_pop = ?, stat_comps = ?, stat_value = ?, stat_cfactor = ? WHERE RegionID = ?', [$pop, $comps, $value, $cf, $reg['RegionID']]);
            $regions++;
        }
        foreach ($this->ranks->rankCitizens() as $i => $cit) {
            $db->exec('UPDATE citizens SET stat_rank_int = ? WHERE CitizenID = ?', [$i + 1, $cit['CitizenID']]);
        }
        $counIDs = array_column($db->rows('SELECT CountryID FROM country ORDER BY CountryID'), 'CountryID');
        foreach ($counIDs as $cid) {
            foreach ($this->ranks->rankCitizens($cid) as $i => $cit) {
                $db->exec('UPDATE citizens SET stat_rank_coun = ?, stat_rank_coun_id = ? WHERE CitizenID = ?', [$i + 1, $cid, $cit['CitizenID']]);
            }
        }
        $db->exec('TRUNCATE TABLE stat_country');
        $base = "FROM country LEFT JOIN citizens ON citizens.nationality = country.CountryID WHERE citizens.ban_due != 'PERMANENTLY' AND citizens.wellness > '0' AND country.CountryID = ? AND citizens.accType = 'citizen'";
        foreach ($counIDs as $i) {
            if ($i == 1) {
                continue;
            }
            $ep = $db->row("SELECT IFNULL(SUM(citizens.ep), 0) AS EP, IFNULL(ROUND(AVG(citizens.ep), 2), 0) AS avgEP, COUNT(citizens.CitizenID) AS POP,
                IFNULL(ROUND(AVG(citizens.mSkill), 2), 0) AS mSkill, IFNULL(ROUND(AVG(citizens.wSkill), 2), 0) AS wSkill $base", [$i]);
            $numComps = $db->count("SELECT company.CompanyID FROM country LEFT JOIN region ON region.CountryID = country.CountryID LEFT JOIN company ON company.RegionID = region.RegionID WHERE company.ManagerID != '0' AND country.CountryID = ?", [$i]);
            $natives = $db->count("SELECT citizens.CitizenID FROM country LEFT JOIN region ON region.CountryID = country.CountryID LEFT JOIN citizens ON citizens.regionID = region.RegionID WHERE citizens.ban_due != 'PERMANENTLY' AND citizens.wellness > '0' AND country.CountryID = ? AND citizens.accType = 'citizen'", [$i]);
            $native = $natives ? round($ep['POP'] * 100 / $natives, 2) : 0;
            $apc1 = $db->getAPC($i);
            $apc2 = $db->getAPC($i, $today - 7);
            $inflation = $apc2 != 0 ? round((($apc1 / $apc2) - 1) * 100, 2) : -10000;
            $db->exec('INSERT INTO stat_country (CountryID, ep, pop, avgEP, avgMilitary, avgWorking, numComps, native, apc, inflation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$i, $ep['EP'], $ep['POP'], $ep['avgEP'], $ep['mSkill'], $ep['wSkill'], $numComps, $native, $apc1, $inflation]);
        }

        return ['regions' => $regions, 'countries' => count($counIDs) - 1];
    }
}
