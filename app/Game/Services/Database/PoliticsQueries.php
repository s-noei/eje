<?php

namespace App\Game\Services\Database;

/**
 * Parties, congress candidates, military units (port of the politics part of MySQLDB).
 */
trait PoliticsQueries
{
    public function createParty(string $pName, $pOrientE, $pOrientS, int|string $ppID, int|string $CounID): int
    {
        $ctx = $this->context();
        if (! ($ctx && $ctx->logged_in)) {
            return 0;
        }
        $oldAmount = $this->getCitizenMoney($ppID, 1);
        $price = (float) $this->getSetting('price_party');
        if ($oldAmount < $price) {
            return 0;
        }
        $this->updateUserMoney($ppID, 1, $oldAmount - $price);
        $pID = $this->insertGetId("INSERT INTO party (pName, eOrient, sOrient, pLogo, PP, CountryID) VALUES (?, ?, ?, 'noavatar.gif', ?, ?)",
            [$pName, (int) $pOrientE, (int) $pOrientS, $ppID, $CounID]);
        $this->politics()->joinParty($pID, $ppID);

        return $pID;
    }

    public function createMilitaryUnit(string $mName, int|string $mOwner, int|string $CounID): int
    {
        $ctx = $this->context();
        if (! ($ctx && $ctx->logged_in)) {
            return 0;
        }
        $oldAmount = $this->getCitizenMoney($mOwner, 1);
        if ($oldAmount < 50) {
            return 0;
        }
        $this->updateUserMoney($mOwner, 1, $oldAmount - 50);
        $mID = $this->insertGetId("INSERT INTO military_unit (mName, mOwner, mCountryID, mCommander, mLogo, mMembers, timestamp) VALUES (?, ?, ?, ?, 'noavatar.gif', 1, ?)",
            [$mName, $mOwner, $CounID, $mOwner, time()]);
        $this->updateUserFieldID($mOwner, 'military_unit', $mID);

        return $mID;
    }

    public function updateMilitaryUnitField(int|string $mID, string $field, mixed $value): int
    {
        return $this->exec('UPDATE military_unit SET '.$this->col($field).' = ? WHERE mID = ?', [$value, $mID]);
    }

    public function updatePartyField(int|string $partyid, string $field, mixed $value): int
    {
        return $this->exec('UPDATE party SET '.$this->col($field).' = ? WHERE pID = ?', [$value, $partyid]);
    }

    public function getParty(int|string $pID): ?array
    {
        return $this->row('SELECT * FROM party WHERE pID = ?', [$pID]);
    }

    public function getCongressCandidates(int|string $pID, int|string $rID = '', $view = ''): array
    {
        if ($view) {
            if (! $rID) {
                return [];
            }

            return $this->rows('SELECT citizens.name, citizens.CitizenID, elections_cg_candidates.*, region.stat_pop
                FROM elections_cg_candidates JOIN party ON elections_cg_candidates.PartyID = party.pID
                JOIN citizens ON elections_cg_candidates.CitizenID = citizens.CitizenID
                JOIN region ON elections_cg_candidates.RegionID = region.RegionID
                WHERE elections_cg_candidates.PartyID = ? AND elections_cg_candidates.RegionID = ? ORDER BY elections_cg_candidates.Order', [$pID, $rID]);
        }
        $sql = 'SELECT citizens.*, elections_cg_candidates.* FROM elections_cg_candidates
            JOIN party ON elections_cg_candidates.PartyID = party.pID
            JOIN citizens ON elections_cg_candidates.CitizenID = citizens.CitizenID
            WHERE elections_cg_candidates.PartyID = ?';
        $b = [$pID];
        if ($rID !== '' && $rID !== null) {
            $sql .= ' AND elections_cg_candidates.RegionID = ?';
            $b[] = $rID;
        }

        return $this->rows($sql.' ORDER BY elections_cg_candidates.Order', $b);
    }

    public function getPartyLogo(int|string $PartyID, string $avaLoc): string|int
    {
        $logo = $this->value('SELECT pLogo FROM party WHERE pID = ?', [$PartyID]);
        if ($logo === null) {
            return -1;
        }

        return $avaLoc.($logo === '' ? 'noavatar.gif' : $logo);
    }

    public function getPartyCandidates(int|string $pID): array
    {
        return $this->rows('SELECT citizens.*, elections_pp_candidates.* FROM elections_pp_candidates
            JOIN party ON elections_pp_candidates.PartyID = party.pID
            JOIN citizens ON elections_pp_candidates.CitizenID = citizens.CitizenID
            WHERE elections_pp_candidates.PartyID = ? ORDER BY citizens.ep DESC', [$pID]);
    }

    public function getPartyMembers(int|string $pID): array
    {
        return $this->rows('SELECT party_members.*, citizens.name, citizens.Avatar, citizens.ep, party.PP, congressmen.cgCountryID, citizens.accType,
                region.rName AS RegionName, country.cName
            FROM party_members JOIN party ON party_members.PartyID = party.pID
            JOIN citizens ON party_members.CitizenID = citizens.CitizenID
            JOIN region ON region.RegionID = citizens.regionID
            JOIN country ON country.CountryID = region.CountryID
            LEFT JOIN congressmen ON party_members.CitizenID = congressmen.cgCitizenID
            WHERE party_members.PartyID = ? ORDER BY citizens.ep DESC', [$pID]);
    }

    public function isCGCandidate(int|string $CitID): int
    {
        return $this->count('SELECT cID FROM elections_cg_candidates WHERE CitizenID = ?', [$CitID]) ? 1 : 0;
    }

    public function isMember(int|string $cit, int|string $party): int
    {
        return $this->count('SELECT CitizenID FROM party_members WHERE CitizenID = ? AND PartyID = ?', [$cit, $party]) ? 1 : 0;
    }

    public function isPP(int|string $cit, int|string $party = ''): int
    {
        $sql = 'SELECT PP FROM party WHERE PP = ?';
        $b = [$cit];
        if ($party !== '' && $party !== null) {
            $sql .= ' AND pID = ?';
            $b[] = $party;
        }

        return $this->count($sql, $b) ? 1 : 0;
    }

    public function isPPCandidate(int|string $CitID): int
    {
        return $this->count('SELECT cID FROM elections_pp_candidates WHERE CitizenID = ?', [$CitID]) ? 1 : 0;
    }
}
