<?php

namespace App\Http\Controllers\Api;

use App\Game\Services\Economy;
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
        $gds = min((int) ($c['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $options = [];
        foreach ([1 => 'Normal', 2 => 'Extra', 3 => 'Hard'] as $t => $label) {
            $x = $comp['Stars'] * pow(2, $t - 1);
            $p = $eco->getProduct4Work($c, $comp, $t);
            $options[] = ['type' => $t, 'label' => $label, 'production' => round((float) $p, 2), 'salary' => round($c['Salary'] * max(1, $p), 2),
                'skillGain' => (float) $this->database->getChangedSkill($c, $t), 'wellness' => -($x - abs(round($x * $gdpercs[$gds])))];
        }
        if ($c['LastWorked'] == $today && !$report) {
            $rep = $this->database->row('SELECT * FROM log_working WHERE CitizenID = ? AND Day = ?', [$this->citID(), $today]);
            if ($rep) {
                $w = explode('|', (string) $rep['wellness']) + [0, 0, 0];
                $s = explode('|', (string) $rep['skill']) + [0, 0, 0];
                $e = explode('|', (string) $rep['ep']) + [0, 0];
                $report = ['produced' => (float) ($rep['products'] ?? 0), 'salary' => (float) ($rep['salary'] ?? 0), 'tax' => (float) ($rep['tax'] ?? 0), 'type' => (int) $rep['type'],
                    'wellnessBefore' => (float) $w[0], 'wellnessLoss' => (float) $w[1], 'wellnessRecovered' => (float) $w[2], 'skill' => (float) $s[0], 'sp' => (float) $s[1], 'spGained' => (float) $s[2], 'epGained' => (float) $e[1]];
            }
        }

        return [
            'employed' => true,
            'company' => ['id' => (int) $comp['CompanyID'], 'name' => $comp['Name'], 'avatar' => url('/uploads/avatars/company/'.$comp['Avatar']), 'stars' => (int) $comp['Stars'], 'industry' => $this->database->getIndustry($comp['IndustryID'])],
            'salary' => (float) $c['Salary'], 'salaryCurrency' => $this->database->getCurrency($c['SalaryCurID']),
            'workedToday' => $c['LastWorked'] >= $today, 'occupiedUntil' => (int) $c['occDue'], 'options' => $options, 'foods' => $this->foods($this->citID()), 'report' => $report,
        ];
    }

    /** GET /api/v1/work */
    public function index()
    {
        $c = $this->cit(true);

        return $this->ok($this->state($c, $this->company($c)));
    }

    /** POST /api/v1/work {type: 1|2|3, foods: {stars: amount}} */
    public function work(Request $request)
    {
        $c = $this->cit(true);
        $comp = $this->company($c);
        if (!$comp) {
            return $this->fail('You are not employed.');
        }
        $today = $this->database->today;
        $wtype = (int) $request->input('type');
        if (!in_array($wtype, [1, 2, 3], true)) {
            return $this->fail('Invalid work type.');
        }
        if ($c['LastWorked'] >= $today) {
            return $this->fail($this->msg('error_worked_today2'));
        }
        if ($c['wellness'] <= pow(2, $wtype - 1) * $comp['Stars']) {
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
        $this->database->work($c, $comp, $wtype, array_map('intval', (array) $request->input('foods', [])));
        $c = $this->cit(true);

        return $this->ok($this->state($c, $comp) + ['citizen' => $this->citizenPayload($c, true)]);
    }
}
