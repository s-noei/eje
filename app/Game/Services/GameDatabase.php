<?php

namespace App\Game\Services;

use App\Game\Services\Database\CitizenQueries;
use App\Game\Services\Database\EconomyQueries;
use App\Game\Services\Database\GeoQueries;
use App\Game\Services\Database\MediaQueries;
use App\Game\Services\Database\PoliticsQueries;
use App\Game\Services\Database\QueryHelpers;
use App\Game\Services\Database\SettingsQueries;
use App\Game\Support\GameClock;
use App\Game\Support\Url;

/**
 * Port of the legacy MySQLDB class ($database). Method names are preserved so
 * the ported pages read like the originals; results are arrays instead of
 * mysql resources and every user-supplied value is bound.
 */
class GameDatabase
{
    use QueryHelpers, SettingsQueries, CitizenQueries, EconomyQueries, GeoQueries, MediaQueries, PoliticsQueries;

    public int $today;

    public int $todayts;

    public function __construct(protected GameClock $clock)
    {
        $this->today = $clock->today;
        $this->todayts = $clock->todayTs;
        $this->setting = $this->getSetting();
    }

    public function getToday(?int $t = null): int
    {
        return $this->clock->dayOf($t);
    }

    public function getTodateDiff(int $time): int
    {
        return $this->clock->dayOf($time);
    }

    /* Lazy collaborators (resolved from the container to avoid constructor cycles). */

    protected function context(): ?GameContext
    {
        return app()->bound(GameContext::class) ? app(GameContext::class) : null;
    }

    protected function translator(): Translator
    {
        return app(Translator::class);
    }

    protected function economy(): Economy
    {
        return app(Economy::class);
    }

    protected function politics(): Politics
    {
        return app(Politics::class);
    }

    protected function payments(): Payment
    {
        return app(Payment::class);
    }

    protected function url(string $type, ...$params): string
    {
        return app(Url::class)->getURL($type, ...$params);
    }
}
