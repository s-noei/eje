<?php

namespace App\Console\Commands;

use App\Game\Cron\GameCron;
use Illuminate\Console\Command;

/**
 * Runs the legacy cron jobs: php artisan game:cron {minutely|hourly|daily|stats|lottery|invites|laws|battles}
 */
class GameCronCommand extends Command
{
    protected $signature = 'game:cron {job : minutely|hourly|daily|stats|lottery|invites|laws|battles} {--force : run daily even if already run today}';

    protected $description = 'Run the eJahan game cron jobs (port of include/cron/*)';

    public function handle(GameCron $cron): int
    {
        $job = $this->argument('job');
        $result = match ($job) {
            'minutely' => $cron->minutely(),
            'hourly' => $cron->hourly(),
            'daily' => $cron->daily((bool) $this->option('force')),
            'stats' => $cron->stats(),
            'lottery' => $cron->lottery(),
            'invites' => $cron->recycleInvites(),
            'laws' => app(\App\Game\Cron\LawProcessor::class)->run(),
            'battles' => $cron->battles(),
            default => null,
        };
        if ($result === null) {
            $this->error("Unknown job: $job");

            return self::FAILURE;
        }
        $this->info("game:cron $job -> ".json_encode($result));

        return self::SUCCESS;
    }
}
