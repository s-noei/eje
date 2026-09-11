<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AjaxController;
use Illuminate\Http\Request;

/**
 * The legacy home-page chatbox (include/chat.php + ajax getchat/addchat).
 */
class ChatController extends ApiController
{
    /** GET /api/v1/chat?since={chatID} */
    public function index(Request $request)
    {
        $since = (int) $request->query('since', 0);
        $rows = app(\App\Game\Services\Chat::class)->getChat($since);
        $announce = app(\App\Game\Services\Chat::class)->getAnnounce();

        return $this->ok([
            'announce' => $announce ? $this->row($announce) : null,
            'messages' => array_map([$this, 'row'], $rows),
        ]);
    }

    /** POST /api/v1/chat {message} — reuses the legacy addchat rules (limits, cooldown, filters). */
    public function store(Request $request)
    {
        $res = app(AjaxController::class)->addChat($request);
        $html = trim((string) $res->getContent());
        $text = trim(strip_tags($html));
        $okSend = str_contains($html, 'lime');

        return $okSend ? $this->ok(['message' => $text]) : $this->fail($text ?: 'Message not sent', 422);
    }

    private function row(array $r): array
    {
        return [
            'id' => (int) $r['chatID'],
            'citizenId' => (int) $r['citID'],
            'name' => $r['name'],
            'avatar' => url('/uploads/avatars/citizen/'.($r['Avatar'] ?: 'default.jpg')),
            'message' => strip_tags(str_replace('<br>', "\n", (string) $r['message'])),
            'time' => (int) $r['timestamp'],
        ];
    }
}
