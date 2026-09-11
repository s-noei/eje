<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of extra.php + include/extra/{credits,history,emblems,devnews}.php and invite.php.
 */
class ExtraController extends GameController
{
    /** extra[-{go}].html */
    public function extra(Request $request, ?string $go = null)
    {
        $go = in_array($go, ['history', 'emblems', 'devnews'], true) ? $go : 'credits';
        $data = ['go' => $go];
        $db = $this->database;

        if ($go === 'devnews') {
            if ($request->input('subadddev') && ($this->citInfo['CitizenID'] ?? 0) == 1) {
                $db->exec('INSERT INTO dev_news (newsType, ver, title, repBy, timestamp, link) VALUES (?, ?, ?, ?, ?, ?)',
                    [(string) $request->input('type'), (string) $request->input('version'), strip_tags((string) $request->input('title')),
                        strip_tags((string) $request->input('repBy')), time(), strip_tags((string) $request->input('link'))]);

                return redirect($request->getRequestUri());
            }
            $types = ['bugfix' => '<font color="red">[BUG FIXED]</font>', 'minupd' => '<font color="green">[UPDATE]</font>', 'majupd' => '<font color="green">[NEW FEATURE]</font>'];
            $data['news'] = array_map(function ($n) use ($types) {
                $n['typeLabel'] = $types[$n['newsType']] ?? e($n['newsType']);

                return $n;
            }, $db->rows('SELECT * FROM dev_news ORDER BY newsID DESC'));
        } elseif ($go === 'history') {
            $data['latest'] = $db->row('SELECT newsID, ver FROM dev_news ORDER BY newsID DESC LIMIT 1');
        }

        return $this->page('pages.extra.index', $data, ['title' => $this->lang->getstr('title_extra', 'title'), 'bar_title' => 'eJahan Extra', 'actiontype' => 'extra']);
    }

    /** invite.html */
    public function invite()
    {
        if (!$this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }

        return $this->page('pages.extra.invite', [
            'invited' => $this->database->rows('SELECT name, CitizenID FROM citizens WHERE referrer = ?', [$this->citInfo['CitizenID']]),
        ], ['title' => $this->lang->getstr('title_invite', 'title'), 'bar_title' => 'Invite friends', 'actiontype' => 'invite']);
    }
}
