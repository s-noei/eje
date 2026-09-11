<?php

namespace App\Http\Controllers;

use App\Game\Support\Constants;
use Illuminate\Http\Request;

/**
 * Port of company.php, companyinfo.php and include/company/*.
 */
class CompanyController extends GameController
{
    private const QUALITY_VALUES = [0, 20, 40, 90, 200, 430];

    /** company.html — my companies / my workplace. */
    public function index()
    {
        if (! $this->loggedIn()) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('company');
        $citID = $this->citID();
        $layout = ['title' => $this->lang->getstr('title_company', 'title'), 'bar_title' => 'Company', 'actiontype' => 'company'];
        if (! $this->isCA()) {
            if ($this->database->isManager($citID)) {
                return $this->page('pages.company.list', ['companies' => $this->database->rows('SELECT * FROM company WHERE ManagerID = ?', [$citID]), 'mode' => 'manager'], $layout);
            }
            $compID = $this->database->getWorkingCompany($citID);
            if ($compID > 0) {
                return redirect($this->vars->getURL('company', $compID));
            }

            return $this->page('pages.company.list', ['companies' => [], 'mode' => 'none'], $layout);
        }

        return $this->page('pages.company.list', ['companies' => $this->database->rows('SELECT * FROM company WHERE ManagerID = ?', [$citID]), 'mode' => 'ca'], $layout);
    }

    /** company-{id}[-{go}]-{lang}.html */
    public function show(Request $request, int $id, ?string $go = null)
    {
        $this->lang->addPhrases('company');
        $row = $this->database->row('SELECT company.*, citizens.name AS ManagerName, industry.Unit, industry.xFactor, industry.iName, region.rName AS regionName, region.stat_cfactor, country.*, company.CompanyID AS CompanyID
            FROM company LEFT JOIN citizens ON citizens.CitizenID = company.ManagerID
            JOIN region ON region.RegionID = company.RegionID
            JOIN country ON region.CountryID = country.CountryID
            JOIN industry ON industry.IndustryID = company.IndustryID
            WHERE company.CompanyID = ? LIMIT 1', [$id]);
        if (! $row) {
            return redirect('/index.html');
        }
        $acc = $this->accesses();
        $citID = $this->citID();
        $isManager = $this->loggedIn() && ((int) $row['ManagerID'] === $citID || $this->session->isAdmin());
        $layout = ['title' => sprintf($this->lang->getstr('title_company_info', 'title'), $row['Name']), 'bar_title' => $this->lang->getstr('company_bartitle', 'company'), 'ambient' => 'company', 'actiontype' => 'companyinfo'];

        if ($request->has('Resign') && $this->loggedIn() && ! ($isManager && ! $acc['can_view_private_data']) && $this->database->isWorker($citID, $row['CompanyID'])) {
            $this->database->resignWorker($citID);
            $this->session->fillInfo(null, true);

            return redirect($request->getRequestUri());
        }
        if ($acc['can_view_private_data'] && $request->input('suspend')) {
            $this->database->exec("UPDATE company_workers SET CompanyID = '-1' WHERE CompanyID = ?", [$id]);
            $this->database->exec('DELETE FROM company_market WHERE CompanyID = ?', [$id]);
            $this->database->exec("UPDATE company_money SET Amount = '0' WHERE CompID = ?", [$id]);
            $this->database->exec("UPDATE company SET Stock = '0', Products = '0', ManagerID = '0', company_message = 'Suspended company' WHERE CompanyID = ?", [$id]);
            $this->database->exec('DELETE FROM jobOffers WHERE CompanyID = ?', [$id]);
            $this->database->exec('DELETE FROM market WHERE CompanyID = ?', [$id]);

            return redirect($request->getRequestUri());
        }

        $common = [
            'row' => $row, 'req_id' => $id, 'isManager' => $isManager,
            'cName' => $row['cName'], 'cIMG' => $vars = $this->vars->getImgLoc('CountryFlag').$row['Flag'].'.gif', 'cID' => $row['CountryID'],
            'aIMG' => $this->vars->getImgLoc('CompanyAvatar').$row['Avatar'],
            'myCompanies' => $isManager ? $this->database->rows('SELECT Avatar, CompanyID, Name FROM company WHERE ManagerID = ?', [$row['ManagerID']]) : [],
        ];
        $go = $go ?: 'index';
        $sub = match ($go) {
            'details' => $this->details($request, $row, $isManager, $acc, true),
            'migrate' => (int) $row['IndustryID'] === 2 ? $this->migrate($request, $row, $isManager) : redirect($this->vars->getURL('company', $id)),
            'edit' => $this->edit($request, $row, $isManager),
            'finance' => $this->finance($request, $row),
            'license' => $this->license($request, $row, $isManager),
            'quality' => $this->quality($request, $row, $isManager),
            'sell' => $this->sell($request, $row, $isManager),
            'status' => $this->status($request, $row),
            'workers' => $this->workers($request, $row, $acc),
            'workplace' => $this->workplace($request, $row),
            default => $this->details($request, $row, $isManager, $acc, false),
        };
        if ($sub instanceof \Symfony\Component\HttpFoundation\Response) {
            return $sub;
        }
        if (isset($sub['redirect'])) {
            return redirect($sub['redirect']);
        }

        return $this->page('pages.company.show', $common + $sub, $layout);
    }

    private function details(Request $request, array $row, bool $isManager, array $acc, bool $nored): array|\Symfony\Component\HttpFoundation\Response
    {
        $citID = $this->citID();
        $id = $row['CompanyID'];
        $msg = null;
        if ($isManager && $request->isMethod('post')) {
            if ($request->has('actOffer')) {
                $ttokn = md5($request->input('actOffer').$id.$request->input('actCountry').'key4 offer making');
                if (! hash_equals($ttokn, (string) $request->input('token'))) {
                    $msg = '<h3 class="errHandle">'.$this->lang->getstr('error_cheating', 'msgs').'</h3>';
                } else {
                    $result = $this->database->setCompanyOffer($request->input('actOffer'), $id, $row['Stars'], (int) $request->input('actCountry'), (int) $request->input('oAmount'), (float) $request->input('oPrice'));
                    if ($result === 1) {
                        return redirect($request->getRequestUri());
                    }
                    $msg = '<h3 class="errHandle">'.($result === 0 ? $this->lang->getstr('error_unknown', 'msgs') : e($result)).'</h3>';
                }
            }
            if ($request->input('delJoID') && $acc['can_view_private_data']) {
                $this->database->exec('DELETE FROM jobOffers WHERE CompanyID = ?', [$id]);
            }
            if ($request->input('actJoID')) {
                $r = $this->database->row('SELECT company.* FROM jobOffers JOIN company ON jobOffers.CompanyID = company.CompanyID WHERE jobOffers.joID = ?', [(int) $request->input('actJoID')]);
                if ($r && (((int) $r['CompanyID'] === (int) $id && (int) $r['ManagerID'] === $citID) || $acc['can_view_private_data'])) {
                    $this->database->exec('DELETE FROM jobOffers WHERE joID = ?', [(int) $request->input('actJoID')]);
                }
            }
            if ($request->input('inserttool') && ! $row['tool_a']) {
                $tr = $this->database->row("SELECT * FROM inventory WHERE Owner = ? AND Usable = '1' AND Type = 7 AND Stars = ? LIMIT 1", [$citID, (int) $request->input('toolq')]);
                if ($tr) {
                    $this->database->updateCompanyField($id, 'tool_a', $tr['Stars']);
                    $this->database->exec("UPDATE inventory SET Usable = '0' WHERE pID = ?", [$tr['pID']]);
                }

                return redirect($request->getRequestUri());
            }
            if ($request->input('installtool') && $row['tool_a']) {
                $this->database->updateCompanyField($id, 'tool_q', $row['tool_a']);
                $this->database->updateCompanyField($id, 'tool_e', 100);
                $this->database->updateCompanyField($id, 'tool_a', 0);

                return redirect($request->getRequestUri());
            }
            if ($request->has('SaveOffer')) {
                $wSalary = (float) $request->input('wSalary');
                $counID = $this->database->getCountryID($row['RegionID']);
                if ($wSalary < 0.01) {
                    $msg = '<h3 class="errHandle">'.sprintf($this->lang->getstr('error_job_offer_incorrect', 'msgs'), '0.01').'</h3>';
                } elseif ($this->database->hasJOffer($id, $counID, $wSalary)) {
                    $msg = '<h3 class="errHandle">'.$this->lang->getstr('error_job_offer_duplicate', 'msgs').'</h3>';
                } elseif ($this->database->setJOffer($id, $counID, (int) $request->input('wAmount'), $wSalary)) {
                    $msg = '<h3 class="infHandle">'.$this->lang->getstr('success_job_offer_created', 'msgs').'</h3>';
                } else {
                    $msg = '<h3 class="errHandle">'.$this->lang->getstr('error_unknown', 'msgs').'</h3>';
                }
            }
        }
        $uInf = $this->loggedIn() ? $this->database->isWorker($citID, $id) : 0;
        if ($uInf && ! $nored && $this->citInfo['LastWorked'] != $this->database->getToday()) {
            return redirect($this->vars->getURL('company', $id, 'workplace'));
        }
        $workers = $this->database->getWorkers($id);
        $licenses = [];
        if ((int) $row['IndustryID'] !== 11) {
            foreach ($this->database->getLicenses($id, $row['CountryID']) as $lic) {
                $offs = $this->database->getCompanyOffers($id, $row['Stars'], $lic['CountryID']);
                $status = 0;
                if ($this->eco()->getEmbargoes($row['CountryID'], 'trade', $lic['CountryID'])) {
                    $status = 1;
                } elseif ($this->war()->haveWar($row['CountryID'], $lic['CountryID'])) {
                    $status = 2;
                }
                $lic['offs'] = $offs;
                $lic['status'] = $status;
                $lic['noOffer'] = ! $offs['Stock'];
                $lic['curName'] = $this->database->getCurrency($lic['CountryID']);
                $licenses[] = $lic;
            }
        }

        return [
            'sub' => 'index', 'msg' => $msg, 'nored' => $nored, 'uInf' => $uInf,
            'accounts' => $isManager ? $this->database->getCompanyAccounts($id) : [],
            'workers' => $workers, 'maxworkers' => $row['xFactor'] * ($row['Stars'] + 4),
            'indName' => $this->database->getIndustry($row['IndustryID']),
            'tools' => $isManager ? $this->database->rows("SELECT Stars FROM inventory WHERE Owner = ? AND Usable = '1' AND Type = 7 GROUP BY Stars ORDER BY Stars", [$citID]) : [],
            'licenses' => $licenses,
            'actJobs' => $this->database->getCompanyJobOffers($id, $this->database->getCountryID($row['RegionID'])),
            'localCur' => $this->database->getCurrency($this->database->getCountryID($row['RegionID'])),
        ];
    }

    private function workplace(Request $request, array $row): array|\Symfony\Component\HttpFoundation\Response
    {
        $this->lang->addPhrases('workplace');
        $cit = $this->citInfo;
        $citID = $this->citID();
        if (! $this->loggedIn() || ! $this->database->isWorker($citID, $row['CompanyID'])) {
            return redirect('/index.html');
        }
        $view_info = null;
        $nowWorked = 0;
        $report = null;
        $today = $this->database->getToday();

        if ($request->isMethod('post') && $request->has('work') && $cit['LastWorked'] < $today) {
            $wtype = (int) $request->input('work');
            if (! in_array($wtype, [Constants::WORK_SHIFT, Constants::WORK_STUDY], true)) {
                return redirect($request->getRequestUri());
            }
            $nwell = Constants::workWellnessCost((int) $row['Stars'], (int) ($cit['efficiency'] ?? 0), $wtype);
            $acc = $this->database->getCompanyAccount($row['CompanyID'], $cit['SalaryCurID']);
            $compAcc = $acc['Amount'] ?? 0;
            $prod = $this->eco()->getProduct4Work($cit, $row, $wtype);
            $salary = round($cit['Salary'] * ($prod >= 1 ? $prod : 1), 2);
            $ttokn = md5($citID.$row['CompanyID'].'key4 w0rkIng');
            $e = fn ($k, ...$a) => '<h3 class=errHandle>'.($a ? sprintf($this->lang->getstr($k, 'msgs'), ...$a) : $this->lang->getstr($k, 'msgs')).'</h3>';
            if (! hash_equals($ttokn, (string) $request->input('token'))) {
                $view_info = $e('error_cheating');
            } elseif ($cit['LastWorked'] >= $today) {
                $view_info = $e('error_worked_today2');
            } elseif ($cit['wellness'] <= $nwell) {
                $view_info = $e('error_low_wellness');
            } elseif ($cit['occDue'] >= time()) {
                $view_info = $e('error_occupied', $this->session->getDiffF($cit['occDue']));
            } elseif ($compAcc < $salary) {
                $view_info = $e('error_no_money');
                $this->database->sendNote($row['ManagerID'], '', 'Your workers in <a href="'.$this->vars->getURL('company', $row['CompanyID']).'">'.$row['Name'].'</a> cannot work because there is no money in the company account. Please invest money in it as soon as possible.');
            } else {
                $this->database->work($cit, $row, $wtype);
                $nowWorked = 1;
                $this->session->fillInfo(null, true);
                $cit = $this->citInfo = $this->session->userinfo;
            }
        }
        if ($cit['LastWorked'] == $today) {
            $report = $this->workReport($row, $cit, $nowWorked);
        }

        return [
            'sub' => 'workplace', 'view_info' => $view_info, 'report' => $report, 'nowWorked' => $nowWorked,
            'sCur' => $this->database->getCurrency($cit['SalaryCurID']),
            'sessions' => self::workSessions($cit, $row, $this->eco()),
            'craft' => self::craft($cit),
            'cit' => $cit,
        ];
    }

    /** The two work sessions with what they would produce / pay / cost today. */
    public static function workSessions(array $cit, array $comp, \App\Game\Services\Economy $eco): array
    {
        $out = [];
        foreach (Constants::WORK_TYPES as $t => $label) {
            $p = $eco->getProduct4Work($cit, $comp, $t);
            $out[] = [
                'type' => $t, 'label' => $label, 'stat' => $t === Constants::WORK_SHIFT ? 'craft' : 'efficiency',
                'effect' => $t === Constants::WORK_SHIFT ? '+1 Craft' : '+1 Efficiency',
                'desc' => $t === Constants::WORK_SHIFT ? 'Full shift: full output and pay' : 'Study day: half output and pay, cheaper shifts later',
                'production' => round((float) $p, 2), 'salary' => round($cit['Salary'] * max(1, $p), 2),
                'wellness' => -Constants::workWellnessCost((int) $comp['Stars'], (int) ($cit['efficiency'] ?? 0), $t),
            ];
        }

        return $out;
    }

    /** Workshop summary (craft / efficiency / streak). */
    public static function craft(array $cit): array
    {
        $craft = (int) ($cit['craft'] ?? 0);
        $eff = (int) ($cit['efficiency'] ?? 0);
        $stage = (int) floor(($craft + $eff) / 2);

        return [
            'craft' => $craft, 'efficiency' => $eff, 'streak' => (int) ($cit['rowWorkedStart'] ?? 0), 'max' => Constants::SHAPE_MAX,
            'stage' => $stage, 'name' => Constants::CRAFT_NAMES[$stage] ?? '', 'names' => Constants::CRAFT_NAMES,
            'factor' => Constants::craftFactor($craft),
        ];
    }

    /** Today's work report row, decoded (port of work_report.php). */
    private function workReport(array $row, array $cit, int $nowWorked): ?array
    {
        $rep = $this->database->row('SELECT log_working.*, country.curName, industry.iName, industry.Unit FROM log_working
            JOIN country ON log_working.curID = country.CountryID JOIN company ON company.companyID = log_working.CompanyID
            JOIN industry ON industry.IndustryID = company.IndustryID
            WHERE CitizenID = ? AND log_working.CompanyID = ? AND Day = ?', [$cit['CitizenID'], $row['CompanyID'], $this->database->today]);
        if (! $rep) {
            return null;
        }
        $ex = explode('|', (string) $rep['ep']) + [0, 0];
        $rep['ep2'] = $ex[1];
        $rep['ep'] = $ex[0] + $ex[1];
        $ex = explode('|', (string) $rep['wellness']) + [0, 0, 0];
        [$rep['wellness'], $rep['wellness2'], $rep['wellness3']] = $ex;
        $ex = explode('|', (string) $rep['skill']) + [0, 0, 0];
        [$rep['craft'], $rep['efficiency'], $rep['streak']] = array_map('intval', $ex);
        if ($rep['efficiency'] > Constants::SHAPE_MAX) { // row written by the old skill-point work
            [$rep['craft'], $rep['efficiency'], $rep['streak']] = [(int) $cit['craft'], (int) $cit['efficiency'], (int) $cit['rowWorkedStart']];
        }
        $rep['type'] = min((int) $rep['type'], Constants::WORK_STUDY);
        $rep['products'] = round($rep['products'], 2);
        $rep['stock'] = $rep['Unit'] ? round($rep['products'] / $rep['Unit'], 4) : 0;
        $rep['rowWorked'] = $cit['rowWorkedStart'] + ($nowWorked ? 0 : 0);

        return $rep;
    }

    private function workers(Request $request, array $row, array $acc): array
    {
        $citID = $this->citID();
        $id = $row['CompanyID'];
        $isManager = $this->loggedIn() && ((int) $row['ManagerID'] === $citID || $acc['can_view_private_data']);
        $msg = null;
        if ($isManager && $request->isMethod('post')) {
            if ($request->has('actOffer')) {
                $offer = (int) $request->input('actOffer');
                $ok = hash_equals(md5($offer.$id.'F**kingBugs'), (string) $request->input('token'));
                if ($request->has('cmdUpdate')) {
                    if (! $ok) {
                        $msg = '<h3 class="errHandle">Cheating detected</h3>';
                    } elseif ((float) $request->input('Salary') < 0.5) {
                        $msg = '<h3 class="errHandle">You cannot change salary lower than 0.5</h3>';
                    } elseif ($this->database->isWorker($offer, $id)) {
                        $this->database->setSalary($offer, (float) $request->input('Salary'));
                        $this->database->sendNote($offer, '', "The manager of <a href='".$this->vars->getURL('company', $id)."'>".e($row['Name']).'</a> changed your salary from '.e($request->input('oSalary')).' to '.e($request->input('Salary')).'.');
                    }
                }
                if ($request->has('cmdFire')) {
                    if (! $ok) {
                        $msg = '<h3 class="errHandle">Cheating detected</h3>';
                    } elseif ($this->database->isWorker($offer, $id)) {
                        $this->database->resignWorker($offer);
                        $this->database->sendNote($offer, '', "Unfortunately the manager of <a href='".$this->vars->getURL('company', $id)."'>".e($row['Name']).'</a> fired you.'
                            ." But don't worry, you can <a href='".$this->vars->getURL('jobs')."'>Get another job</a> or even <a href='".$this->vars->getURL('cmarket')."'>Buy a company</a>.");
                    }
                }
            }
            if ($request->has('cmdFireAll') && $acc['can_view_private_data']) {
                $this->database->exec("UPDATE company_workers SET CompanyID = '-1' WHERE CompanyID = ?", [$id]);
            }
        }

        return ['sub' => 'workers', 'msg' => $msg, 'isManagerW' => $isManager, 'workers' => $this->database->getWorkers($id)];
    }

    private function edit(Request $request, array $row, bool $isManager): array|\Symfony\Component\HttpFoundation\Response
    {
        if (! $isManager) {
            return redirect($this->vars->getURL('home'));
        }
        $id = $row['CompanyID'];
        $values = self::QUALITY_VALUES;
        $msg = null;
        if ($request->has('editOk')) {
            $errEdit = 0;
            $this->database->updateCompanyField($id, 'Name', mb_substr((string) $request->input('cName'), 0, 50));
            $this->database->updateCompanyField($id, 'company_message', (string) $request->input('compmsg'));
            $regionID = (int) $request->input('regionID');
            $countryID = (int) $request->input('countryID');
            if ((int) $row['RegionID'] !== $regionID && $regionID) {
                $cvalue = $values[$row['Stars']] ?? 0;
                $valid = $this->database->count('SELECT RegionID FROM region JOIN country ON country.CountryID = region.CountryID WHERE RegionID = ? AND country.CountryID = ? AND Hidden = 0', [$regionID, $countryID]);
                if ($valid) {
                    $price = ($countryID === (int) $row['CountryID']) ? (($row['freemove'] && $this->database->today < 500) ? 0 : $cvalue / 5) : $cvalue / 2;
                    $cmoney = $this->database->getCompanyMoney($id, 1);
                    if ($price > $cmoney) {
                        $msg = '<h3 class=errHandle>You have not enough money in your company account to be able to move.</h3>';
                        $errEdit = 1;
                    } else {
                        $this->database->addMoney(1, -$price, $id, 'company', 1, "> Move from region {$row['RegionID']} to region {$regionID} <");
                        $this->database->updateCompanyField($id, 'RegionID', $regionID);
                        $this->database->updateCompanyField($id, 'freemove', '0');
                        if ($countryID !== (int) $row['CountryID']) {
                            foreach ($this->database->getMarketOffers($row['IndustryID'], 0, '', $id) as $off) {
                                if ($this->eco()->getEmbargoes($row['CountryID'], 'trade', $countryID) || $this->war()->haveWar($row['CountryID'], $countryID)) {
                                    $this->eco()->stopTrade($row['CountryID'], $countryID);
                                } else {
                                    $tPrice = $this->database->addTax2Price($row['IndustryID'], $off['CountryID'], $countryID, (float) $off['Price']);
                                    $this->database->exec('UPDATE market SET tPrice = ? WHERE OfferID = ?', [$tPrice, $off['OfferID']]);
                                }
                            }
                        }
                    }
                }
            }
            $file = $request->file('compAvatar');
            if ($file) {
                if (in_array($file->getMimeType(), ['image/jpeg', 'image/pjpeg'], true) && $file->getSize() < 50 * 1024) {
                    $fName = md5($id.'CoMPaNY').'.jpg';
                    $file->move(public_path('uploads/avatars/company'), $fName);
                    $this->database->updateCompanyField($id, 'Avatar', $fName);
                } else {
                    $msg = '<h3 class=errHandle>The file you specified is not correct.</h3>';
                    $errEdit = 1;
                }
            }
            if (! $errEdit) {
                return redirect($this->vars->getURL('company', $id));
            }
        }

        return ['sub' => 'edit', 'msg' => $msg, 'values' => $values,
            'countries' => $this->database->rows('SELECT CountryID, cName FROM country WHERE Hidden = 0 ORDER BY cName'),
            'regions' => $this->database->rows('SELECT RegionID, rName FROM region WHERE CountryID = ? ORDER BY rName', [$row['CountryID']]),
            'movePriceHome' => ($row['freemove'] && $this->database->today < 500) ? 0 : ($values[$row['Stars']] ?? 0) / 5,
            'movePriceAway' => ($values[$row['Stars']] ?? 0) / 2];
    }

    private function finance(Request $request, array $row): array|\Symfony\Component\HttpFoundation\Response
    {
        $id = $row['CompanyID'];
        $citID = $this->citID();
        if (! $this->loggedIn() || ! $this->database->isManagerThis($citID, $id)) {
            return redirect($this->vars->getURL('home'));
        }
        $comp = $this->database->getCompany($id);
        $msg = null;
        if ($request->isMethod('post') && $request->has('actOffer')) {
            $curID = (int) $request->input('actOffer');
            $amount = (float) $request->input('Amount');
            if ($request->has('cmdInvest')) {
                $money = $this->database->getCitizenMoney($citID, $curID);
                if ($amount > $money) {
                    $msg = "<h3 class=errHandle>Your money is not enough ({$amount} > {$money})...</h3>";
                } elseif ($amount <= 0) {
                    $msg = '<h3 class=errHandle>You cannot invest negative amount...</h3>';
                } else {
                    $this->database->transferMoney($curID, $amount, $citID, 'citizen', $id, 'company');
                    $msg = "<h3 class=infHandle>You've successfully invested {$amount} ".$this->database->getCurrency($curID).' to your company account!</h3>';
                }
            }
            if ($request->has('cmdCollect')) {
                $money = $this->database->getCompanyMoney($id, $curID);
                if ($amount > $money) {
                    $msg = "<h3 class=errHandle>Your money is not enough ({$amount} > {$money})...</h3>";
                } elseif ($amount <= 0) {
                    $msg = '<h3 class=errHandle>You cannot collect negative amount...</h3>';
                } else {
                    $taxes = $this->database->getIndustryTax($comp['CountryID'], $comp['IndustryID'], 1);
                    $tax = $taxes ? (float) $taxes['Income'] : 0;
                    $amount2 = $amount * ($tax / 100);
                    $amount1 = $amount - $amount2;
                    $this->database->transferMoney($curID, $amount1, $id, 'company', $citID, 'citizen');
                    $this->database->transferMoney($curID, $amount2, $id, 'company', $comp['CountryID'], 'country');
                    $cur = $this->database->getCurrency($curID);
                    $msg = "<h3 class=infHandle>You've successfully collected {$amount1} {$cur} from your company account and {$amount2} {$cur} were transfered to country's treasury!</h3>";
                }
            }
        }
        $accounts = [];
        foreach ($this->database->getCompanyAccounts($id) as $a) {
            $mine = $this->database->row('SELECT Amount FROM citizen_money JOIN country ON citizen_money.CurID = country.CountryID WHERE CitID = ? AND country.CountryID = ?', [$citID, $a['CurID']]);
            $a['mine'] = $mine['Amount'] ?? 0;
            $accounts[] = $a;
        }

        return ['sub' => 'finance', 'msg' => $msg, 'accounts' => $accounts];
    }

    private function license(Request $request, array $row, bool $isManager): array|\Symfony\Component\HttpFoundation\Response
    {
        if (! $isManager) {
            return redirect($this->vars->getURL('home'));
        }
        $id = $row['CompanyID'];
        $msg = null;
        $cMon = $this->database->getCompanyMoney($id, 1);
        if ($request->has('buyOk') && (int) $request->input('licTo')) {
            $licTo = (int) $request->input('licTo');
            if ($this->database->hasLicense($id, $licTo)) {
                $msg = '<h3 class=errHandle>Company already has a license for selected country</h3>';
            } elseif ($cMon < 10) {
                $msg = "<h3 class=errHandle>You have not enough money in your company's account</h3>";
            } else {
                $this->database->createCompanyLicense($id, $licTo);

                return redirect($this->vars->getURL('company', $id));
            }
        }

        return ['sub' => 'license', 'msg' => $msg, 'cMon' => $cMon, 'countries' => $this->database->getCountries($this->session->isAdmin() ? 1 : 0)];
    }

    private function quality(Request $request, array $row, bool $isManager): array|\Symfony\Component\HttpFoundation\Response
    {
        if (! $isManager) {
            return redirect($this->vars->getURL('home'));
        }
        $id = $row['CompanyID'];
        $costs = self::QUALITY_VALUES;
        $cValue = $costs[$row['Stars']] ?? 0;
        $getCost = function (int $gv) use ($costs, $cValue, $row) {
            $g = $costs[$gv];

            return $cValue > $g ? (($g - $cValue) * (((int) $row['IndustryID'] === 2) ? 1 : 0.6)) : $g - $cValue;
        };
        $msg = null;
        $cMon = $this->database->getCompanyMoney($id, 1);
        if ($row['sale_bid_id']) {
            return ['sub' => 'quality', 'blocked' => true, 'msg' => null, 'cMon' => $cMon, 'costs' => [], 'getCost' => $getCost];
        }
        if ($request->has('upgOk')) {
            $gv = (int) $request->input('gValue');
            if ($gv < 0 || $gv > 5 || (string) $gv !== (string) $request->input('gValue')) {
                $msg = '<h3 class="errHandle">Cheating detected.</h3>';
            } else {
                $pMon = $getCost($gv);
                if ($cMon < $pMon) {
                    $msg = "<h3 class=errHandle>You have not enough money in your company's account</h3>";
                } elseif (! $gv) {
                    $this->database->addMoney(1, -$pMon, $row['ManagerID'], 'citizen', 1, "Dissolve company {$id}");
                    $this->database->exec('UPDATE company SET ManagerID = 0 WHERE CompanyID = ?', [$id]);
                    $this->database->exec('DELETE FROM company_workers WHERE CompanyID = ?', [$id]);
                    $this->database->exec('DELETE FROM market WHERE CompanyID = ?', [$id]);
                    $this->database->exec('UPDATE company_money SET Amount = 0 WHERE CompID = ?', [$id]);

                    return redirect('/index.html');
                } else {
                    $this->database->addMoney(1, -$pMon, $id, 'company', 1, "Changed company {$id} to quality {$gv}");
                    $this->database->exec('UPDATE company SET Stock = 0, Products = 0, Stars = ? WHERE CompanyID = ?', [$gv, $id]);

                    return redirect($this->vars->getURL('company', $id));
                }
            }
        }

        return ['sub' => 'quality', 'blocked' => false, 'msg' => $msg, 'cMon' => $cMon, 'getCost' => $getCost];
    }

    private function sell(Request $request, array $row, bool $isManager): array|\Symfony\Component\HttpFoundation\Response
    {
        if (! $isManager) {
            return redirect($this->vars->getURL('home'));
        }
        $id = $row['CompanyID'];
        if ($request->has('sellOk')) {
            $sPrice = (float) $request->input('sPrice');
            $bPrice = (float) $request->input('bPrice');
            if ($sPrice >= 0 && $bPrice >= 0 && ! $row['sale_bid_id']) {
                $this->database->updateCompanyField($id, 'sale_base', (int) $bPrice);
                $this->database->updateCompanyField($id, 'sale_step', $sPrice);
                $this->database->updateCompanyField($id, 'sale_due', time() + 7 * 24 * 3600);
            }

            return redirect($request->getRequestUri());
        }
        if ($request->has('removeSell')) {
            if (! $row['sale_bid_id']) {
                foreach (['sale_base', 'sale_step', 'sale_due'] as $f) {
                    $this->database->updateCompanyField($id, $f, 0);
                }
            }

            return redirect($request->getRequestUri());
        }
        if ($request->has('submitSell')) {
            if ($row['sale_bid_id']) {
                $this->database->transferCompany($row);
            }

            return redirect($request->getRequestUri());
        }

        return ['sub' => 'sell', 'bid' => $this->eco()->getBid($id)];
    }

    private function status(Request $request, array $row): array|\Symfony\Component\HttpFoundation\Response
    {
        if (! $this->loggedIn() || ((int) $row['ManagerID'] !== $this->citID() && $this->citID() !== 1)) {
            return redirect('/index.html');
        }
        $id = $row['CompanyID'];
        $today = (int) ($request->input('selectedday') ?: $this->database->today);
        $citizen = (int) $request->input('selectedcit', 0);

        return [
            'sub' => 'status', 'selDay' => $today, 'selCit' => $citizen,
            'dayLogs' => $this->database->rows("SELECT log_working.*, citizens.name, citizens.ep, citizens.puberty, citizens.Avatar, citizens.regionID, 'citizen' AS accType, country.curName
                FROM log_working JOIN citizens ON citizens.CitizenID = log_working.CitizenID JOIN country ON country.CountryID = log_working.curID
                WHERE Day = ? AND log_working.CompanyID = ? ORDER BY timestamp", [$today, $id]),
            'workerList' => $this->database->rows('SELECT citizens.name, citizens.CitizenID FROM log_working JOIN citizens ON citizens.CitizenID = log_working.CitizenID WHERE log_working.CompanyID = ? GROUP BY log_working.CitizenID', [$id]),
            'citLogs' => $citizen ? $this->database->rows('SELECT log_working.*, country.curName FROM log_working JOIN country ON country.CountryID = log_working.curID WHERE CitizenID = ? AND log_working.CompanyID = ? ORDER BY timestamp DESC', [$citizen, $id]) : [],
        ];
    }

    private function migrate(Request $request, array $row, bool $isManager): array|\Symfony\Component\HttpFoundation\Response
    {
        if (! $isManager) {
            return redirect($this->vars->getURL('home'));
        }
        $id = $row['CompanyID'];
        $msg = null;
        if ($request->input('submigrate')) {
            $inds = [1, 3, 4, 7, 8];
            $units = [0, 1, 0, 2, 5, 0, 0, 100, 200];
            $tar = (int) $request->input('iName');
            if (! in_array($tar, $inds, true)) {
                $msg = '<h3 class="errHandle">Invalid industry selected.</h3>';
            } else {
                $prods = $row['Stock'] * $row['Unit'] + $row['Products'];
                $unit = $units[$tar];
                $stock = (int) floor($prods / $unit);
                $prod = $prods - ($stock * $unit);
                $this->database->exec('UPDATE company SET Stock = ?, Products = ?, IndustryID = ? WHERE CompanyID = ?', [$stock, $prod, $tar, $id]);
                $this->database->exec('DELETE FROM market WHERE CompanyID = ?', [$id]);

                return redirect($this->vars->getURL('company', $id));
            }
        }

        return ['sub' => 'migrate', 'msg' => $msg, 'industries' => $this->database->rows('SELECT * FROM industry WHERE IndustryID < 9 AND IndustryID != 2')];
    }
}
