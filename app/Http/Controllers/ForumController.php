<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of forum/* (index, viewindex, viewboard, viewtopic, newtopic, editpost, removepost, history).
 * URLs: forum.html, forum-{go}-{id}.html, forum-{go}-{id}-{page}.html
 */
class ForumController extends GameController
{
    private array $roles = [];

    public function index(Request $request, ?string $go = null, ?int $id = null, ?string $page = null)
    {
        $layout = ['title' => $this->lang->getstr('title_forum', 'title'), 'bar_title' => 'Forum index', 'actiontype' => 'forum'];
        if (!$this->loggedIn()) {
            return $this->page('pages.forum.maintenance', [], $layout);
        }
        $this->roles = $this->forumRoles();
        $canMod = $this->accesses()['can_moderate_forum'] ?? false;
        $common = ['roles' => $this->roles, 'canMod' => $canMod, 'isAdmin' => $this->session->isAdmin(), 'userlevel' => (int) ($this->citInfo['userlevel'] ?? 0), 'errors' => []];

        return match ($go) {
            'board' => $this->board($request, (int) $id, $page, $common, $layout),
            'topic' => $this->topic($request, (int) $id, $page, $common, $layout),
            'newtopic' => $this->newTopic($request, (int) $id, $common, $layout),
            'editpost' => $this->editPost($request, (int) $id, $common, $layout),
            'removepost' => $this->removePost((int) $id, $common, $layout),
            'history' => $this->history((int) $id, $common, $layout),
            default => $this->forumIndex($common, $layout),
        };
    }

    /** Congress / CP / minister roles used for private boards (forum/index.php). */
    private function forumRoles(): array
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $r = ['cgCountryID' => 0, 'cpCountry' => 0, 'faCountry' => 0];
        $cg = $db->value('SELECT cgCountryID FROM congressmen WHERE cgCitizenID = ?', [$me], 0);
        if ($cg) {
            $r['cgCountryID'] = (int) $cg;
        }
        $row = $db->row('SELECT CountryID, IF(cpID = ?, 1, 0) cp, IF(minister_fa = ?, 1, 0) fa FROM country WHERE cpID = ? OR minister_e = ? OR minister_fa = ? OR minister_war = ?', [$me, $me, $me, $me, $me, $me]);
        if ($row) {
            $r['cpCountry'] = $row['cp'] ? (int) $row['CountryID'] : 0;
            $r['faCountry'] = $row['fa'] ? (int) $row['CountryID'] : 0;
            $r['cgCountryID'] = (int) $row['CountryID'];
        }

        return $r;
    }

    private function cantViewInside(array $board, bool $forTopic = false, $topicVar = null): bool
    {
        $me = $this->citInfo['CitizenID'];
        if ($me == 1) {
            return false;
        }
        $r = $this->roles;
        if ($board['board_ticketing_style'] && !$this->session->isAdmin()) {
            return true;
        }
        if ($board['board_special_view'] === 'cp' && !$r['cpCountry'] && !$r['faCountry']) {
            return true;
        }
        if ($board['board_special_view'] === 'cg') {
            return $forTopic ? ($r['cgCountryID'] != $topicVar) : !$r['cgCountryID'];
        }

        return false;
    }

    private function forumIndex(array $common, array $layout)
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $level = $common['userlevel'];
        $cats = [];
        foreach ($db->rows('SELECT * FROM forum_cats ORDER BY cat_order') as $cat) {
            $boards = $db->rows('SELECT forum_boards.*, COUNT(DISTINCT forum_posts.topic_id) AS board_count_topic, forum_board_views.view_time, COUNT(forum_posts.post_id) AS board_count_post
                FROM forum_boards
                LEFT JOIN forum_topics ON forum_boards.board_id = forum_topics.board_id
                LEFT JOIN forum_posts ON forum_topics.topic_id = forum_posts.topic_id
                LEFT JOIN forum_board_views ON forum_board_views.board_id = forum_boards.board_id AND forum_board_views.citizen_id = ?
                WHERE forum_boards.cat_id = ? GROUP BY forum_boards.board_id ORDER BY forum_boards.board_order', [$me, $cat['cat_id']]);
            $list = [];
            foreach ($boards as $b) {
                if ($b['board_view_rights'] > $level) {
                    continue;
                }
                $b['private'] = $this->cantViewInside($b) || $b['board_special_view'] === 'cg';
                $b['isNew'] = $b['board_last_post_time'] > ($b['view_time'] ?? 0) && $b['board_last_post_time'] > time() - (24 * 3600 * 30) && !$b['board_special_view'];
                $b['last'] = $b['private'] ? null : $db->row('SELECT forum_posts.*, forum_topics.*, citizens.name AS post_poster_name FROM forum_posts
                    LEFT JOIN forum_topics ON forum_topics.topic_id = forum_posts.topic_id
                    LEFT JOIN forum_boards ON forum_topics.board_id = forum_boards.board_id
                    LEFT JOIN citizens ON citizens.CitizenID = forum_posts.post_poster
                    WHERE forum_boards.board_id = ? ORDER BY forum_posts.post_time DESC LIMIT 1', [$b['board_id']]);
                $list[] = $b;
            }
            $cat['boards'] = $list;
            $cats[] = $cat;
        }
        $r = $this->roles;
        $special = "forum_boards.board_special_view = ''";
        $b = [$level];
        if ($r['cgCountryID']) {
            $special .= " OR (forum_boards.board_special_view = 'cg' AND topic_special_var = ?)";
            $b[] = $r['cgCountryID'];
        }
        if ($r['cpCountry']) {
            $special .= " OR forum_boards.board_special_view = 'cp'";
        }
        $latest = $db->rows("SELECT forum_posts.*, citizens.name AS post_poster_name FROM forum_topics
            LEFT JOIN forum_posts ON forum_topics.topic_id = forum_posts.topic_id AND forum_posts.post_time = forum_topics.topic_last_post_time
            LEFT JOIN forum_boards ON forum_boards.board_id = forum_topics.board_id
            JOIN citizens ON forum_posts.post_poster = citizens.CitizenID
            WHERE forum_boards.board_view_rights <= ? AND forum_boards.board_ticketing_style = 0 AND ($special) AND post_removed = '0' AND topic_removed = '0'
            GROUP BY forum_topics.topic_id ORDER BY topic_last_post_time DESC LIMIT 10", $b);

        return $this->page('pages.forum.index', $common + ['cats' => $cats, 'topPosters' => $db->rows('SELECT CitizenID, name, forum_posts FROM citizens ORDER BY forum_posts DESC LIMIT 10'), 'latest' => $latest], $layout);
    }

    private function pages(int $numPosts, int $count, $pageParam): array
    {
        $page = ($pageParam !== null && $pageParam !== '' && $pageParam !== 'last') ? max(1, (int) $pageParam) : 1;
        if ($pageParam === 'last') {
            $page = max(1, (int) floor($numPosts / $count) + ($numPosts % $count ? 1 : 0));
        }
        $numPages = (int) floor($numPosts / $count);
        if ($numPosts / $count > $numPages) {
            $numPages++;
        }

        return [$page, ($page - 1) * $count, $numPages];
    }

    private function touchView(string $table, string $col, int $id): void
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        if ($db->count("SELECT citizen_id FROM $table WHERE citizen_id = ? AND $col = ?", [$me, $id]) < 1) {
            $db->exec("INSERT INTO $table (citizen_id, $col, view_time) VALUES (?, ?, ?)", [$me, $id, time()]);
            if ($table === 'forum_topic_views') {
                $db->exec('UPDATE forum_topics SET topic_views = topic_views + 1 WHERE topic_id = ?', [$id]);
            }
        } else {
            $db->exec("UPDATE $table SET view_time = ? WHERE citizen_id = ? AND $col = ?", [time(), $me, $id]);
        }
    }

    private function board(Request $request, int $id, $pageParam, array $common, array $layout)
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $layout['bar_title'] = 'View board';
        $layout['title'] = $this->lang->getstr('title_forum_board', 'title');
        $canMod = $common['canMod'];

        if ($canMod && $request->isMethod('post')) {
            foreach (['sublock' => ['topic_locked', 1], 'subunlock' => ['topic_locked', 0], 'substicky' => ['topic_sticky', 1], 'subunsticky' => ['topic_sticky', 0]] as $k => [$col, $val]) {
                if ($request->filled($k)) {
                    $db->exec("UPDATE forum_topics SET $col = ? WHERE topic_id = ?", [$val, (int) $request->input($k)]);

                    return redirect($request->getRequestUri());
                }
            }
            foreach (['subdelete' => [1, '-'], 'subundelete' => [0, '+']] as $k => [$val, $op]) {
                if ($request->filled($k)) {
                    $topic = (int) $request->input($k);
                    $db->exec('UPDATE forum_topics SET topic_removed = ? WHERE topic_id = ?', [$val, $topic]);
                    foreach ($db->rows("SELECT post_poster AS id, COUNT(post_poster) AS amount FROM forum_posts WHERE topic_id = ? AND post_removed = '0' GROUP BY post_poster", [$topic]) as $p) {
                        $db->exec("UPDATE citizens SET forum_posts = forum_posts $op ? WHERE CitizenID = ?", [(int) $p['amount'], $p['id']]);
                    }

                    return redirect($request->getRequestUri());
                }
            }
        }

        $board = $db->row('SELECT * FROM forum_boards WHERE board_id = ?', [$id]);
        if (!$board) {
            return redirect($this->vars->getURL('forum'));
        }
        $this->touchView('forum_board_views', 'board_id', $id);
        $cant = $this->cantViewInside($board);
        $data = $common + ['board' => $board, 'cant' => $cant || $board['board_view_rights'] > $common['userlevel'], 'topics' => [], 'page' => 1, 'numPages' => 0];
        if (!$data['cant']) {
            $ticketing = $board['board_ticketing_style'] && !$this->session->isAdmin() && $me != 694;
            $sql = 'SELECT forum_topics.*, citizens.name AS topic_starter_name, COUNT(forum_posts.post_id) AS topic_count_post, forum_topic_views.view_time
                FROM forum_topics
                LEFT JOIN forum_posts ON forum_topics.topic_id = forum_posts.topic_id
                LEFT JOIN citizens ON citizens.CitizenID = forum_topics.topic_starter
                LEFT JOIN forum_topic_views ON forum_topic_views.topic_id = forum_topics.topic_id AND forum_topic_views.citizen_id = ?
                WHERE board_id = ?';
            $b = [$me, $id];
            if ($ticketing) {
                $sql .= ' AND topic_starter = ?';
                $b[] = $me;
            }
            if ($board['board_special_view'] === 'cg' && $me != 1) {
                $sql .= ' AND topic_special_var = ?';
                $b[] = $this->roles['cgCountryID'];
            }
            $sql .= ' GROUP BY forum_topics.topic_id ORDER BY topic_sticky DESC, topic_last_post_time DESC';
            $num = $db->count($sql, $b);
            [$page, $start, $numPages] = $this->pages($num, 10, $pageParam);
            $topics = $db->rows($sql." LIMIT $start, 10", $b);
            foreach ($topics as &$t) {
                $t['isNew'] = $t['topic_last_post_time'] > ($t['view_time'] ?? 0) && $t['topic_last_post_time'] > time() - (24 * 3600 * 30);
                $t['last'] = $db->row('SELECT forum_posts.*, citizens.name AS post_poster_name FROM forum_posts
                    LEFT JOIN citizens ON citizens.CitizenID = forum_posts.post_poster WHERE forum_posts.topic_id = ? ORDER BY forum_posts.post_time DESC LIMIT 1', [$t['topic_id']]);
            }
            $data = array_merge($data, ['ticketing' => $ticketing, 'topics' => $topics, 'page' => $page, 'numPages' => $numPages]);
            $data['canPost'] = ($common['userlevel'] >= $board['board_post_rights'] && !($board['board_special_view'] === 'cp' && !$this->roles['cpCountry'])) || $me == 1;
        }

        return $this->page('pages.forum.board', $data, $layout);
    }

    private function topic(Request $request, int $id, $pageParam, array $common, array $layout)
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $canMod = $common['canMod'];
        $layout['bar_title'] = 'View topic';
        $layout['title'] = $this->lang->getstr('title_forum_topic', 'title');
        $sql = 'SELECT forum_topics.*, forum_boards.* FROM forum_topics LEFT JOIN forum_boards ON forum_topics.board_id = forum_boards.board_id WHERE topic_id = ?';
        if (!$canMod) {
            $sql .= " AND topic_removed = '0'";
        }
        $topic = $db->row($sql, [$id]);
        if (!$topic) {
            return redirect($this->vars->getURL('forum'));
        }
        $this->touchView('forum_topic_views', 'topic_id', $id);
        $this->touchView('forum_board_views', 'board_id', (int) $topic['board_id']);
        $cant = $this->cantViewInside($topic, true, $topic['topic_special_var']) || $topic['board_view_rights'] > $common['userlevel'];
        $data = $common + ['topic' => $topic, 'cant' => $cant, 'posts' => [], 'page' => 1, 'numPages' => 0];

        if (!$cant) {
            if ($request->filled('post-body') && !($topic['topic_locked'] && !$this->session->isAdmin())) {
                $reply = (string) $request->input('post-body');
                if (strlen($reply) > 5) {
                    $time = time();
                    $db->exec('INSERT INTO forum_posts (post_poster, post_title, post_body, post_time, topic_id) VALUES (?, ?, ?, ?, ?)', [$me, 'Re: '.$topic['topic_name'], $reply, $time, $id]);
                    $db->exec('UPDATE forum_topics SET topic_last_post_time = ? WHERE topic_id = ?', [$time, $id]);
                    $db->exec('UPDATE forum_boards SET board_last_post_time = ? WHERE board_id = ?', [$time, $topic['board_id']]);
                    if ($topic['board_post_count']) {
                        $db->exec('UPDATE citizens SET forum_posts = forum_posts + 1 WHERE CitizenID = ?', [$me]);
                    }
                }

                return redirect($request->getRequestUri());
            }
            $sql = 'SELECT forum_posts.*, citizens.name AS post_poster_name, citizens.userlevel AS post_poster_level, citizens.Avatar AS post_poster_avatar,
                    citizens.forum_posts AS post_poster_posts, editor.name AS eName
                FROM forum_posts LEFT JOIN citizens ON citizens.CitizenID = forum_posts.post_poster
                LEFT JOIN citizens AS editor ON editor.CitizenID = forum_posts.post_edit_by WHERE topic_id = ?';
            if (!$canMod) {
                $sql .= " AND post_removed = '0'";
            }
            $sql .= ' ORDER BY post_time';
            $num = $db->count($sql, [$id]);
            [$page, $start, $numPages] = $this->pages($num, 10, $pageParam);
            $data = array_merge($data, ['posts' => $db->rows($sql." LIMIT $start, 10", [$id]), 'page' => $page, 'numPages' => $numPages,
                'levels' => config('ejahan.levels'), 'canReply' => !$topic['topic_locked'] || $this->session->isAdmin()]);
        }

        return $this->page('pages.forum.topic', $data, $layout);
    }

    private function newTopic(Request $request, int $id, array $common, array $layout)
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $layout['bar_title'] = 'Post a new topic';
        $board = $db->row('SELECT * FROM forum_boards WHERE board_id = ?', [$id]);
        $cant = !$board || $this->cantViewInside($board) || $board['board_post_rights'] > $common['userlevel'];
        $errors = [];
        if ($board && !$cant && $request->filled('target_board')) {
            $title = (string) $request->input('post_title');
            $body = (string) $request->input('post_body');
            if ($request->input('token') !== md5($id.$me.'$lmp1e ')) {
                $errors[] = 'Cheating detected';
            } elseif (strlen($title) < 3 || !preg_match('#^[a-zA-Z0-9\s]+$#', $title) || strlen(str_replace(' ', '', $title)) < 3) {
                $errors[] = 'Title is incorrect.';
            } else {
                $time = time();
                $spevar = $board['board_special_view'] === 'cg' ? $this->roles['cgCountryID'] : '';
                $topicID = $db->insertGetId('INSERT INTO forum_topics (topic_name, board_id, topic_starter, topic_create_time, topic_last_post_time, topic_special_var) VALUES (?, ?, ?, ?, ?, ?)',
                    [$title, $id, $me, $time, $time, $spevar]);
                $db->exec('INSERT INTO forum_posts (post_poster, post_title, post_body, post_time, topic_id) VALUES (?, ?, ?, ?, ?)', [$me, $title, $body, $time, $topicID]);
                $db->exec('UPDATE forum_boards SET board_last_post_time = ? WHERE board_id = ?', [$time, $id]);
                $db->exec('UPDATE citizens SET forum_posts = forum_posts + 1 WHERE CitizenID = ?', [$me]);

                return redirect($this->vars->getURL('forum', 'topic', $topicID));
            }
        }

        return $this->page('pages.forum.newtopic', $common + ['board' => $board, 'cant' => $cant, 'errors' => $errors, 'id' => $id, 'token' => md5($id.$me.'$lmp1e ')], $layout);
    }

    private function loadPost(int $id): ?array
    {
        return $this->database->row('SELECT forum_posts.*, forum_topics.*, forum_boards.board_name FROM forum_posts
            JOIN forum_topics ON forum_topics.topic_id = forum_posts.topic_id
            JOIN forum_boards ON forum_boards.board_id = forum_topics.board_id WHERE post_id = ?', [$id]);
    }

    private function editPost(Request $request, int $id, array $common, array $layout)
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $layout['bar_title'] = 'Edit post';
        $post = $this->loadPost($id);
        $errors = [];
        $state = !$post ? 'invalid' : (($post['post_poster'] != $me && !$common['canMod']) ? 'notowner' : 'ok');
        if ($state === 'ok' && $request->filled('target_post')) {
            if ($request->input('token') !== md5($id.$me.'$lmp1e ')) {
                $errors[] = 'Cheating detected';
            } else {
                $time = time();
                $body = (string) $request->input('post_body');
                $db->exec("INSERT INTO forum_posts_history (post_id, edit_by, edit_reason, edit_time, post_before, post_after) VALUES (?, ?, 'undefined', ?, ?, ?)", [$id, $me, $time, $post['post_body'], $body]);
                $db->exec('UPDATE forum_posts SET post_title = ?, post_body = ?, post_edit_time = ?, post_edit_by = ? WHERE post_id = ?', [strip_tags((string) $request->input('post_title')), $body, $time, $me, $id]);

                return redirect($this->vars->getURL('forum', 'topic', $post['topic_id']).'#post'.$id);
            }
        }

        return $this->page('pages.forum.editpost', $common + ['post' => $post, 'state' => $state, 'errors' => $errors, 'id' => $id, 'token' => md5($id.$me.'$lmp1e ')], $layout);
    }

    private function removePost(int $id, array $common, array $layout)
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $post = $this->loadPost($id);
        if ($post && ($post['post_poster'] == $me || $common['canMod'])) {
            $db->exec("UPDATE forum_posts SET post_removed = '1' WHERE post_id = ?", [$id]);
            $db->exec('UPDATE citizens SET forum_posts = forum_posts - 1 WHERE CitizenID = ?', [$post['post_poster']]);

            return redirect($this->vars->getURL('forum', 'topic', $post['topic_id']).'#post'.$id);
        }

        return $this->page('pages.forum.message', $common + ['message' => $post ? 'You cannot remove a post owned by somebody else' : 'Invalid post specified'], $layout);
    }

    private function history(int $id, array $common, array $layout)
    {
        $layout['bar_title'] = 'Post history';
        $post = $this->loadPost($id);
        $me = $this->citInfo['CitizenID'];
        $state = !$post ? 'invalid' : (($post['post_poster'] != $me && !$common['canMod']) ? 'notowner' : 'ok');
        $hists = $state === 'ok' ? $this->database->rows('SELECT forum_posts_history.*, forum_posts.*, citizens.name AS post_poster_name, citizens.userlevel AS post_poster_level,
                citizens.Avatar AS post_poster_avatar, citizens.forum_posts AS post_poster_posts
            FROM forum_posts_history JOIN forum_posts ON forum_posts_history.post_id = forum_posts.post_id
            JOIN citizens ON forum_posts_history.edit_by = citizens.CitizenID WHERE forum_posts.post_id = ? ORDER BY forum_posts_history.edit_time DESC', [$id]) : [];

        return $this->page('pages.forum.history', $common + ['post' => $post, 'state' => $state, 'hists' => $hists], $layout);
    }
}
