<?php

namespace App\Game\Services;

use Illuminate\Support\Facades\DB;

/**
 * Port of include/ip2c (PHPWeby ip2country over the `ip2c` table).
 */
class Ip2Country
{
    protected string $ip = '';

    protected ?array $row = null;

    public function __construct(?string $ip = null)
    {
        $this->setIp($ip ?: (string) request()->ip());
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;
        $this->row = null;

        return $this;
    }

    protected function lookup(): array
    {
        if ($this->row === null) {
            $num = ip2long($this->ip);
            $this->row = ['country_code' => '', 'country_name' => ''];
            if ($num !== false) {
                $r = DB::table('ip2c')->whereRaw('? BETWEEN begin_ip_num AND end_ip_num', [sprintf('%u', $num)])->first(['country_code', 'country_name']);
                if ($r) {
                    $this->row = ['country_code' => (string) $r->country_code, 'country_name' => (string) $r->country_name];
                }
            }
        }

        return $this->row;
    }

    public function get_country_code(string $ip = ''): string
    {
        if ($ip !== '' && $ip !== $this->ip) {
            $this->setIp($ip);
        }

        return $this->lookup()['country_code'];
    }

    public function get_country_name(string $ip = ''): string
    {
        if ($ip !== '' && $ip !== $this->ip) {
            $this->setIp($ip);
        }

        return $this->lookup()['country_name'];
    }
}
