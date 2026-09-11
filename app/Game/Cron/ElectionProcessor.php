<?php

namespace App\Game\Cron;

use App\Game\Services\Elections;
use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Politics;
use App\Game\Services\Rankings;
use App\Game\Support\Constants;

/** Port of include/cron/cronelec.php — closes finished elections and opens the day's new ones. */
class ElectionProcessor
{
    public function __construct(protected GameDatabase $database, protected Politics $politics, protected Elections $elections, protected Rankings $ranks, protected GameContext $session)
    {
    }

    /** @return string[] log lines */
    public function run(): array
    {
        $db = $this->database;
        $log = [];
        $today = $this->session->getTodayArray();
        $last = $this->elections->getLastElection();
        if ($last && empty($last['processed'])) {
            $lTime = $last['timestamp'] + (($last['day'] - 1) * 86400);
            $elecArr = $this->session->getTodayArray($lTime);
            switch ($last['eType']) {
                case 'CG':
                    $db->exec('TRUNCATE TABLE congressmen');
                    foreach ($db->rows('SELECT CountryID FROM country') as $country) {
                        $congs = $db->rows('SELECT elections_cg_elections.*, country.shortName, region.rName, party.pName FROM elections_cg_elections
                            JOIN country ON country.CountryID = elections_cg_elections.CountryID
                            JOIN region ON region.RegionID = elections_cg_elections.RegionID
                            JOIN party ON party.pID = elections_cg_elections.PartyID
                            WHERE ElectionID = ? AND State > 0 AND elections_cg_elections.CountryID = ?', [$last['eID'], $country['CountryID']]);
                        foreach ($congs as $cong) {
                            $this->politics->beCG($cong);
                        }
                    }
                    $db->exec('TRUNCATE TABLE elections_cg_candidates');
                    $log[] = 'CG election closed';
                    break;
                case 'PP':
                    foreach ($this->elections->getPartyList() as $party) {
                        $eRes = $this->elections->getElectionPP(1, $party['pID'], (int) $elecArr['Year'], (int) $elecArr['Month'])[0] ?? null;
                        if (!empty($eRes['CandidateID'])) {
                            $this->politics->setPP($party['pID'], $eRes['CandidateID'], 'win');
                            $db->addEP($eRes['CandidateID'], 20, 'Won PP Elections');
                        }
                    }
                    $log[] = 'PP election closed';
                    break;
                case 'CP':
                    foreach ($db->rows('SELECT * FROM country') as $coun) {
                        $eRes = $this->elections->getElectionCP($coun['CountryID'], (int) $elecArr['Year'], (int) $elecArr['Month'])[0] ?? null;
                        if (!empty($eRes['CandidateID'])) {
                            $this->politics->setCP($coun['CountryID'], $eRes['CandidateID'], 'win');
                            $db->addEP($eRes['CandidateID'], 40, 'Won CP Elections');
                            $log[] = "CP of {$coun['cName']}: {$eRes['name']}";
                        }
                    }
                    break;
            }
            $db->exec("UPDATE elections_all SET processed = '1' WHERE eID = ?", [$last['eID']]);
        }

        $cDate = mktime(0, 0, 0, (int) $today['Month'], 1, (int) $today['Year']);
        $open = function (string $type) use ($db, $cDate, $today) {
            $db->exec('INSERT INTO elections_all (eType, timestamp, day) VALUES (?, ?, ?)', [$type, $cDate, $today['Day']]);

            return (int) $db->value('SELECT eID FROM elections_all WHERE eType = ? AND timestamp = ? AND day = ?', [$type, $cDate, $today['Day']], 0);
        };

        if ((int) $today['Day'] === Constants::ELECTIONS_PP_DAY) {
            $eID = $open('PP');
            foreach ($db->rows('SELECT elections_pp_candidates.* FROM elections_pp_candidates JOIN party ON party.pID = elections_pp_candidates.PartyID ORDER BY PartyID') as $cand) {
                $db->exec('INSERT INTO elections_pp_elections (ElectionID, CandidateID, PartyID, DocURL) VALUES (?, ?, ?, ?)', [$eID, $cand['CitizenID'], $cand['PartyID'], $cand['DocURL']]);
            }
            $db->exec('TRUNCATE TABLE elections_pp_candidates');
            $log[] = "PP election #$eID opened";
        }

        if ((int) $today['Day'] === Constants::ELECTIONS_CG_DAY) {
            $eID = $open('CG');
            $countries = $db->rows('SELECT country.CountryID, country.cName FROM elections_cg_candidates JOIN region ON region.RegionID = elections_cg_candidates.RegionID
                JOIN country ON region.CountryID = country.CountryID GROUP BY country.CountryID');
            foreach ($countries as $row) {
                $qual = $this->politics->getCountryCGQualifies($row['CountryID']);
                foreach ($this->ranks->rankParties($row['CountryID'], 0, 5) as $party) {
                    $regs = $db->rows('SELECT region.* FROM elections_cg_candidates JOIN region ON region.RegionID = elections_cg_candidates.RegionID WHERE PartyID = ? AND CountryID = ? GROUP BY region.RegionID', [$party['PartyID'], $row['CountryID']]);
                    foreach ($regs as $reg) {
                        $cgCount = $reg['stat_pop'] > 100 ? $qual['xP'] : ($reg['stat_pop'] > 10 ? $qual['xN'] : 2);
                        $cands = $db->rows('SELECT * FROM elections_cg_candidates WHERE PartyID = ? AND RegionID = ? ORDER BY `Order` LIMIT '.(int) $cgCount, [$party['PartyID'], $reg['RegionID']]);
                        foreach ($cands as $cand) {
                            $db->exec('INSERT INTO elections_cg_elections (ElectionID, CandidateID, PartyID, CountryID, RegionID, DocURL) VALUES (?, ?, ?, ?, ?, ?)',
                                [$eID, $cand['CitizenID'], $party['PartyID'], $row['CountryID'], $reg['RegionID'], $cand['DocURL']]);
                        }
                    }
                }
            }
            $log[] = "CG election #$eID opened";
        }

        if ((int) $today['Day'] === Constants::ELECTIONS_CP_DAY) {
            $eID = $open('CP');
            foreach ($db->rows('SELECT * FROM country') as $row) {
                $cands = $db->rows('SELECT citizens.*, party.*, COUNT(party_members.CitizenID) AS POP FROM party JOIN citizens ON party.cpProposed = citizens.CitizenID
                    JOIN party_members ON party.pID = party_members.PartyID WHERE party.CountryID = ? GROUP BY party_members.PartyID ORDER BY POP DESC LIMIT 0, 5', [$row['CountryID']]);
                foreach ($cands as $cand) {
                    $db->exec('INSERT INTO elections_cp_elections (ElectionID, CandidateID, PartyID, CountryID) VALUES (?, ?, ?, ?)', [$eID, $cand['CitizenID'], $cand['pID'], $cand['CountryID']]);
                }
            }
            $db->exec("UPDATE party SET cpProposed = ''");
            $log[] = "CP election #$eID opened";
        }

        return $log;
    }
}
