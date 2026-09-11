<?php

namespace App\Game\Services;

/**
 * Port of include/mod.php ($mod): bans, violations, moderator comments.
 */
class Moderation
{
    public function __construct(protected GameDatabase $database)
    {
    }

    protected function ctx(): GameContext
    {
        return app(GameContext::class);
    }

    public function addModCM(string $type, int|string $typeID, string $body): void
    {
        $this->database->exec('INSERT INTO mod_comments (`Type`, TypeID, `By`, ByMod, Body, timestamp) VALUES (?, ?, ?, ?, ?, ?)',
            [substr($type, 0, 8), $typeID, (int) $this->ctx()->CitID, $this->ctx()->ModID, $body, time()]);
    }

    public function addViolation(int|string $fID, int|string $cID, int|string $bID, string $title, string $desc, int $points, $exp = '', $ban = 1): void
    {
        if (! $exp) {
            $exp = $this->database->getToday() + 30;
        }
        if ((string) $exp === '-1') {
            $exp = 0;
        }
        $id = $this->database->insertGetId('INSERT INTO forfeit_forfeits (forfeitID, citID, byID, Title, Description, Points, timestamp, Expire) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [(int) $fID, $cID, (int) $bID, substr($title, 0, 30), $desc, $points, time(), $exp]);
        $this->database->sendPM(1, $cID, 'Violation received', "You've received a violation from the administration team.\n Title: {$title}\nDescription: {$desc}\nAlso you received {$points} Vio Points.");
        $this->addModCM('citizen', $cID, "Auto comment: Violation # {$id}, Title: {$title}, Description: {$desc}, Points: {$points} was submitted by me!");
        if ($points >= 3 && $ban) {
            $this->banCitizen($cID, 1, 'receiving 3 Vio Points or more at once', 0);
        }
        if ($this->getVioPoints($cID) >= 10 && $ban) {
            $this->banCitizen($cID, 30, 'receiving 10 Vio Points', 0);
        }
    }

    /** $due in days; 0 = permanent. */
    public function banCitizen(int|string $citID, int $due, string $reason, $addvio = 1): void
    {
        $time = time();
        $permanent = ! $due;
        $dueSecs = $due * 86400;
        $this->database->exec('UPDATE citizens SET ban_due = ?, ban_reason = ? WHERE CitizenID = ?', [$permanent ? 'PERMANENTLY' : (string) ($time + $dueSecs), $reason, $citID]);
        if ($permanent) {
            $uInfo = $this->database->getUserInfoFromID($citID);
            if ($uInfo['CompanyID'] ?? null) {
                $this->database->resignWorker($citID);
            }
            foreach (['elections_cg_votes', 'elections_cp_votes', 'elections_pp_votes'] as $t) {
                $this->database->exec("DELETE FROM {$t} WHERE VoterID = ?", [$citID]);
            }
            $this->database->exec('DELETE FROM friendship WHERE (Part1 = ? OR Part2 = ?) AND Part1 != 1 AND Part2 != 1', [$citID, $citID]);
            if ($addvio) {
                $this->addViolation(0, $citID, (int) $this->ctx()->CitID, 'Permanent ban', "You have been permanently banned for {$reason}", 10, '-1', 0);
            }
        } elseif ($addvio) {
            $vpts = $this->database->getToday($time + $dueSecs) - $this->database->getToday();
            $this->addViolation(0, $citID, (int) $this->ctx()->CitID, 'Temporary ban', "You have been temporarily banned for {$reason}", $vpts, '', 0);
        }
        $this->addModCM('citizen', $citID, "Auto comment: This citizen was banned by me! Reason: {$reason}, Type: ".($permanent ? 'Permanently' : 'Temporarily'));
    }

    public function deactiveViolation(int|string $id): void
    {
        $this->database->exec("UPDATE forfeit_forfeits SET Active = '0' WHERE ID = ?", [$id]);
        $citID = $this->database->value('SELECT citID FROM forfeit_forfeits WHERE ID = ?', [$id]);
        if ($citID) {
            $this->addModCM('citizen', $citID, "Auto comment: Violation # {$id} was removed by me!");
        }
    }

    public function getVioPoints(int|string $citID): int
    {
        return (int) $this->database->value("SELECT SUM(Points) AS Total FROM forfeit_forfeits WHERE citID = ? AND Active = '1' AND Expire > ? GROUP BY citID",
            [$citID, $this->database->getToday()], 0);
    }

    public function unbanCitizen(int|string $citID): void
    {
        $this->database->exec("UPDATE citizens SET ban_due = '', ban_reason = '' WHERE CitizenID = ?", [$citID]);
        $this->addModCM('citizen', $citID, 'Auto comment: This citizen was unbanned by me!');
    }
}
