<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of jobs.php, market.php (+ include/ajax/market.php), exchange.php (+ my),
 * imarket.php (+ index/my), cmarket.php.
 */
class MarketController extends GameController
{
    private function hiddenGuard(int $counID)
    {
        if ($counID && $this->database->isHiddenCountry($counID) && ! $this->session->isAdmin()) {
            return redirect('/index.html');
        }

        return null;
    }

    /* ------------------------------------------------------------------ jobs */
    public function jobs(Request $request, $sID = null, $cID = null, $page = null)
    {
        if (! $this->loggedIn()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('jobs');
        $this->lang->addPhrases('filter');
        $cit = $this->citInfo;
        $page = max(1, (int) ($page ?: 1));
        $count = 10;
        $start = ($page - 1) * $count;
        $counID = ($cID !== null && $cID !== '') ? (int) $cID : (int) $cit['CountryID'];
        if ($r = $this->hiddenGuard($counID)) {
            return $r;
        }
        $quality = (int) $sID;
        $msg = null;
        if ($request->isMethod('post') && $request->has('actOffer')) {
            if (! $this->database->getWorkingCompany($this->citID()) && ! $this->database->isManager($this->citID())) {
                $actOff = (int) $request->input('actOffer');
                $comp = (int) $request->input('compID');
                $salary = (float) $request->input('Salary');
                $ttoken = md5($actOff.$comp.$this->citID().$request->input('Salary').'k3y4 j0b 0o0off3r$');
                $offer = $this->database->row('SELECT * FROM jobOffers WHERE joID = ? AND CompanyID = ? AND Salary = ?', [$actOff, $comp, $salary]);
                if (! hash_equals($ttoken, (string) $request->input('token')) || ! $offer) {
                    $msg = '<h3 class=errHandle>'.$this->lang->getstr('error_cheating', 'msgs').'</h3>';
                } elseif ((int) $offer['CountryID'] !== (int) $cit['CountryID'] || $this->isCA()) {
                    $msg = '<h3 class=errHandle>'.$this->lang->getstr('error_cheating', 'msgs').'</h3>';
                } else {
                    $this->database->addWorker($this->citID(), $comp, $salary, $cit['CountryID']);
                    $am = (int) $offer['Amount'] - 1;
                    if ($am <= 0) {
                        $this->database->exec('DELETE FROM jobOffers WHERE joID = ?', [$actOff]);
                    } else {
                        $this->database->exec('UPDATE jobOffers SET Amount = ? WHERE joID = ?', [$am, $actOff]);
                    }
                    $this->session->fillInfo(null, true);

                    return redirect($this->vars->getURL('company', $comp));
                }
            } else {
                $msg = '<h3 class="errHandle">'.$this->lang->getstr('error_is_worker', 'msgs').'</h3>';
            }
        }
        $puberty = $cit['accType'] === 'citizen' ? (int) $cit['puberty'] : -1;
        $offs = $this->database->getJobOffers($counID, $quality, $puberty, $start, $count);
        $rows = [];
        foreach ($offs as $rec) {
            $recs = $this->database->getCompany($rec['CompanyID']);
            if (! $recs || $rec['Amount'] == 0) {
                continue;
            }
            if (! $this->isCA() && (int) $cit['CountryID'] === (int) $recs['CountryID']) {
                $prod1 = $this->eco()->getProduct4Work($cit, $recs, 1);
                $prod2 = $this->eco()->getProduct4Work($cit, $recs, 3);
                $salary = round(max(1, $prod1) * $rec['Salary'], 2).' ~ '.round(max(1, $prod2) * $rec['Salary'], 2);
            } else {
                $salary = $rec['Salary'];
            }
            $rows[] = ['rec' => $rec, 'comp' => $recs, 'salaryText' => $salary];
        }
        $coun = $counID ? $this->database->getCountryRec($counID) : null;

        return $this->page('pages.market.jobs', [
            'msg' => $msg, 'counID' => $counID, 'quality' => $quality, 'page' => $page, 'start' => $start, 'count' => $count,
            'jo_count' => $this->database->getJobOffers($counID, $quality, $puberty, -1),
            'rows' => $rows, 'coun' => $coun,
            'countries' => $this->database->getCountries($this->session->isAdmin() ? 1 : 0),
            'canApply' => (int) $cit['CountryID'] === $counID && ! $this->database->isManager($this->citID()) && ! $this->isCA(),
        ], ['title' => $this->lang->getstr('title_jobs', 'title'), 'bar_title' => $this->lang->getstr('jobs_title', 'jobs'), 'actiontype' => 'jobs']);
    }

    /* ---------------------------------------------------------------- market */
    public function market(Request $request, $iID = null, $sID = null, $cID = null)
    {
        $this->lang->addPhrases('market');
        $this->lang->addPhrases('filter');
        $cit = $this->citInfo;
        $indID = (int) $iID;
        $starID = (int) $sID;
        $counID = ($cID !== null && $cID !== '') ? (int) $cID : ($this->loggedIn() ? (int) $cit['CountryID'] : 0);
        if ($r = $this->hiddenGuard($counID)) {
            return $r;
        }
        $cpID = $counID ? $this->database->value('SELECT cpID FROM country WHERE CountryID = ?', [$counID]) : null;
        $isCP = $this->loggedIn() && $cpID && (int) $cpID === $this->citID();
        $offers = [];
        if ($indID) {
            foreach ($this->database->getMarketOffers($indID, $starID, $counID) as $rec) {
                $recs = $this->database->getCompany($rec['CompanyID']);
                if ($recs && $rec['Stock'] >= 0) {
                    $offers[] = ['rec' => $rec, 'comp' => $recs];
                }
            }
        }
        $coun = $counID ? $this->database->getCountryRec($counID) : null;

        return $this->page('pages.market.market', [
            'indID' => $indID, 'starID' => $starID, 'counID' => $counID, 'coun' => $coun, 'isCP' => $isCP, 'offers' => $offers,
            'industries' => $this->database->rows("SELECT * FROM industry WHERE Hidden = '0' ORDER BY IndustryID"),
            'countries' => $this->database->getCountries($this->session->isAdmin() ? 1 : 0),
            'money' => $this->loggedIn() ? round($this->database->getCitizenMoney($this->citID(), $cit['CountryID']), 2) : 0,
            'free' => $this->loggedIn() ? $this->database->getCitizenFreeIS($this->citID()) : 0,
        ], ['title' => $this->lang->getstr('title_market', 'title'), 'bar_title' => $this->lang->getstr('market_title', 'market'), 'actiontype' => 'market']);
    }

    /** ajax-market-view-{i}-{q}-{c}.html */
    public function marketView(int $iID, int $qID, int $cID)
    {
        $rows = $this->database->getMarketOffers($iID, $qID, $cID);

        return response()->json(['result' => $rows ? 1 : 0, 'data' => $rows ?: null]);
    }

    /** ajax-market-buy.html (POST) */
    public function marketBuy(Request $request)
    {
        if (! $this->loggedIn()) {
            return response()->json(['result' => 'cheat']);
        }
        $cit = $this->database->getUserInfoFromID($this->citID());
        $amount = (string) $request->input('amount', '');
        $actOffer = (int) $request->input('actOffer');
        $offer = $this->database->getMarketOffer($actOffer);
        $cID = $this->database->getCurrencyID($cit['CountryID']);
        $citMoney = $this->database->getCitizenMoney($cit['CitizenID'], $cID);
        $industry = (int) $request->input('Industry');
        $stars = (int) $request->input('Stars');
        $ttok = md5($actOffer.'key44 M@rl<eT'.$stars.'key44 M@rl<eT'.$industry);
        if (! $offer || ! hash_equals($ttok, (string) $request->input('token')) || (int) $offer['Quality'] !== $stars) {
            return response()->json(['result' => 'cheat']);
        }
        $compRow = $this->database->getCompany($offer['CompanyID']);
        if (! $compRow || (int) $compRow['IndustryID'] !== $industry || in_array($industry, [9, 10], true) || (int) $offer['CountryID'] !== (int) $cit['CountryID']) {
            return response()->json(['result' => 'cheat']);
        }
        $price = (float) $amount * (float) $offer['Price'];
        $cCoun = $offer['cCountryID'];
        $mCoun = $offer['CountryID'];
        $tPrice = $this->database->addTax2Price($industry, $cCoun, $mCoun, $price);
        $tax = $this->database->getIndustryTax($cCoun, $industry, 1);
        $tVAT = $tax ? (float) $tax['VAT'] : 0;
        $tImport = 0;
        if ((int) $cCoun !== (int) $mCoun) {
            $tax = $this->database->getIndustryTax($mCoun, $industry, 1);
            $tImport = $tax ? (float) $tax['Import'] : 0;
        }
        $hTax = $price * ($tVAT / 100);
        $dTax = ($price + $hTax) * ($tImport / 100);
        $amountI = (int) $amount;
        if (! preg_match('/^[0-9]+$/', $amount) || $amountI <= 0) {
            $result = 'invalid';
        } elseif ($offer['Stock'] < $amountI) {
            $result = 'notenough';
        } elseif ($this->database->getCitizenFreeIS($cit['CitizenID']) < $amountI) {
            $result = 'full';
        } elseif ($citMoney < $tPrice) {
            $result = 'lowmoney';
        } else {
            $this->database->addSold($offer['CompanyID'], $amountI, $mCoun, (float) $offer['tPrice']);
            $this->database->setCompanyOffer($actOffer, $offer['CompanyID'], $stars, $offer['CountryID'], $offer['Stock'] - $amountI, $offer['Price'], 1);
            $this->database->transferMoney($cID, $price, $cit['CitizenID'], 'citizen', $offer['CompanyID'], 'company', 1, '> Bought from Market <');
            $this->database->transferMoney($cID, $hTax, $cit['CitizenID'], 'citizen', $offer['CountryID'], 'country', 1, '> Market VAT Tax <');
            $this->database->transferMoney($cID, $dTax, $cit['CitizenID'], 'citizen', $offer['CountryID'], 'country', 1, '> Market Import Tax <');
            for ($i = 0; $i < $amountI; $i++) {
                $this->database->createProduct($industry, $stars, $cit['CitizenID']);
            }
            $this->session->fillInfo(null, true);

            return response()->json(['result' => 'done', 'data' => [
                'amount' => $amountI, 'price' => $offer['tPrice'] * $amountI, 'cur' => $this->database->getCurrency($cID), 'offerid' => $actOffer,
                'remain' => $offer['Stock'] - $amountI, 'money' => round($citMoney - $tPrice, 2), 'free' => $this->database->getCitizenFreeIS($cit['CitizenID']),
            ]]);
        }

        return response()->json(['result' => $result]);
    }

    /* -------------------------------------------------------------- exchange */
    public function exchange(Request $request, $sell = null, $buy = null, $acc = null, ?string $view = null)
    {
        if (! $this->loggedIn()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('monetary');
        $this->lang->addPhrases('filter');
        $cit = $this->citInfo;
        $citID = $this->citID();
        $layout = ['title' => $this->lang->getstr('title_exchange', 'title'), 'bar_title' => $this->lang->getstr('monetary_bartitle', 'monetary'), 'actiontype' => 'exchange'];

        // account selection (citizen or one of my companies — CA only)
        $accID = $citID;
        $accType = 'citizen';
        if ($acc) {
            $exurl = $view === 'mine' ? $this->vars->getURL('exchange', 'my') : $this->vars->getURL('exchange', $sell, $buy);
            if (! $this->isCA() || ! $this->database->count('SELECT ManagerID FROM company WHERE ManagerID = ? AND CompanyID = ?', [$citID, (int) $acc])) {
                return redirect($exurl);
            }
            $accID = (int) $acc;
            $accType = 'company';
        }
        $accInfo = $accType === 'citizen'
            ? ['name' => $cit['name'], 'avatar' => $this->vars->getImgLoc('CitizenAvatar').$cit['Avatar']]
            : (function () use ($accID) {
                $c = $this->database->row('SELECT Name, Avatar FROM company WHERE CompanyID = ?', [$accID]);

                return ['name' => $c['Name'] ?? '', 'avatar' => $this->vars->getImgLoc('CompanyAvatar').($c['Avatar'] ?? '')];
            })();
        $myCompanies = $this->isCA() ? $this->database->rows('SELECT Avatar, Name, CompanyID FROM company WHERE ManagerID = ?', [$citID]) : [];

        if ($view === 'mine') {
            if ($request->input('delOffer')) {
                $oID = (int) $request->input('delOffer');
                $ttoken = md5($oID.$request->input('accID').$request->input('accType').'k3y4 d3l3+3 0ff3r');
                $own = $this->database->count('SELECT mOfferID FROM monetary WHERE mOfferID = ? AND sellerID = ? AND sellerType = ?', [$oID, $accID, $accType]);
                if (! hash_equals($ttoken, (string) $request->input('token')) || ! $own) {
                    return redirect('/index.html');
                }
                $this->database->deleteMOffer($oID);

                return redirect($request->getRequestUri());
            }

            return $this->page('pages.market.exchange-my', [
                'accID' => $accID, 'accType' => $accType, 'accInfo' => $accInfo, 'myCompanies' => $myCompanies,
                'offs' => $this->database->getExchangeOffers('my', $accID, $accType),
            ], $layout);
        }

        $msg = null;
        $e = fn ($k) => '<h3 class="errHandle">'.$this->lang->getstr($k, 'msgs').'</h3>';
        if ($request->input('subaddoffer')) {
            $sellerID = (int) $request->input('accID');
            $sellerType = (string) $request->input('accType');
            $sCurID = (int) $request->input('sCur');
            $bCurID = (int) $request->input('bCur');
            $amount = (float) $request->input('amount');
            $eRate = (float) $request->input('rate');
            $ttoken = md5($citID.$sCurID.$bCurID.$sellerID.$sellerType.'add 0ff3r k3y');
            $haveMoney = $sellerType === 'citizen' ? $this->database->getCitizenMoney($citID, $sCurID) : $this->database->getCompanyMoney($sellerID, $sCurID);
            if (! hash_equals($ttoken, (string) $request->input('token')) || $sellerID !== $accID || $sellerType !== $accType) {
                $msg = $e('error_cheating');
            } elseif ($amount <= 0) {
                $msg = $e('error_invalid_amount');
            } elseif ($eRate <= 0) {
                $msg = $e('error_invalid_rate');
            } elseif ($haveMoney < $amount) {
                $msg = $e('error_not_enough_money');
            } else {
                $this->database->setExchangeOffer('', $amount, $eRate, $sellerID, $sellerType, $sCurID, $bCurID);
                $this->database->addMoney($sCurID, -$amount, $sellerID, $sellerType);

                return redirect($request->getRequestUri());
            }
        }
        if ($request->has('actOffer')) {
            $actOffer = (int) $request->input('actOffer');
            $offer = $this->database->getExchangeOffer($actOffer);
            $buyerAccount = (int) $request->input('accID');
            $buyerType = (string) $request->input('accType');
            $amount = (string) $request->input('amount');
            $ttoken = md5($actOffer.$buyerAccount.$buyerType.'A key for monetary markett...!!');
            if (! $offer || ! hash_equals($ttoken, (string) $request->input('token')) || $buyerAccount !== $accID || $buyerType !== $accType) {
                $msg = $e('error_cheating');
            } else {
                $buyerGetCur = $offer['sCurID'];
                $sellerGetCur = $offer['bCurID'];
                $buyerMoney = $buyerType === 'citizen' ? $this->database->getCitizenMoney($buyerAccount, $sellerGetCur) : $this->database->getCompanyMoney($buyerAccount, $sellerGetCur);
                $rate = (float) $offer['eRate'];
                $price = (float) $amount * $rate;
                if (! preg_match('/^[0-9.]+$/', $amount) || (float) $amount <= 0) {
                    $msg = $e('error_invalid_amount');
                } elseif ($offer['Amount'] < (float) $amount) {
                    $msg = $e('error_amount_high');
                } elseif ($buyerMoney < $price) {
                    $msg = $e('error_not_enough_money');
                } else {
                    $this->database->setExchangeOffer($actOffer, $offer['Amount'] - (float) $amount, $rate);
                    $this->database->addMoney($buyerGetCur, (float) $amount, $buyerAccount, $buyerType);
                    $this->database->transferMoney($sellerGetCur, $price, $buyerAccount, $buyerType, $offer['sellerID'], $offer['sellerType']);
                    $tarID = $offer['sellerType'] === 'citizen' ? $offer['sellerID'] : $this->database->getManagerID($offer['sellerID']);
                    $addon = $offer['sellerType'] === 'citizen' ? '' : 'from your company ';
                    $this->database->sendNote($tarID, '', "We inform you that an amount of {$amount} ".$this->database->getCurrency($buyerGetCur).' '.$addon."for {$price} ".$this->database->getCurrency($sellerGetCur).' has been sold to another account using the monetary market.');

                    return redirect($request->getRequestUri());
                }
            }
        }

        $sellID = ($sell !== null && $sell !== '') ? (int) $sell : $this->database->getCurrencyID($cit['CountryID']);
        $buyID = ($buy !== null && $buy !== '') ? (int) $buy : 1;
        if ($sellID === 1 && ! $this->isCA() && ! $cit['active']) {
            return redirect($this->vars->getURL('exchange'));
        }
        if ($buyID === $sellID) {
            $sellID = $buyID === 1 ? (int) $cit['CountryID'] : 1;
        }
        $accMoney = $accType === 'citizen' ? $this->database->getCitizenMoney($accID) : $this->database->getCompanyMoney($accID);
        $offs = $this->database->getExchangeOffers($sellID, $buyID);
        $rows = [];
        foreach ($offs as $rec) {
            if ($rec['Amount'] == 0) {
                continue;
            }
            $name = $rec['sellerType'] === 'citizen'
                ? $this->database->value('SELECT name FROM citizens WHERE CitizenID = ?', [$rec['sellerID']])
                : $this->database->value('SELECT Name FROM company WHERE CompanyID = ?', [$rec['sellerID']]);
            $rows[] = $rec + ['providerName' => $name];
        }

        return $this->page('pages.market.exchange', [
            'msg' => $msg, 'sellID' => $sellID, 'buyID' => $buyID, 'accID' => $accID, 'accType' => $accType, 'accInfo' => $accInfo, 'myCompanies' => $myCompanies,
            'sellHave' => $accMoney[$sellID] ?? 0, 'buyHave' => $accMoney[$buyID] ?? 0, 'rows' => $rows,
            'myCurrencies' => $this->database->rows('SELECT country.* FROM citizen_money JOIN country ON citizen_money.CurID = country.CountryID WHERE citizen_money.CitID = ?', [$citID]),
            'allCurrencies' => $this->database->getCountries(1, 'curName'),
        ], $layout);
    }

    /* --------------------------------------------------------------- imarket */
    public function imarket(Request $request, $iID = null, $sID = null, ?string $go = null)
    {
        if (! $this->loggedIn() || ! $this->citInfo['active']) {
            return redirect('/index.html');
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $indID = (int) $iID;
        $starID = (int) $sID;
        if ($indID > 9 && $indID !== 12) {
            return redirect('/index.html');
        }
        $msg = null;
        $layout = ['title' => $this->lang->getstr('title_imarket', 'title'), 'bar_title' => 'International Market', 'actiontype' => 'imarket'];
        if ($request->input('subadd')) {
            $invID = (int) $request->input('invID');
            $price = (string) $request->input('price');
            $inv = $this->database->row("SELECT * FROM inventory WHERE Owner = ? AND pID = ? AND Usable = '1'", [$citID, $invID]);
            $used = (int) $this->database->value('SELECT COUNT(*) AS Total FROM imarket WHERE SellerID = ?', [$citID], 0);
            if (! $inv) {
                $msg = '<h3 class="errHandle">Cheating?!</h3>';
            } elseif ($this->isCA()) {
                $msg = '<h3 class="errHandle">Co-Accounts cannot put offers in IMarket</h3>';
            } elseif ($used >= 5) {
                $msg = '<h3 class="errHandle">You cannot put more than 5 offers in IMarket</h3>';
            } elseif (! is_numeric($price) || (float) $price < 0.01 || (float) $price > 999) {
                $msg = '<h3 class="errHandle">Please enter a valid price</h3>';
            } else {
                $this->database->exec("UPDATE inventory SET Usable = '2' WHERE pID = ?", [$invID]);
                $this->database->exec('INSERT INTO imarket (OfferID, SellerID, Type, Quality, Price, endtime) VALUES (?, ?, ?, ?, ?, ?)',
                    [$invID, $citID, $inv['Type'], $inv['Stars'], (float) $price, time() + 7 * 24 * 3600]);
                $this->session->fillInfo(null, true);

                return redirect($request->getRequestUri());
            }
        }
        if ($request->has('actOffer') && ! $request->has('remOffer')) {
            $actOffer = (int) $request->input('actOffer');
            $offer = $this->database->row('SELECT * FROM imarket WHERE OfferID = ?', [$actOffer]);
            $citMoney = $this->database->getCitizenMoney($citID, 1);
            $price = (float) ($offer['Price'] ?? 0);
            $ttok = md5($actOffer.'key44 IM@rl<eT'.($offer['Price'] ?? ''));
            if (! $offer || ! hash_equals($ttok, (string) $request->input('token')) || (int) $offer['SellerID'] === $citID) {
                $msg = '<h3 class=errHandle>Cheating detected!</h3>';
            } elseif ($this->database->getCitizenFreeIS($citID) == 0) {
                $msg = '<h3 class=errHandle>You have not enough free inventory slots...</h3>';
            } elseif ($citMoney < $price) {
                $msg = '<h3 class=errHandle>You have not enough money in your account...</h3>';
            } else {
                $this->database->exec('DELETE FROM imarket WHERE OfferID = ?', [$actOffer]);
                $this->database->transferMoney(1, $price, $citID, 'citizen', $offer['SellerID'], 'citizen');
                $this->database->exec("UPDATE inventory SET Usable = '1', Owner = ? WHERE pID = ?", [$citID, $actOffer]);
                $this->database->sendNote($offer['SellerID'], '', 'One of your offers in international market was sold to another citizen.');
                $this->session->fillInfo(null, true);

                return redirect($request->getRequestUri());
            }
        }
        if ($go === 'my' && $request->input('remOffer')) {
            $actOffer = (int) $request->input('actID');
            if (! $this->database->count('SELECT OfferID FROM imarket WHERE OfferID = ? AND SellerID = ?', [$actOffer, $citID])) {
                $msg = '<h3 class="errHandle">Cheating?!</h3>';
            } elseif (! $this->database->getCitizenFreeIS($citID)) {
                $msg = '<h3 class="errHandle">You have no free place in your inventory</h3>';
            } else {
                $this->database->exec("UPDATE inventory SET Usable = '1' WHERE pID = ? AND Owner = ?", [$actOffer, $citID]);
                $this->database->exec('DELETE FROM imarket WHERE OfferID = ?', [$actOffer]);
                $this->session->fillInfo(null, true);
            }
        }
        $data = [
            'msg' => $msg, 'indID' => $indID, 'starID' => $starID, 'go' => $go,
            'money' => round($this->database->getCitizenMoney($citID, 1), 2), 'free' => $this->database->getCitizenFreeIS($citID),
            'used' => (int) $this->database->value('SELECT COUNT(*) AS Total FROM imarket WHERE SellerID = ?', [$citID], 0),
            'industries' => $this->database->rows("SELECT * FROM industry WHERE (Hidden = '0' AND IndustryID < 9) OR IndustryID = 12 ORDER BY IndustryID"),
        ];
        if ($go === 'my') {
            $data['offs'] = $this->database->rows('SELECT imarket.*, industry.iName, citizens.name FROM imarket JOIN citizens ON imarket.SellerID = citizens.CitizenID
                JOIN industry ON industry.IndustryID = imarket.Type WHERE SellerID = ? ORDER BY Type, Quality, Price', [$citID]);
        } else {
            $data['myInventory'] = $this->isCA() ? [] : $this->database->rows("SELECT inventory.*, industry.iName FROM inventory JOIN industry ON inventory.Type = industry.IndustryID
                WHERE Owner = ? AND Usable = '1' GROUP BY Type, Stars ORDER BY Type, Stars", [$citID]);
            $offs = [];
            if ($indID) {
                $sql = 'SELECT imarket.*, citizens.name FROM imarket JOIN citizens ON imarket.SellerID = citizens.CitizenID WHERE Type = ? AND SellerID != ?';
                $b = [$indID, $citID];
                if ($starID) {
                    $sql .= ' AND Quality = ?';
                    $b[] = $starID;
                }
                $offs = $this->database->rows($sql.' ORDER BY Price', $b);
            }
            $data['offs'] = $offs;
        }

        return $this->page('pages.market.imarket', $data, $layout);
    }

    /* --------------------------------------------------------------- cmarket */
    public function cmarket(Request $request, $iID = null, $cID = null)
    {
        $cit = $this->citInfo;
        $indID = (int) $iID;
        $counID = ($cID !== null && $cID !== '') ? (int) $cID : ($this->loggedIn() ? (int) $cit['CountryID'] : 0);
        if ($r = $this->hiddenGuard($counID)) {
            return $r;
        }
        $msg = null;
        if ($this->loggedIn() && $request->has('actOffer') && $request->input('subbuy')) {
            $newCit = $this->citID();
            $actOffer = (int) $request->input('actOffer');
            $offer = $this->database->row('SELECT * FROM company WHERE CompanyID = ?', [$actOffer]);
            if ($offer) {
                $price = $offer['sale_bid_amount'] ? ($offer['sale_bid_amount'] + $offer['sale_step']) : $offer['sale_base'];
                $oldCit = (int) $offer['sale_bid_id'];
                $citMoney = $this->database->getCitizenMoney($newCit, 1);
                $toket = md5($offer['sale_base'].$actOffer.$offer['IndustryID'].'k3y44 l3uy c0mp@ny');
                if (! hash_equals($toket, (string) $request->input('token')) || $offer['sale_due'] < time()) {
                    $msg = '<h3 class=errHandle>Cheating?!</h3>';
                } elseif ($oldCit === $newCit) {
                    $msg = '<h3 class=errHandle>You cannot place a bid on your last bid</h3>';
                } elseif (! $this->isCA()) {
                    $msg = '<h3 class=errHandle>Only co-accounts can buy companies</h3>';
                } elseif ((int) $offer['ManagerID'] === $newCit) {
                    $msg = '<h3 class=errHandle>You cannot bid on your own company</h3>';
                } elseif ($citMoney < $price) {
                    $msg = '<h3 class=errHandle>You have not enough money in your account...</h3>';
                } else {
                    if ($oldCit) {
                        $this->database->addMoney(1, $price - $offer['sale_step'], $oldCit);
                    }
                    $this->database->addMoney(1, -$price, $newCit);
                    $this->database->updateCompanyField($actOffer, 'sale_bid_id', $newCit);
                    $this->database->updateCompanyField($actOffer, 'sale_bid_amount', $price);

                    return redirect($request->getRequestUri());
                }
            }
        }
        $rows = [];
        if ($indID) {
            foreach ($this->eco()->getCMarketOffers($indID, $counID) as $rec) {
                $recs = $this->database->getCompany($rec['CompanyID']);
                if ($recs && $rec['Price'] != 0 && (int) $rec['CountryID'] === $counID) {
                    $rows[] = ['rec' => $rec, 'comp' => $recs];
                }
            }
        }

        return $this->page('pages.market.cmarket', [
            'msg' => $msg, 'indID' => $indID, 'counID' => $counID, 'coun' => $counID ? $this->database->getCountryRec($counID) : null, 'rows' => $rows,
            'industries' => $this->database->rows("SELECT * FROM industry WHERE Hidden = '0' ORDER BY IndustryID"),
            'countries' => $this->database->getCountries($this->session->isAdmin() ? 1 : 0),
            'money' => $this->loggedIn() ? round($this->database->getCitizenMoney($this->citID(), 1), 2) : 0,
        ], ['title' => $this->lang->getstr('title_company_market', 'title'), 'bar_title' => 'Company Market', 'actiontype' => 'cmarket']);
    }
}
