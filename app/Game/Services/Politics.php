<?php

namespace App\Game\Services;

use App\Game\Support\Constants;
use App\Game\Support\Url;

/**
 * Port of include/politics.php ($politics).
 */
class Politics
{
    public function __construct(protected GameDatabase $database, protected Url $url)
    {
    }

    public function addLaw(int|string $by, int|string $coun, string $type, string $param, string $debate, int $pTime = 0): int
    {
        $pTime = $pTime ?: time();
        $dTime = $pTime + 86400;
        $lawID = $this->database->insertGetId('INSERT INTO laws (byID, CountryID, Type, Params, pTime, dTime, Debate) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$by, $coun, $type, $param, $pTime, $dTime, substr($debate, 0, 50)]);
        $this->sendNoteCGs($coun, 'A new law is open for voting and debate in congress. To vote or debate, come to <a href="'.$this->url->getURL('law', $lawID).'">this location</a>.');

        return $lawID;
    }

    public function arrangeCGCandidates(int|string $partyID): void
    {
        $order = 0;
        foreach ($this->database->getCongressCandidates($partyID) as $cg) {
            $order++;
            $this->database->exec('UPDATE elections_cg_candidates SET `Order` = ? WHERE CitizenID = ? AND PartyID = ? AND RegionID = ?',
                [$order, $cg['CitizenID'], $cg['PartyID'], $cg['RegionID']]);
        }
    }

    public function beCandidateCG(array $cit, int|string $partyID, string $docURL): void
    {
        $this->database->exec('INSERT INTO elections_cg_candidates (CitizenID, PartyID, RegionID, DocURL, `Order`) VALUES (?, ?, ?, ?, 9999)',
            [$cit['CitizenID'], $partyID, $cit['regionID'], substr($docURL, 0, 40)]);
        $this->arrangeCGCandidates($partyID);
    }

    public function beCandidatePP(int|string $citID, int|string $partyID): void
    {
        $this->database->transferMoney(1, 1, $citID, 'citizen', $partyID, 'party', 1, "Became PP candidate of party {$partyID}");
        $this->database->exec('INSERT INTO elections_pp_candidates (CitizenID, PartyID) VALUES (?, ?)', [$citID, $partyID]);
    }

    public function beCG(array $cong): void
    {
        $cID = $cong['CandidateID'];
        $this->database->exec('INSERT INTO congressmen (cgCitizenID, cgPartyID, cgCountryID) VALUES (?, ?, ?)', [$cID, $cong['PartyID'], $cong['CountryID']]);
        $this->database->sendNote($cID, 'beCG', "{$cong['RegionID']}|{$cong['PartyID']}|{$cong['pName']}");
        $this->database->addMedal($cID, 'cg');
        $this->database->addEP($cID, 20, 'Being Congressman');
    }

    public function getCountryCGQualifies(int|string $counID): array
    {
        $row = $this->database->row('SELECT SUM(IF(region.stat_pop >= 100, 1, 0)) AS nP,
                SUM(IF(region.stat_pop < 100 AND region.stat_pop >= 10, 1, 0)) AS nN,
                SUM(IF(region.stat_pop < 10, 1, 0)) AS nD FROM region WHERE CountryID = ?', [$counID]) ?? ['nP' => 0, 'nN' => 0, 'nD' => 0];
        $row = array_map('intval', $row);
        $row['xP'] = ($row['nP'] < 5) ? 5 : (($row['nP'] == 5) ? 4 : (($row['nP'] < 8) ? 3 : 2));
        $row['xN'] = ($row['nN'] <= 10) ? 2 : 1;
        $row['xD'] = ($row['nD'] <= 10) ? $row['nD'] : 10;
        $totCands = $row['xP'] * $row['nP'] + $row['xN'] * $row['nN'] + $row['xD'];
        $row['xW'] = 10 - ($totCands % 10);
        if ($row['xD'] == 20) {
            $row['xD'] = 10;
        }
        if ($this->database->count('SELECT RegionID FROM region WHERE CountryID = ?', [$counID]) === 1) {
            $row['xC'] = 10;
        }

        return $row;
    }

    public function getCountryCGMembers(int|string $counID): array
    {
        return $this->database->rows('SELECT congressmen.*, party.*, COUNT(congressmen.cgCitizenID) AS CGCount FROM congressmen
            JOIN party_members ON party_members.CitizenID = congressmen.cgCitizenID
            LEFT JOIN party ON party_members.PartyID = party.pID
            WHERE congressmen.cgCountryID = ? GROUP BY party.pID', [$counID]);
    }

    public function getCP(int|string $coun, $det = 0): ?array
    {
        if (! $det) {
            return $this->database->row('SELECT citizens.* FROM country JOIN citizens ON country.cpID = citizens.CitizenID WHERE country.CountryID = ?', [$coun]);
        }

        return $this->database->row('SELECT citizens.*, region.rName AS RegionName, resident.cName FROM country
            JOIN citizens ON country.cpID = citizens.CitizenID
            JOIN region ON region.RegionID = citizens.regionID
            JOIN country AS resident ON resident.CountryID = region.CountryID
            WHERE country.CountryID = ?', [$coun]);
    }

    /** @return array{YES:int,NO:int,NA:int} */
    public function getVotes(int|string $lawID): array
    {
        $votes = ['YES' => 0, 'NO' => 0, 'NA' => 0];
        foreach ($this->database->rows('SELECT Vote, COUNT(Vote) AS TotVotes FROM law_votes WHERE LawID = ? GROUP BY Vote', [$lawID]) as $r) {
            $votes[$r['Vote']] = (int) $r['TotVotes'];
        }

        return $votes;
    }

    public function getLastLaw(int|string $coun, string $type = ''): ?array
    {
        $sql = 'SELECT * FROM laws WHERE CountryID = ?';
        $b = [$coun];
        if ($type !== '') {
            $sql .= ' AND Type = ?';
            $b[] = $type;
        }

        return $this->database->row($sql.' ORDER BY dTime DESC LIMIT 1', $b);
    }

    public function getLaw(int|string $lawID): ?array
    {
        return $this->database->row('SELECT laws.*, citizens.name AS byName, country.cName FROM laws
            JOIN citizens ON laws.byID = citizens.CitizenID JOIN country ON laws.CountryID = country.CountryID WHERE laws.lawID = ?', [$lawID]);
    }

    /** Laws of a country (paged) or the count when $start < 0. */
    public function getLaws(int|string $counID, int $start = 0, int $count = 20): array|int
    {
        $sql = 'SELECT laws.*, citizens.name AS byName FROM laws JOIN citizens ON laws.byID = citizens.CitizenID WHERE laws.CountryID = ? ORDER BY laws.dTime DESC';
        if ($start < 0) {
            return $this->database->count($sql, [$counID]);
        }

        return $this->database->rows($sql.' LIMIT '.(int) $start.', '.(int) $count, [$counID]);
    }

    /** Laws proposed by a citizen since the current congress term started. */
    public function getLawsBy(int|string $citID): array
    {
        $now = app(GameContext::class)->getTodayArray();
        $sDay = Constants::ELECTIONS_CG_DAY + 1;
        if ($now['Day'] < $sDay) {
            $sMonth = $now['Month'] - 1;
            $sYear = $now['Year'];
            if ($sMonth < 1) {
                $sMonth = 12;
                $sYear--;
            }
        } else {
            $sMonth = $now['Month'];
            $sYear = $now['Year'];
        }
        $sTime = mktime(0, 0, 0, $sMonth, $sDay, $sYear);

        return $this->database->rows('SELECT * FROM laws WHERE byID = ? AND dTime >= ? ORDER BY dTime', [$citID, $sTime]);
    }

    public function getPartyCGMembers(int|string $partyID, $origin = 1): int
    {
        if ($origin) {
            return $this->database->count('SELECT cgID FROM congressmen WHERE cgPartyID = ?', [$partyID]);
        }

        return $this->database->count('SELECT party_members.PartyID FROM congressmen JOIN party_members ON party_members.CitizenID = congressmen.cgCitizenID WHERE party_members.PartyID = ?', [$partyID]);
    }

    public function isCP(int|string $citID, int|string $counID = ''): int
    {
        $sql = 'SELECT CountryID FROM country WHERE cpID = ?';
        $b = [$citID];
        if ($counID !== '' && $counID !== null) {
            $sql .= ' AND CountryID = ?';
            $b[] = $counID;
        }

        return $this->database->count($sql, $b) ? 1 : 0;
    }

    public function isPartyMember(int|string $cit, int|string $part): int
    {
        return $this->database->count('SELECT ID FROM party_members WHERE CitizenID = ? AND PartyID = ? LIMIT 0, 1', [$cit, $part]);
    }

    public function isVoted(int|string $citID, int|string $lawID): int
    {
        return $this->database->count('SELECT voteID FROM law_votes WHERE CitizenID = ? AND LawID = ?', [$citID, $lawID]) ? 1 : 0;
    }

    public function joinParty(int|string $party, int|string $citizen): void
    {
        $this->database->exec('INSERT INTO party_members (PartyID, CitizenID, timestamp) VALUES (?, ?, ?)', [$party, $citizen, time()]);
        if ($this->database->count("SELECT pID FROM party WHERE pID = ? AND PP != '' AND PP != 0", [$party]) < 1) {
            $this->database->exec('UPDATE party SET PP = ? WHERE pID = ?', [$citizen, $party]);
        }
    }

    public function resignCG(int|string $citID, int|string $counID): void
    {
        $cit = $this->database->getUserInfoFromID($citID);
        $this->sendNoteCGs($counID, '<a href="'.$this->url->getURL('profile', $citID).'">'.($cit['name'] ?? '').'</a> resigned from congress.');
        $this->database->exec('DELETE FROM congressmen WHERE cgCitizenID = ?', [$citID]);
    }

    public function resignCandidateCG(int|string $citID, int|string $partyID): void
    {
        $this->database->exec('DELETE FROM elections_cg_candidates WHERE CitizenID = ? AND PartyID = ?', [$citID, $partyID]);
        $this->arrangeCGCandidates($partyID);
    }

    public function resignCandidatePP(int|string $citID, int|string $partyID): void
    {
        $this->database->exec('DELETE FROM elections_pp_candidates WHERE CitizenID = ? AND PartyID = ?', [$citID, $partyID]);
    }

    /** Leave a party; if the leaver was PP, the highest-EP member takes over. */
    public function resignParty(int|string $party, int|string $citizen): void
    {
        $this->database->exec('DELETE FROM party_members WHERE PartyID = ? AND CitizenID = ?', [$party, $citizen]);
        $pp = $this->database->value('SELECT PP FROM party WHERE pID = ?', [$party]);
        if ((int) $pp === (int) $citizen) {
            $new = $this->database->row('SELECT party_members.* FROM party_members LEFT JOIN citizens ON citizens.CitizenID = party_members.CitizenID
                WHERE party_members.PartyID = ? ORDER BY citizens.ep DESC LIMIT 1', [$party]);
            $this->setPP($party, $new['CitizenID'] ?? '');
        }
    }

    public function sendNoteCGs(int|string $counID, string $note): void
    {
        foreach ($this->database->rows('SELECT cgCitizenID FROM congressmen WHERE cgCountryID = ?', [$counID]) as $cong) {
            $this->database->sendNote($cong['cgCitizenID'], '', $note);
        }
    }

    public function setCGCandidateOrder(int|string $party, int|string $citID, float $new): void
    {
        $this->database->exec('UPDATE elections_cg_candidates SET `Order` = ? WHERE PartyID = ? AND CitizenID = ?', [$new - 0.1, $party, $citID]);
        $this->arrangeCGCandidates($party);
    }

    public function setCP(int|string $counID, int|string $cpID, string $why = 'impeach'): void
    {
        $this->database->exec('UPDATE country SET cpID = ? WHERE CountryID = ?', [$cpID ?: null, $counID]);
        $coun = $this->database->getCountryRec($counID);
        if (! $cpID) {
            return;
        }
        if ($why === 'win') {
            $this->database->addMedal($cpID, 'cp');
            $this->database->sendNote($cpID, 'cpwin', "{$coun['CountryID']}|{$coun['shortName']}");
        } else {
            $this->database->sendNote($cpID, '', 'The former president of your country was impeached. Now, you are the new country president of <a href="'
                .$this->url->getURL('country', $coun['CountryID']).'">'.$coun['cName'].'</a>, congratulations! Also you\'ve received a medal for this post.');
        }
    }

    public function setPP(int|string $partyID, int|string $ppID, string $why = 'resign'): void
    {
        $this->database->updatePartyField($partyID, 'PP', $ppID === '' ? 0 : $ppID);
        $this->database->updatePartyField($partyID, 'coPP', 0);
        $party = $this->database->getParty($partyID);
        if (! $ppID || ! $party) {
            return;
        }
        $link = '<a href="'.$this->url->getURL('party', $party['pID']).'">'.$party['pName'].'</a>';
        if ($why === 'win') {
            $note = "Congratulations, you have won the party presidency elections and you are now the new party president of {$link}! Also, you have received 20 Experience Points, a trophy and 5 Tala for this winning!";
            $this->database->addMedal($ppID, 'pp');
        } else {
            $note = "Because the party president of your party resigned his/her presidency, you are now the new party president of {$link}, congratulations!";
        }
        $this->database->sendNote($ppID, '', $note);
    }

    public function setVote(int|string $citID, int|string $lawID, string $vote): void
    {
        $this->database->exec('INSERT INTO law_votes (CitizenID, LawID, Vote, timestamp) VALUES (?, ?, ?, ?)', [$citID, $lawID, $vote, time()]);
    }
}
