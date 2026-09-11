<?php

namespace App\Game\Services\Database;

trait SettingsQueries
{
    /** @var array<string,mixed>|null */
    public ?array $setting = null;

    /** getSetting — one value by title, or the whole map. */
    public function getSetting(string $title = ''): mixed
    {
        if ($title !== '') {
            return $this->value('SELECT `Value` FROM settings WHERE Title = ?', [$title]);
        }
        $out = [];
        foreach ($this->rows('SELECT * FROM settings') as $r) {
            $out[$r['Title']] = $r['Value'];
        }

        return $out;
    }

    /** getSettings — array (with sURL/pTitle/sName always present) or single value. */
    public function getSettings(string $what = ''): mixed
    {
        if ($what !== '') {
            return $this->value('SELECT `Value` FROM settings WHERE Title = ? LIMIT 1', [$what]);
        }
        $out = ['sURL' => '', 'pTitle' => '', 'sName' => ''];
        foreach ($this->rows('SELECT * FROM settings') as $r) {
            $out[$r['Title']] = $r['Value'];
        }

        return $out;
    }

    public function setSetting(string $title, mixed $value): int
    {
        return $this->exec('INSERT INTO settings (Title, `Value`) VALUES (?, ?)', [$title, $value]);
    }

    public function updateSetting(string $title, mixed $value): int
    {
        return $this->exec('UPDATE settings SET `Value` = ? WHERE Title = ?', [$value, $title]);
    }

    public function getAwardedFee(): mixed
    {
        return $this->value('SELECT `Value` FROM settings WHERE Title = ?', ['award_signup'], 0);
    }
}
