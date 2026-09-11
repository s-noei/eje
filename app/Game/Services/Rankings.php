<?php

namespace App\Game\Services;

/**
 * Port of include/ranking.php ($ranks).
 */
class Rankings
{
    public function __construct(protected GameDatabase $database)
    {
    }

    public function getCitizenNRank(int|string $cit, int|string $coun = ''): int
    {
        $co = 0;
        foreach ($this->rankCitizens($coun ?: 0) as $row) {
            $co++;
            if ((int) $row['CitizenID'] === (int) $cit) {
                return $co;
            }
        }

        return 0;
    }

    public function getCountryRank(int|string $coun): int
    {
        $co = 0;
        foreach ($this->rankCountries() as $row) {
            $co++;
            if ((int) $row['CountryID'] === (int) $coun) {
                return $co;
            }
        }

        return 0;
    }

    public function getRankCount(string $what, $p1): int
    {
        return count(match ($what) {
            'citizen' => $this->rankCitizens($p1),
            'country' => $this->rankCountries($p1 ?: 1),
            'newspaper' => $this->rankNPs($p1),
            'party' => $this->rankParties($p1),
            default => [],
        });
    }

    public function rankCitizens(int|string $coun = 0, int $start = 0, int $amount = 0): array
    {
        $sql = "SELECT citizens.CitizenID, citizens.ep, citizens.name, citizens.Avatar, country.cName, country.Flag, country.CountryID
            FROM country LEFT JOIN region ON country.CountryID = region.CountryID
            JOIN citizens ON citizens.regionID = region.RegionID
            WHERE citizens.accType = 'citizen' AND citizens.ban_due != 'PERMANENTLY' AND citizens.wellness > '0'";
        $b = [];
        if ($coun) {
            $sql .= ' AND country.CountryID = ?';
            $b[] = $coun;
        }
        $sql .= ' ORDER BY citizens.ep DESC';
        if ($amount) {
            $sql .= ' LIMIT '.(int) $start.', '.(int) $amount;
        }

        return $this->database->rows($sql, $b);
    }

    public function rankCountries(int|string $by = 1, int $start = 0, int $amount = 0): array
    {
        $sql = match ((int) $by) {
            2 => 'SELECT country.cName, country.CountryID, country.flag, stat_country.pop AS POP FROM country
                LEFT JOIN stat_country ON stat_country.CountryID = country.CountryID ORDER BY POP DESC',
            3 => "SELECT country.cName, country.CountryID, country.flag, ROUND(AVG(citizens.mSkill), 2) AS mSkill FROM country
                LEFT JOIN region ON region.CountryID = country.CountryID LEFT JOIN citizens ON citizens.regionID = region.RegionID
                WHERE citizens.ban_due != 'PERMANENTLY' AND citizens.wellness > '0' GROUP BY country.CountryID ORDER BY mSkill DESC",
            4 => "SELECT country.cName, country.CountryID, country.flag, ROUND(AVG(citizens.wSkill), 2) AS wSkill FROM country
                LEFT JOIN region ON region.CountryID = country.CountryID LEFT JOIN citizens ON citizens.regionID = region.RegionID
                WHERE citizens.ban_due != 'PERMANENTLY' AND citizens.wellness > '0' GROUP BY country.CountryID ORDER BY wSkill DESC",
            default => 'SELECT country.cName, country.CountryID, country.flag, stat_country.ep AS EP, stat_country.avgEP as avgEP FROM country
                LEFT JOIN stat_country ON stat_country.CountryID = country.CountryID GROUP BY country.CountryID ORDER BY EP DESC, avgEP DESC',
        };
        if ($amount) {
            $sql .= ' LIMIT '.(int) $start.', '.(int) $amount;
        }

        return $this->database->rows($sql);
    }

    public function rankParties(int|string $coun = 0, int $start = 0, int $amount = 0): array
    {
        $sql = 'SELECT party.pName, country.cName, country.Flag, party.CountryID, party_members.PartyID, SUM(citizens.ep) AS EP, COUNT(party_members.PartyID) AS POP
            FROM party_members JOIN citizens ON citizens.CitizenID = party_members.CitizenID
            JOIN party ON party.pID = party_members.PartyID JOIN country ON country.CountryID = party.CountryID';
        $b = [];
        if ($coun) {
            $sql .= ' WHERE party.CountryID = ?';
            $b[] = $coun;
        }
        $sql .= ' GROUP BY party_members.PartyID ORDER BY EP DESC';
        if ($amount) {
            $sql .= ' LIMIT '.(int) $start.', '.(int) $amount;
        }

        return $this->database->rows($sql, $b);
    }

    public function rankNPs(int|string $coun = 0, int $start = 0, int $amount = 0): array
    {
        $sql = 'SELECT np_details.*, COUNT(np_subs.npID) AS Subs, country.cName, country.Flag FROM np_details
            JOIN np_subs ON np_details.npID = np_subs.npID JOIN country ON country.CountryID = np_details.CountryID';
        $b = [];
        if ($coun) {
            $sql .= ' WHERE np_details.CountryID = ?';
            $b[] = $coun;
        }
        $sql .= ' GROUP BY np_details.npID ORDER BY Subs DESC';
        if ($amount) {
            $sql .= ' LIMIT '.(int) $start.', '.(int) $amount;
        }

        return $this->database->rows($sql, $b);
    }
}
