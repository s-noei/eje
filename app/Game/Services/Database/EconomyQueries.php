<?php

namespace App\Game\Services\Database;

use App\Game\Support\Constants;

/**
 * Companies, markets, money and work (port of the economy part of MySQLDB).
 */
trait EconomyQueries
{
    private const MONEY_TABLES = [
        'citizen_money' => 'CitID',
        'company_money' => 'CompID',
        'country_money' => 'CounID',
        'party_money' => 'PartyID',
    ];

    private function moneyTable(string $type): array
    {
        $table = $type.'_money';
        if (! isset(self::MONEY_TABLES[$table])) {
            throw new \InvalidArgumentException("Unknown account type {$type}");
        }

        return [$table, self::MONEY_TABLES[$table]];
    }

    private function transactionPage(string $url): string
    {
        $page = $url !== '' ? $url : request()->getRequestUri();
        if (stripos($page, 'cron') !== false) {
            $page = '> Hidden process <';
        }
        if (stripos($page, 'ejbotman') !== false) {
            $page = '> Unknown/Internal error <';
        }

        return substr($page, 0, 40);
    }

    /** Ensure an account row exists and return it. */
    private function accountRow(string $table, string $idCol, int|string $id, int|string $curID): array
    {
        $row = $this->row("SELECT * FROM {$table} WHERE {$idCol} = ? AND CurID = ?", [$id, $curID]);
        if (! $row) {
            $this->exec("INSERT INTO {$table} ({$idCol}, CurID) VALUES (?, ?)", [$id, $curID]);
            $row = $this->row("SELECT * FROM {$table} WHERE {$idCol} = ? AND CurID = ?", [$id, $curID]);
        }

        return $row;
    }

    /** Credit an account out of thin air (rewards). $apr allows it without a logged-in user. */
    public function addMoney(int|string $curID, float|int $amount, int|string $toID, string $toType = 'citizen', $apr = 0, string $url = '', $trunc = 1): int
    {
        $ctx = $this->context();
        if (! (($ctx && $ctx->logged_in) || $apr)) {
            return 0;
        }
        [$table, $idCol] = $this->moneyTable($toType);
        $tTo = $this->accountRow($table, $idCol, $toID, $curID);
        $befTo = (float) $tTo['Amount'];
        $aft = $befTo + $amount;
        $this->exec("UPDATE {$table} SET Amount = ? WHERE {$idCol} = ? AND CurID = ?", [$aft, $toID, $curID]);
        $this->exec('INSERT INTO transactions (fromID, fromType, toID, toType, curID, Amount, Page, timestamp, fromBef, fromAft, toBef, toAft)
            VALUES (0, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?)',
            ['N/A', $toID, $toType, $curID, $amount, $this->transactionPage($url), time(), $befTo, $aft]);

        return 1;
    }

    /** Move money between accounts. $fee (truthy) allows it without a logged-in user. */
    public function transferMoney(int|string $curID, float|int $amount, int|string $fromID, string $fromType = 'citizen', int|string $toID = '', string $toType = 'citizen', $fee = 0, string $url = '', $trunc = 1): int
    {
        $ctx = $this->context();
        if (! (($ctx && $ctx->logged_in) || $fee)) {
            return 0;
        }
        [$fTable, $fCol] = $this->moneyTable($fromType);
        $tFrom = $this->row("SELECT * FROM {$fTable} WHERE {$fCol} = ? AND CurID = ?", [$fromID, $curID]) ?? ['Amount' => 0];
        $tTo = null;
        if ($toType !== '' && $toID !== '') {
            [$tTable, $tCol] = $this->moneyTable($toType);
            $tTo = $this->accountRow($tTable, $tCol, $toID, $curID);
        }
        $befFrom = (float) $tFrom['Amount'];
        if ($befFrom < $amount + 0.01 && $trunc) {
            $amount = $befFrom;
        }
        $befTo = $tTo ? (float) $tTo['Amount'] : 0;
        $aftFrom = $befFrom - $amount;
        $aftTo = $tTo ? $befTo + $amount : 0;

        if (! $this->exec("UPDATE {$fTable} SET Amount = ? WHERE {$fCol} = ? AND CurID = ?", [$aftFrom, $fromID, $curID])) {
            // account row may not exist yet — create it with the (negative-safe) balance
            $this->accountRow($fTable, $fCol, $fromID, $curID);
            $this->exec("UPDATE {$fTable} SET Amount = ? WHERE {$fCol} = ? AND CurID = ?", [$aftFrom, $fromID, $curID]);
        }
        if ($tTo) {
            $this->exec("UPDATE {$tTable} SET Amount = ? WHERE {$tCol} = ? AND CurID = ?", [$aftTo, $toID, $curID]);
        }
        $this->exec('INSERT INTO transactions (fromID, fromType, toID, toType, curID, Amount, Page, timestamp, fromBef, fromAft, toBef, toAft)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$fromID, $fromType, (int) $toID, $toType, $curID, $amount, $this->transactionPage($url), time(), $befFrom, $aftFrom, $befTo, $aftTo]);

        return 1;
    }

    /** Donation with log (type = currency id, 0 for items). */
    public function donate(int|string $curID, float|int $amount, $type, int|string $fromID, string $fromType, int|string $toID, string $toType): int
    {
        $ctx = $this->context();
        if (! ($ctx && $ctx->logged_in && $fromID && $toID)) {
            return 0;
        }
        if ($type) {
            $this->transferMoney($curID, $amount, $fromID, $fromType, $toID, $toType);
        }
        $this->exec('INSERT INTO log_donations (FromID, FromType, ToID, ToType, Type, Amount, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$fromID, $fromType, $toID, $toType, (int) $curID, $amount, time()]);

        $from = $this->getUserInfoFromID($fromID);
        $cur = $this->getCurrency($curID);
        $note = '<a href="'.$this->url('profile', $fromID).'">'.($from['name'] ?? '')."</a> has been transfered {$amount} "
            .($curID ? $cur : ($amount > 1 ? 'items' : 'item'))
            .' to you'.($curID ? ',' : 'r inventory,')
            .' check <a href="'.$this->url('profile', $toID, 'donations').'">your donation log</a>.';
        if ($toType === 'citizen') {
            $this->sendNote($toID, '', $note);
        }

        return 1;
    }

    public function updateCompanyField(int|string $companyid, string $field, mixed $value): int
    {
        return $this->exec('UPDATE company SET '.$this->col($field).' = ? WHERE CompanyID = ?', [$value, $companyid]);
    }

    public function addProduct(array $comp, float $prod): void
    {
        $pro = (float) $comp['Products'] + $prod;
        $ind = $this->getIndustryRec($comp['IndustryID']);
        $unit = (int) $ind['Unit'];
        $sto = (int) floor($pro / $unit);
        $pro = fmod($pro * 100, $unit * 100) / 100;

        if ((int) $comp['IndustryID'] !== 11) {
            $sto += (int) $comp['Stock'];
            $this->updateCompanyField($comp['CompanyID'], 'Stock', $sto);
        } elseif ($sto === 1) {
            $this->createProduct(11, $comp['Stars'], $comp['ManagerID']);
            $this->sendNote($comp['ManagerID'], '', "A {$comp['Stars']}-star rage is created!");
        }
        $this->updateCompanyField($comp['CompanyID'], 'Products', $pro);
    }

    public function addTax2Price(int|string $indID, int|string $homeID, int|string $destID, float $price, $noRound = ''): float
    {
        $tax = $this->getIndustryTax($destID, $indID, 1);
        $vat = $tax ? (float) $tax['VAT'] : 0;
        $nPrice = $price * (1 + $vat / 100);
        if ((int) $homeID !== (int) $destID && $tax) {
            $nPrice *= (1 + (float) $tax['Import'] / 100);
        }

        return $noRound ? $nPrice : round($nPrice, 2);
    }

    public function addWorker(int|string $citID, int|string $compID, float $wSalary, int|string $curID): void
    {
        if ($this->isWorker($citID, '', '1')) {
            $this->exec('UPDATE company_workers SET CompanyID = ?, Salary = ?, curID = ? WHERE CitizenID = ?', [$compID, $wSalary, $curID, $citID]);
        } else {
            $this->exec('INSERT INTO company_workers (CitizenID, CompanyID, Salary, curID) VALUES (?, ?, ?, ?)', [$citID, $compID, $wSalary, $curID]);
            $cit = $this->getUserInfoFromID($citID);
            if (! $cit['LastWorked']) {
                $this->updateUserFieldID($citID, 'LastWorked', '-1');
            }
        }
        $uInfo = $this->getUserInfoFromID($citID);
        $cInfo = $this->getCompany($compID);
        $msg = '<b><a href="'.$this->url('profile', $citID).'">'.$uInfo['name'].'</a></b> applied for work for your company <b>'
            .'<a href="'.$this->url('company', $compID).'">'.$cInfo['Name'].'</a></b>';
        $this->sendNote($this->getManagerID($compID), '', $msg);
    }

    public function addSold(int|string $companyID, int $amount, int|string $counID, float $price): void
    {
        $comp = $this->getCompany($companyID);
        $this->exec('INSERT INTO market_sells (companyID, industryID, Stars, CountryID, Day, Amount, Price) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$companyID, $comp['IndustryID'], $comp['Stars'], $counID, $this->getToday(), $amount, $price]);
    }

    public function createCompany(string $cName, int|string $iName, int|string $managerID, int|string $regID): int
    {
        $ctx = $this->context();
        if (! ($ctx && $ctx->logged_in)) {
            return 0;
        }
        $oldAmount = $this->getCitizenMoney($managerID, 1);
        $price = (float) $this->getSetting('price_company');
        if ($oldAmount < $price) {
            return 0;
        }
        $this->updateUserMoney($managerID, 1, $oldAmount - $price);
        $companyID = $this->insertGetId('INSERT INTO company (Name, IndustryID, ManagerID, RegionID, Stock, Products, Stars) VALUES (?, ?, ?, ?, 0, 0, 1)',
            [$cName, $iName, $managerID, $regID]);
        $this->createCompanyCurrency($companyID, 1, 0);
        $this->createLicense($companyID, $this->getCountryID($regID), 0);

        return $companyID;
    }

    public function createCompanyLicense(int|string $compID, int|string $counID): void
    {
        $this->transferMoney(1, 10, $compID, 'company', '', '');
        $this->createLicense($compID, $counID, 1);
    }

    public function createLicense(int|string $compID, int|string $counID, $type): void
    {
        $this->exec('INSERT INTO company_license (CompanyID, CountryID, Type) VALUES (?, ?, ?)', [$compID, $counID, (int) $type]);
        $this->createCompanyCurrency($compID, $this->getCurrencyID($counID), 0);
    }

    public function createCompanyCurrency(int|string $compID, int|string $curID, float|int $amount): void
    {
        $this->exec('INSERT INTO company_money (CompID, CurID, Amount) VALUES (?, ?, ?)', [$compID, $curID, $amount]);
    }

    public function createProduct(int|string $ind, int|string $star, int|string $owner): void
    {
        $this->exec('INSERT INTO inventory (Type, Stars, Owner) VALUES (?, ?, ?)', [$ind, $star, $owner]);
    }

    public function deleteMOffer(int|string $offer): void
    {
        $row = $this->row('SELECT * FROM monetary WHERE mOfferID = ?', [$offer]);
        if ($row) {
            $this->addMoney($row['sCurID'], $row['Amount'], $row['sellerID'], $row['sellerType'], 1, '> Return money from MM <');
            $this->exec('DELETE FROM monetary WHERE mOfferID = ?', [$offer]);
        }
    }

    public function getAPC(int|string $countryID, int $day = 0): float
    {
        $day = $day ?: $this->getToday();
        $rows = $this->rows('SELECT market_sells.*, industry.Unit, (market_sells.Price/industry.Unit) AS avgPrice
            FROM market_sells JOIN industry ON market_sells.industryID = industry.IndustryID
            WHERE market_sells.Day <= ? AND market_sells.Day > ? AND market_sells.CountryID = ?', [$day, $day - 7, $countryID]);
        if (! $rows) {
            return 0;
        }
        $sum = array_sum(array_column($rows, 'avgPrice'));

        return round($sum / count($rows), 2);
    }

    public function getCompany(int|string $compID): ?array
    {
        return $this->row('SELECT company.*, region.CountryID, region.stat_cfactor, country.CurID, industry.xFactor
            FROM company JOIN region ON region.RegionID = company.RegionID
            JOIN country ON country.CountryID = region.CountryID
            JOIN industry ON industry.IndustryID = company.IndustryID
            WHERE company.CompanyID = ?', [$compID]);
    }

    public function getCompanyAccount(int|string $compID, int|string $curID = ''): ?array
    {
        if ($curID !== '' && $curID !== null) {
            return $this->row('SELECT * FROM company_money WHERE CompID = ? AND CurID = ?', [$compID, $curID]);
        }

        return $this->row('SELECT * FROM company_money WHERE CompID = ?', [$compID]);
    }

    public function getCompanyAccounts(int|string $compID): array
    {
        return $this->rows('SELECT company_money.*, country.curName FROM company_money
            JOIN country ON company_money.CurID = country.CountryID WHERE CompID = ?', [$compID]);
    }

    public function getCompanyJobOffers(int|string $compID, int|string $counID): array
    {
        return $this->rows('SELECT * FROM jobOffers WHERE CompanyID = ? AND CountryID = ? ORDER BY Amount', [$compID, $counID]);
    }

    public function getCompanyMoney(int|string $compID, int|string $curID = ''): array|float|int
    {
        $sql = 'SELECT CurID, Amount FROM company_money WHERE CompID = ?';
        $b = [$compID];
        if ($curID !== '' && $curID !== null) {
            $row = $this->row($sql.' AND CurID = ?', [$compID, $curID]);

            return $row ? (float) $row['Amount'] : 0;
        }
        $out = [];
        foreach ($this->rows($sql, $b) as $r) {
            $out[$r['CurID']] = (float) $r['Amount'];
        }

        return $out;
    }

    public function getCompanyOffers(int|string $compID, int|string $quality, int|string $counID): array
    {
        return $this->row('SELECT * FROM market WHERE CompanyID = ? AND CountryID = ? AND Quality = ?', [$compID, $counID, $quality])
            ?? ['Stock' => '0', 'Price' => '0', 'OfferID' => '-1'];
    }

    public function getCompanyStocksAll(int|string $compID): int
    {
        $stocks = (int) $this->value('SELECT Stock FROM company WHERE CompanyID = ?', [$compID], 0);
        foreach ($this->rows('SELECT Stock, CountryID FROM market WHERE CompanyID = ?', [$compID]) as $r) {
            $stocks += (int) $r['Stock'];
        }

        return $stocks;
    }

    public function getIndustry(int|string $indID): string|int
    {
        return $this->value('SELECT iName FROM industry WHERE IndustryID = ?', [$indID], 0);
    }

    public function getIndustryRec(int|string $indID): ?array
    {
        return $this->row('SELECT * FROM industry WHERE IndustryID = ?', [$indID]);
    }

    public function getIndustryIcon(int|string $indID, string $icons): string
    {
        $icon = $this->value('SELECT Icon FROM industry WHERE IndustryID = ?', [$indID]);

        return $icon === null ? $icons.'select.gif' : $icons.$icon.'.png';
    }

    /** Tax rows for a country (all industries) or one record when $retRec. */
    public function getIndustryTax(int|string $counID, int|string $indID = '', $retRec = ''): array|null
    {
        $sql = 'SELECT taxes.*, industry.* FROM taxes JOIN industry ON taxes.IndustryID = industry.IndustryID WHERE taxes.CountryID = ?';
        $b = [$counID];
        if ($indID !== '' && $indID !== null) {
            $sql .= ' AND taxes.IndustryID = ?';
            $b[] = $indID;
        }
        $sql .= ' ORDER BY taxes.IndustryID';
        $rows = $this->rows($sql, $b);
        if (! $rows) {
            return null;
        }

        return $retRec ? $rows[0] : $rows;
    }

    /** Job offers of a country; count when $start < 0. Returns [] when none. */
    public function getJobOffers(int|string $counID, int|string $quality, int $puberty, int $start = 0, int $count = 20): array|int
    {
        $sql = 'SELECT jobOffers.*, company.Stars, company.tool_e, company.tool_q, company.RegionID, country.curName
            FROM jobOffers JOIN company ON company.CompanyID = jobOffers.CompanyID
            JOIN country ON jobOffers.CountryId = country.CountryID WHERE country.CountryID = ?';
        $b = [$counID];
        if ($puberty == 0) {
            $sql .= ' AND company.Stars = 1';
        } elseif ($puberty == 1) {
            $sql .= ' AND company.Stars < 4';
        } elseif ($quality) {
            $sql .= ' AND company.Stars = ?';
            $b[] = $quality;
        }
        if ($start < 0) {
            return $this->count($sql, $b);
        }

        return $this->rows($sql.' ORDER BY Salary DESC LIMIT '.(int) $start.', '.(int) $count, $b);
    }

    public function getManager(int|string $comID): string
    {
        return (string) $this->value('SELECT citizens.name FROM citizens JOIN company ON citizens.CitizenID = company.ManagerID WHERE company.CompanyID = ?', [$comID], 'Suspended');
    }

    public function getManagerID(int|string $comID): int
    {
        return (int) $this->value('SELECT citizens.CitizenID FROM citizens JOIN company ON citizens.CitizenID = company.ManagerID WHERE company.CompanyID = ?', [$comID], 0);
    }

    public function getExchangeOffer(int|string $offerID): ?array
    {
        if ($offerID === '' || $offerID === null) {
            return null;
        }

        return $this->row('SELECT monetary.*, citizens.name FROM monetary JOIN citizens ON citizens.CitizenID = monetary.sellerID WHERE mOfferID = ?', [$offerID]);
    }

    public function getExchangeOffers(int|string $sellID, int|string $buyID, string $buyType = 'citizen'): array
    {
        if ($sellID === '' || $buyID === '') {
            return [];
        }
        if ($sellID !== 'my') {
            return $this->rows('SELECT monetary.*, citizens.name FROM monetary JOIN citizens ON monetary.sellerID = citizens.CitizenID
                WHERE monetary.sCurID = ? AND monetary.bCurID = ? ORDER BY monetary.eRate, monetary.timestamp', [$buyID, $sellID]);
        }
        if ($buyType === 'company') {
            return $this->rows("SELECT monetary.*, company.Name AS name FROM monetary JOIN company ON monetary.sellerID = company.CompanyID
                WHERE monetary.sellerID = ? AND monetary.sellerType = 'company' ORDER BY monetary.eRate, monetary.timestamp", [$buyID]);
        }

        return $this->rows("SELECT monetary.*, citizens.name FROM monetary JOIN citizens ON monetary.sellerID = citizens.CitizenID
            WHERE monetary.sellerID = ? AND monetary.sellerType = 'citizen' ORDER BY monetary.eRate, monetary.timestamp", [$buyID]);
    }

    public function getLicenses(int|string $compID, int|string $countryID): array
    {
        return $this->rows('SELECT company_license.ID, country.Flag, country.CountryID, country.cName,
                ROUND(IFNULL(taxes.VAT, 0) + IF(country.CountryID = ?, 0, IFNULL(taxes.Import, 0)), 0) / 100 AS tax
            FROM company_license JOIN country ON company_license.CountryID = country.CountryID
            JOIN company ON company.CompanyID = company_license.CompanyID
            LEFT JOIN taxes ON (taxes.CountryID = country.CountryID AND taxes.IndustryID = company.IndustryID)
            WHERE company.CompanyID = ? ORDER BY Type, ID', [$countryID, $compID]);
    }

    public function getMarketOffer(int|string $offerID): ?array
    {
        if ($offerID === '' || $offerID === null) {
            return null;
        }

        return $this->row('SELECT market.*, company.Stars, region.CountryID AS cCountryID FROM market
            LEFT JOIN company ON company.CompanyID = market.CompanyID
            LEFT JOIN region ON region.RegionID = company.RegionID WHERE OfferID = ?', [$offerID]);
    }

    public function getMarketOffers(int|string $industryID = '', int|string $starID = 0, int|string $countryID = '', int|string $companyID = ''): array
    {
        if ($industryID === '' || $industryID === null) {
            return [];
        }
        $sql = "SELECT market.*, company.Name, company.Stars, company.IndustryID, region.CountryID AS cCountryID
            FROM market LEFT JOIN company ON company.CompanyID = market.CompanyID
            LEFT JOIN region ON region.RegionID = company.RegionID WHERE market.Stock != '0'";
        $b = [];
        if ($countryID !== '' && $countryID !== null) {
            $sql .= ' AND market.CountryID = ?';
            $b[] = $countryID;
        }
        $sql .= ' AND company.IndustryID = ?';
        $b[] = $industryID;
        if ($starID) {
            $sql .= ' AND Quality = ?';
            $b[] = $starID;
        }
        if ($companyID) {
            $sql .= ' AND company.CompanyID = ?';
            $b[] = $companyID;
        }

        return $this->rows($sql.' ORDER BY market.tPrice, company.Stars, market.timestamp', $b);
    }

    public function getMarketOfferC(int|string $compID, int|string $counID): array
    {
        return $this->rows('SELECT * FROM market WHERE CompanyID = ? AND CountryID = ? ORDER BY Quality', [$compID, $counID]);
    }

    public function getOldStock(int|string $offerID): int
    {
        return (int) $this->value('SELECT Stock FROM market WHERE OfferID = ?', [$offerID], 0);
    }

    public function getTotStock(int|string $offerID, int|string $companyID): int
    {
        $cSt = (int) $this->value('SELECT Stock FROM company WHERE CompanyID = ?', [$companyID], 0);

        return $cSt + $this->getOldStock($offerID);
    }

    public function getWorkedRow(int|string $citID): int
    {
        $row = $this->row('SELECT LastWorked, rowWorkedStart FROM citizens WHERE CitizenID = ?', [$citID]);

        return $row ? ($row['LastWorked'] - $row['rowWorkedStart'] + 1) : 0;
    }

    public function getWorker(int|string $citID): ?array
    {
        return $this->row('SELECT company_workers.*, citizens.name, citizens.wSkill, citizens.wellness, citizens.LastWorked, citizens.RegionID
            FROM company_workers JOIN citizens ON company_workers.CitizenID = citizens.CitizenID WHERE company_workers.CitizenID = ?', [$citID]);
    }

    public function getWorkers(int|string $compID): array
    {
        return $this->rows('SELECT company_workers.*, citizens.*, region.rName AS RegionName, country.cName, country.curName
            FROM company_workers INNER JOIN citizens ON company_workers.CitizenID = citizens.CitizenID
            JOIN region ON citizens.regionID = region.RegionID JOIN country ON country.CountryID = region.CountryID
            WHERE company_workers.CompanyID = ? ORDER BY citizens.wSkill DESC', [$compID]);
    }

    public function getWorkingCompany(int|string $citID): int
    {
        $v = (int) $this->value('SELECT CompanyID FROM company_workers WHERE CitizenID = ?', [$citID], 0);

        return $v === -1 ? 0 : $v;
    }

    public function resignWorker(int|string $citID): void
    {
        $this->exec("UPDATE company_workers SET CompanyID = '-1' WHERE CitizenID = ?", [$citID]);
    }

    /** Legacy company-market offer (table company_market lacks Base/Step in the dump; kept for parity). */
    public function setCMarketOffer(int|string $compID, $base, $step, string $what = 'OfferID'): void
    {
        $exists = $this->row('SELECT * FROM company_market WHERE CompanyID = ?', [$compID]);
        if (! $exists) {
            $this->exec('INSERT INTO company_market (CompanyID, Price) VALUES (?, ?)', [$compID, (int) $base]);
        } else {
            $this->exec('UPDATE company_market SET Price = ? WHERE CompanyID = ?', [(int) $base, $compID]);
        }
    }

    /** Add/update a market offer. Returns 1 on success, or an error string. */
    public function setCompanyOffer(int|string $offerID, int|string $compID, int|string $quality, int|string $actCountry, $oAmount, $oPrice, $buy = ''): int|string
    {
        $tStamp = time();
        $totStock = $this->getTotStock($offerID, $compID);
        $comp = $this->getCompany($compID);
        $tPrice = $this->addTax2Price($comp['IndustryID'], $comp['CountryID'], $actCountry, (float) $oPrice);

        if ((string) $offerID !== '-1') {
            $exists = $this->row('SELECT * FROM market WHERE OfferID = ? AND CompanyID = ? AND Quality = ?', [$offerID, $compID, $quality]);
            if (! $exists) {
                return 'Cheating detected.';
            }
        }
        if ($totStock < $oAmount) {
            return "Desired amount is above maximum can held ({$totStock})";
        } elseif ($oAmount < 0) {
            return 'You cannot put negative amount';
        } elseif (! is_numeric($oPrice)) {
            return 'You can only use numbers and (.) in price field.';
        } elseif ($oPrice <= 0.01) {
            return 'The minimum price for market is 0.01';
        } elseif (floor($oAmount) != $oAmount) {
            return 'You must enter an integer';
        }

        if ((string) $offerID !== '-1') {
            if ($oAmount) {
                $this->exec('UPDATE market SET Stock = ?, Price = ?, tPrice = ?, timestamp = ? WHERE OfferID = ?', [$oAmount, $oPrice, $tPrice, $tStamp, $offerID]);
            } else {
                $this->exec('DELETE FROM market WHERE OfferID = ?', [$offerID]);
            }
        } else {
            $this->exec('INSERT INTO market (CompanyID, CountryID, Quality, Stock, Price, tPrice, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$compID, $actCountry, $quality, $oAmount, $oPrice, $tPrice, $tStamp]);
        }
        if (! $buy) {
            $this->updateCompanyField($compID, 'Stock', $totStock - $oAmount);
        }

        return 1;
    }

    public function setExchangeOffer(int|string $offerID, $oAmount, $eRate, int|string $sellerID = '', string $sellerType = '', int|string $sCurID = '', int|string $bCurID = ''): int
    {
        $tStamp = time();
        if ($offerID) {
            $this->exec('UPDATE monetary SET Amount = ?, eRate = ?, timestamp = ? WHERE mOfferID = ?', [$oAmount, $eRate, $tStamp, $offerID]);
        } else {
            $this->exec('INSERT INTO monetary (sellerID, sellerType, sCurID, bCurID, Amount, eRate, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$sellerID, $sellerType, $sCurID, $bCurID, $oAmount, $eRate, $tStamp]);
        }

        return 1;
    }

    public function setJOffer(int|string $compID, int|string $counID, int $wAmount, float $wSalary): int
    {
        return $this->exec('INSERT INTO jobOffers (CompanyID, CountryID, Amount, Salary) VALUES (?, ?, ?, ?)', [$compID, $counID, $wAmount, $wSalary]) ? 1 : 0;
    }

    public function setMarketOffers(int|string $counID, int|string $indID): void
    {
        $rows = $this->rows("SELECT market.*, company.IndustryID, region.CountryID AS cCountryID FROM market
            JOIN company ON market.CompanyID = company.CompanyID JOIN region ON company.RegionID = region.RegionID
            WHERE market.CountryID = ? AND company.IndustryID = ? AND market.Stock > '0'", [$counID, $indID]);
        foreach ($rows as $row) {
            $tPrice = $this->addTax2Price($indID, $row['cCountryID'], $counID, (float) $row['Price']);
            $this->exec('UPDATE market SET tPrice = ? WHERE OfferID = ?', [$tPrice, $row['OfferID']]);
        }
    }

    public function setSalary(int|string $citID, float $salary): int
    {
        $this->exec('UPDATE company_workers SET Salary = ? WHERE CitizenID = ?', [$salary, $citID]);

        return 1;
    }

    public function transferCompany(array $comp, $old = null, $new = null): void
    {
        $compID = $comp['CompanyID'];
        $old = $comp['ManagerID'];
        $new = $comp['sale_bid_id'];
        $price = $comp['sale_bid_amount'];
        $compN = $comp['Name'];
        $link = '<a href=\''.$this->url('company', $compID)."'>{$compN}</a>";
        if ($new && $price) {
            $this->addMoney(1, $price, $old, 'citizen', 1);
            $this->sendNote($old, '', "Your company {$link} has been sold for {$price} Tala and the amount appeared in your account.");
            $this->sendNote($new, '', "Your bid was the highest bid for company {$link}  and this company is for you!");
            $this->updateCompanyField($compID, 'ManagerID', $new);
        } else {
            $this->sendNote($old, '', "Your company {$link} didn't have any bids during a week and has been just removed from company market.");
        }
        foreach (['sale_base', 'sale_step', 'sale_due', 'sale_bid_id', 'sale_bid_amount'] as $f) {
            $this->updateCompanyField($compID, $f, 0);
        }
    }

    /** WORK — the daily work action. */
    public function work(array $cit, array $comp, int $type, array $foods): array
    {
        $citID = $cit['CitizenID'];
        $compID = $comp['CompanyID'];

        $formula = $this->economy()->getProduct4Work($cit, $comp, $type, 1);
        $Prod = $formula['Prod'];
        $A = $formula['A'];
        $A2 = $formula['A2'];
        $B = $formula['B'];
        $B2 = $formula['B2'];
        $C = $formula['C'];

        $sum = $this->consumeFoods($citID, $foods);
        $wChange = round($cit['wellness'] - $B2, 2);
        if ($wChange < $sum) {
            $sum = $wChange;
        }
        $B2 += $sum;

        $this->addProduct($comp, $Prod);

        // Company tool depreciation
        $CA = $A;
        $CB = 1.01 - $cit['wellness'] / 100;
        $CC = $comp['tool_q'] ? 1 / $comp['tool_q'] : 1;
        $CD = 2 - ($comp['tool_e'] / 100);
        $CE = ($CA > 4) ? (4 / $CA) : 1;
        $cDep = $CA * $CB * $CC * $CD * $CE;
        $TE = max(0, $comp['tool_e'] - $cDep);
        $this->updateCompanyField($compID, 'tool_e', $TE);

        if ($TE < 50) {
            $clink = '<a href="'.$this->url('company', $compID).'">'.$comp['Name'].'</a>';
            if ($comp['tool_a']) {
                $this->updateCompanyField($compID, 'tool_q', $comp['tool_a']);
                $this->updateCompanyField($compID, 'tool_e', 100);
                $this->updateCompanyField($compID, 'tool_a', 0);
                $this->sendNote($comp['ManagerID'], '', "The efficiency of the tool in your company {$clink} reached 0 and your alternate tool has been replaced automatically. Please buy a new alternate tool for your company as soon as possible!");
            } else {
                $this->sendNote($comp['ManagerID'], '', "The efficiency of the tool in your company {$clink} is now {$TE}%, means that your company loses more than a half of it's products. Please buy/install a new tool for your company as soon as possible!");
            }
        }

        // Salary
        $salary = (float) $cit['Salary'];
        $cur = $cit['SalaryCurID'];
        $tSalary = ($Prod >= 1) ? $Prod : 1;
        $salary = round($salary * $tSalary, 2);
        $taxes = $this->getIndustryTax($comp['CountryID'], $comp['IndustryID'], 1);
        $tax = $taxes ? (float) $taxes['Income'] : 0;
        $amount2 = $salary * ($tax / 100);
        $amount1 = $salary - $amount2;
        $this->transferMoney($cur, $amount1, $compID, 'company', $citID, 'citizen', 1, "> # {$citID} Worked in # {$compID} <");
        $this->transferMoney($cur, $amount2, $compID, 'company', $comp['CountryID'], 'country', 1, "> # {$citID} Working income tax <");

        $EP2 = ((string) $cit['LastWorked'] === '-1') ? 3 : 1;
        $this->addEP($citID, $EP2, 'Working');
        $this->updateUserFieldID($citID, 'LastWorked', $this->today);
        $this->updateUserFieldID($citID, 'rowWorkedStart', $cit['rowWorkedStart'] + 1);

        $wSP = $cit['wSP'] + $A2;
        $wSkill = $cit['wSkill'];
        $newSkill = $wSkill;
        if ((Constants::SP_CPS[$wSkill + 1] ?? PHP_INT_MAX) < $wSP) {
            $newSkill++;
        }
        $this->exec('UPDATE citizens SET wSP = ?, wSkill = ? WHERE CitizenID = ?', [$wSP, $newSkill, $citID]);
        $this->updateUserFieldID($citID, 'wellness', round($B2));

        $this->exec('INSERT INTO log_working (CitizenID, CompanyID, Day, timestamp, type, wellness, skill, ep, products, tooldec, salary, formula_base, formula_cfactor, formula_impind, tax, curID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$citID, $compID, $this->today, time(), (string) $type, "{$cit['wellness']}|{$wChange}|{$sum}", "{$wSkill}|{$wSP}|{$A2}", "{$cit['ep']}|{$EP2}",
                $Prod, $cDep, $amount1, $formula['Formula']['Base'], $formula['Formula']['CFactor'], $formula['Formula']['Goddess'], $amount2, $cur]);

        $ctx = $this->context();
        if ($ctx) {
            $ctx->updateInfo(['LastWorked' => $this->today, 'rowWorkedStart' => $cit['rowWorkedStart'] + 1, 'wSP' => $wSP, 'wSkill' => $newSkill, 'wellness' => round($B2)]);
        }

        return [
            'Formula' => "Skill ({$A}) * Wellness(".round($B, 2).') * Company Stars('.round($C, 2).') = '.round($Prod, 2),
            'Product' => "$Prod",
            'Type' => (50 + $type * 50).'%',
            'Stars' => (string) $comp['Stars'],
            'Logo' => (string) $comp['Avatar'],
            'Skill' => "$A2",
            'Well' => "$B2",
            'EP' => "$EP2",
            'Salary' => "$amount1",
        ];
    }

    public function hasJOffer(int|string $compID, int|string $counID, float $wSalary): int
    {
        return $this->count('SELECT joID FROM jobOffers WHERE CompanyID = ? AND CountryID = ? AND Salary = ?', [$compID, $counID, $wSalary]) ? 1 : 0;
    }

    public function hasLicense(int|string $compID, int|string $counID): int
    {
        return $this->count('SELECT ID FROM company_license WHERE CompanyID = ? AND CountryID = ?', [$compID, $counID]) ? 1 : 0;
    }

    public function isManager(int|string $CitID): int
    {
        return $this->count('SELECT CompanyID FROM company WHERE ManagerID = ?', [$CitID]) ? 1 : 0;
    }

    public function isManagerThis(int|string $citID, int|string $compID): int
    {
        return $this->count('SELECT ManagerID FROM company WHERE ManagerID = ? AND CompanyID = ?', [$citID, $compID]) ? 1 : 0;
    }

    public function isWorkedAlready(int|string $citID): int
    {
        $lw = $this->value('SELECT LastWorked FROM citizens WHERE CitizenID = ?', [$citID]);

        return (! $lw || (string) $lw === '-1') ? 0 : 1;
    }

    public function isWorker(int|string $citID, int|string $compID = '', $ignore = ''): int
    {
        $sql = 'SELECT wID FROM company_workers WHERE CitizenID = ?';
        $b = [$citID];
        if ($compID !== '' && $compID !== null) {
            $sql .= ' AND CompanyID = ?';
            $b[] = $compID;
        } else {
            $sql .= " AND CompanyID != '0'";
        }
        if (! $ignore) {
            $sql .= " AND CompanyID != '-1'";
        }

        return $this->count($sql, $b) ? 1 : 0;
    }

    /* Currencies & country treasuries */

    public function getCurrencyID(int|string $counID): int
    {
        return (int) $counID;
    }

    public function getCurrency(int|string $curID): string|int
    {
        return $this->value('SELECT curName FROM country WHERE CountryID = ?', [$curID], 0);
    }

    public function getCurrencyIco(int|string $curID, string $flags): string
    {
        $flag = $this->value('SELECT Flag FROM country WHERE CountryID = ?', [$curID]);
        if ($flag === null) {
            return (string) $curID;
        }
        $flag = ((int) $curID === 1) ? 'Tala' : $flag;

        return rtrim($flags, '/').'/'.$flag.'.gif';
    }

    public function getCountryAccount(int|string $counID, int|string $curID): ?array
    {
        return $this->row('SELECT * FROM country_money WHERE CounID = ? AND CurID = ?', [$counID, $curID]);
    }

    public function getCountryAccounts(int|string $counID): array
    {
        return $this->rows('SELECT ID, CounID, ROUND(CurID, 2) AS CurID, Amount FROM country_money WHERE CounID = ?', [$counID]);
    }

    public function getCountryFee(int|string $counID): float
    {
        return (float) $this->value('SELECT cFee FROM country WHERE CountryID = ?', [$counID], 0);
    }
}
