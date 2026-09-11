<?php

namespace App\Http\Controllers;

use App\Game\Services\GameDatabase;

/**
 * Port of xpand/* — the public "Xpand" API (JSON/XML region + citizen info).
 */
class XpandController extends Controller
{
    public function __construct(protected GameDatabase $database)
    {
    }

    public function index()
    {
        return view('pages.xpand');
    }

    private function noCache($resp)
    {
        return $resp->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate', 'Pragma' => 'no-cache', 'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT']);
    }

    private function region(int $id): ?array
    {
        $r = $this->database->row('SELECT region.*, Owner.cName AS OwnerName, Origin.cName AS OriginName FROM region
            JOIN country AS Owner ON Owner.CountryID = region.CountryID JOIN country AS Origin ON Origin.CountryID = region.oCountryID WHERE region.RegionID = ?', [$id]);
        if (!$r) {
            return null;
        }

        return ['regionID' => (int) $r['RegionID'], 'name' => $r['rName'], 'countryID' => (int) $r['CountryID'], 'countryName' => $r['OwnerName'],
            'oCountryID' => (int) $r['oCountryID'], 'oCountryName' => $r['OriginName'], 'clinic' => $r['Clinic'], 'population' => $this->database->getRegionPop($id)];
    }

    /** region-{id}.html → JSON */
    public function regionJson(int $id)
    {
        $r = $this->region($id);
        if (!$r) {
            return response('Invalid region', 404)->header('Content-Type', 'text/plain');
        }

        return $this->noCache(response(json_encode($r), 200)->header('Content-Type', 'text/plain; charset=utf-8'));
    }

    /** region-{id}.xml */
    public function regionXml(int $id)
    {
        $r = $this->region($id);
        if (!$r) {
            return response('Invalid region', 404)->header('Content-Type', 'text/plain');
        }
        $x = new \SimpleXMLElement('<region/>');
        $x->addChild('name', htmlspecialchars($r['name']));
        $x->addChild('id', (string) $r['regionID']);
        $x->addChild('pop', (string) $r['population']);
        $x->addChild('country', htmlspecialchars($r['countryName']));
        $x->addChild('country-id', (string) $r['countryID']);
        $x->addChild('original', htmlspecialchars($r['oCountryName']));
        $x->addChild('original-id', (string) $r['oCountryID']);
        $x->addChild('clinic', (string) $r['clinic']);
        $n = $x->addChild('neighbors');
        foreach ($this->database->rows('SELECT region.RegionID, region.rName AS RegionName, country.CountryID, country.cName AS CountryName FROM neighbors
            JOIN region ON region.RegionID = neighbors.Region2 JOIN country ON region.CountryID = country.CountryID WHERE Region1 = ?', [$id]) as $row) {
            $c = $n->addChild('neighbor');
            $c->addChild('name', htmlspecialchars($row['RegionName']));
            $c->addChild('id', (string) $row['RegionID']);
            $c->addChild('country', htmlspecialchars($row['CountryName']));
            $c->addChild('country-id', (string) $row['CountryID']);
        }

        return $this->noCache(response($x->asXML(), 200)->header('Content-Type', 'text/xml; charset=utf-8'));
    }

    /** citizen-{id}.xml */
    public function citizenXml(int $id)
    {
        $o = $this->database->row('SELECT citizens.*, region.rName, region.CountryID, country.cName FROM citizens JOIN region ON region.RegionID = citizens.regionID
            JOIN country ON country.CountryID = region.CountryID WHERE citizens.CitizenID = ?', [$id]);
        if (!$o) {
            return response('Invalid citizen', 404)->header('Content-Type', 'text/plain');
        }
        $x = new \SimpleXMLElement('<citizen/>');
        $x->addChild('name', htmlspecialchars($o['name']));
        $x->addChild('id', (string) $o['CitizenID']);
        $x->addChild('account-type', $o['accType']);
        $x->addChild('banned', $o['ban_due'] === 'PERMANENTLY' ? 'yes' : 'no');
        $x->addChild('region-name', htmlspecialchars($o['rName']));
        $x->addChild('region-id', (string) $o['regionID']);
        $x->addChild('country-name', htmlspecialchars($o['cName']));
        $x->addChild('country-id', (string) $o['CountryID']);
        if ($o['accType'] === 'citizen') {
            foreach (['nationality' => $o['nationality'], 'join-day' => $o['joined'] ?: 'prebeta', 'wellness' => $o['wellness'], 'ep' => $o['ep'], 'skill-work' => $o['wSkill'],
                'skill-mili' => $o['mSkill'], 'rank-mili' => $o['mRank'], 'total-fight' => $o['fight_count'], 'total-advance' => $o['total_damage'],
                'trophies-lucky-miner' => $o['medals_lm'], 'trophies-party-president' => $o['medals_pp'], 'trophies-congress' => $o['medals_cg'],
                'trophies-country-president' => $o['medals_cp'], 'trophies-revolt' => $o['medals_revolt'], 'trophies-battle-hero' => $o['medals_hero']] as $k => $v) {
                $x->addChild($k, (string) $v);
            }
        }

        return $this->noCache(response($x->asXML(), 200)->header('Content-Type', 'text/xml; charset=utf-8'));
    }

    /** citizen-{name}.html → JSON (legacy json/citninfo.php looked citizens up by name) */
    public function citizenJson(string $name)
    {
        $o = $this->database->row('SELECT citizens.CitizenID, citizens.name, citizens.wSkill, citizens.mSkill, citizens.ep, citizens.accType, region.rName, country.cName
            FROM citizens JOIN region ON region.RegionID = citizens.regionID JOIN country ON region.CountryID = country.CountryID WHERE name = ?', [$name]);
        if (!$o) {
            return response('Invalid citizen', 404)->header('Content-Type', 'text/plain');
        }

        return $this->noCache(response(json_encode($o), 200)->header('Content-Type', 'text/plain; charset=utf-8'));
    }
}
