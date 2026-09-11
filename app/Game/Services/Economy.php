<?php

namespace App\Game\Services;

/**
 * Port of include/economy.php ($eco).
 */
class Economy
{
    public function __construct(protected GameDatabase $database)
    {
    }

    public function addEmbargo(int|string $c1, int|string $c2, string $type): void
    {
        $exp = $this->database->getToday() + 30;
        $this->database->exec('INSERT INTO embargo (Country1, Country2, Type, Expire) VALUES (?, ?, ?, ?), (?, ?, ?, ?)',
            [$c1, $c2, $type, $exp, $c2, $c1, $type, $exp]);
    }

    public function getBid(int|string $compID): ?array
    {
        return $this->database->row('SELECT company.*, citizens.name FROM company LEFT JOIN citizens ON citizens.CitizenID = company.sale_bid_id WHERE company.CompanyID = ?', [$compID]);
    }

    /** Companies for sale on the company market (excluding the viewer's own). */
    public function getCMarketOffers(int|string $industryID = '', int|string $countryID = ''): array
    {
        $ctx = app(GameContext::class);
        $sql = 'SELECT company.*, region.CountryID, citizens.name AS sale_bid_name FROM company
            JOIN region ON region.RegionID = company.RegionID
            LEFT JOIN citizens ON company.sale_bid_id = citizens.CitizenID
            WHERE company.sale_due > ? AND ManagerID != ?';
        $b = [time(), (int) $ctx->CitID];
        if ($countryID !== '' && $countryID !== null) {
            $sql .= ' AND region.CountryID = ?';
            $b[] = $countryID;
        }
        if ($industryID !== '' && (string) $industryID !== '0') {
            $sql .= ' AND company.IndustryID = ?';
            $b[] = $industryID;
        }

        return $this->database->rows($sql.' ORDER BY company.sale_due, company.Stars', $b);
    }

    /** Embargoes of a country; a single row when $dest given. */
    public function getEmbargoes(int|string $coun, string $type, int|string $dest = ''): array|null
    {
        $sql = 'SELECT embargo.*, country.* FROM embargo JOIN country ON embargo.Country2 = country.CountryID WHERE Country1 = ? AND Type = ?';
        $b = [$coun, $type];
        if ($dest !== '' && $dest !== null) {
            $sql .= ' AND Country2 = ?';
            $b[] = $dest;

            return $this->database->row($sql, $b);
        }

        return $this->database->rows($sql, $b);
    }

    /** Productivity formula for working. Returns the product or the full breakdown when $arr. */
    public function getProduct4Work(array $cit, array $comp, int $type, $arr = 0): float|array
    {
        $S = (float) $cit['wSkill'];
        $S2 = $this->database->getChangedSkill($cit, $type);
        if ($S == 0) {
            $S = 0.1;
        }
        $W = 2.5;
        $wChange = $comp['Stars'] * pow(2, $type - 1);
        $gds = min((int) ($cit['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $wChange -= abs(round($wChange * $gdpercs[$gds], 2));
        $W2 = max(0, $cit['wellness'] - $wChange);
        $WT = 0.5 + ($type * 0.5);
        $A = $S * $W * $WT;

        $maxworkers = $comp['xFactor'] * ($comp['Stars'] + 4);
        $CQ = 2 / ($comp['Stars'] + 1);
        $workers = count($this->database->getWorkers($comp['CompanyID']));
        if ($workers <= $maxworkers) {
            $WM = 1;
        } elseif ($workers <= $maxworkers * 2) {
            $WM = round((5 - ($workers / $maxworkers)) / 4, 2);
        } else {
            $WM = 0.01;
        }
        $B = $CQ * $WM;
        $C = (float) ($comp['stat_cfactor'] ?: 1);

        if ((int) $comp['IndustryID'] === 11) {
            $D = match ((int) ($cit['gd_darkness'] ?? 0)) {
                0 => 0,
                1, 2, 3 => ((int) $cit['gd_darkness'] + 6) / 10,
                4 => 0.95,
                default => 1,
            };
        } elseif (in_array($comp['IndustryID'], [$cit['impind1'] ?? null, $cit['impind2'] ?? null, $cit['impind3'] ?? null], false) && ($cit['gd_work'] ?? 0)) {
            $D = match ((int) $cit['gd_work']) {
                1 => 1.1, 2 => 1.14, 3 => 1.16, 4 => 1.17, 5 => 1.18, 6 => 1.19, default => 1.2,
            };
        } else {
            $D = 1;
        }
        $E = ($this->database->today >= 837) ? 1 : ($comp['tool_q'] + 4) * $comp['tool_e'] / 1000;

        $Prod = round($A * $B * $C * $D * $E * 0.75, 2);
        $Formula = ['Base' => round($A * $B * $E * 0.75, 2), 'CFactor' => ($C - 1) * 100, 'Goddess' => ($D - 1) * 100];

        if (! $arr) {
            return $Prod;
        }

        return ['Prod' => $Prod, 'A' => $S, 'A2' => $S2, 'B' => $W, 'B2' => $W2, 'C' => $C, 'D' => $D, 'E' => $E, 'Formula' => $Formula];
    }

    /** Remove all market offers between two countries (war). */
    public function stopTrade(int|string $c1, int|string $c2): void
    {
        $rows = $this->database->rows('SELECT market.*, company.*, market.Stock AS Market, region.CountryID AS cCountryID FROM market
            LEFT JOIN company ON company.CompanyID = market.CompanyID
            LEFT JOIN region ON company.RegionID = region.RegionID
            WHERE (region.CountryID = ? AND market.CountryID = ?) OR (region.CountryID = ? AND market.CountryID = ?)', [$c1, $c2, $c2, $c1]);
        foreach ($rows as $offer) {
            $this->database->setCompanyOffer($offer['OfferID'], $offer['CompanyID'], $offer['Quality'], $offer['CountryID'], 0, $offer['Price']);
        }
    }
}
