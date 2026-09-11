<?php

namespace App\Http\Controllers\Api;

use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Translator;
use App\Game\Support\Constants;
use App\Game\Support\Vars;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Base for the JSON API (v1) used by the Flutter client. Reuses the same game services as the web UI.
 */
abstract class ApiController extends Controller
{
    protected GameDatabase $database;
    protected GameContext $session;
    protected Translator $lang;
    protected Vars $vars;

    public function __construct()
    {
        $this->database = app(GameDatabase::class);
        $this->session = app(GameContext::class);
        $this->lang = app(Translator::class);
        $this->vars = app(Vars::class);
    }

    protected function cit(bool $fresh = false): array
    {
        if ($fresh) {
            $this->session->fillInfo(null, true);
        }

        return $this->session->userinfo;
    }

    protected function citID(): int
    {
        return (int) $this->session->CitID;
    }

    protected function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(['ok' => true] + $data, $status);
    }

    protected function fail(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => strip_tags($message)] + $extra, $status);
    }

    protected function msg(string $key, string $loc = 'msgs', ...$args): string
    {
        $s = $this->lang->getstr($key, $loc);

        return $args ? sprintf($s, ...$args) : $s;
    }

    /** Public shape of a citizen record (own profile or somebody else's). */
    protected function citizenPayload(array $c, bool $self = false): array
    {
        $pub = (int) ($c['puberty'] ?? 0);
        $cur = Constants::PUB_EPS[$pub] ?? 0;
        $next = Constants::PUB_EPS[$pub + 1] ?? $cur;
        $out = [
            'id' => (int) $c['CitizenID'], 'name' => $c['name'], 'avatar' => url('/uploads/avatars/citizen/'.$c['Avatar']),
            'accType' => $c['accType'] ?? 'citizen', 'level' => $pub + 1, 'rank' => Constants::PUB_RANKS[$pub] ?? '',
            'ep' => (float) ($c['ep'] ?? 0), 'epLevelStart' => $cur, 'epNextLevel' => $next,
            'wellness' => (float) ($c['wellness'] ?? 0), 'wSkill' => (float) ($c['wSkill'] ?? 0), 'mSkill' => (float) ($c['mSkill'] ?? 0),
            'mRank' => $c['mRank'] ?? null, 'worldRank' => $c['stat_rank_int'] ?? null,
            'country' => ['id' => (int) ($c['CountryID'] ?? 0), 'name' => $c['cName'] ?? '', 'flag' => url('/images/flags/s/'.($c['cName'] ?? '').'.gif'), 'currency' => $c['curName'] ?? ''],
            'region' => ['id' => (int) ($c['regionID'] ?? 0), 'name' => $c['RegionName'] ?? ''],
            'nationality' => (int) ($c['nationality'] ?? 0),
        ];
        if ($self) {
            $today = $this->database->today;
            $out += [
                'email' => $c['email'] ?? null, 'partyID' => (int) ($c['PartyID'] ?? 0), 'militaryUnit' => (int) ($c['military_unit'] ?? 0), 'npID' => (int) ($c['npID'] ?? 0),
                'occupiedUntil' => (int) ($c['occDue'] ?? 0), 'trainedToday' => ($c['LastTrained'] ?? 0) == $today, 'workedToday' => ($c['LastWorked'] ?? 0) >= $today,
                'exploredToday' => ($c['LastExplored'] ?? 0) == $today, 'dailyClaimed' => ($c['LastDaily'] ?? 0) == $today,
                'isPro' => ($c['proExpire'] ?? 0) >= time(), 'isPlus' => ($c['plusExpire'] ?? 0) >= time(),
                'inventory' => array_values(array_map(fn ($i) => ['type' => (int) $i['Type'], 'icon' => url('/images/icons/'.$i['Icon'].'.png'), 'name' => $i['Icon'], 'stars' => (int) $i['Stars'], 'amount' => (int) $i['Amount']], $c['inventory'] ?? [])),
                'money' => $this->moneyPayload(),
            ];
        }

        return $out;
    }

    protected function moneyPayload(): array
    {
        $out = [];
        foreach ($this->database->rows('SELECT citizen_money.CurID, citizen_money.Amount, country.curName, country.cName FROM citizen_money JOIN country ON country.CountryID = citizen_money.CurID WHERE CitID = ?', [$this->citID()]) as $m) {
            $out[] = ['curID' => (int) $m['CurID'], 'name' => (int) $m['CurID'] === 1 ? 'Tala' : $m['curName'], 'amount' => round((float) $m['Amount'], 2), 'icon' => url((int) $m['CurID'] === 1 ? '/images/tala.gif' : '/images/flags/s/'.$m['cName'].'.gif')];
        }

        return $out;
    }

    protected function battlePayload(array $b): array
    {
        $att = $b['battle_type'] === 'battle' ? ($b['attName'] ?? '') : 'Revolt';

        return [
            'id' => (int) $b['battleID'], 'region' => $b['regionName'] ?? $b['rName'] ?? '', 'regionID' => (int) $b['regionID'],
            'attacker' => $att, 'attackerFlag' => url('/images/flags/s/'.($b['battle_type'] === 'battle' ? ($b['attName'] ?? '') : 'revolt').'.gif'),
            'defender' => $b['defName'] ?? '', 'defenderFlag' => url('/images/flags/s/'.($b['defName'] ?? '').'.gif'),
            'start' => (int) $b['Start'], 'end' => (int) $b['End'], 'wall' => (float) ($b['wall'] ?? 0), 'securePoint' => (float) ($b['extra'] ?? $b['sPoint'] ?? 0),
            'result' => $b['Result'] ?? '', 'type' => $b['battle_type'],
        ];
    }

    protected function articlePayload(array $a): array
    {
        return ['id' => (int) $a['aID'], 'title' => strip_tags((string) $a['aTitle']) ?: '----', 'votes' => (int) ($a['aVotes'] ?? 0), 'time' => (int) $a['timestamp'],
            'newspaper' => ['id' => (int) $a['npID'], 'name' => $a['npName'] ?? '']];
    }

    protected function foods(int $citID): array
    {
        $c = $this->cit();
        $max = ($c['proExpire'] ?? 0) >= time() ? 200 : ((($c['plusExpire'] ?? 0) >= time()) ? 100 : 40);
        $foods = array_fill(1, 5, 0);
        foreach ($this->database->rows("SELECT Stars, COUNT(pID) Amount FROM (SELECT * FROM inventory WHERE Usable = 1 AND Owner = ? LIMIT {$max}) inv WHERE Type = 1 GROUP BY Stars ORDER BY Stars DESC", [$citID]) as $f) {
            $foods[(int) $f['Stars']] = (int) $f['Amount'];
        }

        return $foods;
    }
}
