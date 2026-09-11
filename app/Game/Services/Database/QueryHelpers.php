<?php

namespace App\Game\Services\Database;

use Illuminate\Support\Facades\DB;

/**
 * Thin helpers over the Laravel DB facade so the ported legacy methods read
 * like the originals (rows / row / value / count) while using bindings.
 */
trait QueryHelpers
{
    public int $numQueries = 0;

    /** @return array<int, array<string, mixed>> */
    public function rows(string $sql, array $bindings = []): array
    {
        $this->numQueries++;
        $start = microtime(true);
        $rows = array_map(fn ($r) => (array) $r, DB::select($sql, $bindings));
        $this->logSlow($sql, $start);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function row(string $sql, array $bindings = []): ?array
    {
        $rows = $this->rows($sql, $bindings);

        return $rows[0] ?? null;
    }

    public function value(string $sql, array $bindings = [], mixed $default = null): mixed
    {
        $row = $this->row($sql, $bindings);
        if (! $row) {
            return $default;
        }

        return reset($row);
    }

    public function count(string $sql, array $bindings = []): int
    {
        return count($this->rows($sql, $bindings));
    }

    /** Executes INSERT/UPDATE/DELETE, returns affected rows. */
    public function exec(string $sql, array $bindings = []): int
    {
        $this->numQueries++;
        $start = microtime(true);
        $n = DB::affectingStatement($sql, $bindings);
        $this->logSlow($sql, $start);

        return $n;
    }

    public function insertGetId(string $sql, array $bindings = []): int
    {
        DB::insert($sql, $bindings);
        $this->numQueries++;

        return (int) DB::getPdo()->lastInsertId();
    }

    /** Legacy: getInsertID() */
    public function getInsertID(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }

    /** Legacy compatibility: run a raw statement (used by ported pages that build SQL dynamically). */
    public function query(string $sql, array $bindings = []): mixed
    {
        if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE)/i', $sql)) {
            return $this->rows($sql, $bindings);
        }

        return $this->exec($sql, $bindings);
    }

    private function logSlow(string $sql, float $start): void
    {
        $dur = round(microtime(true) - $start, 4);
        if ($dur > 1) {
            try {
                DB::table('susp_slow_queries')->insert([
                    'query' => $sql,
                    'page' => substr(request()->getRequestUri(), 0, 100),
                    'time' => $dur,
                ]);
            } catch (\Throwable) {
                // never let telemetry break the page (legacy table has a unique key on page)
            }
        }
    }

    /** Whitelist a column name that comes from code (never from the request). */
    protected function col(string $field): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
            throw new \InvalidArgumentException("Bad column name: {$field}");
        }

        return "`{$field}`";
    }
}
