<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/** lens/include/modmgr/* — moderator management (admin only). */
class ModmgrController extends LensController
{
    public const ACCS = ['', 'Normal user', 'Forum moderator', 'Forum Administrator', 'Technical moderator', 'Local moderator', 'Police', 'Super moderator', 'Guard', 'Administrator'];
    public const ACCESS1 = ['No access', 'View stats', 'Track', 'Edit'];
    public const ACCESS2 = ['No access', 'Have access'];
    public const AT_FIELDS = ['at_citizen', 'at_company', 'at_country', 'at_elections', 'at_multrack', 'at_transactions', 'at_tickets', 'at_payments', 'at_ads', 'at_mods', 'at_punishment', 'at_viewmoney'];

    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_mods'] || $this->level() !== self::ACCESS_ADMIN) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $links = [['Stats', self::url('modmgr')], ['Edit citizen', self::url('modmgr', 'edit')], ['Pending registrations', self::url('modmgr', 'regs')]];
        $base = ['links' => $links, 'type' => $type, 'id' => (int) $id, 'accs' => self::ACCS, 'access1' => self::ACCESS1, 'access2' => self::ACCESS2];
        $loadMod = fn () => $id ? $db->row('SELECT lens_info.*, citizens.* FROM lens_info JOIN citizens ON citizens.CitizenID = lens_info.AssignedTo WHERE AssignedTo = ?', [(int) $id]) : null;

        if ($type === 'regs') {
            return $this->regs($request, $base);
        }

        if ($type === 'edit') {
            if ($request->filled('citID')) {
                return redirect(self::url('modmgr', 'edit', (int) $request->input('citID')));
            }
            $cit = $loadMod();
            $msg = '';
            if ($id && $cit && $request->input('subchange') && isset(self::ACCS[(int) $request->input('access')])) {
                $newAcc = (int) $request->input('access');
                $db->exec('UPDATE lens_info SET Access = ?, AccessD = ? WHERE AssignedTo = ?', [$newAcc, (int) $request->input('AccessD'), (int) $id]);
                if ((int) $cit['Access'] !== $newAcc) {
                    $newMID = chr($newAcc + 61).substr($cit['ModID'], 1);
                    $db->exec('UPDATE lens_info SET ModID = ? WHERE AssignedTo = ?', [$newMID, (int) $id]);
                    $db->sendPM(1, (int) $id, 'Change in Moderation access', 'Your post in eJahan Lens is changed to '.self::ACCS[$newAcc].", and your new Mod ID is $newMID.<br>Your password is as same as before.<br><br>eJahan Administration team.");
                }

                return redirect($request->getRequestUri());
            }
            if ($id && $cit && $request->input('subeditdone')) {
                $sets = [];
                $b = [];
                foreach (self::AT_FIELDS as $f) {
                    $sets[] = "$f = ?";
                    $b[] = $f === 'at_tickets' ? (string) $request->input($f, '') : (int) $request->input($f, 0);
                }
                $b[] = (int) $id;
                $db->exec('UPDATE lens_info SET '.implode(', ', $sets).' WHERE AssignedTo = ?', $b);

                return redirect($request->getRequestUri());
            }
            if ($id && $cit && $request->input('subinvite')) {
                $msg = $this->invite((int) $id, (int) $request->input('access', 4));
            }
            if ($cit && (($cit['userlevel'] == 9 && $this->mod['CitizenID'] != 1) || $cit['accType'] === 'co-account')) {
                return redirect(self::url('modmgr'));
            }

            return $this->lensPage('lens.modmgr.edit', $base + ['cit' => $cit, 'msg' => $msg], 'Mod management', 'modmgr');
        }

        if ($request->filled('citID')) {
            return redirect(self::url('modmgr', 'view', (int) $request->input('citID')));
        }
        $cit = $loadMod();
        $msg = '';
        if ($id && !$cit && $request->input('subinvite')) {
            $msg = $this->invite((int) $id, (int) $request->input('access', 4));
        }
        $data = ['cit' => $cit, 'msg' => $msg];
        if ($cit) {
            $mid = $cit['ModID'];
            $rate = $db->row('SELECT COUNT(rate) Nums, SUM(rate) Tot, ROUND(SUM(rate) / COUNT(rate), 2) Avg FROM ticket_posts WHERE by_name = ? AND auto_answer = 0 AND rate > 0', [$mid]);
            $data['activity'] = [
                'replied' => $db->count('SELECT post_id FROM ticket_posts WHERE by_name = ? GROUP BY ticket_id', [$mid]),
                'total' => $db->count('SELECT post_id FROM ticket_posts WHERE by_name = ?', [$mid]),
                'auto' => $db->count('SELECT post_id FROM ticket_posts WHERE by_name = ? AND auto_answer = 1', [$mid]),
                'rate' => $rate,
            ];
        }

        return $this->lensPage('lens.modmgr.home', $base + $data, 'Mod management', 'modmgr');
    }

    /** Invite a citizen to register in lens (legacy only echoed the message; we also send it as a PM). */
    private function invite(int $citID, int $access): string
    {
        if (!isset(self::ACCS[$access]) || $access < 4) {
            return 'Invalid access level.';
        }
        $inv = md5($citID.'Lens Access');
        $this->database->exec('INSERT INTO lens_registration (citID, invID, invBy, access, status) VALUES (?, ?, ?, ?, 0)', [$citID, $inv, $this->mod['ModID'], $access]);
        $regLink = url(self::url('register', $inv));
        $msg = "Moderator {$this->mod['ModID']} sent an invite for you to register in eJahan Lens as a ".self::ACCS[$access].'. You can register in Lens via this link: '
            ."<a href=\"$regLink\">$regLink</a><br><br>eJahan Administration team";
        $this->database->sendPM(1, $citID, 'eJahan Lens invitation', $msg);

        return $msg;
    }

    private function regs(Request $request, array $base)
    {
        $db = $this->database;
        $citID = (int) $request->input('citID');
        if ($citID && $request->input('subapprove')) {
            $reg = $db->row('SELECT * FROM lens_registration WHERE citID = ?', [$citID]);
            $row = $db->row('SELECT * FROM citizens WHERE CitizenID = ?', [$citID]);
            if ($reg && $row) {
                $db->exec('UPDATE lens_registration SET status = 2 WHERE citID = ?', [$citID]);
                $access = (int) $reg['access'];
                $modID = chr($access + 61).strtoupper(dechex(($citID % 240) + 16)).'-';
                for ($i = 1; $i <= 4; $i++) {
                    $modID .= random_int(1, 9);
                }
                $at = [
                    'citz' => $access > 4 ? 2 : 0, 'comp' => $access > 4 ? 2 : 0, 'cont' => $access > 4 ? 2 : 0, 'elec' => $access > 5 ? 1 : 0,
                    'trns' => $access > 6 ? 2 : ($access > 4 ? 1 : 0),
                    'tick' => $access == 4 ? 'bugreport' : (($access > 4 && $access < 7) ? 'abuse' : ($access == 7 ? 'smod' : ($access == 8 ? 'guard' : ($access == 9 ? 'admin' : '')))),
                    'mult' => $access == 6 ? 1 : ($access > 6 ? 2 : 0),
                    'pnsh' => $access == 5 ? 1 : ($access == 6 ? 3 : ($access == 7 ? 4 : 0)),
                    'vmon' => $access > 6 ? 1 : 0,
                ];
                $db->exec('INSERT INTO lens_info (ModID, Password, LensID, AssignedTo, Access, AccessD, at_citizen, at_company, at_country, at_elections, at_transactions, at_tickets, at_multrack, at_punishment, at_viewmoney, ModVio)
                    VALUES (?, ?, "", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
                    [$modID, $row['password'], $citID, $access, (int) $reg['country'], $at['citz'], $at['comp'], $at['cont'], $at['elec'], $at['trns'], $at['tick'], $at['mult'], $at['pnsh'], $at['vmon']]);
                $db->sendPM(1, $citID, 'Lens registration result', 'The moderators reviewed your request to register in eJahan Lens and they approved it.'
                    ."<br><b>Your login info:</b> $modID<br><b>Your password:</b> Your current citizen password<br>You can log into lens <a href=\"".url('/lens/login.html').'">here</a>.<br><br>Your eJahan moderation team');
            }

            return redirect($request->getRequestUri());
        }
        if ($citID && $request->input('subreject')) {
            $db->exec('UPDATE lens_registration SET status = 3 WHERE citID = ?', [$citID]);
            $db->sendPM(1, $citID, 'Lens registration result', 'The moderators reviewed your request to register in eJahan Lens and they rejected it. It may be because you entered wrong or incomplete data. Please double check your registration data and submit it again.<br><br>Your eJahan moderation team');

            return redirect($request->getRequestUri());
        }
        if ($citID && $request->input('subremove')) {
            $db->exec('UPDATE lens_registration SET status = 4 WHERE citID = ?', [$citID]);

            return redirect($request->getRequestUri());
        }
        $rows = $db->rows('SELECT lens_registration.*, citizens.name, country.cName FROM lens_registration JOIN citizens ON citID = CitizenID LEFT JOIN country ON country.CountryID = lens_registration.country');

        return $this->lensPage('lens.modmgr.regs', $base + ['rows' => $rows], 'Mod management', 'modmgr');
    }
}
