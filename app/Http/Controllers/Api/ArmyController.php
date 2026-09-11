<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

/** Training (army.php) and the mines. */
class ArmyController extends ApiController
{
    private function trainState(array $c): array
    {
        $citID = $this->citID();
        $today = $this->database->today;
        $gds = min((int) ($c['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $wChange = function (int $type) use ($gdpercs, $gds) {
            $x = pow(3, $type - 1);

            return -($x - abs(round($x * $gdpercs[$gds])));
        };
        $rep = $this->database->row('SELECT * FROM log_training WHERE CitizenID = ? AND Day = ?', [$citID, $today]);
        $report = null;
        if ($rep) {
            $w = explode('|', (string) $rep['wellness']) + [0, 0, 0];
            $s = explode('|', (string) $rep['skill']) + [0, 0, 0];
            $report = ['type' => (int) $rep['type'], 'wellnessBefore' => (float) $w[0], 'wellnessLoss' => (float) $w[1], 'wellnessRecovered' => (float) $w[2],
                'skill' => (float) $s[0], 'sp' => (float) $s[1], 'spGained' => (float) $s[2], 'received' => round((float) $rep['received'], 3), 'ep' => 1];
        }
        $options = [];
        foreach ([1 => 'Normal', 2 => 'Hard', 3 => 'Max'] as $t => $label) {
            $unlocked = $t === 1 || ($t === 2 && $c['puberty'] > 0) || ($t === 3 && $c['puberty'] > 1);
            $options[] = ['type' => $t, 'label' => $label, 'unlocked' => $unlocked, 'wellness' => $wChange($t), 'skillGain' => (float) $this->database->getChangedSkill($c, $t, 1)];
        }

        return ['trainedToday' => $c['LastTrained'] == $today, 'occupiedUntil' => (int) $c['occDue'], 'options' => $options, 'foods' => $this->foods($citID), 'report' => $report];
    }

    /** GET /api/v1/army */
    public function index()
    {
        return $this->ok($this->trainState($this->cit(true)));
    }

    /** POST /api/v1/army/train {type: 1|2|3, foods: {stars: amount}} */
    public function train(Request $request)
    {
        $c = $this->cit(true);
        $today = $this->database->today;
        $t = (int) $request->input('type');
        $ttype = ($t === 1 || ($t === 2 && $c['puberty'] > 0) || ($t === 3 && $c['puberty'] > 1)) ? $t : 0;
        if (!$ttype) {
            return $this->fail('Invalid training type.');
        }
        if ($c['accType'] !== 'citizen') {
            return $this->fail('Co-accounts cannot train.');
        }
        if ($c['LastTrained'] == $today) {
            return $this->fail($this->msg('error_trained_today'));
        }
        if ($c['wellness'] <= pow(3, $ttype - 1)) {
            return $this->fail($this->msg('error_low_wellness'));
        }
        if ($c['occDue'] >= time()) {
            return $this->fail($this->msg('error_occupied', 'msgs', $this->session->getDiffF($c['occDue'])));
        }
        $this->database->doTrain($c, $ttype, array_map('intval', (array) $request->input('foods', [])));
        $c = $this->cit(true);

        return $this->ok($this->trainState($c) + ['citizen' => $this->citizenPayload($c, true)]);
    }
}
