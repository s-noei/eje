@php
	$rep = $report;
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$sign = fn($v) => (substr((string)$v, 0, 1) != '-') ? "+$v" : $v;
	$rp = "<table><tr><td style='width: 200px'>Base productivity</td><td>{$rep['formula_base']}</td></tr>"
		."<tr><td>Crowd factor</td><td>".$sign($rep['formula_cfactor'])."%</td></tr>"
		."<tr><td>Important industry</td><td>".$sign($rep['formula_impind'])."%</td></tr></table>";
	$rp2 = "<table><tr><td style='width: 200px'>Wellness before task</td><td>{$rep['wellness']}</td></tr>"
		."<tr><td>Wellness loss</td><td>-{$rep['wellness2']}</td></tr>"
		."<tr><td>Recover by foods</td><td>+{$rep['wellness3']}</td></tr>"
		."<tr><th>Total wellness loss</th><th>".($rep['wellness3'] - $rep['wellness2'])."</th></tr></table>";
@endphp
<div id="work-report">
	<div class="product" title="{{ sprintf($tit, "Full report", $rp) }}">
		Your productivity
		<br>
		{{ round($rep['products'], 2) }}
		<div class="prod-made">Made {{ $rep['stock'] }} {{ $rep['iName'] }} units</div>
	</div>
	<hr size="1">
	<div class="hummy">&nbsp;</div>
	<div class="info">
		<div class="title">Skill</div>
		<div class="detail">{{ $rep['skill'] }}</div>
		<div style="clear: both"></div>
		<div class="title">Wellness</div>
		<div class="detail">{{ $rep['wellness'] }}</div>
		<div style="clear: both"></div>
		<div class="title">Work type</div>
		<div class="detail">{{ (50 + $rep['type'] * 50) . "%" }}</div>
		<div style="clear: both"></div>
		<div class="title">Work in a row</div>
		<div class="detail">{{ $citInfo['rowWorkedStart'] }}</div>
	</div>
	<div class="changes">
		<div class="title">Wellness</div>
		<div class="detail">{{ $rep['wellness'] }} <span title="{{ sprintf($tit, "Wellness change", $rp2) }}">(?)</span></div>
		<div style="clear: both"></div>
		<div class="title">Skill points</div>
		<div class="detail">{{ $rep['sp'] }} (<font color="green">+{{ $rep['sp2'] }}</font>)</div>
		<div style="clear: both"></div>
		<div class="title">EP</div>
		<div class="detail">{{ $rep['ep'] }} (<font color="green">+{{ $rep['ep2'] }}</font>)</div>
		<div style="clear: both"></div>
		<hr size="1">
		<div class="title">Salary</div>
		<div class="detail">{{ round($rep['salary'], 2) }} {{ $rep['curName'] }}</div>
		<div class="title">Tax</div>
		<div class="detail">{{ round($rep['tax'], 2) }} {{ $rep['curName'] }}</div>
	</div>
	<div style="clear: both"></div>
</div>
<br>
<a href="{{ $vars->getURL('company', $row['CompanyID']) }}" class="button-blue-1">{!! $lang->getstr('company_back_link', 'company') !!}</a>
<hr>
