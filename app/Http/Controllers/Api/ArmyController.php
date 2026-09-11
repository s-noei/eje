<?php

namespace App\Http\Controllers\Api;

use App\Game\Support\Constants;
use App\Http\Controllers\WarController;
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
        $wChange = fn (int $type) => -(Constants::TRAIN_WELLNESS[$type] - abs(round(Constants::TRAIN_WELLNESS[$type] * $gdpercs[$gds])));
        $rep = $this->database->row('SELECT * FROM log_training WHERE CitizenID = ? AND Day = ?', [$citID, $today]);
        $report = null;
        if ($rep) {
            $w = explode('|', (string) $rep['wellness']) + [0, 0, 0];
            $s = explode('|', (string) $rep['skill']) + [0, 0, 0];
            if ((int) $s[1] > Constants::SHAPE_MAX) { // row written by the old skill-point training
                $s = [$c['strength'], $c['stamina'], $c['train_streak']];
            }
            $report = ['type' => (int) $rep['type'], 'wellnessBefore' => (float) $w[0], 'wellnessLoss' => (float) $w[1], 'wellnessRecovered' => (float) $w[2],
                'strength' => (int) $s[0], 'stamina' => (int) $s[1], 'streak' => (int) $s[2], 'ep' => 1];
        }
        $options = [
            ['type' => Constants::TRAIN_WEIGHTS, 'label' => 'Weights', 'stat' => 'strength', 'effect' => '+1 Strength', 'desc' => 'Hit harder', 'wellness' => $wChange(Constants::TRAIN_WEIGHTS)],
            ['type' => Constants::TRAIN_CARDIO, 'label' => 'Cardio', 'stat' => 'stamina', 'effect' => '+1 Stamina', 'desc' => 'Fights cost less wellness', 'wellness' => $wChange(Constants::TRAIN_CARDIO)],
        ];
        $mRank = (int) $c['mRank'];
        $stats = [
            'rank' => $mRank, 'rankName' => Constants::MILI_RANKS[$mRank] ?? '', 'rankIcon' => url('/images/game/war/mrank/'.$mRank.'.gif'),
            'damage' => (float) $c['total_damage'], 'damageFrom' => Constants::RANK_DAMAGES[$mRank] ?? 0, 'damageTo' => Constants::RANK_DAMAGES[$mRank + 1] ?? 0,
            'nextRankName' => Constants::MILI_RANKS[$mRank + 1] ?? null, 'nextRankReward' => Constants::RANK_UP_TALA * ($mRank + 1),
        ];

        return ['trainedToday' => $c['LastTrained'] == $today, 'occupiedUntil' => (int) $c['occDue'], 'options' => $options, 'foods' => $this->foods($citID),
            'report' => $report, 'shape' => WarController::shape($c), 'stats' => $stats];
    }

    /** GET /api/v1/army */
    public function index()
    {
        return $this->ok($this->trainState($this->cit(true)));
    }

    /** POST /api/v1/army/train {type: 1 weights | 2 cardio, foods: {stars: amount}} */
    public function train(Request $request)
    {
        $c = $this->cit(true);
        $today = $this->database->today;
        $ttype = (int) $request->input('type');
        if (!in_array($ttype, [Constants::TRAIN_WEIGHTS, Constants::TRAIN_CARDIO], true)) {
            return $this->fail('Invalid training type.');
        }
        if ($c['accType'] !== 'citizen') {
            return $this->fail('Co-accounts cannot train.');
        }
        if ($c['LastTrained'] == $today) {
            return $this->fail($this->msg('error_trained_today'));
        }
        if ($c['wellness'] <= Constants::TRAIN_WELLNESS[$ttype]) {
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
