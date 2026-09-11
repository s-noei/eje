<?php

namespace App\Http\Controllers;

use App\Game\Services\NoteParser;
use Illuminate\Http\Request;

/**
 * Port of mailbox.php + include/mail/{inbox,sent,notes,requests,compose,viewpm}.php.
 * URL: mail.html, mail-{go}-{id}.html (id may be empty, e.g. mail-compose-.html).
 */
class MailController extends GameController
{
    public function index(Request $request, ?string $go = null, ?string $id = null)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('pm');
        $db = $this->database;
        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'];
        $back = fn () => redirect($request->getRequestUri());
        $ids = fn (string $key) => array_map('intval', array_keys((array) $request->input($key, [])));

        if ($request->isMethod('post')) {
            if ($request->input('subdeli') && ($arr = $ids('mail'))) {
                $db->exec('UPDATE pm SET isRead = 1, rem_inbox = 1 WHERE pmID IN ('.implode(',', $arr).') AND toID = ?', [$me]);

                return $back();
            }
            if ($request->input('subdels') && ($arr = $ids('mail'))) {
                $db->exec('UPDATE pm SET rem_sent = 1 WHERE pmID IN ('.implode(',', $arr).') AND fromID = ?', [$me]);

                return $back();
            }
            if ($request->input('subdeln') && ($arr = $ids('note'))) {
                $db->exec('DELETE FROM notes WHERE NoteID IN ('.implode(',', $arr).') AND toID = ?', [$me]);

                return $back();
            }
            if ($request->input('toDo') === 'compose') {
                $toDet = $db->getUserInfo((string) $request->input('toName'));
                if ($toDet && (int) $toDet['userlevel'] === (int) config('ejahan.levels.admin', 9)) {
                    return redirect($this->vars->getURL('contact'));
                }
                if ($toDet) {
                    $db->sendPM($me, $toDet['CitizenID'], strip_tags((string) $request->input('subject')), (string) $request->input('body'));
                }

                return redirect($this->vars->getURL('mail'));
            }
        }

        $havePro = $this->havePro();
        $havePlus = $this->havePlus();
        $page = (int) ($id ?: 1);
        if ($page < 1) {
            $page = 1;
        }
        if (!$havePro && $page > 5) {
            $page = 5;
        }
        if (!($havePlus || $havePro) && $page > 3) {
            $page = 3;
        }
        $nums = 10;
        $start = ($page - 1) * $nums;
        $target = $go ?: 'inbox';
        $data = ['target' => $target, 'page' => $page, 'start' => $start, 'nums' => $nums, 'errors' => []];

        switch ($target) {
            case 'delete_i':
                return $db->deletePM((int) $id, $me) ? redirect($this->vars->getURL('mail')) : $this->error('An error occured...');
            case 'delete_s':
                return $db->deletePM((int) $id, $me, 'sent') ? redirect($this->vars->getURL('mail')) : $this->error('An error occured...');
            case 'delete_n':
                return $db->deleteNote((int) $id, $me) ? redirect($this->vars->getURL('mail', 'notes')) : $this->error('An error occured...');

            case 'compose':
                $toID = (int) $id;
                $toName = $toID ? $db->getCitizenName($toID) : '';
                $data += ['toID' => $toID, 'toName' => $toName == -1 ? '' : $toName, 'replyTo' => null, 'rSubject' => '', 'rBody' => ''];
                $bar = $this->lang->getstr('pm_compose', 'pm');
                break;

            case 'view':
                $pm = $db->getPM((int) $id);
                if (!$pm) {
                    return redirect($this->vars->getURL('mail'));
                }
                $inbox = $me == $pm['toID'];
                if (!$inbox && !($this->accesses()['can_view_pms'] ?? false) && $me != $pm['fromID']) {
                    return redirect('/index.html');
                }
                if ($inbox && !$pm['isRead']) {
                    $db->setReadPM($pm['pmID']);
                }
                $rSubject = $pm['Subject'];
                if (substr($rSubject, 0, 12) === 'Re: Re: Re: ') {
                    $rSubject = 'Re*3: '.substr($rSubject, 12);
                }
                $data += ['pm' => $pm, 'inbox' => $inbox, 'toID' => $pm['fromID'], 'toName' => $pm['fromName'], 'replyTo' => $inbox ? $pm['fromID'] : null, 'rSubject' => $rSubject, 'rBody' => $pm['Body']];
                $bar = $this->lang->getstr('pm_view', 'pm');
                break;

            case 'sent':
                $data += ['rows' => $db->getCitizenSent($me, $start, $nums), 'size' => $db->getCitizenSent($me, -1)];
                $bar = $this->lang->getstr('pm_mailbox', 'pm');
                break;

            case 'notes':
                $this->lang->addPhrases('notes');
                $rows = $db->getCitizenNotes($me, $start, $nums);
                $db->exec('UPDATE notes SET isRead = 1 WHERE toID = ?', [$me]);
                $parser = app(NoteParser::class);
                foreach ($rows as &$r) {
                    $r['parsed'] = $parser->parseNote($r);
                }
                $data += ['rows' => $rows, 'size' => $db->getCitizenNotes($me, -1)];
                $bar = $this->lang->getstr('pm_mailbox', 'pm');
                break;

            case 'requests':
                $data += ['rows' => $db->getPendingFriendRequests($me)];
                $bar = $this->lang->getstr('pm_mailbox', 'pm');
                break;

            default:
                $target = $data['target'] = 'inbox';
                $data += ['rows' => $db->getCitizenInbox($me, $start, $nums), 'size' => $db->getCitizenInbox($me, -1)];
                $bar = $this->lang->getstr('pm_mailbox', 'pm');
        }
        $data['accPaid'] = $this->vars->getAccPaid($citInfo);

        return $this->page('pages.mail.index', $data, ['title' => $this->lang->getstr('title_mail', 'title'), 'bar_title' => $bar, 'actiontype' => 'mail']);
    }

    private function error(string $msg)
    {
        return $this->page('pages.message', ['message' => $msg], ['title' => 'Error']);
    }
}
