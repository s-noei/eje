<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/** lens/include/citizen/* */
class CitizenController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_citizen']) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $links = [['Stats', self::url('citizen')]];
        if ($this->mod['at_citizen'] >= 2) {
            $links[] = ['Tracker', self::url('citizen', 'tracker')];
        }
        if ($this->mod['at_citizen'] >= 3) {
            $links[] = ['Actions', self::url('citizen', 'actions')];
        }
        $base = ['links' => $links, 'type' => $type, 'id' => $id];

        if ($type === 'tracker' && $this->mod['at_citizen'] >= 2) {
            if ($request->filled('trackid')) {
                return redirect(self::url('citizen', 'tracker', (int) $request->input('trackid')));
            }
            $data = ['cit' => null];
            if ($id) {
                $cit = $db->row('SELECT citizens.*, referrer.CitizenID AS refID, referrer.name AS refName FROM citizens LEFT JOIN citizens AS referrer ON citizens.referrer = referrer.CitizenID WHERE citizens.CitizenID = ?', [(int) $id]);
                $data['cit'] = $cit;
                if ($cit) {
                    $data['money'] = $this->mod['at_viewmoney'] ? $db->rows('SELECT citizen_money.Amount, country.curName FROM citizen_money JOIN country ON country.curID = citizen_money.CurID WHERE CitID = ?', [$cit['CitizenID']]) : null;
                    $data['invites'] = $db->rows("SELECT citizens.name, citizens.CitizenID FROM invites JOIN citizens ON citizens.CitizenID = invites.toID WHERE invites.byID = ? AND invites.toID != ''", [$cit['CitizenID']]);
                    $logins = $db->rows("SELECT `Desc`, COUNT(`Desc`) AS Times FROM reports WHERE repType = 'login' AND citID = ? GROUP BY `Desc` ORDER BY Times DESC", [$cit['CitizenID']]);
                    foreach ($logins as &$l) {
                        $l['conflicts'] = $db->count("SELECT `Desc` FROM reports JOIN citizens ON citizens.CitizenID = reports.citID WHERE repType = 'login' AND `Desc` = ? AND citID != ? AND accType = 'citizen' GROUP BY citID", [$l['Desc'], $cit['CitizenID']]);
                    }
                    $data['logins'] = $logins;
                }
            }

            return $this->lensPage('lens.citizen.tracker', $base + $data, 'Citizen tools', 'citizen');
        }

        if ($type === 'actions' && $this->mod['at_citizen'] >= 3) {
            if ($request->filled('targetid')) {
                return redirect(self::url('citizen', 'actions', (int) $request->input('targetid')));
            }
            $errors = [];
            $cit = $id ? $db->row('SELECT * FROM citizens WHERE CitizenID = ?', [(int) $id]) : null;
            if ($cit && $request->isMethod('post')) {
                $cid = $cit['CitizenID'];
                $back = fn () => redirect($request->getRequestUri());
                if ($request->input('subinfo')) {
                    $db->exec('UPDATE citizens SET email = ? WHERE CitizenID = ?', [(string) $request->input('email'), $cid]);

                    return $back();
                }
                if ($request->input('subname')) {
                    $db->exec('UPDATE citizens SET name = ? WHERE CitizenID = ?', [strip_tags((string) $request->input('name')), $cid]);

                    return $back();
                }
                if ($request->input('subpass')) {
                    $p1 = (string) $request->input('pass1');
                    if ($p1 !== (string) $request->input('pass2') || strlen($p1) < 6) {
                        $errors[] = 'ERROR: Password is incorrect!';
                    } else {
                        $db->exec('UPDATE citizens SET password = ? WHERE CitizenID = ?', [\Illuminate\Support\Facades\Hash::make($p1), $cid]);

                        return $back();
                    }
                }
                $medals = ['lm', 'pp', 'cg', 'cp', 'wf', 'ap', 'mp'];
                if ($request->input('subaddmed') && in_array($request->input('mtype'), $medals, true)) {
                    $db->addMedal($cid, (string) $request->input('mtype'));

                    return $back();
                }
                if ($request->input('subdelmed') && in_array($request->input('mtype'), $medals, true)) {
                    $db->delMedal($cid, (string) $request->input('mtype'));

                    return $back();
                }
                if ($request->input('subactivate')) {
                    $db->exec('UPDATE citizens SET active = 1 WHERE CitizenID = ?', [$cid]);

                    return $back();
                }
                if ($request->input('subremavatar')) {
                    $db->exec("UPDATE citizens SET avatar = 'no-avatar-m.gif' WHERE CitizenID = ?", [$cid]);

                    return $back();
                }
                if ($request->input('subremabout')) {
                    $db->exec("UPDATE citizens SET aboutme = '' WHERE CitizenID = ?", [$cid]);

                    return $back();
                }
            }

            return $this->lensPage('lens.citizen.actions', $base + ['cit' => $cit, 'errors' => $errors], 'Citizen tools', 'citizen');
        }

        $t = time();
        $today = $db->today;
        $stats = [
            ['Total citizens', $db->count("SELECT CitizenID FROM citizens WHERE accType = 'citizen'")],
            ['Total co-accounts', $db->count("SELECT CitizenID FROM citizens WHERE accType = 'co-account'")],
            ['Active citizens<br>(active within 3 days)', $db->count("SELECT CitizenID FROM citizens WHERE accType = 'citizen' AND timestamp >= ?", [$t - 3 * 86400])],
            ['Highly active citizens<br>(active within 24 hours)', $db->count("SELECT CitizenID FROM citizens WHERE accType = 'citizen' AND timestamp >= ?", [$t - 86400])],
            ['Banned accounts', $db->count("SELECT CitizenID FROM citizens WHERE ban_due != ''")],
            ['Signed up today', $db->count("SELECT CitizenID FROM citizens WHERE accType = 'citizen' AND joined = ?", [$today])],
            ['Signed up yesterday', $db->count("SELECT CitizenID FROM citizens WHERE accType = 'citizen' AND joined = ?", [$today - 1])],
        ];

        return $this->lensPage('lens.citizen.home', $base + ['stats' => $stats], 'Citizen tools', 'citizen');
    }
}
