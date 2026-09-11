<link rel="stylesheet" type="text/css" href="/include/css/army.css">
@php
	use App\Game\Support\Constants;
	$rep = $report;
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$tot = $rep['wellness2'] - $rep['wellness3']; if ($tot < 0) $tot = "-$tot";
	$rp2 = "<table><tr><td style='width: 200px'>Wellness before task</td><td>{$rep['wellness']}</td></tr><tr><td>Wellness loss</td><td>-{$rep['wellness2']}</td></tr>"
		."<tr><td>Recovered by foods</td><td>+{$rep['wellness3']}</td></tr><tr><th>Total wellness loss</th><th>{$tot}</th></tr></table>";
@endphp
<div id="train-report">
	<div class="product">
		Your received skill
		<br>
		{{ $rep['received'] }}
		<div class="prod-made">
@if (floor($rep['sp'] / 7500) > floor(($rep['sp'] - $rep['sp2']) / 7500))
			<blink>You received an Imperishable Soldier trophy!</blink><br>
@endif
			{{ ((floor($rep['sp'] / 7500) + 1) * 7500) - $rep['sp'] }} Skill points left to receive IS trophy
@if ((Constants::SP_CPS[$rep['skill'] + 1] ?? PHP_INT_MAX) < $rep['sp'])
			<br><blink style="color: red">Your military skill is now {{ $rep['skill'] + 1 }}!</blink>
@endif
		</div>
	</div>
	<hr size="1">
	<div class="hummy">&nbsp;</div>
	<div class="info">
		<div class="title">Skill</div><div class="detail">{{ $rep['skill'] }}</div>
		<div style="clear: both"></div>
		<div class="title">Wellness</div><div class="detail">{{ $rep['wellness'] }}</div>
		<div style="clear: both"></div>
		<div class="title">Train type</div><div class="detail">{{ (50 + $rep['type'] * 50) . "%" }}</div>
	</div>
	<div class="changes">
		<div class="title">Wellness</div>
		<div class="detail">{{ $rep['wellness'] - ($rep['wellness2'] - $rep['wellness3']) }} <span title="{{ sprintf($tit, "Wellness change", $rp2) }}">(?)</span></div>
		<div style="clear: both"></div>
		<div class="title">Skill points</div><div class="detail">{{ $rep['sp'] }} (<font color="green">+{{ $rep['sp2'] }}</font>)</div>
		<div style="clear: both"></div>
		<div class="title">EP</div><div class="detail">{{ $rep['ep'] }} (<font color="green">+{{ $rep['ep2'] }}</font>)</div>
	</div>
	<div style="clear: both"></div>
</div>
<hr>
