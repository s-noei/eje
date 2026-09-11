<?php

namespace App\Http\Controllers\Api;

use App\Game\Support\Constants;
use App\Game\Services\Economy;
use App\Http\Controllers\CompanyController;
use Illuminate\Http\Request;

/** Workplace (company-{id}-workplace). */
class WorkController extends ApiController
{
    private function company(array $c): ?array
    {
        if (!$this->database->isWorker($this->citID())) {
            return null;
        }
        $compID = (int) $this->database->value("SELECT CompanyID FROM company_workers WHERE CitizenID = ? AND CompanyID != '0'", [$this->citID()], 0);

        return $compID ? $this->database->getCompany($compID) : null;
    }

    private function state(array $c, ?array $comp, ?array $report = null): array
    {
        if (!$comp) {
            return ['employed' => false];
        }
        $eco = app(Economy::class);
        $today = $this->database->today;
        $options = CompanyController::workSessions($c, $comp, $eco);
        if ($c['LastWorked'] == $today && !$report) {
            $rep = $this->database->row('SELECT log_working.*, industry.iName, industry.Unit FROM log_working JOIN company ON company.companyID = log_working.CompanyID
                JOIN industry ON industry.IndustryID = company.IndustryID WHERE CitizenID = ? AND Day = ?', [$this->citID(), $today]);
            if ($rep) {
                $w = explode('|', (string) $rep['wellness']) + [0, 0, 0];
                $s = array_map('intval', explode('|', (string) $rep['skill']) + [0, 0, 0]);
                if ($s[1] > Constants::SHAPE_MAX) { // row written by the old skill-point work
                    $s = [(int) $c['craft'], (int) $c['efficiency'], (int) $c['rowWorkedStart']];
                }
                $e = explode('|', (string) $rep['ep']) + [0, 0];
                $report = ['produced' => (float) ($rep['products'] ?? 0), 'units' => $rep['Unit'] ? round($rep['products'] / $rep['Unit'], 2) : 0, 'item' => $rep['iName'],
                    'salary' => (float) ($rep['salary'] ?? 0), 'tax' => (float) ($rep['tax'] ?? 0), 'type' => min((int) $rep['type'], Constants::WORK_STUDY),
                    'wellnessBefore' => (float) $w[0], 'wellnessLoss' => (float) $w[1], 'craft' => $s[0], 'efficiency' => $s[1], 'streak' => $s[2], 'epGained' => (float) $e[1]];
            }
        }

        return [
            'employed' => true,
            'craft' => CompanyController::craft($c),
            'company' => ['id' => (int) $comp['CompanyID'], 'name' => $comp['Name'], 'avatar' => url('/uploads/avatars/company/'.$comp['Avatar']), 'stars' => (int) $comp['Stars'], 'industry' => $this->database->getIndustry($comp['IndustryID'])],
            'salary' => (float) $c['Salary'], 'salaryCurrency' => $this->database->getCurrency($c['SalaryCurID']),
            'workedToday' => $c['LastWorked'] >= $today, 'occupiedUntil' => (int) $c['occDue'], 'options' => $options, 'report' => $report,
        ];
    }

    /** GET /api/v1/work */
    public function index()
    {
        $c = $this->cit(true);

        return $this->ok($this->state($c, $this->company($c)));
    }

    /** POST /api/v1/work {type: 1 shift | 2 study} */
    public function work(Request $request)
    {
        $c = $this->cit(true);
        $comp = $this->company($c);
        if (!$comp) {
            return $this->fail('You are not employed.');
        }
        $today = $this->database->today;
        $wtype = (int) $request->input('type');
        if (!in_array($wtype, [Constants::WORK_SHIFT, Constants::WORK_STUDY], true)) {
            return $this->fail('Invalid work type.');
        }
        if ($c['LastWorked'] >= $today) {
            return $this->fail($this->msg('error_worked_today2'));
        }
        if ($c['wellness'] <= Constants::workWellnessCost((int) $comp['Stars'], (int) ($c['efficiency'] ?? 0), $wtype)) {
            return $this->fail($this->msg('error_low_wellness'));
        }
        if ($c['occDue'] >= time()) {
            return $this->fail($this->msg('error_occupied', 'msgs', $this->session->getDiffF($c['occDue'])));
        }
        $prod = app(Economy::class)->getProduct4Work($c, $comp, $wtype);
        $salary = round($c['Salary'] * ($prod >= 1 ? $prod : 1), 2);
        $acc = $this->database->getCompanyAccount($comp['CompanyID'], $c['SalaryCurID']);
        if (($acc['Amount'] ?? 0) < $salary) {
            $this->database->sendNote($comp['ManagerID'], '', 'Your workers in <a href="'.$this->vars->getURL('company', $comp['CompanyID']).'">'.$comp['Name'].'</a> cannot work because there is no money in the company account. Please invest money in it as soon as possible.');

            return $this->fail($this->msg('error_no_money'));
        }
        $this->database->work($c, $comp, $wtype);
        $c = $this->cit(true);

        return $this->ok($this->state($c, $comp) + ['citizen' => $this->citizenPayload($c, true)]);
    }
}
