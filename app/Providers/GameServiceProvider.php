<?php

namespace App\Providers;

use App\Game\Services\Chat;
use App\Game\Services\Economy;
use App\Game\Services\Elections;
use App\Game\Services\Friendship;
use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Ip2Country;
use App\Game\Services\Mailer;
use App\Game\Services\Military;
use App\Game\Services\Moderation;
use App\Game\Services\NoteParser;
use App\Game\Services\Payment;
use App\Game\Services\Politics;
use App\Game\Services\Rankings;
use App\Game\Services\Ticket;
use App\Game\Services\Translator;
use App\Game\Support\GameClock;
use App\Game\Support\LegacyForm;
use App\Game\Support\Url;
use App\Game\Support\Vars;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            GameClock::class, GameDatabase::class, Translator::class, Url::class, Vars::class, GameContext::class,
            Economy::class, Politics::class, Military::class, Elections::class, Rankings::class, Payment::class,
            Moderation::class, Friendship::class, Chat::class, Ticket::class, NoteParser::class, Mailer::class, Ip2Country::class,
        ] as $class) {
            $this->app->singleton($class);
        }
        $this->app->bind(LegacyForm::class, fn () => new LegacyForm);
    }

    public function boot(): void
    {
        date_default_timezone_set(config('ejahan.timezone'));
    }
}
