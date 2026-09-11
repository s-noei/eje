<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of mines.php + include/mines/explore_report.php.
 */
class MinesController extends GameController
{
    public function index(Request $request)
    {
        if (!$this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('explore');
        $db = $this->database;
        $citInfo = $this->citInfo;
        $gds = min((int) ($citInfo['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $wMul = $gdpercs[$gds];
        $getWChange = function (int $type) use ($wMul) {
            $wChange = pow(2, $type - 1) * 4;
            $wChange -= abs(round($wChange * $wMul));

            return -$wChange;
        };

        $error = null;
        $showReport = false;
        $bar = $this->lang->getstr('explore_bartitle', 'explore');

        if ($request->input('subexplore')) {
            if ($citInfo['puberty'] == 0) {
                return redirect('/index.html');
            }
            $dur = (int) $request->input('explore');
            $bar = 'Explore';
            $nwell = $getWChange($dur ?: 1);
            if ($citInfo['LastExplored'] == $db->today) {
                $error = $this->lang->getstr('err_explored', 'msgs');
            } elseif ($dur < 1 || ($dur > 1 && $citInfo['puberty'] == 1) || ($dur > 3 && $citInfo['puberty'] > 1)) {
                $error = $this->lang->getstr('err_dur_invalid', 'msgs');
            } elseif ($citInfo['wellness'] <= $nwell) {
                $error = $this->lang->getstr('error_low_wellness', 'msgs');
            } elseif ($citInfo['occDue'] >= time()) {
                $error = sprintf($this->lang->getstr('err_explored', 'msgs'), $this->session->getDiffF($citInfo['occDue'])).'.';
            } else {
                $db->doExplore($citInfo, pow(2, $dur - 1) * 2, array_map('intval', (array) $request->input('am', [])));
                $this->session->fillInfo(null, true);
                $citInfo = $this->citInfo = $this->session->userinfo;
                $showReport = true;
            }
        }

        $exploredToday = $citInfo['LastExplored'] == $db->today;
        $report = null;
        if ($showReport || $exploredToday) {
            $report = $this->report($citInfo['CitizenID']);
        }

        $max = $this->havePro() ? 200 : ($this->havePlus() ? 100 : 40);
        $foods = array_fill(1, 5, 0);
        foreach ($db->rows("SELECT Stars, COUNT(pID) Amount FROM (SELECT * FROM inventory WHERE Usable = 1 AND Owner = ? LIMIT $max) inv WHERE Type = 1 GROUP BY Stars ORDER BY Stars DESC", [$citInfo['CitizenID']]) as $f) {
            if (isset($foods[(int) $f['Stars']])) {
                $foods[(int) $f['Stars']] = (int) $f['Amount'];
            }
        }
        $tri = max(10, min(29, $citInfo['mines_tried'] + 1));

        return $this->page('pages.mines.index', [
            'error' => $error, 'showReport' => $showReport, 'exploredToday' => $exploredToday, 'report' => $report,
            'wChange' => [1 => $getWChange(1), 2 => $getWChange(2), 3 => $getWChange(3)],
            'foods' => $foods, 'tri' => $tri,
            'tit' => 'cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]',
        ], ['title' => $this->lang->getstr('title_mines', 'title'), 'bar_title' => $bar, 'actiontype' => 'mines']);
    }

    /** include/mines/explore_report.php data. */
    private function report(int $citID): ?array
    {
        $rep = $this->database->row('SELECT * FROM log_exploring WHERE CitizenID = ? AND Day = ?', [$citID, $this->database->today]);
        if (!$rep) {
            return null;
        }
        $ex = explode('|', (string) $rep['ep']) + [0, 0];
        $rep['ep'] = $ex[0] + $ex[1];
        $rep['ep2'] = $ex[1];
        $ex = explode('|', (string) $rep['wellness']) + [0, 0, 0];
        $rep['wellness'] = $ex[0];
        $rep['wellness2'] = $ex[1];
        $rep['wellness3'] = $ex[2];
        $ex = explode('|', (string) $rep['totpoints']) + [0, 0];
        $rep['totpoints1'] = $ex[0] + $ex[1];
        $rep['totpoints2'] = $ex[1];
        $rep['received'] = round((float) $rep['received'], 3);
        $rep['left'] = (1600 - $rep['totpoints1'] > 0) ? (1600 - $rep['totpoints1']) : (3200 - $rep['totpoints1']);

        return $rep;
    }
}
