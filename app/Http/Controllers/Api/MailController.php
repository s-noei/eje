<?php

namespace App\Http\Controllers\Api;

use App\Game\Services\NoteParser;
use Illuminate\Http\Request;

class MailController extends ApiController
{
    private function pm(array $r): array
    {
        return ['id' => (int) $r['pmID'], 'subject' => $r['Subject'], 'body' => $r['Body'], 'read' => (bool) $r['isRead'], 'time' => (int) $r['timestamp'],
            'from' => ['id' => (int) $r['fromID'], 'name' => $r['fromName'] ?? '', 'avatar' => url('/uploads/avatars/citizen/'.($r['fromAvatar'] ?? 'no-avatar-m.gif'))],
            'to' => ['id' => (int) $r['toID'], 'name' => $r['toName'] ?? '', 'avatar' => url('/uploads/avatars/citizen/'.($r['toAvatar'] ?? 'no-avatar-m.gif'))]];
    }

    /** GET /api/v1/mail/inbox?page=1 */
    public function inbox(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $rows = $this->database->getCitizenInbox($this->citID(), ($page - 1) * 10, 10);

        return $this->ok(['page' => $page, 'total' => $this->database->getCitizenInbox($this->citID(), -1), 'messages' => array_map([$this, 'pm'], $rows)]);
    }

    /** GET /api/v1/mail/sent?page=1 */
    public function sent(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $rows = $this->database->getCitizenSent($this->citID(), ($page - 1) * 10, 10);

        return $this->ok(['page' => $page, 'total' => $this->database->getCitizenSent($this->citID(), -1), 'messages' => array_map([$this, 'pm'], $rows)]);
    }

    /** GET /api/v1/mail/notes?page=1 */
    public function notes(Request $request)
    {
        $this->lang->addPhrases('notes');
        $page = max(1, (int) $request->input('page', 1));
        $rows = $this->database->getCitizenNotes($this->citID(), ($page - 1) * 10, 10);
        $this->database->exec('UPDATE notes SET isRead = 1 WHERE toID = ?', [$this->citID()]);
        $parser = app(NoteParser::class);

        return $this->ok(['page' => $page, 'total' => $this->database->getCitizenNotes($this->citID(), -1),
            'notes' => array_map(fn ($n) => ['id' => (int) $n['NoteID'], 'html' => $parser->parseNote($n), 'text' => strip_tags($parser->parseNote($n)), 'read' => (bool) $n['isRead'], 'time' => (int) $n['timestamp']], $rows)]);
    }

    /** GET /api/v1/mail/{id} */
    public function show(int $id)
    {
        $pm = $this->database->getPM($id);
        if (!$pm || ($pm['toID'] != $this->citID() && $pm['fromID'] != $this->citID())) {
            return $this->fail('Message not found.', 404);
        }
        if ($pm['toID'] == $this->citID() && !$pm['isRead']) {
            $this->database->setReadPM($id);
            $pm['isRead'] = 1;
        }

        return $this->ok(['message' => $this->pm($pm)]);
    }

    /** POST /api/v1/mail/send {to: name|id, subject, body} */
    public function send(Request $request)
    {
        $to = (string) $request->input('to');
        $toDet = ctype_digit($to) ? $this->database->getUserInfoFromID((int) $to) : $this->database->getUserInfo($to);
        if (!$toDet) {
            return $this->fail('Recipient not found.', 404);
        }
        $subject = strip_tags((string) $request->input('subject'));
        $body = (string) $request->input('body');
        if ($subject === '' || trim(strip_tags($body)) === '') {
            return $this->fail('Subject and body are required.');
        }
        $this->database->sendPM($this->citID(), $toDet['CitizenID'], $subject, $body);

        return $this->ok();
    }

    /** DELETE /api/v1/mail/{id} */
    public function destroy(int $id)
    {
        $pm = $this->database->getPM($id);
        if (!$pm) {
            return $this->fail('Message not found.', 404);
        }
        $this->database->deletePM($id, $this->citID(), $pm['toID'] == $this->citID() ? 'inbox' : 'sent');

        return $this->ok();
    }
}
