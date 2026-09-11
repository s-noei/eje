<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of ranks.php + include/ranks/*, online.php and search.php.
 */
class RankingController extends GameController
{
    private const CIT_BY = [1 => 'ep', 2 => 'mSkill', 3 => 'wSkill', 4 => 'medals_lm', 5 => 'medals_gg', 6 => 'medals_wf', 7 => 'medals_is', 8 => 'medals_revolt',
        9 => 'medals_hero', 10 => 'medals_mh', 11 => 'medals_pp', 12 => 'medals_cg', 13 => 'medals_cp', 14 => 'medals_mp', 15 => 'medals_ap', 16 => 'medals_am',
        17 => 'fight_count', 18 => 'total_damage', 19 => 'avgForce', 20 => 'stat_maxadvance'];
    private const CIT_CAP = [1 => 'EP', 2 => 'Skill', 3 => 'Skill', 17 => 'Fights', 18 => 'Advance', 19 => 'Avg. Force', 20 => 'Max. Advance'];
    private const COUN_BY = [1 => 'ep', 2 => 'pop', 3 => 'avgMilitary', 4 => 'avgWorking', 5 => 'avgEP', 6 => 'numComps', 7 => 'apc', 8 => 'inflation'];
    private const COUN_CAP = [1 => 'EP', 2 => 'POP', 3 => 'Skill', 4 => 'Skill', 5 => 'Avg. EP', 6 => 'Companies', 7 => 'APC', 8 => 'Inflation'];

    /** ranking[-{what}[-{page}[-{p1}[-{p2}]]]].html */
    public function ranking(string $what = '', int $page = 1, int $p1 = 0, int $p2 = 0)
    {
        if (!in_array($what, ['citizens', 'countries', 'parties', 'newspapers', 'battles'], true)) {
            return redirect($this->vars->getURL('ranking', 'citizens', $page ?: 1, $p1, $p2));
        }
        $db = $this->database;
        $page = max(1, $page);
        $start = ($page - 1) * 10;
        $isAdmin = $this->session->isAdmin();
        if ($p1 && $what !== 'countries' && $what !== 'battles' && $db->isHiddenCountry($p1) && !$isAdmin) {
            return redirect('/index.html');
        }

        $flag = $this->vars->getImgLoc('CountryFlag').'l/world.gif';
        $cName = 'World';
        if ($what !== 'countries' && $p1) {
            $cName = $db->getCountryC($p1);
            if (!$cName) {
                return redirect($this->vars->getURL('ranking', $what, $page, 0, $p2));
            }
            $flag = $db->getCountryFlagC($p1, $this->vars->getImgLoc('CountryFlag'));
        }

        $data = ['what' => $what, 'page' => $page, 'p1' => $p1, 'p2' => $p2, 'start' => $start, 'flag' => $flag, 'cName' => $cName,
            'countries' => $db->getCountries($isAdmin)];

        switch ($what) {
            case 'citizens':
                $p2 = $p2 ?: 1;
                $type = self::CIT_BY[$p2] ?? 'ep';
                $sel = $p2 == 19 ? 'IFNULL(ROUND(citizens.total_damage/citizens.fight_count, 2), 0) AS avgForce' : "citizens.$type";
                $sql = "SELECT citizens.CitizenID, citizens.name, citizens.Avatar, country.cName, country.Flag, country.CountryID, $sel
                    FROM country LEFT JOIN region ON country.CountryID = region.CountryID
                    JOIN citizens ON citizens.regionID = region.RegionID
                    WHERE citizens.accType = 'citizen' AND citizens.ban_due != 'PERMANENTLY' AND citizens.wellness > '0'";
                $b = [];
                if ($p1) {
                    $sql .= ' AND country.CountryID = ?';
                    $b[] = $p1;
                }
                $sql .= " ORDER BY $type DESC".($type === 'mSkill' ? ', mSP DESC, ep DESC' : ($type === 'wSkill' ? ', wSP DESC, ep DESC' : ($type !== 'ep' ? ', ep DESC' : '')));
                $data += ['p2' => $p2, 'type' => $type, 'typeC' => self::CIT_CAP[$p2] ?? 'Trophies',
                    'rows' => $db->rows($sql." LIMIT $start, 10", $b), 'hasNext' => $this->ranks()->getRankCount('citizen', $p1) > $start + 10];
                break;
            case 'countries':
                $p1 = $p1 ?: 1;
                $type = self::COUN_BY[$p1] ?? 'ep';
                $data += ['p1' => $p1, 'type' => $type, 'typeC' => self::COUN_CAP[$p1] ?? 'EP',
                    'rows' => $db->rows("SELECT stat_country.CountryID, country.cName, country.Flag, stat_country.$type AS val FROM country
                        JOIN stat_country ON country.CountryID = stat_country.CountryID ORDER BY stat_country.$type DESC LIMIT $start, 10"),
                    'hasNext' => $start + 10 < 42];
                break;
            case 'parties':
                $start = ($page - 1) * 5;
                $data += ['start' => $start, 'rows' => $this->ranks()->rankParties($p1, $start, 5), 'hasNext' => $this->ranks()->getRankCount('party', $p1) > $start + 5];
                break;
            case 'newspapers':
                $data += ['rows' => $this->ranks()->rankNPs($p1, $start, 10), 'hasNext' => $this->ranks()->getRankCount('newspaper', $p1) > $start + 10];
                break;
            case 'battles':
                $rows = $db->rows("SELECT battles.battleID, battles.battle_type, region.rName AS RegionName, def.cName AS DefName, def.Flag AS DefFlag, att.cName AS AttName, att.Flag AS AttFlag,
                        stat_totdmg AS TotDamage, stat_totfights AS TotFight
                    FROM battles JOIN region ON region.RegionID = battles.regionID
                    LEFT JOIN country att ON att.CountryID = battles.Attacker JOIN country def ON def.CountryID = battles.Defender
                    ORDER BY TotDamage DESC LIMIT $start, 10");
                foreach ($rows as &$r) {
                    if ($r['battle_type'] === 'revolt') {
                        $r['AttFlag'] = 'revolt';
                        $r['AttName'] = 'Revolt Force';
                    } else {
                        $r['AttFlag'] = 'l/'.$r['AttFlag'];
                    }
                    $r['DefFlag'] = 'l/'.$r['DefFlag'];
                }
                $data += ['rows' => $rows, 'hasNext' => $start + 10 < 42];
                break;
        }

        return $this->page('pages.ranks.index', $data, ['title' => $this->lang->getstr('title_ranking', 'title'), 'bar_title' => 'Ranking Center', 'actiontype' => 'ranking']);
    }

    /** online[-{page}[-{cID}]].html */
    public function online(int $page = 1, int $coun = 0)
    {
        $db = $this->database;
        $page = max(1, $page);
        $start = ($page - 1) * 10;
        if ($coun) {
            $cName = $db->getCountryC($coun);
            if (!$cName) {
                return redirect($this->vars->getURL('online', 1));
            }
            $flag = $db->getCountryFlagC($coun, $this->vars->getImgLoc('CountryFlag'));
        } else {
            $cName = 'World';
            $flag = $this->vars->getImgLoc('CountryFlag').'world.gif';
        }
        $sql = 'SELECT active_users.timestamp as LastActivity, citizens.*, region.*, country.* FROM active_users
            JOIN citizens ON citizens.name = active_users.username
            JOIN region ON region.RegionID = citizens.RegionID
            JOIN country ON country.CountryID = region.CountryID';
        $b = [];
        if ($coun) {
            $sql .= ' WHERE country.CountryID = ?';
            $b[] = $coun;
        }
        $sql .= ' ORDER BY LastActivity DESC';

        return $this->page('pages.ranks.online', [
            'page' => $page, 'start' => $start, 'coun' => $coun, 'cName' => $cName, 'flag' => $flag,
            'countries' => $db->getCountries($this->session->isAdmin()),
            'num' => $db->count($sql, $b),
            'rows' => $db->rows($sql." LIMIT $start, 10", $b),
        ], ['title' => $this->lang->getstr('title_online', 'title'), 'bar_title' => 'Online tracker', 'actiontype' => 'online']);
    }

    /** search.html */
    public function search(Request $request)
    {
        $sq = (string) $request->input('search', '');
        if ($sq === '' || $sq === 'Search!') {
            return redirect('/index.html');
        }
        $field = in_array($request->input('field'), ['citizen', 'company'], true) ? $request->input('field') : 'citizen';

        return $this->page('pages.ranks.search', ['sq' => $sq, 'field' => $field, 'rows' => $this->database->search($sq, $field)],
            ['title' => $this->lang->getstr('title_search', 'title'), 'bar_title' => 'Search', 'actiontype' => 'search']);
    }
}
