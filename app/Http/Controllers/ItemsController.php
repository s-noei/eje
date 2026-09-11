<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of items.php, special-items.php and create.php (+ include/create/*).
 */
class ItemsController extends GameController
{
    public function items(Request $request)
    {
        if (! $this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $msg = null;
        if ($request->input('Use')) {
            if ((int) $request->input('Item') !== 1) {
                $msg = '<h3 class=errHandle>Something is wrong.</h3>';
            } else {
                $item = $this->database->row('SELECT * FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = 13 AND Stars = 5 LIMIT 1', [$citID]);
                if (! $item) {
                    $msg = '<h3 class=errHandle>You have no Gold Pack (30 Days) in your inventory.</h3>';
                } else {
                    $time = time();
                    $tGP = ($cit['gold_pack'] >= $time ? $cit['gold_pack'] : $time) + 2592000;
                    $this->database->exec('UPDATE inventory SET Usable = 0 WHERE pID = ?', [$item['pID']]);
                    $this->database->updateUserFieldID($citID, 'gold_pack', $tGP);
                    $this->database->updateUserFieldID($citID, 'gold_pack_q', $cit['gold_pack_q'] + 1);
                    $this->session->fillInfo(null, true);
                    $cit = $this->citInfo = $this->session->userinfo;
                    $msg = '<h3 class=infHandle>You have successfully activated Gold Pack (30 Days).</h3>';
                }
            }
        }
        $items = $this->database->rows("SELECT inventory.*, industry.Icon, industry.iName, COUNT(Type) Amount
            FROM (SELECT * FROM inventory WHERE Owner = ? AND Usable = 1 AND Type > 12) inventory
            JOIN industry ON industry.IndustryID = inventory.Type WHERE Owner = ? AND Usable = '1' GROUP BY Type, Stars ORDER BY Stars DESC, pID DESC", [$citID, $citID]);

        return $this->page('pages.items.items', ['msg' => $msg, 'items' => $items, 'goldpack' => $this->session->getDiffF($cit['gold_pack'])],
            ['title' => $this->lang->getstr('title_items', 'title'), 'bar_title' => 'Items', 'actiontype' => 'items']);
    }

    public function special(Request $request)
    {
        if (! $this->loggedIn() || $this->isCA()) {
            return redirect('/index.html');
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $msg = null;
        $catalog = [
            1 => ['name' => 'Health Kit', 'db' => 12, 'price' => 5, 'stars' => 5, 'req' => 1],
            2 => ['name' => 'Gold Pack', 'db' => 13, 'price' => 50, 'stars' => 5, 'req' => 0],
        ];
        $boosters = [
            3 => ['name' => 'Damage Booster (+20%)', 'price' => 2, 'q' => 1],
            4 => ['name' => 'Damage Booster (+50%)', 'price' => 5, 'q' => 2],
        ];
        if ($request->input('Buy')) {
            $it = $catalog[(int) $request->input('Item')] ?? null;
            $citMoney = $this->database->getCitizenMoney($citID, 1);
            if (! $it) {
                $msg = '<h3 class=errHandle>Something is wrong.</h3>';
            } elseif ($citMoney < $it['price']) {
                $msg = "<h3 class=errHandle>You cannot buy {$it['name']} because you do not have enough Tala.</h3>";
            } elseif ($this->database->getCitizenFreeIS($citID) < 1) {
                $msg = '<h3 class=errHandle>Your inventory is full.</h3>';
            } elseif ($it['req'] && $cit['gold_pack'] < time()) {
                $msg = '<h3 class=errHandle> Requirements: Gold Account.</h3>';
            } else {
                $this->database->updateUserMoney($citID, 1, $citMoney - $it['price']);
                $this->database->createProduct($it['db'], $it['stars'], $citID);
                $this->session->fillInfo(null, true);
                $msg = "<h3 class=infHandle>You have successfully purchased {$it['name']}.</h3>";
            }
        }
        if ($request->input('BuyUse')) {
            $it = $boosters[(int) $request->input('ItemUse')] ?? null;
            $citMoney = $this->database->getCitizenMoney($citID, 1);
            if (! $it) {
                $msg = '<h3 class=errHandle>Something is wrong.</h3>';
            } elseif ($citMoney < $it['price']) {
                $msg = "<h3 class=errHandle>You cannot buy {$it['name']} because you do not have enough Tala.</h3>";
            } elseif ($cit['gold_pack'] < time()) {
                $msg = '<h3 class=errHandle> Requirements: Gold Account.</h3>';
            } else {
                $this->database->updateUserMoney($citID, 1, $citMoney - $it['price']);
                $this->database->updateUserFieldID($citID, 'dmg_booster', time() + 1200);
                $this->database->updateUserFieldID($citID, 'dmg_booster_q', $it['q']);
                $this->session->fillInfo(null, true);
                $msg = "<h3 class=infHandle>You have successfully activated {$it['name']}.</h3>";
            }
        }

        return $this->page('pages.items.special', ['msg' => $msg], ['title' => $this->lang->getstr('title_special', 'title'), 'bar_title' => 'Special Items', 'actiontype' => 'special']);
    }

    /** create-{what}.html */
    public function create(Request $request, string $what)
    {
        if (! $this->loggedIn()) {
            return redirect('/index.html');
        }
        $cit = $this->citInfo;
        $citID = $this->citID();
        $sets = $this->database->setting + config('ejahan.prices');
        $msg = null;
        $layout = ['title' => $this->lang->getstr('title_create', 'title'), 'bar_title' => 'Create', 'actiontype' => 'create'];
        $nameOk = fn (string $n) => strlen(strip_tags($n)) >= 3 && preg_match('#^[a-zA-Z0-9\s]+$#', $n);
        $saveAvatar = function (?\Illuminate\Http\UploadedFile $f, string $dir, string $name): ?string {
            if (! $f) {
                return null;
            }
            if (in_array($f->getMimeType(), ['image/jpeg', 'image/pjpeg'], true) && $f->getSize() < 50 * 1024) {
                $f->move(public_path($dir), $name);

                return $name;
            }

            return 'bad';
        };

        switch ($what) {
            case 'company':
                if ($request->input('Create')) {
                    $cName = strip_tags((string) $request->input('cName', ''));
                    $iName = (int) $request->input('iName');
                    if (! $nameOk($cName)) {
                        $msg = '<h3 class=errHandle>Company name is incorrect.</h3>';
                    } elseif ($this->database->getCitizenMoney($citID, 1) < $sets['price_company']) {
                        $msg = '<h3 class=errHandle>You can not create a company, because you have not enough Tala.</h3>';
                    } elseif (! $this->isCA()) {
                        $msg = '<h3 class=errHandle>Creating companies is available with Co-Accounts, only.</h3>';
                    } elseif (! $this->database->count('SELECT IndustryID FROM industry WHERE IndustryID = ? AND IndustryID < 12', [$iName])) {
                        $msg = '<h3 class=errHandle>Choose Industry.</h3>';
                    } else {
                        $aID = $this->database->createCompany($cName, $iName, $citID, $cit['regionID']);
                        if ($aID) {
                            return redirect($this->vars->getURL('company', $aID));
                        }
                        $msg = 'There was an error...';
                    }
                }
                $acc = $this->accesses();

                return $this->page('pages.items.create-company', ['msg' => $msg, 'price' => $sets['price_company'], 'money' => round($this->database->getCitizenMoney($citID, 1), 2),
                    'industries' => $this->database->rows('SELECT * FROM industry WHERE IndustryID != 0 AND IndustryID < 12'.($acc['is_nca_war'] ? '' : " AND Hidden = '0'"))], $layout);

            case 'newspaper':
                if ($this->database->count('SELECT CitizenID FROM citizens WHERE npID != 0 AND CitizenID = ?', [$citID])) {
                    return redirect('/index.html');
                }
                if ($request->input('Create')) {
                    $title = strip_tags((string) $request->input('aTitle', ''));
                    if (! $nameOk($title)) {
                        $msg = '<h3 class=errHandle>The newspaper name is incorrect.</h3>';
                    } elseif ($this->database->getCitizenMoney($citID, 1) < $sets['price_np']) {
                        $msg = '<h3 class=errHandle>You can not create a newspaper, because you have not enough Tala.</h3>';
                    } else {
                        $aID = $this->database->createNP(mb_substr($title, 0, 25), $citID, $cit['regionID']);
                        if ($aID) {
                            $r = $saveAvatar($request->file('npAvatar'), 'uploads/avatars/newspaper', md5($aID.'NeWsPaPeR').'.jpg');
                            if ($r && $r !== 'bad') {
                                $this->database->updateNewspaperField($aID, 'Avatar', $r);
                            }
                            $this->session->fillInfo(null, true);

                            return redirect($this->vars->getURL('newspaper', $aID));
                        }
                        $msg = 'There was an error...';
                    }
                }

                return $this->page('pages.items.create-newspaper', ['msg' => $msg], $layout);

            case 'party':
                if ($this->database->count('SELECT ID FROM party_members WHERE CitizenID = ?', [$citID])) {
                    return redirect('/index.html');
                }
                if ($request->input('Create')) {
                    $pName = strip_tags((string) $request->input('pName', ''));
                    $eMap = ['Far-left' => 1, 'Center-left' => 2, 'Center' => 3, 'Center-right' => 4, 'Far-right' => 5];
                    $sMap = ['Totalitarian' => 1, 'Authoritarian' => 2, 'Libertarian' => 3, 'Anarchist' => 4];
                    $eOrient = $eMap[(string) $request->input('eOrient')] ?? 0;
                    $sOrient = $sMap[(string) $request->input('sOrient')] ?? 0;
                    if (! $nameOk($pName)) {
                        $msg = '<h3 class=errHandle>The party name is incorrect.</h3>';
                    } elseif (! $eOrient) {
                        $msg = '<h3 class=errHandle>Choose economical orientation.</h3>';
                    } elseif (! $sOrient) {
                        $msg = '<h3 class=errHandle>Choose social orientation.</h3>';
                    } elseif ($this->database->getCitizenMoney($citID, 1) < $sets['price_party']) {
                        $msg = '<h3 class=errHandle>You can not create a party, because you have not enough Tala.</h3>';
                    } else {
                        $aID = $this->database->createParty(mb_substr($pName, 0, 30), $eOrient, $sOrient, $citID, $cit['nationality']);
                        if ($aID) {
                            $r = $saveAvatar($request->file('partyLogo'), 'uploads/avatars/party', md5($aID.'PaRtY').'.jpg');
                            if ($r && $r !== 'bad') {
                                $this->database->updatePartyField($aID, 'pLogo', $r);
                            }
                            $this->session->fillInfo(null, true);

                            return redirect($this->vars->getURL('party', $aID));
                        }
                        $msg = 'There was an error...';
                    }
                }

                return $this->page('pages.items.create-party', ['msg' => $msg], $layout);

            case 'unit':
                if ($this->isCA()) {
                    return redirect('/index.html');
                }
                if ((int) $cit['military_unit'] > 0) {
                    return redirect($this->vars->getURL('military-unit', $cit['military_unit']));
                }
                if ($this->database->count('SELECT mID FROM military_unit WHERE mOwner = ?', [$citID])) {
                    return redirect('/index.html');
                }
                if ($request->input('Create')) {
                    $mName = strip_tags((string) $request->input('mName', ''));
                    if (strlen($mName) < 3 || strlen($mName) > 30 || ! preg_match('#^[a-zA-Z0-9\s]+$#', $mName)) {
                        $msg = '<h3 class=errHandle>A military unit name is incorrect.</h3>';
                    } elseif ($this->database->getCitizenMoney($citID, 1) < 50) {
                        $msg = '<h3 class=errHandle>You can not create a military unit, because you have not enough Tala.</h3>';
                    } elseif ($cit['gold_pack'] < time()) {
                        $msg = '<h3 class=errHandle>Requirements: Gold Account</h3>';
                    } elseif ((int) $cit['mRank'] < \App\Game\Support\Constants::RANK_MIN_UNIT_OWNER) {
                        $msg = '<h3 class=errHandle>Requirements: military rank '.\App\Game\Support\Constants::MILI_RANKS[\App\Game\Support\Constants::RANK_MIN_UNIT_OWNER].' or higher</h3>';
                    } else {
                        $mID = $this->database->createMilitaryUnit($mName, $citID, $cit['nationality']);
                        if ($mID) {
                            $r = $saveAvatar($request->file('mLogo'), 'uploads/avatars/military-unit', md5($mID.'MiLiTaRyUnIt').'.jpg');
                            if ($r && $r !== 'bad') {
                                $this->database->updateMilitaryUnitField($mID, 'mLogo', $r);
                            }
                            $this->session->fillInfo(null, true);

                            return redirect($this->vars->getURL('military-unit', $mID));
                        }
                        $msg = 'There was an error...';
                    }
                }

                return $this->page('pages.items.create-unit', ['msg' => $msg], $layout);
        }

        return redirect('/index.html');
    }
}
