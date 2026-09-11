<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of adcenter.php + include/adcenter/{index,add,click}.php.
 * URLs: ads.html, ads-{go}.html, ads-{go}-{id}.html (go: add|edit|stop|start|click).
 */
class AdCenterController extends GameController
{
    public function index(Request $request, ?string $go = null, ?string $id = null)
    {
        $db = $this->database;
        if ($go === 'click') {
            if (!$id) {
                abort(404);
            }
            $db->exec('UPDATE ads SET clicks = clicks+1 WHERE admd5 = ?', [$id]);
            $link = $db->value('SELECT link FROM ads WHERE admd5 = ?', [$id], '');
            if (!$link) {
                abort(404);
            }
            // Ads may only target internal links (enforced on creation).
            return redirect('/'.ltrim((string) $link, '/'));
        }

        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'];
        $layout = ['title' => $this->lang->getstr('title_ads', 'title'), 'bar_title' => 'Advertisement', 'actiontype' => 'ads'];

        if ($go === 'stop' || $go === 'start') {
            if ($db->count('SELECT adID FROM ads WHERE adID = ? AND creator = ?', [(int) $id, $me])) {
                $db->exec('UPDATE ads SET status = ? WHERE adID = ?', [$go === 'start' ? 1 : 0, (int) $id]);
            }

            return redirect($this->vars->getURL('ads'));
        }

        if ($go === 'add' || $go === 'edit') {
            $row = null;
            if ($go === 'edit') {
                $row = $db->row('SELECT * FROM ads WHERE adID = ? AND creator = ?', [(int) $id, $me]);
                if (!$row) {
                    return redirect('/index.html');
                }
                if ($row['status'] == 1) {
                    return redirect($this->vars->getURL('ads'));
                }
            }
            $errors = [];
            $form = $row ? ['title' => $row['title'], 'link' => $row['link'], 'content' => $row['desc'], 'price' => $row['cost'], 'rtl' => $row['rtl'], 'country' => $row['viewin']]
                : ['title' => '', 'link' => '', 'content' => '', 'price' => '0', 'rtl' => 0, 'country' => 0];
            $token = md5($me.'ad center'.($row ? $row['adID'] : ''));

            if ($request->input('submitad')) {
                $form = ['title' => (string) $request->input('title'), 'link' => (string) $request->input('link'), 'content' => (string) $request->input('content'),
                    'price' => $request->input('price'), 'rtl' => $request->has('rtl') ? 1 : 0, 'country' => (int) $request->input('country')];
                $price = $form['price'];
                $balance = $row ? $price - $row['cost'] : $price;
                $citMoney = $db->getCitizenMoney($me);
                $pic = $request->file('adpic');
                if ($request->input('token') !== $token) {
                    $errors[] = 'Cheating detected.';
                } elseif (!is_numeric($price)) {
                    $errors[] = 'The price can handle numbers, only.';
                } elseif ($price < 0.01) {
                    $errors[] = 'The minimum budget is 0.01 TALA.';
                } elseif ($balance >= ($citMoney[1] ?? 0)) {
                    $errors[] = 'You have not enough money in your account.';
                } elseif (str_contains($form['link'], '://')) {
                    $errors[] = 'You have to use internal links only.';
                } elseif ($pic && !($pic->isValid() && in_array($pic->getMimeType(), ['image/jpeg', 'image/pjpeg']) && $pic->getSize() < 50 * 1024)) {
                    $errors[] = 'Invalid picture.';
                } else {
                    $title = strip_tags($form['title']);
                    $content = strip_tags($form['content']);
                    $ulink = strip_tags($form['link']);
                    $admd5 = md5($form['title'].'An ad');
                    $db->addMoney(1, -$balance, $me);
                    if (!$row) {
                        $adid = $db->insertGetId('INSERT INTO ads (title, link, pic, `desc`, admd5, creator, cost, viewin, rtl, edit, views, clicks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)',
                            [$title, $ulink, 'noavatar.gif', $content, $admd5, $me, $price, $form['country'], $form['rtl'], time()]);
                    } else {
                        $adid = $row['adID'];
                        $db->exec('UPDATE ads SET title = ?, link = ?, `desc` = ?, cost = ?, viewin = ?, rtl = ?, edit = ? WHERE adID = ? AND creator = ?',
                            [$title, $ulink, $content, $price, $form['country'], $form['rtl'], time(), $adid, $me]);
                    }
                    if ($pic) {
                        $fName = md5((string) $adid).'.jpg';
                        $pic->move(public_path('uploads/ads'), $fName);
                        $db->exec('UPDATE ads SET pic = ? WHERE adID = ?', [$fName, $adid]);
                    }
                    $this->session->fillInfo(null, true);

                    return redirect($this->vars->getURL('ads'));
                }
            }

            return $this->page('pages.ads.add', ['go' => $go, 'row' => $row, 'form' => $form, 'errors' => $errors, 'token' => $token, 'id' => $id,
                'allCountries' => $db->rows("SELECT * FROM country WHERE Hidden = '0' ORDER BY cName")], $layout);
        }

        $layout['bar_title'] .= ' - Your active advertisements';

        return $this->page('pages.ads.index', ['rows' => $db->rows('SELECT ads.* FROM ads WHERE creator = ? ORDER BY edit DESC', [$me])], $layout);
    }
}
