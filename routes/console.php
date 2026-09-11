<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Legacy cron schedule (include/cron/init.php + minly.php were hit by external cron).
 * Run `php artisan schedule:work` (or a system cron calling `schedule:run` every minute).
 */
Schedule::command('game:cron minutely')->everyMinute()->withoutOverlapping();
Schedule::command('game:cron hourly')->hourly();
Schedule::command('game:cron daily')->dailyAt('00:01');
Schedule::command('game:cron stats')->dailyAt('00:20')->withoutOverlapping();
Schedule::command('game:cron invites')->dailyAt('03:00');
