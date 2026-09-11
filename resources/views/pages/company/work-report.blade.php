@php
	use App\Game\Support\Constants;
	$rep = $report;
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$sign = fn($v) => (substr((string)$v, 0, 1) != '-') ? "+$v" : $v;
	$rp = "<table><tr><td style='width: 200px'>Base productivity</td><td>{$rep['formula_base']}</td></tr>"
		."<tr><td>Crowd factor</td><td>".$sign($rep['formula_cfactor'])."%</td></tr>"
		."<tr><td>Important industry</td><td>".$sign($rep['formula_impind'])."%</td></tr></table>";
	$shift = $rep['type'] == Constants::WORK_SHIFT;
	$stat = $shift ? $rep['craft'] : $rep['efficiency'];
	$stage = (int) floor(($rep['craft'] + $rep['efficiency']) / 2);
@endphp
<div id="work-report">
	<div class="product" title="{{ sprintf($tit, "Full report", $rp) }}">
		{{ $shift ? '🏭 Shift' : '📚 Study day' }} done
		<br>
		{{ $shift ? 'Craft' : 'Efficiency' }} {{ $stat }} / {{ Constants::SHAPE_MAX }}
		<div class="prod-made">Day {{ $rep['streak'] }} in a row &mdash; {{ Constants::CRAFT_NAMES[$stage] ?? '' }}<br>Made {{ $rep['stock'] }} {{ $rep['iName'] }} units ({{ round($rep['products'], 2) }} productivity)</div>
	</div>
	<hr size="1">
	<div class="hummy">&nbsp;</div>
	<div class="info">
		<div class="title">Craft</div>
		<div class="detail">{{ $rep['craft'] }} / {{ Constants::SHAPE_MAX }}</div>
		<div style="clear: both"></div>
		<div class="title">Efficiency</div>
		<div class="detail">{{ $rep['efficiency'] }} / {{ Constants::SHAPE_MAX }}</div>
		<div style="clear: both"></div>
		<div class="title">Wellness</div>
		<div class="detail">{{ $rep['wellness'] - $rep['wellness2'] }}</div>
	</div>
	<div class="changes">
		<div class="title">Salary</div>
		<div class="detail">{{ round($rep['salary'], 2) }} {{ $rep['curName'] }}</div>
		<div style="clear: both"></div>
		<div class="title">Tax</div>
		<div class="detail">{{ round($rep['tax'], 2) }} {{ $rep['curName'] }}</div>
		<div style="clear: both"></div>
		<div class="title">EP</div>
		<div class="detail">{{ $rep['ep'] }} (<font color="green">+{{ $rep['ep2'] }}</font>)</div>
	</div>
	<div style="clear: both"></div>
</div>
<br>
<a href="{{ $vars->getURL('company', $row['CompanyID']) }}" class="button-blue-1">{!! $lang->getstr('company_back_link', 'company') !!}</a>
<hr>
