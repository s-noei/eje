<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class NewsController extends ApiController
{
    /** GET /api/v1/news?type=top|new&country=0&page=1 */
    public function index(Request $request)
    {
        $type = $request->input('type') === 'new' ? 'new' : 'top';
        $country = (int) $request->input('country', 0);
        $page = max(1, (int) $request->input('page', 1));
        $sql = "SELECT articles.*, np_details.* FROM articles JOIN np_details ON articles.npID = np_details.npID AND Deleted = '0' AND isDraft = '0'";
        $b = [];
        if ($country) {
            $sql .= ' WHERE articles.aCountry = ?';
            $b[] = $country;
        }
        if ($type === 'top') {
            $sql .= ($country ? ' AND' : ' WHERE').' timestamp >= ? ORDER BY aVotes DESC';
            $b[] = time() - 2 * 86400;
        } else {
            $sql .= ' ORDER BY timestamp DESC';
        }
        $rows = $this->database->rows($sql.' LIMIT '.(($page - 1) * 10).', 10', $b);

        return $this->ok(['page' => $page, 'articles' => array_map([$this, 'articlePayload'], $rows)]);
    }

    /** GET /api/v1/articles/{id} */
    public function show(int $id)
    {
        $art = $this->database->getArticle($id);
        if (!$art || $art['Deleted']) {
            return $this->fail('Article not found.', 404);
        }
        $c = $this->cit();
        $comments = $this->database->getComments($id, $this->citID(), false);

        return $this->ok([
            'article' => $this->articlePayload($art) + [
                'html' => nl2br(stripslashes($this->vars->utfcorrect($art['aContent']))), 'rtl' => (bool) $art['RTL'], 'picture' => $art['aPic'] ?: null,
                'country' => $art['cName'] ?? '', 'author' => ['id' => (int) $art['npAuthorID'], 'name' => $art['npAuthor']],
                'newspaperAvatar' => url('/uploads/avatars/newspaper/'.$art['Avatar']),
                'voted' => (bool) $this->database->isVoted($id, $this->citID()),
            ],
            'comments' => array_map(fn ($cm) => ['id' => (int) $cm['cmID'], 'body' => $this->session->addSmileys($cm['cmBody']), 'time' => (int) $cm['timestamp'],
                'author' => ['id' => (int) $cm['CitID'], 'name' => $cm['name'], 'avatar' => url('/uploads/avatars/citizen/'.$cm['Avatar'])],
                'up' => (int) $cm['thumbs_up'], 'down' => (int) $cm['thumbs_down']], $comments),
        ]);
    }

    /** POST /api/v1/articles/{id}/vote {vote: 1|-1, reason} */
    public function vote(Request $request, int $id)
    {
        $v = (int) $request->input('vote') > 0 ? 1 : -1;
        $r = $this->database->setVote($id, $this->citID(), $v, (string) $request->input('reason', ''));

        return $this->ok(['votes' => $r]);
    }

    /** POST /api/v1/articles/{id}/comments {body, quote?} */
    public function comment(Request $request, int $id)
    {
        if (!$this->database->getArticle($id)) {
            return $this->fail('Article not found.', 404);
        }
        $r = $this->database->postCM($this->citID(), $id, (int) $request->input('quote', 0), $this->vars->convert_urls((string) $request->input('body')));
        if ($r != 1) {
            return $this->fail((string) $r);
        }

        return $this->ok();
    }
}
