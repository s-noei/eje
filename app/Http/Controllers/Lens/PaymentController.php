<?php

namespace App\Http\Controllers\Lens;

use App\Game\Services\Payment;
use Illuminate\Http\Request;

/** lens/include/payment/* (admin only). */
class PaymentController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_payments'] || $this->level() !== self::ACCESS_ADMIN) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $links = [['Stats', self::url('payment')], ['Search', self::url('payment', 'search')]];
        $base = ['links' => $links, 'type' => $type, 'id' => $id];

        if ($type === 'search') {
            $data = ['searched' => false];
            if ($request->input('subsearch')) {
                $citID = (int) $request->input('citID');
                $itemID = (int) $request->input('itemID');
                $stat = in_array($request->input('stat'), ['Pending', 'Completed'], true) ? $request->input('stat') : 'Pending';
                $sql = 'SELECT * FROM store_sales WHERE transtat = ?';
                $b = [$stat];
                if ($citID) {
                    $sql .= ' AND buyer = ?';
                    $b[] = $citID;
                }
                if ($itemID) {
                    $sql .= ' AND itemno = ?';
                    $b[] = $itemID;
                }
                $data = ['searched' => true, 'citID' => $citID, 'rows' => $db->rows($sql.' ORDER BY timestamp DESC', $b)];
            }

            return $this->lensPage('lens.payment.search', $base + $data, 'Payment tools', 'payment');
        }

        if ($type === 'view' && $id) {
            $sale = $db->row('SELECT store_sales.*, store_items.itemtitle FROM store_sales JOIN store_items ON store_sales.itemno = store_items.itemno WHERE saleID = ?', [(int) $id]);
            if ($sale && $sale['transtat'] === 'Pending' && $request->input('subfinish')) {
                $bank = in_array($request->input('txn_bank'), ['Paypal', 'Zarinpal', 'Sepehr', 'Siba', 'Other'], true) ? $request->input('txn_bank') : 'Paypal';
                app(Payment::class)->finishPurchase($sale['saleID'], (string) $request->input('buyermail'), (string) $request->input('txn_id'), $bank);

                return redirect($request->getRequestUri());
            }

            return $this->lensPage('lens.payment.view', $base + ['sale' => $sale], 'Payment tools', 'payment');
        }

        return $this->lensPage('lens.payment.home', $base + [
            'byCur' => $db->rows("SELECT currency, ROUND(SUM(price), 2) AS total FROM store_sales WHERE transtat = 'Completed' GROUP BY currency"),
            'byBank' => $db->rows("SELECT txn_bank, currency, ROUND(SUM(price), 2) AS total FROM store_sales WHERE transtat = 'Completed' GROUP BY txn_bank, currency"),
        ], 'Payment tools', 'payment');
    }
}
