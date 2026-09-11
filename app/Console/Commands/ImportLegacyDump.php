<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Imports the data rows from the original phpMyAdmin dump (ejahan.sql) into the
 * schema created by the migrations. DDL statements in the dump are skipped — the
 * migrations own the schema — only INSERT statements are replayed.
 *
 *   php artisan legacy:import /legacy/ejahan.sql --truncate
 */
class ImportLegacyDump extends Command
{
    protected $signature = 'legacy:import {file? : Path to ejahan.sql (defaults to EJAHAN_LEGACY_SQL)}
                            {--truncate : Empty every legacy table before importing}
                            {--only= : Comma separated list of tables to import}';

    protected $description = 'Import row data from the legacy ejahan.sql dump';

    public function handle(): int
    {
        $file = $this->argument('file') ?: env('EJAHAN_LEGACY_SQL');
        if (! $file || ! is_readable($file)) {
            $this->error("Dump file not readable: {$file}");

            return self::FAILURE;
        }

        $only = $this->option('only') ? array_filter(array_map('trim', explode(',', $this->option('only')))) : null;

        DB::unprepared("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION'");
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
        DB::unprepared('SET NAMES utf8mb4');

        if ($this->option('truncate')) {
            $this->info('Truncating legacy tables…');
            foreach ($this->legacyTables() as $t) {
                if ($only && ! in_array($t, $only, true)) {
                    continue;
                }
                DB::table($t)->truncate();
            }
        }

        $this->info("Importing {$file}…");
        $bar = $this->output->createProgressBar(filesize($file));
        $bar->setFormat(' %current%/%max% bytes [%bar%] %percent:3s%%');

        $count = 0;
        $skipped = 0;
        foreach ($this->statements($file, $bar) as $stmt) {
            if (! preg_match('/^INSERT\s+INTO\s+`?(\w+)`?/i', $stmt, $m)) {
                $skipped++;
                continue; // CREATE TABLE / SET / comments
            }
            if ($only && ! in_array($m[1], $only, true)) {
                continue;
            }
            try {
                DB::unprepared($stmt);
                $count++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Failed on table {$m[1]}: ".substr($e->getMessage(), 0, 200));
            }
        }
        $bar->finish();
        $this->newLine();

        DB::unprepared('SET FOREIGN_KEY_CHECKS = 1');
        $this->info("Done: {$count} INSERT statements executed, {$skipped} non-INSERT statements skipped.");

        return self::SUCCESS;
    }

    /**
     * Quote-aware statement splitter that streams the dump without loading it fully.
     *
     * @return \Generator<string>
     */
    private function statements(string $file, $bar): \Generator
    {
        $h = fopen($file, 'rb');
        $buf = '';
        $inStr = false;
        $quote = '';
        while (! feof($h)) {
            $chunk = fread($h, 1 << 20);
            $bar->advance(strlen($chunk));
            $len = strlen($chunk);
            $start = 0;
            for ($i = 0; $i < $len; $i++) {
                $c = $chunk[$i];
                if ($inStr) {
                    if ($c === '\\') {
                        $i++;
                    } elseif ($c === $quote) {
                        $inStr = false;
                    }
                } elseif ($c === "'" || $c === '"') {
                    $inStr = true;
                    $quote = $c;
                } elseif ($c === ';') {
                    $buf .= substr($chunk, $start, $i - $start);
                    $stmt = trim(preg_replace('/^(--[^\n]*|\/\*![^;]*?\*\/)\s*$/m', '', $buf));
                    if ($stmt !== '') {
                        yield $stmt;
                    }
                    $buf = '';
                    $start = $i + 1;
                }
            }
            $buf .= substr($chunk, $start);
        }
        fclose($h);
    }

    private function legacyTables(): array
    {
        $rows = DB::select("SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME NOT IN ('migrations','sessions','cache','cache_locks')");

        return array_map(fn ($r) => $r->t, $rows);
    }
}
