<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class BattleController extends ApiController
{
    /** GET /api/v1/battles — all active battles (mine first). */
    public function index()
    {
        $c = $this->cit();
        $cid = (int) $c['CountryID'];
        $rows = $this->database->rows("SELECT battles.*, region.rName AS regionName, attacker.cName AS attName, defender.cName AS defName,
                (Attacker = ? OR Defender = ? OR ally_att LIKE ? OR ally_def LIKE ?) AS mine
            FROM battles JOIN region ON region.RegionID = battles.regionID
            LEFT JOIN country AS attacker ON attacker.CountryID = battles.Attacker
            JOIN country AS defender ON defender.CountryID = battles.Defender
            WHERE Result = '' ORDER BY mine DESC, End", [$cid, $cid, "% $cid %", "% $cid %"]);

        return $this->ok(['battles' => array_map(fn ($b) => $this->battlePayload($b) + ['mine' => (bool) $b['mine']], $rows)]);
    }

    /** GET /api/v1/battles/{id} */
    public function show(int $id)
    {
        $bat = $this->war()->getBattleInfo($id);
        if (!$bat) {
            return $this->fail('Battle not found.', 404);
        }
        $c = $this->cit(true);
        $side = $this->side($c, $bat);
        $heroes = $this->war()->getHeroes($id);
        $my = (float) $this->database->value('SELECT SUM(ABS(damage)) dmg FROM fights WHERE battleID = ? AND fighterID = ?', [$id, $c['CitizenID']], 0);
        $weapons = $this->database->rows("SELECT Stars, COUNT(pID) Amount FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = 4 GROUP BY Stars ORDER BY Stars", [$c['CitizenID']]);
        $hero = fn ($h) => ['id' => (int) $h['CitizenID'], 'name' => $h['name'], 'avatar' => url('/uploads/avatars/citizen/'.$h['Avatar']), 'damage' => abs((float) $h['advance'])];

        return $this->ok([
            'battle' => $this->battlePayload($bat + ['battle_type' => $bat['Type'] === 'revolt' ? 'revolt' : 'battle', 'regionName' => $bat['rName']]) + [
                'clinic' => (int) $bat['Clinic'], 'hasRoute' => (bool) $bat['hasRoute'], 'starter' => $bat['Starter'] ?? null,
                'timeLeft' => max(0, (int) $bat['End'] - time()),
            ],
            'side' => $side, 'canFight' => $side !== null && $c['accType'] === 'citizen' && $bat['End'] > time() && !$bat['Result'],
            'myForce' => $my, 'wellness' => (float) $c['wellness'], 'occupiedUntil' => (int) $c['occDue'],
            'heroes' => ['attacker' => array_map($hero, $heroes['attacker']), 'defender' => array_map($hero, $heroes['defender'])],
            'weapons' => array_map(fn ($w) => ['stars' => (int) $w['Stars'], 'amount' => (int) $w['Amount']], $weapons),
            'log' => $this->log($id),
        ]);
    }

    private function log(int $id): array
    {
        return array_map(fn ($f) => ['name' => $f['name'], 'avatar' => url('/uploads/avatars/citizen/'.$f['Avatar']), 'damage' => (float) $f['damage'], 'time' => (int) $f['timestamp']],
            $this->database->rows('SELECT fights.damage, fights.timestamp, citizens.name, citizens.Avatar FROM fights JOIN citizens ON citizens.CitizenID = fights.fighterID WHERE battleID = ? ORDER BY fights.timestamp DESC LIMIT 15', [$id]));
    }

    private function side(array $c, array $bat): ?string
    {
        $allyDef = $this->session->getStr2Array($bat['ally_def']);
        $allyAtt = $this->session->getStr2Array($bat['ally_att']);
        if (in_array($c['CountryID'], $allyDef) || (int) $c['CountryID'] === (int) $bat['Defender']) {
            return 'def';
        }
        if (in_array($c['CountryID'], $allyAtt) || (int) $c['CountryID'] === (int) $bat['Attacker']) {
            return 'att';
        }

        return null;
    }

    /** POST /api/v1/battles/{id}/fight {weapon: 0-5} */
    public function fight(Request $request, int $id)
    {
        $c = $this->cit(true);
        $bat = $this->war()->getBattleInfo($id);
        if (!$bat) {
            return $this->fail('Battle not found.', 404);
        }
        $side = $this->side($c, $bat);
        if ($c['accType'] !== 'citizen') {
            return $this->fail('Co-accounts cannot fight.');
        }
        if ($bat['End'] <= time() || $bat['Result']) {
            return $this->fail('This battle is over.');
        }
        if ($c['occDue'] >= time()) {
            return $this->fail('You are occupied.');
        }
        if (!$side) {
            return $this->fail('Your country is not involved in this battle.');
        }
        if ($c['wellness'] < 20) {
            return $this->fail('You need at least 20 wellness to fight.');
        }
        $for = $bat['Type'] === 'revolt' ? ($side === 'att' ? 'att' : 'def') : 'std';
        $msg = $this->war()->fight($c, $bat, (int) $request->input('weapon', 0), $for);
        $d = (float) $msg['Damage'];
        $tier = $d < 50 ? 'Good' : ($d < 100 ? 'Charming' : ($d < 200 ? 'Elegant' : ($d < 350 ? 'Terrific' : ($d < 500 ? 'Excellent' : 'Epic'))));
        $c = $this->cit(true);

        return $this->ok([
            'damage' => $d, 'tier' => $tier, 'wellness' => (float) $msg['Well'], 'skill' => $msg['Skill'], 'mRank' => $msg['mRank'], 'ep' => $msg['EP'],
            'weaponUsed' => $msg['Weapon'], 'totalForce' => $msg['totForce'], 'canFightAgain' => $msg['Well'] >= 20,
            'myForce' => (float) $this->database->value('SELECT SUM(ABS(damage)) dmg FROM fights WHERE battleID = ? AND fighterID = ?', [$id, $c['CitizenID']], 0),
            'citizen' => $this->citizenPayload($c, true), 'log' => $this->log($id),
        ]);
    }

    private function war(): \App\Game\Services\Military
    {
        return app(\App\Game\Services\Military::class);
    }
}
