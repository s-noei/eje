<?php

namespace App\Game\Support;

/**
 * The in-game calendar. Day numbers count from the game epoch (2013-04-04).
 * Port of MySQLDB::getToday / getTodateDiff and Session::getTodayArray.
 */
class GameClock
{
    public readonly int $time;

    public readonly int $today;

    public readonly int $todayTs;

    public function __construct()
    {
        $this->time = time();
        $this->today = $this->dayOf($this->time);
        $this->todayTs = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));
    }

    public function epoch(): int
    {
        $e = config('ejahan.epoch');

        return mktime(0, 0, 0, $e['month'], $e['day'], $e['year']);
    }

    /** eJahan day number for a unix timestamp (default: now). */
    public function dayOf(?int $t = null): int
    {
        $t = $t ?: time();

        return (int) floor(($t - $this->epoch()) / 86400);
    }

    public function getToday(?int $t = null): int
    {
        return $this->dayOf($t);
    }

    /** @return array{Now:int,eDay:int,Day:int,Month:int,Year:int} */
    public function todayArray(?int $time = null): array
    {
        $time = $time ?: time();

        return [
            'Now' => $time,
            'eDay' => $this->dayOf($time),
            'Day' => (int) date('d', $time),
            'Month' => (int) date('m', $time),
            'Year' => (int) date('Y', $time),
        ];
    }

    /** Midnight of the current real day (used by "did X today" checks). */
    public function startOfRealDay(): int
    {
        $n = $this->todayArray();

        return mktime(0, 0, 0, $n['Month'], $n['Day'], $n['Year']);
    }
}
