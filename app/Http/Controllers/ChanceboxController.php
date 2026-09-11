<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of chancebox.php + include/chancebox/{index,view,buy,open}.php and lottery.php.
 */
class ChanceboxController extends GameController
{
    public function index(Request $request, ?string $do = null, ?int $id = null)
    {
        if (!$this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('chancebox');
        $db = $this->database;
        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'];
        $errors = [];
        $info = [];
        $data = ['do' => $do];

        $bg = match ($this->vars->browser['name'] ?? '') {
            'Mozilla Firefox' => '-moz-radial-gradient(center, ellipse, #67BBF6, #1E5A84)',
            'Google Chrome' => '-webkit-gradient(radial, center center, 0, center center, 60, from(#67BBF6), to(#1E5A84))',
            default => '',
        };
        $data['bg'] = $bg;

        switch ($do) {
            case 'view':
                $data['boxes'] = $db->rows('SELECT * FROM chanceboxes WHERE toID = ? AND ISNULL(result) AND day >= ?', [$me, $db->today - 30]);
                break;

            case 'buy':
                $setting = $db->setting;
                $happyTime2 = ($setting['ht2_start'] ?? 0) <= time() && ($setting['ht2_due'] ?? 0) > time();
                if ($request->input('subbuy')) {
                    $money = $db->getCitizenMoney($me, 1);
                    $pts = (int) $request->input('pts');
                    if ($happyTime2) {
                        $pts = (int) ($pts / 2);
                    }
                    if ($pts <= 0 || $pts > 50) {
                        return redirect($request->getRequestUri());
                    }
                    $price = $pts / 10;
                    if ($money < $price) {
                        $errors[] = sprintf($this->lang->getstr('err_cb_not_enough_money', 'msgs'), $price);
                    } else {
                        $db->transferMoney(1, $price, $me, 'citizen', 1, 'citizen', 1, '> Bought chancebox <');
                        $db->addCB($me, 'bought', $pts * ($happyTime2 ? 2 : 1), 0);
                        $this->session->fillInfo(null, true);
                        $info[] = sprintf($this->lang->getstr('inf_cb_bought', 'msgs'), $pts * ($happyTime2 ? 2 : 1), $price);
                    }
                }
                $data += ['happyTime2' => $happyTime2, 'htDue' => ($setting['ht2_due'] ?? 0) - time()];
                break;

            case 'open':
                $cb = $db->row('SELECT * FROM chanceboxes WHERE toID = ? AND cbID = ?', [$me, (int) $id]);
                if (!$cb) {
                    return redirect('/index.html');
                }
                $rs = random_int(1, 9);
                if ($rs > 5) {
                    $rs -= 5;
                }
                $db->exec('UPDATE chanceboxes SET expResult = ? WHERE cbID = ?', [$rs, $cb['cbID']]);
                $canOpenMore = $db->count('SELECT LastCBsOpened FROM citizens WHERE CitizenID = ? AND LastCBsOpened < 3', [$me]) > 0;
                $state = 'ok';
                if ($cb['day'] < $db->today - 30) {
                    $state = 'expired';
                } elseif (!$canOpenMore) {
                    $state = 'finished';
                } elseif ($cb['result']) {
                    $state = 'used';
                }
                $data += ['cb' => $cb, 'state' => $state, 'id' => $cb['cbID'], 'eggs' => ['blue', 'red', 'green', 'purple', 'orange'],
                    'token' => crc32($cb['cbID'].$me.'k3y4 chance egg')];
                break;

            default:
                $data['do'] = null;
                $data['sealed'] = $db->count('SELECT * FROM chanceboxes WHERE toID = ? AND ISNULL(result) AND day >= ?', [$me, $db->today - 30]);
        }

        return $this->page('pages.chancebox.index', $data + ['errors' => $errors, 'info' => $info], [
            'title' => $this->lang->getstr('title_chancebox', 'title'), 'bar_title' => 'Chance box', 'coltype' => 1, 'hideups' => true, 'actiontype' => 'chancebox',
        ]);
    }

    /** lottery.html, lottery-results-{day}.html */
    public function lottery(Request $request, ?string $go = null, ?int $day = null)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $db = $this->database;
        $me = $this->citInfo['CitizenID'];
        $day = $day ?: $db->today;
        $errors = [];
        $info = [];
        $tickets = $db->rows('SELECT * FROM lottery_tickets WHERE buyerID = ?', [$me]);
        $oTala = $db->getCitizenMoney($me, 1);

        if ($request->filled('Amount')) {
            $amount = (int) floor((float) $request->input('Amount'));
            $price = $amount * 0.1;
            if ($amount < 1) {
                $errors[] = 'Invalid amount has been entered.';
            } elseif ($amount + count($tickets) > 20) {
                $errors[] = 'You can buy max. 20 tickets in every round.';
            } elseif ($oTala < $price) {
                $errors[] = "You don't have enough money to buy this amount of tickets.";
            } else {
                $time = time();
                for ($i = 1; $i <= $amount; $i++) {
                    $db->exec('INSERT INTO lottery_tickets (buyerID, timestamp) VALUES (?, ?)', [$me, $time]);
                }
                $db->transferMoney(1, $price, $me, 'citizen', 1, 'citizen');
                $this->session->fillInfo(null, true);
                $tickets = $db->rows('SELECT * FROM lottery_tickets WHERE buyerID = ?', [$me]);
                $oTala = $db->getCitizenMoney($me, 1);
                $info[] = 'Your tickets have been bought successfully, good luck!';
            }
        }

        $data = ['go' => $go, 'day' => $day, 'oTala' => $oTala, 'tickets' => $tickets, 'errors' => $errors, 'info' => $info];
        if ($go === 'results') {
            $data['days'] = range($db->today, $db->today - 99, -1);
            $data['lotstat'] = $db->row('SELECT * FROM lottery_stats WHERE lottery_day = ?', [$day]);
            $data['winners'] = $db->rows('SELECT lottery_winners.*, citizens.* FROM lottery_winners JOIN citizens ON lottery_winners.winnerID = citizens.CitizenID WHERE Day = ? ORDER BY ID', [$day]);
        }

        return $this->page('pages.chancebox.lottery', $data, ['title' => $this->lang->getstr('title_lottery', 'title'), 'bar_title' => 'Lottery', 'actiontype' => 'lottery']);
    }
}
