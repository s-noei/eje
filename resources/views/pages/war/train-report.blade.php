<link rel="stylesheet" type="text/css" href="/include/css/army.css">
@php
	use App\Game\Support\Constants;
	$rep = $report;
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$tot = $rep['wellness2'] - $rep['wellness3']; if ($tot < 0) $tot = "-$tot";
	$rp2 = "<table><tr><td style='width: 200px'>Wellness before task</td><td>{$rep['wellness']}</td></tr><tr><td>Wellness loss</td><td>-{$rep['wellness2']}</td></tr>"
		."<tr><td>Recovered by foods</td><td>+{$rep['wellness3']}</td></tr><tr><th>Total wellness loss</th><th>{$tot}</th></tr></table>";
	$weights = $rep['type'] == Constants::TRAIN_WEIGHTS;
	$stat = $weights ? $rep['strength'] : $rep['stamina'];
	$stage = (int) floor(($rep['strength'] + $rep['stamina']) / 2);
@endphp
<div id="train-report">
	<div class="product">
		{{ $weights ? 'Weights' : 'Cardio' }} session done
		<br>
		{{ $weights ? 'Strength' : 'Stamina' }} {{ $stat }} / {{ Constants::SHAPE_MAX }}
		<div class="prod-made">
@if ($stat >= Constants::SHAPE_MAX)
			<blink>{{ $weights ? 'Maximum strength!' : 'Maximum stamina!' }}</blink><br>
@endif
			Day {{ $rep['streak'] }} in a row &mdash; {{ Constants::SHAPE_NAMES[$stage] ?? '' }}
		</div>
	</div>
	<hr size="1">
	<div class="hummy">&nbsp;</div>
	<div class="info">
		<div class="title">Strength</div><div class="detail">{{ $rep['strength'] }} / {{ Constants::SHAPE_MAX }}</div>
		<div style="clear: both"></div>
		<div class="title">Stamina</div><div class="detail">{{ $rep['stamina'] }} / {{ Constants::SHAPE_MAX }}</div>
		<div style="clear: both"></div>
		<div class="title">Hit</div><div class="detail">{{ Constants::shapeDamage((int) $rep['strength']) }}</div>
	</div>
	<div class="changes">
		<div class="title">Wellness</div>
		<div class="detail">{{ $rep['wellness'] - ($rep['wellness2'] - $rep['wellness3']) }} <span title="{{ sprintf($tit, "Wellness change", $rp2) }}">(?)</span></div>
		<div style="clear: both"></div>
		<div class="title">Fight cost</div><div class="detail">{{ Constants::fightWellnessCost((int) $rep['stamina']) }} wellness</div>
		<div style="clear: both"></div>
		<div class="title">EP</div><div class="detail">{{ $rep['ep'] }} (<font color="green">+{{ $rep['ep2'] }}</font>)</div>
	</div>
	<div style="clear: both"></div>
</div>
<hr>
