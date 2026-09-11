<?php

namespace App\Game\Services;

use App\Game\Support\Constants;
use App\Game\Support\Url;

/**
 * Port of include/military.php ($war): wars, battles, fighting, walls, allies.
 */
class Military
{
    public function __construct(protected GameDatabase $database, protected Url $url)
    {
    }

    protected function ctx(): GameContext
    {
        return app(GameContext::class);
    }

    public function addAlly(int|string $c1, int|string $c2, int $dur): void
    {
        $exp = $this->database->today + $dur;
        if (! $this->isAlly($c1, $c2)) {
            $this->database->exec('INSERT INTO alliances (Country1, Country2, Expire) VALUES (?, ?, ?), (?, ?, ?)', [$c1, $c2, $exp, $c2, $c1, $exp]);
        } else {
            $this->database->exec('UPDATE alliances SET Expire = Expire + ? WHERE (Country1 = ? AND Country2 = ?) OR (Country1 = ? AND Country2 = ?)', [$dur, $c1, $c2, $c2, $c1]);
        }
    }

    public function attackRegion(int|string $warID, int|string $regID, string $action = 'reaction'): void
    {
        $war = $this->getWarInfo($warID);
        if ($action === 'reaction') {
            $att = $war['Attacker'];
            $def = $war['Defender'];
        } else {
            $def = $war['Attacker'];
            $att = $war['Defender'];
        }
        if (! $this->haveWarReg($regID, $war['warID'])) {
            $this->startBattle($war['warID'], $regID, $att, $def);
        }
        $reg = $this->database->row('SELECT oCountryID, CountryID FROM region WHERE RegionID = ?', [$regID]);
        if ($reg && (int) $reg['CountryID'] === (int) $reg['oCountryID']) {
            $this->decWarAlly($def, $att);
        }
    }

    /** Attacker wins the region. */
    public function battleConquer(int|string $battleID, string $conq = 'conq'): int
    {
        $battle = $this->getBattleInfo($battleID);
        $att = $battle['attID'];
        $def = $battle['defID'];
        if ((int) $att === (int) $battle['Defender']) {
            [$att, $def] = [$def, $att];
        }
        if ($conq !== 'conq') {
            $counmon = $this->database->getCountryAccount($def, 1);
            if (! $counmon || $counmon['Amount'] < 25) {
                return 0;
            }
            $this->database->transferMoney(1, 25, $def, 'country', '', '', 1);
        }
        $this->database->addEvent('conq', ($battle['Type'] === 'war' ? $att : 0), $def, $battle['battleID']);
        if ($battle['Type'] === 'revolt') {
            $att = $battle['oCountryID'];
        }
        $numregs = $this->database->count('SELECT RegionID FROM region WHERE CountryID = ?', [$def]);
        $this->database->exec('UPDATE battles SET Result = ?, End = ? WHERE battleID = ?', [$conq, time(), $battleID]);

        if ($battle['Type'] === 'revolt') {
            $this->endWar($battle['warID']);
            $note = 'Congratulations! The region <a href="'.$this->url->getURL('region', $battle['regionID']).'">'
                .$this->database->getRegion($battle['regionID']).'</a> has been liberated by a revolt started by you! So you received a revolt trophy and 5 Tala for reward!';
            $this->database->sendNote($battle['attID'], '', $note);
            $this->database->addMedal($battle['attID'], 'revolt');
            $this->database->exec('UPDATE region SET CountryID = oCountryID WHERE RegionID = ?', [$battle['regionID']]);
        } else {
            $this->database->exec('UPDATE region SET CountryID = ? WHERE RegionID = ?', [$att, $battle['regionID']]);
        }

        foreach ($this->database->getCountryAccounts($battle['Defender']) as $cAcc) {
            $amount = (float) $cAcc['Amount'];
            $win = $numregs == 1 ? $amount : round($amount / ($numregs * $numregs));
            $this->database->transferMoney($cAcc['CurID'], $win, $def, 'country', $att, 'country', 1);
        }

        $this->getHeroPrize($battleID);

        if ($battle['Type'] !== 'revolt') {
            $prize = (time() - 25 * 3600 < $battle['Start'] && $conq === 'conq') ? $battle['Trust'] * 1.1 : $battle['Trust'] * 0.75;
            $this->database->addMoney(1, $prize, $att, 'country', 1, '> Payback the trust <');
            if (count($this->getActiveBattles($battle['warID'])) < 1) {
                $this->database->exec('UPDATE wars SET winID = ?, winDue = ? WHERE warID = ?', [$att, time() + 86400, $battle['warID']]);
            }
        }

        foreach ($this->database->rows('SELECT CompanyID FROM company WHERE RegionID = ?', [$battle['regionID']]) as $comp) {
            $this->database->exec("UPDATE company_workers SET CompanyID = '-1' WHERE CompanyID = ?", [$comp['CompanyID']]);
            $this->database->exec('DELETE FROM jobOffers WHERE CompanyID = ?', [$comp['CompanyID']]);
        }

        foreach ($this->database->rows("SELECT battleID FROM battles WHERE Result = '' AND regionID = ?", [$battle['regionID']]) as $bat) {
            $this->battleSecure($bat['battleID'], 'secu', 0);
        }

        if ($numregs == 1) {
            foreach ($this->database->rows('SELECT warID FROM wars WHERE (Attacker = ? OR Defender = ?) AND End = 0', [$battle['Defender'], $battle['Defender']]) as $w) {
                $this->endWar($w['warID']);
            }
            $this->database->exec('DELETE FROM alliances WHERE Country1 = ? OR Country2 = ?', [$battle['Defender'], $battle['Defender']]);
            $this->database->exec('DELETE FROM congressmen WHERE cgCountryID = ?', [$battle['Defender']]);
            foreach ($this->database->rows('SELECT pID FROM party WHERE CountryID = ?', [$battle['Defender']]) as $part) {
                $this->database->exec("UPDATE party SET PP = 0, cpProposed = NULL WHERE pID = ?", [$part['pID']]);
                $this->database->exec('DELETE FROM party_members WHERE PartyID = ?', [$part['pID']]);
                $this->database->exec('DELETE FROM elections_cg_candidates WHERE PartyID = ?', [$part['pID']]);
                $this->database->exec('DELETE FROM elections_pp_candidates WHERE PartyID = ?', [$part['pID']]);
            }
        } else {
            if ($this->haveCap($battle['Defender'])) {
                $reg = $this->database->row('SELECT RegionID FROM region WHERE capFor = ?', [$battle['Defender']]);
            } else {
                $reg = $this->database->row('SELECT region.RegionID, COUNT(citizens.regionID) AS pop FROM citizens JOIN region ON region.RegionID = citizens.regionID
                    WHERE region.CountryID = ? GROUP BY regionID ORDER BY pop DESC', [$battle['Defender']]);
            }
            $tarReg = $reg['RegionID'] ?? null;
            if ($tarReg) {
                foreach ($this->database->rows('SELECT CitizenID FROM congressmen JOIN citizens ON citizens.CitizenID = congressmen.cgCitizenID WHERE cgCountryID = ? AND regionID = ?', [$battle['Defender'], $battle['regionID']]) as $cit) {
                    $this->database->exec('UPDATE citizens SET regionID = ? WHERE CitizenID = ?', [$tarReg, $cit['CitizenID']]);
                }
                foreach ($this->database->rows('SELECT CitizenID FROM country JOIN citizens ON country.cpID = citizens.CitizenID WHERE CountryID = ? AND regionID = ?', [$battle['Defender'], $battle['regionID']]) as $cit) {
                    $this->database->exec('UPDATE citizens SET regionID = ? WHERE CitizenID = ?', [$tarReg, $cit['CitizenID']]);
                }
            }
        }
        $this->detectRoute($def);
        $this->detectRoute($att);

        return 1;
    }

    /** Defender holds the region. */
    public function battleSecure(int|string $battleID, string $sec = 'secu', $attack = 1): int
    {
        $battle = $this->getBattleInfo($battleID);
        if (! $battle) {
            return 0;
        }
        $att = $battle['attID'];
        $def = $battle['defID'];
        if ((int) $att === (int) $battle['Defender']) {
            [$att, $def] = [$def, $att];
        }
        if ($sec !== 'secu') {
            $counmon = $this->database->getCountryAccount($att, 1);
            if (! $counmon || $counmon['Amount'] < 25) {
                return 0;
            }
            $this->database->transferMoney(1, 25, $att, 'country', '', '', 1);
        }
        if ($attack) {
            $this->database->addEvent('secu', $def, ($battle['Type'] === 'war' ? $att : 0), $battle['battleID']);
        }
        $this->database->exec('UPDATE battles SET Result = ?, End = ? WHERE battleID = ?', [$sec, time(), $battleID]);
        if ($battle['Type'] === 'revolt') {
            $this->endWar($battle['warID']);
        }
        $this->getHeroPrize($battleID);
        if ($battle['Type'] !== 'revolt' && $attack && count($this->getActiveBattles($battle['warID'])) < 1) {
            $this->database->exec('UPDATE wars SET winID = ?, winDue = ? WHERE warID = ?', [$def, time() + 86400, $battle['warID']]);
        }

        return 1;
    }

    public function canRetreat(array $battle, int|string $counID): int
    {
        if (($battle['battle_type'] ?? '') === 'revolt') {
            return 0;
        }
        if ((int) $battle['Attacker'] === (int) $counID) {
            return 1;
        }
        $earlier = $this->database->count("SELECT battleID FROM battles WHERE Start < ? AND Defender = ? AND regionID = ? AND battleID != ? AND Result = ''",
            [$battle['Start'], $counID, $battle['regionID'], $battle['battleID']]);
        if ($earlier >= 1) {
            return 0;
        }
        if ($this->database->count('SELECT RegionID FROM region WHERE CountryID = ?', [$battle['Defender']]) === 1) {
            return 0;
        }

        return 1;
    }

    public function changePhase(array $battle, int $phase): void
    {
        if ($phase === 1) {
            $this->database->exec('UPDATE battles SET End = endFlag WHERE battleID = ?', [$battle['battleID']]);
        } else {
            $time = time() + 1800;
            if ($time < $battle['endFlag']) {
                $this->database->exec('UPDATE battles SET End = ? WHERE battleID = ?', [$time, $battle['battleID']]);
            }
        }
    }

    public function changeWallHeight(array $battle, int|float $new, int|float $wall): void
    {
        $battleID = $battle['battleID'];
        if ($new) {
            $this->database->exec('UPDATE battles SET wall = wall + (?) WHERE battleID = ?', [$new, $battleID]);
        } else {
            $this->database->exec('UPDATE battles SET wall = ? WHERE battleID = ?', [$wall, $battleID]);
        }
        $now = time();
        if ($battle['endFlag'] - $now < 24 * 6 * 3600) {
            if ($wall < $battle['extra'] || $wall >= $battle['sPoint']) {
                if (($now > ($battle['Start'] + 86400)) && ($battle['endFlag'] == $battle['End'])) {
                    $this->changePhase($battle, 2);
                }
            } elseif ($battle['endFlag'] != $battle['End']) {
                $this->changePhase($battle, 1);
            }
        }
    }

    public function declareWar(int|string $att, int|string $def, string $type = 'war', int|string $reg = '', $invally = 1): ?int
    {
        if ($type === 'war') {
            if (! $this->haveWar($att, $def) && (int) $att !== (int) $def) {
                if ($this->isAlly($att, $def)) {
                    $this->database->exec('DELETE FROM alliances WHERE (Country1 = ? AND Country2 = ?) OR (Country2 = ? AND Country1 = ?)', [$att, $def, $att, $def]);
                }
                $warID = $this->database->insertGetId('INSERT INTO wars (Attacker, Defender, Start) VALUES (?, ?, ?)', [$att, $def, time()]);
                app(Economy::class)->stopTrade($att, $def);
                $this->database->exec('UPDATE wars SET winID = ?, winDue = ? WHERE warID = ?', [$att, time() + 86400, $warID]);
                if ($invally) {
                    $this->refreshAllies($warID);
                }

                return 1;
            }

            return null;
        }
        if (! $this->haveWarReg($reg)) {
            $warID = $this->database->insertGetId('INSERT INTO wars (Type, Attacker, Defender, Start) VALUES (?, ?, ?, ?)', [$type, $att, $def, time()]);
            $batID = $this->startBattle($warID, $reg, $att, $def, 0);
            $this->database->addEvent('revolt', $def, $def, $batID);

            return 1;
        }

        return null;
    }

    public function decWarAlly(int|string $def, int|string $att): void
    {
        $allies = $this->database->rows('SELECT Neighs.* FROM (SELECT Country2.CountryID, Country2.cName FROM country AS Country1
                RIGHT JOIN region AS reg1 ON reg1.CountryID = Country1.CountryID
                RIGHT JOIN neighbors ON neighbors.Region1 = reg1.RegionID
                LEFT JOIN region AS reg2 ON reg2.RegionID = neighbors.Region2
                LEFT JOIN country AS Country2 ON reg2.CountryID = Country2.CountryID
                WHERE Country1.CountryID = ? AND Country2.CountryID != ? GROUP BY Country2.cName ORDER BY Country2.cName) AS Neighs
            JOIN alliances ON alliances.Country2 = Neighs.CountryID WHERE alliances.Country1 = ?', [$att, $att, $def]);
        foreach ($allies as $ally) {
            if (! $this->haveWar($ally['CountryID'], $att)) {
                $this->declareWar($ally['CountryID'], $att, 'war', '', 0);
            }
        }
    }

    /** Recompute region.hasRoute (connected to the capital) for a country. */
    public function detectRoute(int|string $coun): int
    {
        $this->database->exec('UPDATE region SET hasRoute = 0 WHERE CountryID = ?', [$coun]);
        $cap = $this->database->row('SELECT RegionID FROM region WHERE CountryID = ? AND capFor = ?', [$coun, $coun]);
        if (! $cap) {
            return 0;
        }
        $regs = [$cap['RegionID']];
        while (($reg = array_shift($regs)) !== null) {
            $this->database->exec('UPDATE region SET hasRoute = 1 WHERE RegionID = ?', [$reg]);
            foreach ($this->database->rows('SELECT region.RegionID FROM neighbors JOIN region ON region.RegionID = neighbors.Region2
                WHERE neighbors.Region1 = ? AND region.CountryID = ? AND region.hasRoute = 0', [$reg, $coun]) as $row) {
                $regs[] = $row['RegionID'];
            }
        }

        return 1;
    }

    public function endWar(int|string $warID, int|string $defID = ''): void
    {
        if ($defID !== '' && $defID !== null) {
            $row = $this->database->row("SELECT warID FROM wars WHERE ((Attacker = ? AND Defender = ?) OR (Attacker = ? AND Defender = ?)) AND End = '0'", [$warID, $defID, $defID, $warID]);
            $warID = $row['warID'] ?? 0;
        }
        $this->database->exec('UPDATE wars SET End = ? WHERE warID = ?', [time(), $warID]);
        foreach ($this->database->rows("SELECT battleID FROM battles WHERE warID = ? AND Result = ''", [$warID]) as $row) {
            $this->battleSecure($row['battleID'], 'secu', 0);
        }
    }

    /** FIGHT — returns the fight report array. */
    public function fight(array $cit, array $battle, int|string $weapon = 0, string $for = 'std'): array
    {
        $citID = $cit['CitizenID'];
        $battleID = $battle['battleID'];
        $allyDef = $this->ctx()->getStr2Array($battle['ally_def']);
        $allyAtt = $this->ctx()->getStr2Array($battle['ally_att']);
        $inDef = in_array($cit['CountryID'], $allyDef) || (int) $cit['CountryID'] === (int) $battle['Defender'];
        $inAtt = in_array($cit['CountryID'], $allyAtt) || (int) $cit['CountryID'] === (int) $battle['Attacker'];

        // damage = body shape × weapon × boosters — military rank is prestige only
        $A = Constants::shapeDamage((int) ($cit['strength'] ?? 0));
        $B = 1;
        $Q = 0;
        $C = 1;
        $weapon = (int) $weapon;
        if ($weapon) {
            $row = $this->database->row("SELECT * FROM inventory WHERE Type = '4' AND Owner = ? AND Usable = '1' AND Stars = ? ORDER BY Stars DESC", [$citID, $weapon]);
            if (! $row) {
                $weapon = 0;
            } else {
                $Q = (int) $row['Stars'];
                $weapon = 1;
                $C = 1 + ($Q * 3 * 0.2);
                $this->database->exec("UPDATE inventory SET Usable = '0' WHERE pID = ?", [$row['pID']]);
            }
        }
        $D = 1;
        $gds = min((int) ($cit['gd_war'] ?? 0), 7);
        $gdpercs = [0, 0.05, 0.07, 0.08, 0.085, 0.09, 0.095, 0.1];
        $E = 1 + (($battle['Type'] === 'revolt' && $for === 'att') ? 0 : $gdpercs[$gds]);
        $F = ($battle['hasRoute'] || $inAtt || ($battle['Type'] === 'revolt' && $for === 'att')) ? 1 : 0.8;
        $damage = round($A * $B * $C * $D * $E * $F);

        $unitBonus = 0.0;
        if ((int) $cit['military_unit'] !== 0) {
            $unit = $this->database->row('SELECT military_unit.mID, commander.mRank AS cmdRank FROM military_unit
                LEFT JOIN citizens AS commander ON commander.CitizenID = military_unit.mCommander WHERE mID = ? AND mBattleID = ?', [$cit['military_unit'], $battleID]);
            if ($unit) {
                // the unit's order, led by a ranked commander
                $unitBonus = Constants::unitBonus((int) ($unit['cmdRank'] ?? 0));
                $damage = round($damage * (1 + $unitBonus));
            }
        }
        $time = time();
        if ($cit['dmg_booster'] > $time) {
            if ((int) $cit['dmg_booster_q'] === 1) {
                $damage = round($damage * 1.20);
            }
            if ((int) $cit['dmg_booster_q'] === 2) {
                $damage = round($damage * 1.50);
            }
        }
        if ($battle['Type'] !== 'revolt') {
            if (! $inDef) {
                $damage = -$damage;
            }
        } elseif ($for === 'att') {
            $damage = -$damage;
        }

        $this->database->exec('INSERT INTO fights (fighterID, countryID, battleID, weapon, damage, timestamp) VALUES (?, ?, ?, ?, ?, ?)',
            [$citID, $cit['CountryID'], $battleID, $weapon, $damage, $time]);
        $newWall = $battle['wall'] + $damage;
        $this->changeWallHeight($battle, $damage, $newWall);

        $epA = ($cit['puberty'] >= 7) ? 1 : 2;
        $ep = $cit['ep'] + $epA;
        if ($battleID > 1) {
            $this->database->addEP($citID, $epA, "Fighting in battle {$battleID}");
        }
        $well = round($cit['wellness'] - Constants::fightWellnessCost((int) ($cit['stamina'] ?? 0)), 1);
        $totDamage = $cit['total_damage'] + abs($damage);
        $mRank = (int) $cit['mRank'];
        $sql = 'UPDATE citizens SET wellness = ?, fight_count = fight_count + 1, total_damage = ?';
        $b = [$well, $totDamage];
        $rankedUp = false;
        if (isset(Constants::RANK_DAMAGES[$mRank + 1]) && Constants::RANK_DAMAGES[$mRank + 1] < $totDamage) {
            $mRank++;
            $rankedUp = true;
            $sql .= ', mRank = ?';
            $b[] = $mRank;
        }
        if (abs($damage) > $cit['stat_maxadvance']) {
            $sql .= ', stat_maxadvance = ?';
            $b[] = abs($damage);
        }
        $sql .= ' WHERE CitizenID = ?';
        $b[] = $citID;
        $this->database->exec($sql, $b);
        $this->ctx()->updateInfo(['wellness' => $well, 'total_damage' => $totDamage, 'mRank' => $mRank, 'fight_count' => $cit['fight_count'] + 1]);
        if ($rankedUp) {
            // rank-up reward: tala + a 5-star food, and the new insignia
            $this->database->addMoney(1, Constants::RANK_UP_TALA * $mRank, $citID);
            $this->database->createProduct(1, 5, $citID);
            $this->database->sendNote($citID, 'rank_up', (Constants::MILI_RANKS[$mRank] ?? '').'|'.(Constants::RANK_UP_TALA * $mRank));
        }

        $report = ['Base' => round($A * $B), 'Weapon' => (($C - 1) * 100).'%', 'Unit' => ($unitBonus * 100).'%', 'Goddess' => (($E - 1) * 100).'%'];
        $maxQ = (int) $this->database->value("SELECT Stars FROM inventory WHERE Type = '4' AND Owner = ? AND Usable = '1' ORDER BY Stars DESC", [$citID], 0);

        return [
            'Damage' => abs($damage), 'Report' => $report, 'Skill' => "$A", 'Strength' => (int) ($cit['strength'] ?? 0), 'Stamina' => (int) ($cit['stamina'] ?? 0), 'RankedUp' => $rankedUp,
            'mRank' => Constants::MILI_RANKS[$mRank] ?? '', 'mRankNum' => $mRank,
            'totForce' => "$totDamage \\ ".(Constants::RANK_DAMAGES[$mRank + 1] ?? ''),
            'Well' => "$well", 'Well-inf' => "$well", 'WeapType' => "$weapon", 'Weapon' => "$Q", 'MaxQ' => "$maxQ", 'EP' => "$ep",
        ];
    }

    public function getActiveBattles(int|string $warID): array
    {
        return $this->database->rows("SELECT battles.*, Defender.cName AS defName, Defender.Flag AS defFlag, region.rName FROM wars
            JOIN battles ON battles.warID = wars.warID JOIN country AS Defender ON Defender.CountryID = battles.Defender
            JOIN region ON region.RegionID = battles.regionID WHERE wars.warID = ? AND battles.Result = ''", [$warID]);
    }

    public function getAllies(int|string $coun): array
    {
        return $this->database->rows('SELECT alliances.*, country.* FROM alliances JOIN country ON alliances.Country2 = country.CountryID WHERE Country1 = ? ORDER BY Expire', [$coun]);
    }

    public function getBattleInfo(int|string $battleID): ?array
    {
        return $this->database->row("SELECT battles.*, wars.Type, wars.Attacker AS attID, wars.Defender AS defID, wars.allies, wars.defPoints,
                Attacker.shortName AS attSName, Attacker.cName AS attName, Attacker.Flag AS attFlag,
                AttackerC.CitizenID AS StarterID, AttackerC.name AS Starter, AttackerC.Avatar AS StarterAvatar,
                Defender.shortName AS defSName, Defender.cName AS defName, Defender.Flag AS defFlag,
                region.rName, region.oCountryID, region.Clinic, region.hasRoute
            FROM battles JOIN wars ON wars.warID = battles.warID
            LEFT JOIN country AS Attacker ON (Attacker.CountryID = battles.Attacker AND wars.Type = 'war')
            LEFT JOIN citizens AS AttackerC ON (AttackerC.CitizenID = battles.Attacker AND wars.type = 'revolt')
            JOIN country AS Defender ON Defender.CountryID = battles.Defender
            JOIN region ON region.RegionID = battles.regionID WHERE battleID = ?", [$battleID]);
    }

    /** @return array{attacker:array,defender:array} */
    public function getHeroes(int|string $battleID): array
    {
        $att = $this->database->rows("SELECT citizens.CitizenID, citizens.Avatar, citizens.name, citizens.mRank, SUM(fights.damage) AS advance FROM fights
            JOIN citizens ON fights.fighterID = citizens.CitizenID WHERE fights.battleID = ? AND damage > '0' GROUP BY fighterID ORDER BY advance DESC LIMIT 3", [$battleID]);
        $def = $this->database->rows("SELECT citizens.CitizenID, citizens.Avatar, citizens.name, citizens.mRank, SUM(fights.damage) AS advance FROM fights
            JOIN citizens ON fights.fighterID = citizens.CitizenID WHERE fights.battleID = ? AND damage < '0' GROUP BY fighterID ORDER BY advance LIMIT 3", [$battleID]);

        return ['attacker' => $att, 'defender' => $def];
    }

    public function getHeroPrize(int|string $battleID): void
    {
        $hero = $this->getHeroes($battleID);
        foreach (['attacker', 'defender'] as $side) {
            $co = 6;
            foreach ($hero[$side] as $k) {
                $co--;
                $this->database->addMedal($k['CitizenID'], 'hero', $battleID.'|'.(6 - $co).'|def', '', $co);
                $this->database->sendNote($k['CitizenID'], 'medal_hero', (string) $battleID);
            }
        }
    }

    public function getNewSeedPrice(array $regs): float
    {
        $pop = 0;
        foreach ($regs as $r) {
            $pop += $this->database->getRegionPop($r['RegionID']);
        }

        return round(0.2 + count($regs) * 0.5 + $pop * 0.002, 2);
    }

    public function getRegion(int|string $warID): ?array
    {
        return $this->database->row('SELECT region.* FROM battles JOIN wars ON wars.warID = battles.warID JOIN region ON region.RegionID = battles.regionID WHERE wars.warID = ?', [$warID]);
    }

    /** Wall height computation for a region (returns region + wall/sp/extra). */
    public function getWall(array $reg): array
    {
        $regPop = (int) $reg['stat_pop'];
        // each resident defends with ~8 bare-hand hits of their current strength
        $avest = (float) $this->database->value("SELECT SUM(".Constants::SHAPE_BASE_DAMAGE." + ".Constants::SHAPE_DAMAGE_PER_STAGE." * citizens.strength) * 8 AS Flag FROM region JOIN citizens ON citizens.regionID = region.RegionID
            WHERE region.RegionID = ? AND citizens.wellness > '0' AND citizens.ban_due != 'PERMANENTLY' GROUP BY region.RegionID", [$reg['RegionID']], 0);
        $avest2 = min(100, $this->database->count('SELECT RegionID FROM region WHERE CountryID = ?', [$reg['CountryID']]));
        $avest2 = 1 - ($avest2 * 0.004);

        $wall = Constants::WALL_HEIGHT_BASE + $avest;
        $extra = $regPop * Constants::POP_WALL_STANDARD;
        $wall *= $avest2;
        if ((int) $reg['oCountryID'] === (int) $reg['CountryID']) {
            $wall *= 1.2;
        }
        $sp = $wall * 2 + $extra;
        $wall += $extra;

        $gd = min(10, (int) $this->database->value('SELECT COUNT(RegionID) Total FROM region WHERE CountryID = ? AND goddessType = 6 AND goddessDeath >= ?', [$reg['CountryID'], $this->database->today], 0));
        $gds = [1, 1.1, 1.14, 1.17, 1.19, 1.2, 1.21, 1.22, 1.23, 1.24, 1.25];
        if (! $this->haveCap($reg['CountryID']) || ! $reg['hasRoute']) {
            $wall /= 2;
        }
        $wall = round($wall * $gds[$gd]);
        if ($wall < 0) {
            $wall = -($wall * 2);
            $sp = -($sp * 2);
        }
        $out = $reg;
        $out['wall'] = $wall;
        $out['sp'] = $sp;
        $out['extra'] = $extra;

        return $out;
    }

    public function getWarInfo(int|string $warID): ?array
    {
        return $this->database->row('SELECT wars.*, Attacker.cName AS attName, Attacker.Flag AS attFlag, Defender.cName AS defName, Defender.Flag AS defFlag
            FROM wars JOIN country AS Attacker ON Attacker.CountryID = wars.Attacker JOIN country AS Defender ON Defender.CountryID = wars.Defender WHERE warID = ?', [$warID]);
    }

    public function getWDPrice(int|string $counID, int|string $yourID): float
    {
        $num2 = $this->database->count('SELECT RegionID FROM region WHERE CountryID = ?', [$counID]);
        $num = ($num2 <= 30 ? (30 - $num2) : 0);
        $pop = $this->database->getCountryPop($yourID);
        $gd = min(10, (int) $this->database->value('SELECT COUNT(RegionID) Total FROM region WHERE CountryID = ? AND goddessType = 6 AND goddessDeath >= ?', [$counID, $this->database->today], 0));
        $gds = [1, 1.1, 1.14, 1.17, 1.19, 1.2, 1.21, 1.22, 1.23, 1.24, 1.25];
        if ($num2 <= 0 || (int) $counID === 1) {
            return 100000;
        }

        return round((150 + ($num * 1.1) + ($pop * 0.1)) * $gds[$gd], 2);
    }

    public function haveCap(int|string $counID): int
    {
        return $this->database->count('SELECT RegionID FROM region WHERE CountryID = ? AND capFor = ?', [$counID, $counID]) ? 1 : 0;
    }

    public function haveWar(int|string $c1, int|string $c2): int
    {
        return $this->database->count("SELECT warID FROM wars WHERE ((Attacker = ? AND Defender = ?) OR (Attacker = ? AND Defender = ?)) AND Type = 'war' AND End = '0'", [$c1, $c2, $c2, $c1]) ? 1 : 0;
    }

    public function haveWarReg(int|string $reg, int|string $warID = ''): int
    {
        $sql = "SELECT battleID FROM battles WHERE regionID = ? AND Result = ''";
        $b = [$reg];
        if ($warID !== '' && $warID !== null) {
            $sql .= ' AND warID = ?';
            $b[] = $warID;
        }

        return $this->database->count($sql, $b) ? 1 : 0;
    }

    public function isAlly(int|string $c1, int|string $c2): int
    {
        if ((int) $c1 === (int) $c2) {
            return 1;
        }

        return $this->database->count('SELECT allyID FROM alliances WHERE Country1 = ? AND Country2 = ?', [$c1, $c2]) ? 1 : 0;
    }

    public function refreshAllies(int|string $warID, int|string $att = '', $dec = 1): void
    {
        $war = $this->database->row('SELECT * FROM wars WHERE warID = ?', [$warID]);
        if (! $war) {
            return;
        }
        if (! $att) {
            $att = $war['Attacker'];
        }
        if ((int) $att === (int) $war['Attacker']) {
            $def = $war['Defender'];
            $atn = 'att';
            $dfn = 'def';
        } else {
            $def = $war['Attacker'];
            $atn = 'def';
            $dfn = 'att';
        }
        $allie = '';
        foreach ($this->getAllies($def) as $ally) {
            $allie .= ' '.$ally['Country2'];
        }
        if ($allie) {
            $allie = '.'.$allie.' .';
        }
        $this->database->exec("UPDATE wars SET ally_{$dfn} = ? WHERE warID = ?", [$allie, $warID]);
        $defAllies = $this->ctx()->getStr2Array($allie);

        $allie = '';
        foreach ($this->getAllies($att) as $ally) {
            if (! in_array($ally['Country2'], $defAllies)) {
                $allie .= ' '.$ally['Country2'];
            } else {
                $this->database->exec('DELETE FROM alliances WHERE (Country1 = ? AND Country2 = ?) OR (Country2 = ? AND Country1 = ?)', [$ally['Country1'], $ally['Country2'], $ally['Country1'], $ally['Country2']]);
            }
        }
        if ($allie) {
            $allie = '.'.$allie.' .';
        }
        $this->database->exec("UPDATE wars SET ally_{$atn} = ? WHERE warID = ?", [$allie, $warID]);
    }

    public function isFought(int|string $citID): int
    {
        return $this->database->count('SELECT fightID FROM fights WHERE fighterID = ? AND timestamp >= ?', [$citID, app(\App\Game\Support\GameClock::class)->startOfRealDay()]) ? 1 : 0;
    }

    public function isUsedClinic(int|string $citID): int
    {
        return $this->database->count("SELECT logID FROM log WHERE CitID = ? AND Type = 'Clinic' AND timestamp >= ?", [$citID, app(\App\Game\Support\GameClock::class)->startOfRealDay()]) ? 1 : 0;
    }

    public function startBattle(int|string $warID, int|string $regID, int|string $att, int|string $def, $addeve = 1, $ally = 0): int
    {
        if ($addeve) {
            $this->refreshAllies($warID, $att, 0);
        }
        $reg = $this->database->getRegionRec($regID);
        $walldata = $this->getWall($reg);
        $wall = $walldata['wall'];
        $sp = $walldata['sp'];
        $extra = $walldata['extra'];
        $trust = $reg['stat_value'];
        $start = time();
        $end = $start + 86400;
        $endF = $start + 3 * 86400;
        $war = $this->database->row('SELECT * FROM wars WHERE warID = ?', [$warID]);
        $attallies = $defallies = '';
        if ($addeve) {
            if ((int) $reg['CountryID'] === (int) $reg['oCountryID'] && $war['allies'] < 3) {
                $incr = ((int) $def === (int) $war['Defender']) ? 2 : 1;
                if ((int) $war['allies'] !== $incr) {
                    $war['allies'] += $incr;
                    $this->database->exec('UPDATE wars SET allies = ? WHERE warID = ?', [$war['allies'], $warID]);
                }
            }
            $attallies = ($war['allies'] % 2 == 1) ? $war['ally_att'] : '';
            $defallies = ($war['allies'] > 1) ? $war['ally_def'] : '';
            if ((int) $att !== (int) $war['Attacker']) {
                [$attallies, $defallies] = [$defallies, $attallies];
            }
        }
        $btype = ($war['Type'] === 'war') ? 'battle' : 'revolt';
        $battleID = $this->database->insertGetId('INSERT INTO battles (warID, battle_type, regionID, Attacker, Defender, ally_att, ally_def, Trust, wall, extra, sPoint, Start, End_P1, End, endFlag)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$warID, $btype, $regID, $att, $def, $attallies, $defallies, $trust, $wall, $extra, $sp, $start, $end, $endF, $endF]);
        if ($addeve) {
            $this->database->addEvent('att', $att, $def, $battleID);
        }

        return $battleID;
    }
}
