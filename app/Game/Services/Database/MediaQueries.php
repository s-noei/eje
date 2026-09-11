<?php

namespace App\Game\Services\Database;

/**
 * Newspapers, articles, comments and votes (port of the media part of MySQLDB).
 */
trait MediaQueries
{
    public function createArticle(string $aTitle, $aResp, string $aContent, string $aPic, string $aSound, $rtl, int|string $npID, int|string $CitID, $draft = 0): int
    {
        if (! $this->isManagerNP($npID, $CitID)) {
            return 0;
        }
        $npCoun = (int) $this->value('SELECT CountryID FROM np_details WHERE npID = ?', [$npID], 0);

        return $this->insertGetId('INSERT INTO articles (npID, aTitle, respFor, aPic, aSound, aCountry, aContent, RTL, isDraft, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$npID, $aTitle, (int) $aResp, $aPic, $aSound, $npCoun, $aContent, ($rtl === 'on' || $rtl === '1' || $rtl === 1) ? 1 : 0, (int) $draft, (string) time()]);
    }

    public function createNP(string $npName, int|string $managerID, int|string $regID): int
    {
        $ctx = $this->context();
        if (! ($ctx && $ctx->logged_in)) {
            return 0;
        }
        $oldAmount = $this->getCitizenMoney($managerID, 1);
        $price = (float) $this->getSetting('price_np');
        if ($oldAmount < $price) {
            return 0;
        }
        $this->updateUserMoney($managerID, 1, $oldAmount - $price);
        $counID = $this->getCountryID($regID);
        $npID = $this->insertGetId('INSERT INTO np_details (npName, CountryID) VALUES (?, ?)', [$npName, $counID]);
        $this->exec('UPDATE citizens SET npID = ? WHERE CitizenID = ?', [$npID, $managerID]);

        return $npID;
    }

    public function editArticle(int|string $artID, string $aTitle, string $aContent, $draft = 0): int
    {
        $draft = (int) $draft;
        $this->exec("UPDATE articles SET aTitle = ?, aContent = ?, isDraft = ?, timestamp = IF((NOT(isDraft) OR {$draft}), timestamp, ?) WHERE aID = ?",
            [$aTitle, $aContent, $draft, (string) time(), $artID]);

        return 1;
    }

    public function getAuthor(int|string $npID): string|int
    {
        return $this->value('SELECT name FROM citizens WHERE npID = ?', [$npID], 0);
    }

    public function getAuthorID(int|string $npID): int
    {
        return (int) $this->value('SELECT CitizenID FROM citizens WHERE npID = ?', [$npID], 0);
    }

    public function getArticle(int|string $aID): ?array
    {
        return $this->row('SELECT articles.*, country.Flag aFlag, np_details.*, citizens.name AS npAuthor,
                citizens.CitizenID AS npAuthorID, remover.CitizenID removerID, remover.name removerName
            FROM articles JOIN np_details ON articles.npID = np_details.npID
            JOIN citizens ON np_details.npID = citizens.npID
            LEFT JOIN citizens remover ON articles.DeletedBy = remover.CitizenID
            JOIN country ON aCountry = country.CountryID WHERE aID = ?', [$aID]);
    }

    public function getComment(int|string $cmID, $admLevel = ''): ?array
    {
        $sql = 'SELECT * FROM article_comments WHERE cmID = ?';
        if (! $admLevel) {
            $sql .= " AND Deleted = '0'";
        }

        return $this->row($sql, [$cmID]);
    }

    public function getComments(int|string $artID, int|string $citID, $admLevel = ''): array
    {
        $sql = 'SELECT article_comments.*, IF(NOT ISNULL(article_comments_votes.Vote), 1, 0) Voted, citizens.Avatar,
                citizens.name, citizens.ep, citizens.accType, citizens.accOwner,
                region.rName AS RegionName, country.cName, quoted.cmBody quoted_body, quoted.CitID quoted_id, quotedCit.name quoted_name
            FROM article_comments
            LEFT JOIN article_comments quoted ON article_comments.quote = quoted.cmID
            LEFT JOIN citizens quotedCit ON quoted.CitID = quotedCit.CitizenID
            LEFT JOIN article_comments_votes ON article_comments_votes.CitizenID = ? AND article_comments_votes.CommentID = article_comments.cmID
            JOIN citizens ON citizens.CitizenID = article_comments.CitID
            JOIN region ON citizens.regionID = region.RegionID
            JOIN country ON region.CountryID = country.CountryID
            WHERE article_comments.ArtID = ?';
        if (! $admLevel) {
            $sql .= " AND article_comments.Deleted = '0'";
        }

        return $this->rows($sql.' ORDER BY article_comments.timestamp', [(int) $citID, $artID]);
    }

    public function getNPAdminArticles(): array
    {
        return $this->rows("SELECT * FROM articles WHERE npID = '1' AND Deleted != '1' AND isDraft != '1' ORDER BY timestamp DESC LIMIT 0, 5");
    }

    /** Articles of a newspaper (5 per page) or the count when $start < 0. */
    public function getNPArticles(int|string $npID, $mod = 0, int $start = -1): array|int
    {
        $sql = 'SELECT * FROM articles WHERE npID = ?';
        if ($mod) {
            $sql .= " AND Deleted != '1' AND isDraft != '1'";
        }
        $sql .= ' ORDER BY timestamp DESC';
        if ($start < 0) {
            return $this->count($sql, [$npID]);
        }

        return $this->rows($sql.' LIMIT '.(int) $start.', 5', [$npID]);
    }

    public function getNPAuthorID(int|string $npID): int
    {
        return (int) $this->value('SELECT citizens.CitizenID FROM np_details JOIN citizens ON citizens.npID = np_details.npID WHERE np_details.npID = ?', [$npID], 0);
    }

    public function getNPName(int|string $npID): string|int
    {
        return $this->value('SELECT npName FROM np_details WHERE npID = ?', [$npID], 0);
    }

    public function getNP(int|string $npID): ?array
    {
        return $this->row('SELECT * FROM np_details WHERE npID = ?', [$npID]);
    }

    public function getLatestNews(int|string $counID): array
    {
        return $this->rows("SELECT articles.*, np_details.* FROM articles JOIN np_details ON np_details.npID = articles.npID
            WHERE NOT(np_details.npID = '1') AND articles.aCountry = ? AND articles.Deleted != '1' ORDER BY timestamp DESC LIMIT 0, 5", [$counID]);
    }

    public function getTRNewsI(int|string $counID = 0): array
    {
        return $this->rows("SELECT articles.*, np_details.* FROM articles JOIN np_details ON articles.npID = np_details.npID
            WHERE articles.Deleted != '1' AND timestamp >= ? AND articles.isDraft != '1' ORDER BY aVotes DESC LIMIT 0, 5", [(string) (time() - 172800)]);
    }

    public function getTRNewsL(int|string $counID): array
    {
        return $this->rows("SELECT articles.*, np_details.* FROM articles INNER JOIN np_details ON np_details.npID = articles.npID
            WHERE NOT(np_details.npID = '1') AND articles.aCountry = ? AND articles.Deleted != '1' AND articles.isDraft != '1' AND timestamp >= ?
            ORDER BY aVotes DESC LIMIT 0, 5", [$counID, (string) (time() - 172800)]);
    }

    public function getTRNewsS(int|string $citID): array
    {
        return $this->rows("SELECT articles.*, np_details.* FROM articles JOIN np_details ON np_details.npID = articles.npID
            JOIN np_subs ON np_subs.npID = np_details.npID
            WHERE np_subs.citID = ? AND articles.Deleted != '1' AND articles.isDraft != '1' ORDER BY articles.timestamp DESC LIMIT 0, 5", [$citID]);
    }

    public function postCM(int|string $CitID, int|string $artID, int|string $quote, string $cmBody): int|string
    {
        $cmBody = strip_tags($cmBody, '<b><i><sup><a>');
        $quoted = $this->row('SELECT CitID FROM article_comments WHERE cmID = ? AND ArtID = ?', [(int) $quote, $artID]);
        $quote = $quoted ? (int) $quote : null;
        $this->exec('INSERT INTO article_comments (CitID, ArtID, quote, cmBody, timestamp) VALUES (?, ?, ?, ?, ?)', [$CitID, $artID, $quote, $cmBody, (string) time()]);
        if ($quoted) {
            $this->sendNote($quoted['CitID'], '', 'A citizen replied to your comment in <a href="'.$this->url('article', $artID).'#comment">this article</a>.');
        }

        return 1;
    }

    public function removeArticle(int|string $artID): int
    {
        $ctx = $this->context();
        $this->exec("UPDATE articles SET Deleted = '1', DeletedBy = ? WHERE aID = ?", [$ctx?->CitID, $artID]);

        return 1;
    }

    public function setCMVote(int|string $cmID, int|string $citID, $vote): string|int
    {
        if (! $this->isVotedCM($cmID, $citID, 1) && ((string) $vote === '1' || (string) $vote === '-1')) {
            $this->exec('INSERT INTO article_comments_votes (CommentID, CitizenID, Vote) VALUES (?, ?, ?)', [$cmID, $citID, (string) $vote]);
            $target = (string) $vote === '1' ? 'thumbs_up' : 'thumbs_down';
            $this->exec("UPDATE article_comments SET {$target} = {$target} + 1 WHERE cmID = ?", [$cmID]);

            return (int) $this->value("SELECT {$target} V FROM article_comments WHERE cmID = ?", [$cmID], 0);
        }

        return '<font color="red">E</font>';
    }

    public function setVote(int|string $artID, int|string $citID, $vote, string $reason = ''): int
    {
        if (! in_array($reason, ['spam', 'insult', 'other'], true)) {
            $reason = '';
        }
        if (! $this->isVoted($artID, $citID, 1) && ((string) $vote === '1' || (string) $vote === '-1')) {
            $this->exec('INSERT INTO article_votes (citID, artID, vote, reason) VALUES (?, ?, ?, ?)', [$citID, $artID, (string) $vote, ucfirst($reason)]);
            $art = $this->getArticle($artID);
            $votes = (int) $art['aVotes'] + (int) $vote;
            $this->exec('UPDATE articles SET aVotes = ? WHERE aID = ?', [$votes, $artID]);
            if (! $art['aReaches'] && $votes >= 200) {
                $this->sendNote($art['npAuthorID'], '', '<a href="'.$this->url('article', $artID).'">Your article</a> collected enough votes and you received an article power trophy and 5 Tala!');
                $this->addMedal($art['npAuthorID'], 'ap', (string) $artID);
                $this->exec('UPDATE articles SET aReaches = 1 WHERE aID = ?', [$artID]);
            }

            return $votes;
        }
        $art = $this->getArticle($artID);

        return (int) ($art['aVotes'] ?? 0);
    }

    public function updateNewspaperField(int|string $npid, string $field, mixed $value): int
    {
        return $this->exec('UPDATE np_details SET '.$this->col($field).' = ? WHERE npID = ?', [$value, $npid]);
    }

    public function isAuthorThis(int|string $citID, int|string $npID): int
    {
        return ((int) $this->value('SELECT npID FROM citizens WHERE CitizenID = ?', [$citID], 0) === (int) $npID) ? 1 : 0;
    }

    public function isManagerNP(int|string $npID, int|string $CitID): int
    {
        return $this->count('SELECT npID FROM citizens WHERE CitizenID = ? AND npID = ?', [$CitID, $npID]) ? 1 : 0;
    }

    public function isSub(int|string $citID, int|string $npID): int
    {
        return $this->count('SELECT subID FROM np_subs WHERE npID = ? AND citID = ? LIMIT 1', [$npID, $citID]);
    }

    public function isVoted(int|string $artID, int|string $citID, $quer = 0): int
    {
        if (! $citID) {
            return 1;
        }
        $n = $this->count('SELECT citID FROM article_votes WHERE citID = ? AND artID = ?', [$citID, $artID]);
        $accType = $quer ? ($this->getUserInfoFromID($citID, 0)['accType'] ?? 'citizen') : 'citizen';

        return ($n < 1 && $accType === 'citizen') ? 0 : 1;
    }

    public function isVotedCM(int|string $cmID, int|string $citID, $quer = 0): int
    {
        if (! $citID) {
            return 1;
        }

        return $this->count('SELECT CitizenID FROM article_comments_votes WHERE CitizenID = ? AND CommentID = ?', [$citID, $cmID]) < 1 ? 0 : 1;
    }
}
