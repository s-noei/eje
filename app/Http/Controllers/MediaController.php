<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of newspaper.php, npinfo.php + include/newspaper/* (index, edit, sub, article/{new,edit,view,poll,postpoll})
 * and media.php + include/media/{art,eve}.php.
 */
class MediaController extends GameController
{
    /** newspaper.php */
    public function newspaperIndex()
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $npID = $this->database->getCitizenNP($this->citInfo['CitizenID']);
        if ($npID > 0) {
            return redirect($this->vars->getURL('newspaper', $npID));
        }

        return $this->page('pages.media.newspaper-index', [], ['title' => $this->lang->getstr('title_np', 'title'), 'bar_title' => 'Newspaper', 'actiontype' => 'newspaper']);
    }

    /** npinfo.php: newspaper-{id}[-page-{page}|-{do}].html */
    public function newspaper(Request $request, int $npID, ?string $do = null, int $pageNo = 1)
    {
        $this->lang->addPhrases('np');
        $row = $this->npRow($npID);
        if (!$row) {
            return redirect('/index.html');
        }
        $citInfo = $this->citInfo;
        $logged = $this->loggedIn();
        $isAuthor = $logged && ($citInfo['npID'] == $npID || ($citInfo['CitizenID'] == 694 && $npID == 1));
        $errors = [];

        if ($do === 'sub') {
            if (!$logged || $this->isCA() || !$citInfo['active']) {
                return redirect('/index.html');
            }
            $db = $this->database;
            if (!$db->isSub($citInfo['CitizenID'], $npID)) {
                $db->exec('INSERT INTO np_subs (npID, citID, timestamp) VALUES (?, ?, ?)', [$npID, $citInfo['CitizenID'], time()]);
            } else {
                $db->exec('DELETE FROM np_subs WHERE npID = ? AND citID = ?', [$npID, $citInfo['CitizenID']]);
            }
            $subs = $db->count('SELECT * FROM np_subs WHERE npID = ?', [$npID]);
            if ($subs && $subs % 1000 === 0) {
                if ($db->addMedal($row['npAuthorID'], 'mp', '', (string) floor($subs / 1000))) {
                    $db->sendNote($row['npAuthorID'], '', 'Your newspaper collected 1000 subscribers and you received a media power trophy and 5 Tala!');
                }
            }

            return redirect($this->vars->getURL('newspaper', $npID));
        }

        if ($do === 'edit') {
            if (!$isAuthor) {
                return redirect('/index.html');
            }
            if ($request->input('editOk')) {
                $db = $this->database;
                $db->updateNewspaperField($npID, 'npName', strip_tags((string) $request->input('npName')));
                if ($request->input('npCountry')) {
                    $db->updateNewspaperField($npID, 'CountryID', (int) $request->input('npCountry'));
                }
                $avatar = $request->file('npAvatar');
                if ($avatar) {
                    if ($avatar->isValid() && in_array($avatar->getMimeType(), ['image/jpeg', 'image/pjpeg']) && $avatar->getSize() < 50 * 1024) {
                        $fName = md5($npID.'NeWsPaPeR').'.jpg';
                        $avatar->move(public_path('uploads/avatars/newspaper'), $fName);
                        $db->updateNewspaperField($npID, 'Avatar', $fName);
                    } else {
                        $errors[] = 'The file you specified is not correct.';
                    }
                }
                if (!$errors) {
                    return redirect($this->vars->getURL('newspaper', $npID));
                }
            }
            $data = ['allCountries' => $this->database->rows('SELECT * FROM country ORDER BY cName')];
        } else {
            $do = null;
            $page = max(1, $pageNo);
            $start = ($page - 1) * 5;
            $mod = !($this->session->isAdmin() || $isAuthor);
            $data = [
                'page' => $page, 'start' => $start,
                'articles' => $this->database->getNPArticles($npID, $mod, $start),
                'npNum' => $this->database->getNPArticles($npID, $mod),
            ];
        }

        return $this->page('pages.media.newspaper', $data + [
            'row' => $row, 'do' => $do, 'isAuthor' => $isAuthor, 'errors' => $errors,
            'isSub' => $logged ? $this->database->isSub($citInfo['CitizenID'], $npID) : 0,
        ], [
            'title' => sprintf($this->lang->getstr('title_np_info', 'title'), $row['npName']),
            'bar_title' => $this->lang->getstr('np_bartitle', 'np'),
            'actiontype' => 'newspaperinfo',
        ]);
    }

    /** article-new.html */
    public function articleNew(Request $request)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('np');
        $citInfo = $this->citInfo;
        if (!$citInfo['npID']) {
            return redirect($this->vars->getURL('newspaper'));
        }
        $row = $this->npRow($citInfo['npID']);
        $errors = [];
        $pollForm = false;

        if ($request->input('Create') || $request->input('Save') || $request->input('Poll')) {
            $aPic = (string) $request->input('aPic', '');
            $aSound = (string) $request->input('aSound', '');
            $pTail = substr($aPic, -4);
            $sTail = substr($aSound, -4);
            if ($aPic && !in_array($pTail, ['.jpg', '.gif'])) {
                $errors[] = 'The picture file is invalid';
            } elseif ($aSound && !in_array($sTail, ['.wav', '.mp3', '.mid'])) {
                $errors[] = 'The sound file is invalid';
            } elseif ($request->input('postPoll') && !$request->input('Poll')) {
                $pollForm = true;
            } else {
                $aID = $this->database->createArticle(strip_tags((string) $request->input('aTitle')), 0, (string) $request->input('aContent'), $aPic, $aSound,
                    $request->input('rtlArt') ? '1' : '0', $citInfo['npID'], $citInfo['CitizenID'], $request->input('Save') ? 1 : 0);
                if ($aID) {
                    if ($request->input('Poll')) {
                        $this->savePoll($request, $aID);
                    }

                    return redirect($this->vars->getURL('article', $aID));
                }
                $errors[] = 'There was an error...';
            }
        }

        return $this->page('pages.media.article-new', ['row' => $row, 'errors' => $errors, 'pollForm' => $pollForm, 'isAuthor' => true, 'isSub' => 0], [
            'title' => $this->lang->getstr('title_article_new', 'title'), 'bar_title' => 'Make an article', 'actiontype' => 'article',
        ]);
    }

    /** article-{id}[-{go}].html */
    public function article(Request $request, int $aID, ?string $go = null)
    {
        $this->lang->addPhrases('np');
        $db = $this->database;
        $art = $db->getArticle($aID);
        if (!$art) {
            return redirect($this->vars->getURL('home'));
        }
        $citInfo = $this->citInfo;
        $logged = $this->loggedIn();
        $me = $citInfo['CitizenID'] ?? 0;
        $acc = $this->accesses();
        $canMod = $acc['can_moderate_articles'] ?? false;
        $author = $db->getAuthorID($art['npID']);
        $isAuthor = $logged && $author == $me;
        $row = $this->npRow($art['npID']);

        if ($logged && $request->isMethod('post')) {
            if ($request->has('artAppr') && $this->session->isAdmin()) {
                $db->exec("UPDATE articles SET mastprove = '1' WHERE aID = ?", [(int) $request->input('actArt')]);

                return redirect($request->getRequestUri());
            }
            if ($request->has('artRemove')) {
                $target = $db->getArticle((int) $request->input('actArt'));
                if ($target && ($canMod || $db->getAuthorID($target['npID']) == $me)) {
                    $db->removeArticle($target['aID']);
                }

                return redirect($request->getRequestUri());
            }
            if ($request->has('removeCM')) {
                $sql = "UPDATE article_comments SET Deleted = '1', DeletedBy = ? WHERE cmID = ?";
                $b = [$me, (int) $request->input('actCM')];
                if (!$canMod) {
                    $sql .= ' AND CitID = ?';
                    $b[] = $me;
                }
                $db->exec($sql, $b);

                return redirect($request->getRequestUri());
            }
            if ($request->input('go') === 'post-cm') {
                $result = $db->postCM($me, $aID, (int) $request->input('quote'), $this->vars->convert_urls((string) $request->input('cm-body')));
                if ($result != 1) {
                    abort(500, (string) $result);
                }

                return redirect($this->vars->getURL('article', $aID));
            }
            if ($request->input('subpoll')) {
                $this->answerPoll($request);

                return redirect($request->getRequestUri());
            }
            if ($go === 'edit' && $request->input('eArtDone')) {
                $target = $db->getArticle((int) $request->input('eArtDone'));
                if ($target && ($canMod || $db->getAuthorID($target['npID']) == $me)
                    && $request->input('token') === md5($me.'l w@nnA edit @rticle'.$target['aID'])) {
                    $db->editArticle($target['aID'], strip_tags((string) $request->input('aTitle')), (string) $request->input('aContent'), $request->input('Draft') ? 1 : 0);
                }

                return redirect($this->vars->getURL('article', $target['aID'] ?? $aID));
            }
        }

        $base = ['row' => $row, 'art' => $art, 'isAuthor' => $isAuthor, 'canMod' => $canMod, 'errors' => [],
            'isSub' => $logged ? $db->isSub($me, $art['npID']) : 0];

        if ($go === 'edit') {
            if (!$logged || !($canMod || $isAuthor)) {
                return redirect($this->vars->getURL('article', $aID));
            }

            return $this->page('pages.media.article-edit', $base + ['editToken' => md5($me.'l w@nnA edit @rticle'.$art['aID'])], [
                'title' => sprintf($this->lang->getstr('title_article_edit', 'title'), strip_tags($art['aTitle'])), 'bar_title' => 'Editing article', 'actiontype' => 'article',
            ]);
        }

        $isVoted = $logged ? ($db->isVoted($aID, $me) || $this->isCA() || !$citInfo['active']) : true;
        $reasons = $db->rows("SELECT reason, COUNT(vote) AS Votes FROM article_votes WHERE artID = ? AND vote = '-1' AND reason != '' GROUP BY reason", [$aID]);
        $voters = $db->rows('SELECT citizens.CitizenID, citizens.name, article_votes.vote FROM article_votes JOIN citizens ON citizens.CitizenID = article_votes.citID WHERE artID = ?', [$aID]);
        $comments = $db->getComments($aID, $logged ? $me : 0, $canMod);
        $polls = $this->pollData($aID);

        return $this->page('pages.media.article', $base + [
            'isVoted' => $isVoted, 'reasons' => $reasons, 'voters' => $voters, 'comments' => $comments, 'polls' => $polls,
            'voteToken' => md5($aID.$me.'k3y4 v0+lnj @r+'),
            'showFb' => !app()->environment('local') && app(\App\Game\Services\Ip2Country::class)->get_country_name() !== 'Iran',
        ], [
            'title' => sprintf($this->lang->getstr('title_article_view', 'title'), strip_tags($art['aTitle'])),
            'bar_title' => $this->lang->getstr('np_bartitle', 'np'), 'actiontype' => 'article',
        ]);
    }

    /** media.php: media-{country}-{type}-{page}.html */
    public function media(Request $request, int $country = 0, string $type = 'top', int $page = 1)
    {
        $this->lang->addPhrases('filter');
        $this->lang->addPhrases('mcenter');
        $db = $this->database;
        if (!in_array($type, ['top', 'new', 'eve'], true)) {
            $type = 'top';
        }
        $page = max(1, $page);
        $count = 10;
        $start = ($page - 1) * $count;

        if ($country) {
            $coun = $db->getCountryRec($country);
            $cName = $coun['cName'] ?? 'Select';
            $flag = $coun ? $this->vars->getImgLoc('CountryFlag').$coun['Flag'].'.gif' : '/images/flags/l/world.gif';
        } else {
            $cName = 'Select';
            $flag = '/images/flags/l/world.gif';
        }
        $data = ['country' => $country, 'type' => $type, 'page' => $page, 'start' => $start, 'count' => $count, 'cName' => $cName, 'flag' => $flag,
            'allCountries' => $db->rows('SELECT * FROM country ORDER BY cName')];

        if ($type !== 'eve') {
            $sql = "SELECT articles.*, np_details.* FROM articles JOIN np_details ON articles.npID = np_details.npID AND Deleted = '0' AND isDraft = '0'";
            $b = [];
            if ($country) {
                $sql .= ' WHERE articles.aCountry = ?';
                $b[] = $country;
            }
            if ($type === 'top') {
                $sql .= ($country ? ' AND' : ' WHERE').' timestamp >= ? ORDER BY aVotes DESC';
                $b[] = time() - (2 * 24 * 3600);
            } else {
                $sql .= ' ORDER BY timestamp DESC';
            }
            $data['nums'] = $db->count($sql, $b);
            $data['arts'] = $db->rows($sql." LIMIT $start, $count", $b);
        } else {
            $this->lang->addPhrases('events');
            $this->lang->addPhrases('regions');
            $sql = 'SELECT events.*, Country1.cName AS Country1Name, Country1.shortName AS Country1SN, Country1.CountryID AS Country1ID,
                    Country2.cName AS Country2Name, Country2.shortName AS Country2SN, Country2.CountryID AS Country2ID,
                    region.rName AS RegionName, region.RegionID
                FROM events
                LEFT JOIN country AS Country1 ON Country1.CountryID = events.Country1
                LEFT JOIN country AS Country2 ON Country2.CountryID = events.Country2
                LEFT JOIN battles ON battles.battleID = events.refID
                LEFT JOIN region ON battles.regionID = region.RegionID';
            $b = [];
            if ($country) {
                $sql .= ' WHERE (Country1 = ? OR Country2 = ?)';
                $b = [$country, $country];
            }
            $sql .= ' ORDER BY timestamp DESC';
            $data['nums'] = $db->count($sql, $b);
            $data['events'] = array_map(fn ($e) => $this->describeEvent($e), $db->rows($sql." LIMIT $start, $count", $b));
        }

        return $this->page('pages.media.media', $data, ['title' => $this->lang->getstr('title_media', 'title'), 'bar_title' => $this->lang->getstr('mcenter_bartitle', 'mcenter'), 'actiontype' => 'media']);
    }

    /* ------------------------------------------------------------------ */

    private function npRow(int|string $npID): ?array
    {
        return $this->database->row('SELECT np_details.*, citizens.name AS npAuthor, citizens.CitizenID AS npAuthorID, country.Flag, country.cName, COUNT(np_subs.npID) AS npSubs
            FROM np_details JOIN citizens ON np_details.npID = citizens.npID
            JOIN country ON np_details.CountryID = country.CountryID
            LEFT JOIN np_subs ON np_details.npID = np_subs.npID
            WHERE np_details.npID = ? GROUP BY np_details.npID LIMIT 1', [$npID]);
    }

    /** include/newspaper/article/postpoll.php (save branch). */
    private function savePoll(Request $request, int $aID): void
    {
        $db = $this->database;
        for ($i = 1; $i <= 5; $i++) {
            $q = trim((string) $request->input("q_$i", ''));
            if ($q === '') {
                continue;
            }
            $qID = $db->insertGetId('INSERT INTO article_poll_q (aID, q) VALUES (?, ?)', [$aID, strip_tags($q)]);
            for ($j = 1; $j <= 10; $j++) {
                $s = trim((string) $request->input("q_{$i}_{$j}", ''));
                if ($s !== '') {
                    $db->exec('INSERT INTO article_poll_s (qID, title) VALUES (?, ?)', [$qID, strip_tags($s)]);
                }
            }
        }
    }

    /** include/newspaper/article/poll.php (submit branch). */
    private function answerPoll(Request $request): void
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        if ($this->isCA() || !$this->citInfo['active']) {
            return;
        }
        foreach ((array) $request->input('s', []) as $q => $a) {
            $q = (int) $q;
            $a = (int) $a;
            $ans = $db->row('SELECT article_poll_s.*, article_poll_a.citID FROM article_poll_s
                LEFT JOIN article_poll_a ON (article_poll_s.qID = article_poll_a.qID AND article_poll_a.citID = ?)
                WHERE article_poll_s.sID = ? AND article_poll_s.qID = ?', [$me, $a, $q]);
            if ($ans && !$ans['citID']) {
                $db->exec('INSERT INTO article_poll_a (citID, qID, sID) VALUES (?, ?, ?)', [$me, $q, $a]);
            }
        }
    }

    /** include/newspaper/article/poll.php (render data). */
    private function pollData(int $aID): array
    {
        $db = $this->database;
        $me = $this->citInfo['CitizenID'] ?? 0;
        $canAnswer = $this->loggedIn() && !$this->isCA() && $this->citInfo['active'];
        $out = [];
        $polls = $db->rows('SELECT article_poll_q.*, COUNT(article_poll_s.qID) AS answers FROM article_poll_q
            JOIN article_poll_s ON article_poll_q.qID = article_poll_s.qID WHERE aID = ? GROUP BY article_poll_q.qID HAVING COUNT(article_poll_s.qID) > 0', [$aID]);
        foreach ($polls as $poll) {
            $isVoted = $me ? $db->count('SELECT ansID FROM article_poll_a WHERE citID = ? AND qID = ?', [$me, $poll['qID']]) : 0;
            $item = ['q' => $poll, 'voted' => (bool) $isVoted, 'canAnswer' => $canAnswer && !$isVoted];
            if ($item['canAnswer']) {
                $item['options'] = $db->rows('SELECT * FROM article_poll_s WHERE qID = ?', [$poll['qID']]);
            } else {
                $item['results'] = $db->rows('SELECT article_poll_s.*, COUNT(article_poll_a.ansID) AS Votes,
                        ROUND(100.00 * COUNT(article_poll_a.ansID) / (SELECT COUNT(*) FROM article_poll_a WHERE qID = ?), 2) AS pers
                    FROM article_poll_s LEFT JOIN article_poll_a ON article_poll_s.sID = article_poll_a.sID
                    WHERE article_poll_s.qID = ? GROUP BY article_poll_s.sID ORDER BY Votes DESC', [$poll['qID'], $poll['qID']]);
            }
            $out[] = $item;
        }

        return $out;
    }

    /** include/media/eve.php description builder. */
    private function describeEvent(array $e): array
    {
        $l = $this->lang;
        $c = fn ($sn) => $l->getstr((string) $sn, 'country');
        $reg = fn ($id) => $l->getstr("region_$id", 'regions');
        $force = $l->getstr('events_revolt_force', 'events');
        $ref = 'war';
        $text = '';
        switch ($e['Type']) {
            case 'att':
                if ($e['RegionName']) {
                    $ref = 'battle';
                    $text = sprintf($l->getstr('events_att', 'events'), $c($e['Country1SN']), $reg($e['RegionID']), $c($e['Country2SN']));
                } else {
                    $text = sprintf($l->getstr('att', 'events'), $c($e['Country1SN']), $c($e['Country2SN']));
                }
                break;
            case 'dec':
                $ref = 'law';
                $text = sprintf($l->getstr('dec', 'events'), $c($e['Country1SN']), $c($e['Country2SN']));
                break;
            case 'pce':
                $ref = 'law';
                $text = sprintf($l->getstr('events_pce', 'events'), $c($e['Country1SN']), $c($e['Country2SN']));
                break;
            case 'conq':
            case 'secu':
            case 'revolt':
                $ref = 'battle';
                $key = $e['Type'] === 'revolt' ? 'events_revolt' : $e['Type'];
                $text = sprintf($l->getstr($key, 'events'), $reg($e['RegionID']),
                    $e['Country1Name'] ? $c($e['Country1SN']) : $force, $e['Country2Name'] ? $c($e['Country2SN']) : $force);
                break;
            case 'rage':
                $ref = 'region';
                $ex = explode('|', (string) $e['refID']) + ['', '', ''];
                $e['refID'] = $ex[1];
                $text = sprintf($l->getstr('events_rageshot', 'events'), $c($e['Country1SN']), $ex[0], $reg($ex[1]), $c($e['Country2SN']));
                break;
        }
        $e['ref'] = $ref;
        $e['text'] = $text;

        return $e;
    }
}
