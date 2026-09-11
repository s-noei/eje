<?php

namespace App\Http\Controllers;

use App\Game\Services\Ip2Country;
use App\Game\Services\Paypal;
use Illuminate\Http\Request;

/**
 * Port of store.php + include/store/* (PayPal) and sms.php + include/sms/* (PayGol).
 */
class StoreController extends GameController
{
    /** ejstore[-{go}].html */
    public function index(Request $request, ?string $go = null)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $db = $this->database;
        $citInfo = $this->citInfo;
        $me = $citInfo['CitizenID'];
        $happyTime = false; // legacy $happyTime was never set
        $layout = ['title' => $this->lang->getstr('title_store', 'title'), 'bar_title' => 'eJahan Store', 'actiontype' => 'store'];

        switch ($go) {
            case 'my':
                $layout['bar_title'] .= ' - Your active payments';

                return $this->page('pages.store.my', [
                    'rows' => $db->rows('SELECT store_sales.*, store_items.* FROM store_sales JOIN store_items ON store_sales.itemno = store_items.itemno WHERE buyer = ? ORDER BY timestamp DESC', [$me]),
                ], $layout);

            case 'process':
                $itemno = (int) $request->input('itemno');
                if (!$itemno) {
                    return redirect('/index.html');
                }
                $layout['bar_title'] .= ' - Processing your purchase';
                $item = $db->row('SELECT * FROM store_items WHERE itemno = ?', [$itemno]);
                if (!$item) {
                    return $this->page('pages.store.process', ['item' => null, 'form' => ''], $layout);
                }
                if ($happyTime) {
                    $item['itemprice'] = $item['itempriceht'];
                }
                $time = time();
                $purid = $db->insertGetId('INSERT INTO store_sales (itemno, price, buyer, timestamp) VALUES (?, ?, ?, ?)', [$item['itemno'], $item['itemprice'], $me, $time]);
                $token = md5($me.$item['itemprice'].$item['itemno'].'Hey bro, do not change this!');
                $custom = "ej/$me/$purid/{$item['itemno']}/$time/$token";

                $pp = new Paypal();
                $pp->addField('business', config('ejahan.paypal.business'));
                $pp->addField('currency_code', 'USD');
                $pp->addField('return', url($this->vars->getURL('ejstore', 'success')));
                $pp->addField('cancel_return', url($this->vars->getURL('ejstore', 'failure')));
                $pp->addField('notify_url', url('/ipn/paypal'));
                $pp->addField('item_name', $item['itemtitle']);
                $pp->addField('amount', $item['itemprice']);
                $pp->addField('item_number', $item['itemno']);
                $pp->addField('custom', $custom);

                return $this->page('pages.store.process', ['item' => $item, 'form' => $pp->paymentForm()], $layout);

            case 'success':
                return $this->page('pages.store.result', ['ok' => true], $layout);
            case 'failure':
                return $this->page('pages.store.result', ['ok' => false], $layout);
        }

        return $this->page('pages.store.index', [
            'happyTime' => $happyTime,
            'talaItems' => $db->rows('SELECT * FROM store_items WHERE itemno > 100 AND itemno < 200'),
            'proItems' => $db->rows('SELECT * FROM store_items WHERE itemno > 220 AND itemno < 300'),
            'months' => ['1 month', '3 months', '6 months'],
        ], $layout);
    }

    /** PayPal IPN receiver (legacy ejstore-buyproc). */
    public function paypalIpn(Request $request)
    {
        $pp = new Paypal();
        if ($pp->validateIpn($request->post())) {
            $ipn = $pp->ipnData;
            $custom = explode('/', (string) ($ipn['custom'] ?? ''));
            if (($custom[0] ?? '') !== 'ej' || ($ipn['mc_currency'] ?? '') !== 'USD') {
                return response('', 200);
            }
            [, $citID, $purID, $itemID] = $custom + [null, null, null, null];
            $price = $ipn['mc_gross'] ?? '';
            if (($custom[5] ?? '') !== md5($citID.$price.$itemID.'Hey bro, do not change this!')) {
                return response('', 200);
            }
            if (($ipn['payment_status'] ?? '') === 'Completed') {
                $this->pays()->finishPurchase($purID, (string) ($ipn['payer_email'] ?? ''), (string) ($ipn['txn_id'] ?? ''));
            }
        }

        return response('', 200);
    }

    /** sms[-{go}].html */
    public function sms(Request $request, ?string $go = null)
    {
        if (!$this->loggedIn()) {
            return redirect('/index.html');
        }
        $layout = ['title' => $this->lang->getstr('title_store', 'title'), 'bar_title' => 'eJahan Store', 'actiontype' => 'sms'];

        if ($go === 'smsback') {
            if (!in_array($request->ip(), config('ejahan.paygol.ips'), true)) {
                return response('Error: Unknown IP', 403);
            }
            $custom = (int) $request->input('custom');
            $fields = ['message_id', 'shortcode', 'keyword', 'message', 'sender', 'operator', 'country', 'custom', 'price', 'currency', 'points'];
            $customsms = 's/'.implode('/', array_map(fn ($f) => (string) $request->input($f, ''), $fields)).'/'.$custom;
            $this->database->sendNote($custom, '', 'You received 5 Tala because of a payment. Thanks for your purchase.');
            $this->database->addMoney(1, 5, $custom, 'citizen', 1, '> Bought with RL money <');
            $this->database->exec("INSERT INTO store_sales (itemno, price, currency, buyer, txn_id, txn_bank, buyermail, transtat, timestamp) VALUES ('1', ?, ?, ?, ?, 'PayGol', ?, 'Completed', ?)",
                [(string) $request->input('points', ''), (string) $request->input('price', ''), $custom, $customsms, (string) $request->input('sender', ''), time()]);

            return response('Success');
        }

        return $this->page('pages.store.sms', ['go' => $go, 'serviceId' => config('ejahan.paygol.service_id')], $layout);
    }
}
