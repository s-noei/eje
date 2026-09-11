<?php

namespace App\Game\Services;

use App\Game\Support\Url;

/**
 * Port of include/modules/friendship.class.php ($friendship).
 */
class Friendship
{
    public function __construct(protected GameDatabase $database, protected Url $url)
    {
    }

    protected function link(int|string $citID): string
    {
        $c = $this->database->getUserInfoFromID($citID, 0);

        return '<a href="'.$this->url->getURL('profile', $citID).'">'.($c['name'] ?? '').'</a>';
    }

    public function acceptFriend(int|string $citID1, int|string $citID2): int
    {
        if ($this->isFriends($citID1, $citID2)) {
            return 0;
        }
        $this->database->exec("UPDATE friendship SET Accepted = '1' WHERE Part1 = ? AND Part2 = ? AND AddedBy = ? LIMIT 1", [$citID1, $citID2, $citID2]);
        $this->database->exec("UPDATE friendship SET Accepted = '1' WHERE Part1 = ? AND Part2 = ? AND AddedBy = ? LIMIT 1", [$citID2, $citID1, $citID2]);
        $this->database->sendNote($citID2, 'addyes', $this->link($citID1));
        $this->database->exec('DELETE FROM friendship WHERE ((Part1 = ? AND Part2 = ?) OR (Part2 = ? AND Part1 = ?)) AND AddedBy = ? AND Accepted = 0',
            [$citID1, $citID2, $citID1, $citID2, $citID2]);
        $this->checkWF($citID1, $citID2);

        return 1;
    }

    public function addFriend(int|string $citID1, int|string $citID2): int
    {
        if ($this->isFriends($citID1, $citID2, 1)) {
            return 0;
        }
        $this->database->exec('INSERT INTO friendship (Part1, Part2, AddedBy) VALUES (?, ?, ?), (?, ?, ?)', [$citID1, $citID2, $citID1, $citID2, $citID1, $citID1]);

        return 1;
    }

    public function checkWF(int|string $citID1, int|string $citID2): void
    {
        foreach ([$citID1, $citID2] as $c) {
            $rows = $this->database->count('SELECT fID FROM friendship WHERE Part1 = ? AND Accepted = 1', [$c]);
            $num = (int) floor($rows / 1000);
            if ($num && $this->database->addMedal($c, 'wf', '', $num)) {
                $this->database->sendNote($c, '', 'Congratulations! You have collected 1000 friends and you received a world fame trophy and 5 Tala! You can chack your trophy place in your profile page!');
            }
        }
    }

    public function getFriends(int|string $citID, $rand = ''): array
    {
        $order = $rand ? 'ORDER BY RAND()' : 'ORDER BY citizens.EP DESC';

        return $this->database->rows("SELECT friendship.*, citizens.*, region.rName AS RegionName, country.cName FROM friendship
            JOIN citizens ON friendship.Part2 = citizens.CitizenID
            JOIN region ON region.RegionID = citizens.regionID
            JOIN country ON region.CountryID = country.CountryID
            WHERE friendship.Part1 = ? AND friendship.Accepted = '1' AND friendship.Part2 != 1 {$order}", [$citID]);
    }

    public function rejectFriend(int|string $citID1, int|string $citID2): int
    {
        if (! $this->isFriends($citID1, $citID2, 0)) {
            return 0;
        }
        $this->database->exec('DELETE FROM friendship WHERE ((Part1 = ? AND Part2 = ?) OR (Part2 = ? AND Part1 = ?)) AND AddedBy = ?', [$citID1, $citID2, $citID1, $citID2, $citID2]);
        $this->database->sendNote($citID2, 'addno', $this->link($citID1));

        return 1;
    }

    public function removeFriend(int|string $citID1, int|string $citID2): int
    {
        $this->database->exec('DELETE FROM friendship WHERE (Part1 = ? AND Part2 = ?) OR (Part2 = ? AND Part1 = ?)', [$citID1, $citID2, $citID1, $citID2]);
        $this->database->sendNote($citID2, '', "Unfortunately, you've been removed from ".$this->link($citID1)."'s addlist...");

        return 1;
    }

    public function isFriends(int|string $citID1, int|string $citID2, $acc = 1): int
    {
        if ((int) $citID1 === (int) $citID2) {
            return 1;
        }
        $sql = 'SELECT fID FROM friendship WHERE Part1 = ? AND Part2 = ?';
        if ($acc) {
            $sql .= " AND Accepted = '1'";
        }

        return $this->database->count($sql, [$citID1, $citID2]) ? 1 : 0;
    }
}
