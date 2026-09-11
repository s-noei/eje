<link rel="stylesheet" type="text/css" href="/include/css/mines.css">
@php
	$rp2 = '<table><tr><td style=\'width: 200px\'>Wellness before task</td><td>' . $rep['wellness'] . '</td></tr>'
		. '<tr><td>Wellness loss</td><td>-' . $rep['wellness2'] . '</td></tr>'
		. '<tr><td>Recovered by foods</td><td>+' . $rep['wellness3'] . '</td></tr>'
		. '<tr><th>Total wellness loss</th><th>' . ($rep['wellness3'] - $rep['wellness2']) . '</th></tr></table>';
@endphp
<div id="explore-report">
	<div class="product">
		You have explored
		<br>
		{{ $rep['points'] }}
		<div class="prod-made">{{ $rep['left'] - $rep['points'] }} points to receive reward</div>
	</div>
	<hr size="1">
	<div class="hummy">&nbsp;</div>
	<div class="info">
		<div class="title">Duration</div><div class="detail">{{ $rep['duration'] }} hours</div>
		<div style="clear: both"></div>
		<div class="title">Wellness</div><div class="detail">{{ $rep['wellness'] }}</div>
		<div style="clear: both"></div>
		<div class="title">Chance</div><div class="detail">{{ $rep['chance'] }}</div>
	</div>
	<div class="changes">
		<div class="title">Wellness</div>
		<div class="detail">
			{{ $rep['wellness'] - ($rep['wellness2'] - $rep['wellness3']) }}
            <span title="{{ sprintf($tit, 'Wellness change', $rp2) }}">(?)</span>
		</div>
		<div style="clear: both"></div>
		<div class="title">EP</div><div class="detail">{{ $rep['ep'] }} (<font color="green">+{{ $rep['ep2'] }}</font>)</div>
		<div style="clear: both"></div>
		<div class="title">Reward</div><div class="detail">{{ $rep['received'] }} P</div>
	</div>
	<div style="clear: both"></div>
</div>
<hr>
