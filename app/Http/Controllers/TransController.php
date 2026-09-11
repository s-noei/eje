<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of the translation center: include/trans/{index,view,team}.php
 * (trans.php itself is empty in the legacy snapshot; the dispatcher is reconstructed here).
 * Access: members of trans_team (post 9 = admin, >= 8 = language lead, 1 = collaborator).
 */
class TransController extends GameController
{
    private const LANGS = ['fa' => 'Farsi', 'fr' => 'French', 'de' => 'German', 'hu' => 'Hungarian', 'it' => 'Italian', 'pl' => 'Polish', 'pt' => 'Portoguese',
        'ro' => 'Romanian', 'ru' => 'Russian', 'rs' => 'Serbian', 'es' => 'Spanish', 'tr' => 'Turkish', 'hr' => 'Croatian', 'si' => 'Slovenian'];

    public function index(Request $request, ?string $do = null)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $trdata = $db->row('SELECT * FROM trans_team WHERE citID = ?', [$me]);
        if (!$trdata && !$this->session->isAdmin()) {
            return redirect('/index.html');
        }
        $trdata = $trdata ?: ['citID' => $me, 'langID' => 'en', 'post' => 9];
        $post = (int) $trdata['post'];
        $layout = ['title' => $this->lang->getstr('title_trans', 'title'), 'bar_title' => 'Translation center', 'coltype' => 1, 'actiontype' => 'trans'];
        $langCol = fn (string $l) => 'trans_'.preg_replace('/[^a-z]/', '', $l);
        $validLang = fn (string $l) => in_array($l, config('ejahan.languages'), true);

        if ($do === 'team') {
            if ($post < 8) {
                return redirect($this->vars->getURL('trans'));
            }
            $errors = [];
            if ($request->input('addcol')) {
                $citID = (int) $request->input('colid');
                if ($db->count("SELECT CitizenID FROM citizens WHERE CitizenID = ? AND accType = 'co-account'", [$citID])) {
                    $errors[] = 'The specified ID is a co-account.';
                } elseif ($db->count('SELECT citID FROM trans_team WHERE citID = ?', [$citID])) {
                    $errors[] = 'The specified ID is a member of translation team.';
                } else {
                    $db->exec("INSERT INTO trans_team (citID, langID, post) VALUES (?, ?, '1')", [$citID, $trdata['langID']]);

                    return redirect($request->getRequestUri());
                }
            }
            if ($request->filled('remid')) {
                $citID = (int) $request->input('remid');
                if ($request->input('token') === md5($citID.'colab')) {
                    $db->exec('DELETE FROM trans_team WHERE citID = ? AND post = 1', [$citID]);
                }

                return redirect($request->getRequestUri());
            }
            $layout['bar_title'] = 'Translation - define collaborators';

            return $this->page('pages.trans.team', ['errors' => $errors, 'trdata' => $trdata,
                'rows' => $db->rows('SELECT trans_team.citID, citizens.name FROM trans_team JOIN citizens ON citizens.CitizenID = trans_team.citID WHERE langID = ? AND post = 1', [$trdata['langID']])], $layout);
        }

        if ($do === 'view') {
            $langu = (string) $request->input('lang', $trdata['langID']);
            if ($post === 9 && $request->filled('lang') && $validLang($langu)) {
                $trdata['langID'] = $langu;
            }
            $langID = $validLang($trdata['langID']) ? $trdata['langID'] : 'en';
            $col = $langCol($langID);
            $location = (string) $request->input('location', '');
            $errors = [];

            if ($request->input('addstr') && $validLang($langu)) {
                $trans = (string) $request->input('trans');
                $phrase = (string) $request->input('phrase');
                if ($request->input('token') === md5($phrase.$langu.'LangUagE')) {
                    if ($post >= 8) {
                        $db->exec('UPDATE trans_strings SET '.$langCol($langu).' = ? WHERE phrase = ?', [$trans, $phrase]);
                        app(\App\Game\Services\Translator::class)->flush();
                    } elseif ($db->count('SELECT str FROM trans_suggests WHERE phrase = ? AND lang = ? AND byID = ?', [$phrase, $langu, $me])) {
                        $db->exec('UPDATE trans_suggests SET str = ? WHERE phrase = ? AND lang = ? AND byID = ?', [$trans, $phrase, $langu, $me]);
                    } else {
                        $db->exec('INSERT INTO trans_suggests (phrase, lang, byID, str) VALUES (?, ?, ?, ?)', [$phrase, $langu, $me, $trans]);
                    }
                }
            }
            if ($request->input('selsug') && $post >= 8) {
                $sugid = (int) $request->input('sugid');
                if ($request->input('token') === md5($sugid.'LangUagE')) {
                    $sug = $db->row('SELECT * FROM trans_suggests WHERE ID = ?', [$sugid]);
                    if (!$sug) {
                        return redirect($request->getRequestUri());
                    }
                    if ($validLang($sug['lang'])) {
                        $db->exec('UPDATE trans_strings SET '.$langCol($sug['lang']).' = ? WHERE phrase = ?', [$sug['str'], $sug['phrase']]);
                        $db->exec('DELETE FROM trans_suggests WHERE phrase = ? AND lang = ?', [$sug['phrase'], $sug['lang']]);
                        app(\App\Game\Services\Translator::class)->flush();
                    }
                }
            }

            $locations = $db->rows("SELECT location FROM trans_strings WHERE ISNULL($col) GROUP BY location ORDER BY location");
            $phrases = [];
            if ($request->input('viewstrs')) {
                $phrases = $db->rows("SELECT strID, phrase, $col AS local, trans_en FROM trans_strings WHERE location = ? AND ISNULL($col) ORDER BY phrase", [$location]);
                foreach ($phrases as &$p) {
                    if ($post < 8) {
                        $p['mySug'] = $db->value('SELECT str FROM trans_suggests WHERE phrase = ? AND lang = ? AND byID = ?', [$p['phrase'], $langID, $me], '');
                    } else {
                        $p['sugs'] = $db->rows('SELECT trans_suggests.*, citizens.name FROM trans_suggests JOIN citizens ON citizens.CitizenID = trans_suggests.byID WHERE phrase = ? AND lang = ?', [$p['phrase'], $langID]);
                    }
                }
            }

            return $this->page('pages.trans.view', ['errors' => $errors, 'trdata' => $trdata, 'post' => $post, 'langID' => $langID, 'langu' => $langu,
                'location' => $location, 'locations' => $locations, 'phrases' => $phrases, 'viewing' => (bool) $request->input('viewstrs')], $layout);
        }

        if ($request->input('addnews') && $post === 9) {
            $db->exec('INSERT INTO trans_news (body, timestamp) VALUES (?, ?)', [strip_tags((string) $request->input('body')), time()]);

            return redirect($request->getRequestUri());
        }
        $stats = ['Total strings' => $db->count('SELECT strID FROM trans_strings')];
        foreach (self::LANGS as $code => $name) {
            $stats["Left for $name"] = $db->count('SELECT strID FROM trans_strings WHERE ISNULL('.$langCol($code).')');
        }

        return $this->page('pages.trans.index', ['trdata' => $trdata, 'post' => $post, 'news' => $db->rows('SELECT * FROM trans_news ORDER BY timestamp DESC LIMIT 5'), 'stats' => $stats], $layout);
    }
}
