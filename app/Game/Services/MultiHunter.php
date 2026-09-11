<?php

namespace App\Game\Services;

/**
 * Port of lens/include/multihunter.class.php — heuristic multi-account detector.
 * Each check adds weighted "points" to other citizens sharing a trait with the target.
 */
class MultiHunter
{
    public array $multies = [];
    public int $totalPoints = 1; // one fake point so nobody reaches 100%

    public function __construct(protected GameDatabase $database, public int $id)
    {
        $this->checkIP();
        $this->checkSession();
        $this->checkRegion();
        $this->checkPassword();
        $this->checkAgent();
        $this->checkTimed('log_logins', 'citID', 'Login conflict', 'chocolate', 'L', 1, 0.2);
        $this->checkTimed('log_working', 'CitizenID', 'Work conflict', 'DarkOliveGreen', 'W', 1, 0.2);
        $this->checkTimed('log_training', 'CitizenID', 'Train conflict', 'DarkSlateBlue', 'T', 1, 0.2);
        $this->checkTimed('log_exploring', 'CitizenID', 'Explore conflict', 'teal', 'E', 1, 0.25);
        $this->checkVote();
        $this->checkTrans();
        uasort($this->multies, fn ($a, $b) => $b['points'] <=> $a['points']);
    }

    protected function addMulti(int $id, string $type, string $color, string $char, int $points, int $minPt = 0): void
    {
        $cur = $this->multies[$id]['points'] ?? 0;
        if ($minPt <= $cur) {
            $this->multies[$id]['citID'] = $id;
            $this->multies[$id]['string'] = ($this->multies[$id]['string'] ?? '')."<font color=\"$color\" title=\"$type\">$char</font>";
            $this->multies[$id]['points'] = $cur + $points;
        }
    }

    protected function sameLogin(string $col, string $type, string $color, string $char, int $points): void
    {
        $this->totalPoints += $points;
        $rows = $this->database->rows("SELECT rep2.citID FROM log_logins AS rep1 JOIN log_logins AS rep2 ON (rep1.$col = rep2.$col AND rep1.citID != rep2.citID)
            JOIN citizens ON citizens.CitizenID = rep2.citID WHERE rep1.citID = ? AND citizens.accType = 'citizen' GROUP BY rep2.citID", [$this->id]);
        foreach ($rows as $r) {
            $this->addMulti((int) $r['citID'], $type, $color, $char, $points);
        }
    }

    protected function checkIP(): void
    {
        $this->sameLogin('ip', 'IP conflict', 'olive', 'I', 1);
    }

    protected function checkSession(): void
    {
        $this->sameLogin('session', 'Session conflict', 'red', 'S', 2);
    }

    protected function checkAgent(): void
    {
        $this->sameLogin('agent', 'Agent conflict', 'maroon', 'A', 5);
    }

    protected function checkRegion(): void
    {
        $this->totalPoints += 1;
        foreach ($this->database->rows("SELECT cit2.CitizenID AS citID FROM citizens AS cit1 JOIN citizens AS cit2 ON (cit1.regionID = cit2.regionID AND cit1.CitizenID != cit2.CitizenID)
            WHERE cit1.CitizenID = ? AND cit2.accType = 'citizen'", [$this->id]) as $r) {
            $this->addMulti((int) $r['citID'], 'Same region', 'teal', 'R', 1);
        }
    }

    protected function checkPassword(): void
    {
        $this->totalPoints += 2;
        foreach ($this->database->rows("SELECT cit2.CitizenID AS citID FROM citizens AS cit1 JOIN citizens AS cit2 ON (cit1.password = cit2.password AND cit1.CitizenID != cit2.CitizenID)
            WHERE cit1.CitizenID = ? AND cit2.accType = 'citizen'", [$this->id]) as $r) {
            $this->addMulti((int) $r['citID'], 'Same password', 'navy', 'P', 2);
        }
    }

    /** Actions performed within 10 minutes of each other, at least $ratio of the target's own actions. */
    protected function checkTimed(string $table, string $col, string $type, string $color, string $char, int $points, float $ratio): void
    {
        $this->totalPoints += $points;
        $num1 = $this->database->count("SELECT $col FROM $table WHERE $col = ?", [$this->id]);
        if ($num1 < 1) {
            return;
        }
        $rows = $this->database->rows("SELECT rep2.$col AS citID, COUNT(rep2.$col) numbers FROM $table AS rep1
            JOIN $table AS rep2 ON (rep1.timestamp >= rep2.timestamp - 600 AND rep1.timestamp <= rep2.timestamp + 600 AND rep1.$col != rep2.$col)
            JOIN citizens ON citizens.CitizenID = rep2.$col WHERE rep1.$col = ? AND citizens.accType = 'citizen' GROUP BY rep2.$col", [$this->id]);
        foreach ($rows as $r) {
            if ($r['numbers'] / $num1 >= $ratio) {
                $this->addMulti((int) $r['citID'], $type, $color, $char, $points);
            }
        }
    }

    protected function checkVote(): void
    {
        $this->totalPoints += 2;
        $seen = [];
        foreach (['elections_cg_votes', 'elections_cp_votes', 'elections_pp_votes'] as $table) {
            $rows = $this->database->rows("SELECT rep2.VoterID AS citID FROM $table AS rep1
                JOIN $table AS rep2 ON (rep1.CandidateID = rep2.CandidateID AND rep1.ElectionID <= rep2.ElectionID AND rep1.VoterID != rep2.VoterID)
                JOIN citizens ON citizens.CitizenID = rep2.VoterID WHERE rep1.VoterID = ? AND citizens.accType = 'citizen' GROUP BY rep2.VoterID", [$this->id]);
            foreach ($rows as $r) {
                if (empty($seen[$r['citID']])) {
                    $this->addMulti((int) $r['citID'], 'Vote conflict', 'hotpink', 'V', 2);
                }
                $seen[$r['citID']] = true;
            }
        }
    }

    protected function checkTrans(): void
    {
        $this->totalPoints += 2;
        $seen = [];
        foreach ([['toID', 'fromID'], ['fromID', 'toID']] as [$other, $mine]) {
            $rows = $this->database->rows("SELECT rep1.$other AS citID FROM transactions AS rep1 JOIN citizens ON citizens.CitizenID = rep1.$other
                WHERE rep1.$mine = ? AND citizens.accType = 'citizen' GROUP BY rep1.$other", [$this->id]);
            foreach ($rows as $r) {
                if (empty($seen[$r['citID']])) {
                    $this->addMulti((int) $r['citID'], 'Transaction conflict', 'khaki', 'M', 2, 10);
                }
                $seen[$r['citID']] = true;
            }
        }
    }
}
