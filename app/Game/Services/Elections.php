<?php

namespace App\Game\Services;

use App\Game\Support\Constants;

/**
 * Port of include/elections.php ($elections).
 */
class Elections
{
    public int $ppDay = Constants::ELECTIONS_PP_DAY;

    public int $cgDay = Constants::ELECTIONS_CG_DAY;

    public int $cpDay = Constants::ELECTIONS_CP_DAY;

    public function __construct(protected GameDatabase $database, protected Politics $politics)
    {
    }

    public function addElection(string $type, int $time): void
    {
        $today = app(GameContext::class)->getTodayArray($time);
        $ts = mktime(0, 0, 0, $today['Month'], 1, $today['Year']);
        $this->database->exec('INSERT INTO elections_all (eType, timestamp, day) VALUES (?, ?, ?)', [$type, $ts, $today['Day']]);
    }

    public function getElectionList(string $what = ''): array
    {
        if ($what && $what !== 'select') {
            return $this->database->rows('SELECT * FROM elections_all WHERE eType = ?', [$what]);
        }

        return $this->database->rows('SELECT * FROM elections_all GROUP BY timestamp');
    }

    public function getElectionCG(int|string $country, int|string $region, int $year, int $month, $shuffle = '', $who = 0): array
    {
        $time = mktime(0, 0, 0, $month, 1, $year);
        $b = [$time, $country];
        $regionSql = '';
        if ($region) {
            $regionSql = ' AND elections_cg_elections.RegionID = ?';
            $b[] = $region;
        }
        if ($shuffle) {
            return $this->database->rows("SELECT elections_cg_elections.*, party.pName, party.pLogo, region.stat_pop AS regPop,
                    citizens.name, citizens.Avatar, COUNT(elections_cg_votes.CandidateID) AS TotalVotes
                FROM elections_all JOIN elections_cg_elections ON elections_all.eID = elections_cg_elections.ElectionID
                LEFT JOIN citizens ON elections_cg_elections.CandidateID = citizens.CitizenID
                LEFT JOIN region ON region.RegionID = elections_cg_elections.RegionID
                LEFT JOIN party ON party.pID = elections_cg_elections.PartyID
                LEFT JOIN elections_cg_votes ON (elections_cg_elections.CandidateID = elections_cg_votes.CandidateID AND elections_cg_votes.ElectionID = elections_all.eID)
                WHERE elections_all.timestamp = ? AND elections_all.eType = 'CG' AND elections_cg_elections.CountryID = ?{$regionSql}
                GROUP BY CandidateID ORDER BY pName, RAND()", $b);
        }
        $stateSql = ($who !== 'qual') ? 'AND elections_cg_elections.State = '.(int) $who : 'AND elections_cg_elections.State > 0 AND elections_cg_elections.State < 4';

        return $this->database->rows("SELECT elections_cg_elections.*, party.pName, party.pLogo, region.stat_pop AS regPop,
                citizens.name, citizens.Avatar, COUNT(elections_cg_votes.CandidateID) AS TotalVotes
            FROM elections_all JOIN elections_cg_elections ON elections_all.eID = elections_cg_elections.ElectionID
            LEFT JOIN party ON party.pID = elections_cg_elections.PartyID
            LEFT JOIN citizens ON elections_cg_elections.CandidateID = citizens.CitizenID
            LEFT JOIN region ON region.RegionID = elections_cg_elections.RegionID
            LEFT JOIN elections_cg_votes ON (elections_cg_elections.CandidateID = elections_cg_votes.CandidateID AND elections_cg_votes.ElectionID = elections_all.eID)
            WHERE elections_all.timestamp = ? AND elections_all.eType = 'CG' AND elections_cg_elections.CountryID = ?{$regionSql} {$stateSql}
            GROUP BY CandidateID ORDER BY TotalVotes DESC, citizens.ep DESC", $b);
    }

    public function getElectionCP(int|string $country, int $year, int $month, $shuffle = ''): array
    {
        $time = mktime(0, 0, 0, $month, 1, $year);
        $order = $shuffle ? 'ORDER BY RAND()' : 'ORDER BY TotalVotes DESC, citizens.ep DESC';

        return $this->database->rows("SELECT elections_cp_elections.*, party.*, citizens.name, citizens.Avatar, COUNT(elections_cp_votes.CandidateID) AS TotalVotes
            FROM elections_all JOIN elections_cp_elections ON elections_all.eID = elections_cp_elections.ElectionID
            LEFT JOIN citizens ON citizens.CitizenID = elections_cp_elections.CandidateID
            LEFT JOIN party ON party.pID = elections_cp_elections.PartyID
            LEFT JOIN elections_cp_votes ON (elections_cp_elections.CandidateID = elections_cp_votes.CandidateID) AND (elections_cp_elections.ElectionID = elections_cp_votes.ElectionID)
            WHERE elections_all.timestamp = ? AND elections_all.eType = 'CP' AND elections_cp_elections.CountryID = ?
            GROUP BY CandidateID {$order}", [$time, $country]);
    }

    public function getElectionPP(int|string $country, int|string $party, int $year, int $month, $shuffle = ''): array
    {
        $time = mktime(0, 0, 0, $month, 1, $year);
        $order = $shuffle ? 'ORDER BY RAND()' : 'ORDER BY TotalVotes DESC, citizens.ep DESC';

        return $this->database->rows("SELECT elections_pp_elections.*, citizens.name, citizens.Avatar, COUNT(elections_pp_votes.CandidateID) AS TotalVotes
            FROM elections_all JOIN elections_pp_elections ON elections_all.eID = elections_pp_elections.ElectionID
            LEFT JOIN citizens ON citizens.CitizenID = elections_pp_elections.CandidateID
            LEFT JOIN elections_pp_votes ON elections_pp_elections.CandidateID = elections_pp_votes.CandidateID AND elections_all.eID = elections_pp_votes.ElectionID
            WHERE elections_all.timestamp = ? AND elections_all.eType = 'PP' AND elections_pp_elections.PartyID = ?
            GROUP BY CandidateID {$order}", [$time, $party]);
    }

    /** Human-readable time until the next election of a type. */
    public function getNextElection(string $what): string
    {
        $ctx = app(GameContext::class);
        $today = $ctx->getTodayArray();
        $next = match ($what) {
            'PP' => $this->ppDay,
            'CP' => $this->cpDay,
            default => $this->cgDay,
        };
        if ($next > $today['Day']) {
            $nTime = mktime(0, 0, 0, $today['Month'], $next, $today['Year']);
        } elseif ($next < $today['Day']) {
            $nTime = mktime(0, 0, 0, $today['Month'], $next, $today['Year']) + 2592000;
        } else {
            $nTime = $today['Now'];
        }

        return $ctx->getDiffF($nTime);
    }

    public function getLastElection(string $type = ''): array
    {
        $sql = 'SELECT * FROM elections_all';
        $b = [];
        if ($type !== '') {
            $sql .= ' WHERE eType = ?';
            $b[] = $type;
        }

        return $this->database->row($sql.' ORDER BY timestamp DESC, day DESC', $b) ?? ['timestamp' => 0];
    }

    public function getPartyList(int|string $counID = ''): array
    {
        if ($counID !== '' && $counID !== null) {
            return $this->database->rows('SELECT * FROM party WHERE CountryID = ?', [$counID]);
        }

        return $this->database->rows('SELECT * FROM party');
    }

    public function isVoted(int|string $cit, int|string $eID, string $type = 'pp'): int
    {
        $type = in_array($type, ['pp', 'cp', 'cg'], true) ? $type : 'pp';

        return $this->database->count("SELECT voteID FROM elections_{$type}_votes WHERE ElectionID = ? AND VoterID = ?", [$eID, $cit]) ? 1 : 0;
    }

    public function resignParty(int|string $party, int|string $citizen): void
    {
        $this->database->exec('DELETE FROM party_members WHERE PartyID = ? AND CitizenID = ?', [$party, $citizen]);
    }

    /** Recompute the qualified/elected state of CG candidates for a country. */
    public function setResultCG(int|string $eID, int|string $counID): void
    {
        $this->database->exec('UPDATE elections_cg_elections SET State = 0 WHERE CountryID = ? AND ElectionID = ?', [$counID, $eID]);
        $qual = $this->politics->getCountryCGQualifies($counID);
        foreach ($this->database->rows('SELECT RegionID, stat_pop FROM region WHERE CountryID = ? AND stat_pop >= 10', [$counID]) as $reg) {
            $quali = (int) ($reg['stat_pop'] >= 100 ? $qual['xP'] : $qual['xN']);
            $cands = $this->database->rows("SELECT elections_cg_elections.cID, COUNT(elections_cg_votes.CandidateID) AS TotalVotes
                FROM elections_all JOIN elections_cg_elections ON elections_cg_elections.ElectionID = elections_all.eID
                LEFT JOIN elections_cg_votes ON elections_cg_elections.CandidateID = elections_cg_votes.CandidateID AND elections_cg_elections.ElectionID = elections_cg_votes.ElectionID
                LEFT JOIN citizens ON citizens.CitizenID = elections_cg_elections.CandidateID
                WHERE elections_cg_elections.RegionID = ? AND elections_cg_elections.ElectionID = ?
                GROUP BY elections_cg_elections.CandidateID ORDER BY TotalVotes DESC, ep DESC LIMIT {$quali}", [$reg['RegionID'], $eID]);
            foreach ($cands as $cand) {
                $this->database->exec('UPDATE elections_cg_elections SET State = ? WHERE cID = ?', [$reg['stat_pop'] >= 100 ? 1 : 2, $cand['cID']]);
            }
        }
        $quali = (int) $qual['xD'];
        if ($quali > 0) {
            foreach ($this->database->rows("SELECT elections_cg_elections.cID, COUNT(elections_cg_votes.CandidateID) AS TotalVotes
                FROM elections_all JOIN elections_cg_elections ON elections_cg_elections.ElectionID = elections_all.eID
                LEFT JOIN elections_cg_votes ON elections_cg_elections.CandidateID = elections_cg_votes.CandidateID AND elections_cg_elections.ElectionID = elections_cg_votes.ElectionID
                LEFT JOIN citizens ON citizens.CitizenID = elections_cg_elections.CandidateID
                JOIN region ON elections_cg_elections.RegionID = region.RegionID
                WHERE region.stat_pop < 10 AND region.CountryID = ? AND elections_cg_elections.ElectionID = ?
                GROUP BY elections_cg_elections.CandidateID ORDER BY TotalVotes DESC, ep DESC LIMIT {$quali}", [$counID, $eID]) as $cand) {
                $this->database->exec('UPDATE elections_cg_elections SET State = 3 WHERE cID = ?', [$cand['cID']]);
            }
        }
        $quali = (int) $qual['xW'];
        if ($quali > 0) {
            foreach ($this->database->rows("SELECT elections_cg_elections.cID, COUNT(elections_cg_votes.CandidateID) AS TotalVotes
                FROM elections_all JOIN elections_cg_elections ON elections_cg_elections.ElectionID = elections_all.eID
                LEFT JOIN elections_cg_votes ON elections_cg_elections.CandidateID = elections_cg_votes.CandidateID AND elections_cg_elections.ElectionID = elections_cg_votes.ElectionID
                LEFT JOIN citizens ON citizens.CitizenID = elections_cg_elections.CandidateID
                JOIN region ON elections_cg_elections.RegionID = region.RegionID
                WHERE region.CountryID = ? AND State = 0 AND elections_cg_elections.ElectionID = ?
                GROUP BY elections_cg_elections.CandidateID ORDER BY TotalVotes DESC, ep DESC LIMIT {$quali}", [$counID, $eID]) as $cand) {
                $this->database->exec('UPDATE elections_cg_elections SET State = 4 WHERE cID = ?', [$cand['cID']]);
            }
        }
    }

    public function setVote(int|string $eID, int|string $vID, int|string $cID, string $elecType = 'pp', int|string $counID = ''): void
    {
        $elecType = in_array($elecType, ['pp', 'cp', 'cg'], true) ? $elecType : 'pp';
        if (! $this->isVoted($vID, $eID, $elecType)) {
            $this->database->exec("INSERT INTO elections_{$elecType}_votes (ElectionID, VoterID, CandidateID) VALUES (?, ?, ?)", [$eID, $vID, $cID]);
            $this->database->addEP($vID, 1, "Voted in {$elecType} elections");
        }
        if ($elecType === 'cg') {
            $this->setResultCG($eID, $counID);
        }
    }
}
