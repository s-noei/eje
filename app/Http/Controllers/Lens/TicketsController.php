<?php

namespace App\Http\Controllers\Lens;

use App\Game\Services\Moderation;
use Illuminate\Http\Request;

/** lens/include/tickets/* — ticket center + appeals. */
class TicketsController extends LensController
{
    public const PRIORS = ['<font color="gray">Low</font>', '<font color="green">Medium</font>', '<font color="orange">High</font>', '<font color="red">Critical</font>'];
    public const STATS = ['<font color="red">Pending</font>', '<font color="orange">Waiting for reply</font>', '<font color="blue">Closed</font>'];
    public const SECS = ['bugreport' => 'Report bugs', 'multi' => 'Report multiple accounts', 'feedback' => 'Feedback', 'support' => 'Support eJahan', 'payment' => 'Payment issues',
        'supgame' => 'Game support', 'modreport' => 'Report a moderator', 'abuse' => 'Report obvious content', 'appeal' => 'Appeals', 'modticket' => 'Mod tickets'];

    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_tickets']) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $tick = (string) $this->mod['at_tickets'];
        $istopmod = in_array($tick, ['admin', 'smod', 'guard'], true);
        $allowed = $this->ticketReasons();
        if ($request->filled('ttype')) {
            $tt = (string) $request->input('ttype');
            $tt = $tt === 'modtickets' ? 'modticket' : $tt;

            return redirect(self::url('tickets', $tt));
        }
        $common = ['priors' => self::PRIORS, 'stats' => self::STATS, 'secs' => self::SECS, 'istopmod' => $istopmod, 'tick' => $tick, 'allowed' => $allowed, 'type' => $type];

        if ($type === 'view' && $id) {
            return $this->view($request, (int) $id, $common);
        }
        if ($type === 'appeal' && $id) {
            return $this->viewAppeal($request, (int) $id, $common);
        }

        $atype = $istopmod ? (string) $type : $tick;
        if ($atype === 'modtickets') {
            $atype = 'modticket';
        }
        $data = $common + ['atype' => $atype, 'rows' => [], 'localCountry' => null];
        if ($atype === 'appeal' && in_array('appeal', $allowed, true)) {
            if ($this->canDoActions()) {
                if ($request->filled('proc')) {
                    $db->exec("UPDATE tickets SET processed = '1' WHERE ticketID = ?", [(int) $request->input('proc')]);
                }
                if ($request->filled('unproc')) {
                    $db->exec("UPDATE tickets SET processed = '0' WHERE ticketID = ?", [(int) $request->input('unproc')]);
                }
            }
            $data['rows'] = $db->rows("SELECT forfeit_forfeits.*, forfeit_forfeits.CitID AS sender, citizens.name FROM forfeit_forfeits JOIN citizens ON citizens.CitizenID = forfeit_forfeits.citID
                WHERE NOT ISNULL(appeal) AND ISNULL(appeal_reply) AND forfeit_forfeits.Active = '1' ORDER BY timestamp DESC");

            return $this->lensPage('lens.tickets.appeals', $data, 'Tickets center', 'tickets');
        }
        if ($atype && in_array($atype, $allowed, true)) {
            $sql = 'SELECT ticket_head.*, citizens.name FROM ticket_head JOIN citizens ON citizens.CitizenID = ticket_head.by_id WHERE reason = ?';
            $b = [$atype];
            if ((int) $this->mod['Access'] === self::ACCESS_LOCAL) {
                $sql .= ' AND country = ?';
                $b[] = $this->mod['AccessD'];
                $data['localCountry'] = $db->getCountryC($this->mod['AccessD']);
            }
            $data['rows'] = $db->rows($sql.' AND status = 0 ORDER BY priority DESC, last_reply DESC', $b);

            return $this->lensPage('lens.tickets.list', $data, 'Tickets center', 'tickets');
        }

        return $this->lensPage('lens.tickets.list', $data + ['atype' => null], 'Tickets center', 'tickets');
    }

    private function view(Request $request, int $id, array $common)
    {
        $db = $this->database;
        $tInfo = $db->row('SELECT ticket_head.*, ticket_head.by_id AS sender, citizens.name FROM ticket_head JOIN citizens ON citizens.CitizenID = ticket_head.by_id WHERE ticket_id = ?', [$id]);
        if (!$tInfo) {
            return redirect(self::url('tickets'));
        }
        if (!in_array($tInfo['reason'], $common['allowed'], true) || ((int) $this->mod['Access'] === self::ACCESS_LOCAL && $tInfo['country'] != $this->mod['AccessD'])) {
            return $this->lensPage('lens.tickets.denied', $common, 'Tickets center', 'tickets');
        }
        $modName = (string) $this->mod['ModID'];
        if ($request->input('subreply') && $tInfo['status'] == 0) {
            $time = time();
            $db->exec('INSERT INTO ticket_posts (ticket_id, by_id, by_name, body, timestamp) VALUES (?, 1, ?, ?, ?)', [$id, $modName, (string) $request->input('reply'), $time]);
            $db->exec('UPDATE ticket_head SET status = ?, last_reply = ?, last_replier = ? WHERE ticket_id = ?', [$request->has('opened') ? 1 : 2, $time, $modName, $id]);
            $db->sendNote($tInfo['by_id'], '', "Ticket #$id which was created by you is replied by moderators. You can view it <a href=\"".$this->vars->getURL('contact', 'view', $id).'">here</a>!');

            return redirect($request->getRequestUri());
        }
        if ($request->input('submove') && $tInfo['status'] == 0) {
            $newsec = (string) $request->input('newsec');
            if (isset(self::SECS[$newsec]) && $newsec !== $tInfo['reason']) {
                $time = time();
                $reply = 'Your ticket moved from '.(self::SECS[$tInfo['reason']] ?? $tInfo['reason']).' to '.self::SECS[$newsec].'.';
                $db->exec('INSERT INTO ticket_posts (ticket_id, by_id, by_name, body, auto_answer, timestamp) VALUES (?, 1, ?, ?, 1, ?)', [$id, $modName, $reply, $time]);
                $db->exec('UPDATE ticket_head SET reason = ?, last_reply = ?, last_replier = ? WHERE ticket_id = ?', [$newsec, $time, $modName, $id]);
                $db->sendNote($tInfo['sender'], '', "Ticket #$id which was created by you is moved to a new department by moderators. You can view this ticket <a href=\"".$this->vars->getURL('contact', 'view', $id).'">here</a>!');
            }

            return redirect($request->getRequestUri());
        }

        return $this->lensPage('lens.tickets.view', $common + ['tInfo' => $tInfo, 'posts' => $db->rows('SELECT * FROM ticket_posts WHERE ticket_id = ? ORDER BY timestamp', [$id])], 'Tickets center', 'tickets');
    }

    private function viewAppeal(Request $request, int $id, array $common)
    {
        $db = $this->database;
        $mod = app(Moderation::class);
        if (!in_array('appeal', $common['allowed'], true)) {
            return $this->lensPage('lens.tickets.denied', $common, 'Tickets center', 'tickets');
        }
        if ($request->input('reply')) {
            $db->exec('UPDATE forfeit_forfeits SET appeal_reply = ? WHERE ID = ? AND NOT ISNULL(appeal) AND ISNULL(appeal_reply)', [strip_tags((string) $request->input('appreply')), $id]);
        }
        if ($request->input('deactive')) {
            $mod->deactiveViolation($id);
        }
        $tInfo = $db->row('SELECT forfeit_forfeits.*, citizens.name, starter.name AS byName FROM forfeit_forfeits JOIN citizens ON citizens.CitizenID = forfeit_forfeits.citID
            LEFT JOIN citizens AS starter ON starter.CitizenID = forfeit_forfeits.byID WHERE ID = ?', [$id]);
        if (!$tInfo) {
            return redirect(self::url('tickets', 'appeal'));
        }
        if ($request->input('unban')) {
            $mod->unbanCitizen($tInfo['citID']);
        }
        if ($request->isMethod('post')) {
            return redirect($request->getRequestUri());
        }

        return $this->lensPage('lens.tickets.viewapp', $common + ['tInfo' => $tInfo, 'banned' => $db->usernameBanned($tInfo['citID'])], 'Tickets center', 'tickets');
    }
}
