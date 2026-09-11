<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/**
 * lens/include/ads/* — sponsored ads. Legacy home.php was a copy of the citizen stats page and
 * view.php was missing; here "Stats" shows sponsored-ad totals and "View ads" lists them.
 */
class AdsController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_ads']) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $links = [['Stats', self::url('ads')], ['Add an ad', self::url('ads', 'add')], ['View ads', self::url('ads', 'view')]];
        $base = ['links' => $links, 'type' => $type, 'id' => $id];

        if ($type === 'add') {
            $msg = '';
            if ($request->input('subadd')) {
                $pic = '';
                $file = $request->file('pic');
                if ($file && $file->isValid()) {
                    $pic = md5(random_int(0, PHP_INT_MAX).microtime()).'.jpg';
                    $file->move(public_path('uploads/spads'), $pic);
                }
                $db->exec('INSERT INTO ads_sponsored (location, title, content, link, pic, language, country, credit, costType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    in_array($request->input('location'), ['top', 'left'], true) ? $request->input('location') : 'top',
                    strip_tags((string) $request->input('title')), (string) $request->input('content'), (string) $request->input('link'), $pic,
                    (string) $request->input('language', ''), (int) $request->input('country'), (float) $request->input('credit'), (int) $request->input('costtype'),
                ]);
                $msg = '<strong>Added successfully!</strong>';
            }

            return $this->lensPage('lens.ads.add', $base + ['msg' => $msg, 'countries' => $db->rows('SELECT * FROM country WHERE Hidden = 0 ORDER BY cName')], 'Ad tools', 'ads');
        }

        if ($type === 'view') {
            if ($request->filled('toggle')) {
                $db->exec('UPDATE ads_sponsored SET active = 1 - active WHERE adID = ?', [(int) $request->input('toggle')]);

                return redirect($request->getRequestUri());
            }

            return $this->lensPage('lens.ads.view', $base + ['rows' => $db->rows('SELECT * FROM ads_sponsored ORDER BY adID DESC')], 'Ad tools', 'ads');
        }

        $stats = [
            ['Sponsored ads', $db->count('SELECT adID FROM ads_sponsored')],
            ['Active sponsored ads', $db->count('SELECT adID FROM ads_sponsored WHERE active = 1')],
            ['Citizen ads', $db->count('SELECT adID FROM ads')],
            ['Active citizen ads', $db->count('SELECT adID FROM ads WHERE status = 1')],
        ];

        return $this->lensPage('lens.ads.home', $base + ['stats' => $stats], 'Ad tools', 'ads');
    }
}
