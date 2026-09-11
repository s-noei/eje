<?php

namespace App\Game\Services\Database;

/**
 * Countries and regions (port of the geography part of MySQLDB).
 */
trait GeoQueries
{
    /** Countries with their translated name ("Name") in the current language. */
    public function getCountries($admin = 0, string $sort = 'Name'): array
    {
        $lang = $this->translator()->lang;
        $langCol = in_array($lang, config('ejahan.languages'), true) ? 'trans_'.$lang : 'trans_en';
        $sql = "SELECT country.*, IFNULL(trans_strings.{$langCol}, trans_strings.trans_en) AS Name
            FROM country JOIN trans_strings ON trans_strings.phrase = country.shortName
            WHERE trans_strings.location = 'country'";
        if (! $admin) {
            $sql .= " AND Hidden = '0'";
        }
        $sort = in_array($sort, ['Name', 'cName', 'CountryID', 'Continent'], true) ? $sort : 'Name';

        return $this->rows($sql.' ORDER BY '.$sort);
    }

    public function getCountry(int|string $regID): string|int
    {
        return $this->value('SELECT country.cName FROM region JOIN country ON region.CountryID = country.CountryID WHERE region.RegionID = ?', [$regID], 0);
    }

    public function getCountryC(int|string $counID): string|int
    {
        return $this->value('SELECT cName FROM country WHERE CountryID = ?', [$counID], 0);
    }

    public function getCountryID(int|string $regID): int
    {
        return (int) $this->value('SELECT country.CountryID FROM region JOIN country ON region.CountryID = country.CountryID WHERE region.RegionID = ?', [$regID], 0);
    }

    public function getCountryIDC(string $cName): int
    {
        return (int) $this->value('SELECT CountryID FROM country WHERE cName = ?', [$cName], 0);
    }

    public function getCountryFlag(int|string $regID, string $flags): string|int
    {
        $f = $this->value('SELECT country.Flag FROM region JOIN country ON region.CountryID = country.CountryID WHERE region.RegionID = ?', [$regID]);

        return $f === null ? 0 : $flags.$f.'.gif';
    }

    public function getCountryFlagC(int|string $counID, string $flags): string|int
    {
        $f = $this->value('SELECT Flag FROM country WHERE CountryID = ?', [$counID]);

        return $f === null ? 0 : $flags.$f.'.gif';
    }

    public function getCountryPop(int|string $counID): int
    {
        return (int) $this->value("SELECT COUNT(citizens.regionID) AS POP FROM citizens JOIN region ON citizens.regionID = region.RegionID
            WHERE region.CountryID = ? AND wellness > '0' AND ban_due != 'PERMANENTLY' AND accType = 'citizen' GROUP BY region.CountryID", [$counID], 0);
    }

    public function getCountryRec(int|string $counID): ?array
    {
        return $this->row('SELECT * FROM country WHERE CountryID = ?', [$counID]);
    }

    public function getRegion(int|string $regID): string|int
    {
        return $this->value('SELECT rName FROM region WHERE RegionID = ?', [$regID], 0);
    }

    public function getRegionRec(int|string $regID): ?array
    {
        return $this->row('SELECT * FROM region WHERE RegionID = ?', [$regID]);
    }

    public function getRegionPop(int|string $regID): int
    {
        return $this->count("SELECT regionID FROM citizens WHERE RegionID = ? AND wellness > '0' AND ban_due != 'PERMANENTLY' AND accType = 'citizen'", [$regID]);
    }

    public function isNeighbor(int|string $regID1, int|string $regID2): int
    {
        if ((int) $regID1 === (int) $regID2) {
            return 1;
        }

        return $this->count('SELECT nID FROM neighbors WHERE Region1 = ? AND Region2 = ?', [$regID1, $regID2]) ? 1 : 0;
    }

    public function isHiddenCountry(int|string $counID): int
    {
        return $this->count("SELECT Hidden FROM country WHERE CountryID = ? AND Hidden = '1' LIMIT 1", [$counID]);
    }
}
