<?php

namespace App\Game\Services;

/**
 * Port of include/payment.php ($pays): paid accounts and store purchases.
 */
class Payment
{
    public function __construct(protected GameDatabase $database)
    {
    }

    public function addWPLog(int|string $citID): void
    {
        $this->database->exec('INSERT INTO log_wbox (citID, day) VALUES (?, ?)', [$citID, $this->database->today]);
    }

    /** Extend a plus/pro account by $mon months (fractions allowed). */
    public function extendAcc(int|string $citID, string $type, float $mon): void
    {
        $cit = $this->database->getUserInfoFromID($citID, 0);
        if (! $cit) {
            return;
        }
        $time = time();
        $hPro = $cit['proExpire'] >= $time;
        $hPlus = $cit['plusExpire'] >= $time;
        $dur = (int) round($mon * 30 * 24 * 3600);
        if ($type === 'plus') {
            if ($hPro || $hPlus) {
                $last = max($cit['proExpire'], $cit['plusExpire']);
                $this->database->exec('UPDATE citizens SET plusExpire = ? WHERE CitizenID = ?', [$last + $dur, $citID]);
            } else {
                $this->database->exec('UPDATE citizens SET plusExpire = ? WHERE CitizenID = ?', [$time + $dur, $citID]);
            }
        } elseif ($type === 'pro') {
            $last = $hPro ? $cit['proExpire'] : $time;
            if ($hPlus) {
                $this->database->exec('UPDATE citizens SET plusExpire = ?, proExpire = ? WHERE CitizenID = ?', [$cit['plusExpire'] + $dur, $last + $dur, $citID]);
            } else {
                $this->database->exec('UPDATE citizens SET proExpire = ? WHERE CitizenID = ?', [$last + $dur, $citID]);
            }
        }
    }

    public function finishPurchase(int|string $purID, string $buyermail, string $txn_id = '', string $bank = 'Paypal'): int
    {
        $pur = $this->database->row('SELECT * FROM store_sales WHERE saleID = ?', [$purID]);
        if (! $pur) {
            return 0;
        }
        $row = $this->database->row('SELECT * FROM store_items WHERE itemno = ?', [$pur['itemno']]);
        $citID = $pur['buyer'];
        $this->database->sendNote($citID, '', "You received {$row['itemtitle']} because of a payment. Thanks for your purchase.");
        if ($row['itemtype'] === 'TALA') {
            $this->database->addMoney(1, $row['itemamount'], $citID, 'citizen', 1, '> Bought with RL money <');
        } elseif ($row['itemtype'] === 'ACC') {
            $acctype = (floor($row['itemamount'] / 10) == 1) ? 'plus' : 'pro';
            $accdur = $row['itemamount'] - (floor($row['itemamount'] / 10) * 10);
            $this->extendAcc($citID, $acctype, $accdur);
        }
        $this->database->exec("UPDATE store_sales SET transtat = 'Completed', buyermail = ?, txn_id = ?, txn_bank = ? WHERE saleID = ? LIMIT 1",
            [substr($buyermail, 0, 40), $txn_id, $bank, $purID]);

        return 1;
    }

    public function getBoughtWPNum(int|string $citID): int
    {
        return $this->database->count('SELECT logID FROM log_wbox WHERE citID = ? AND day = ?', [$citID, $this->database->today]);
    }
}
