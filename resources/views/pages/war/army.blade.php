@extends('layouts.game')
@section('content')
@php
	use App\Game\Support\Constants;
	$a = fn($k) => $lang->getstr($k, 'army');
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$W = Constants::TRAIN_WEIGHTS; $C = Constants::TRAIN_CARDIO;
	$meter = function (int $v, string $color) {
		$out = '<div style="display: inline-block; vertical-align: middle">';
		for ($i = 1; $i <= Constants::SHAPE_MAX; $i++) {
			$out .= '<div style="display: inline-block; width: 26px; height: 12px; margin-right: 2px; border: 1px solid #666; border-radius: 2px; background: '.($i <= $v ? $color : '#eee').'"></div>';
		}
		return $out.'</div>';
	};
@endphp
{!! $msg ?? '' !!}
@if ($trainednow && $report)
	@include('pages.war.train-report')
@elseif ($trained)
	<h3 class="infHandle">You have trained today!<br>Please come back tomorrow</h3>
	<hr>
	@if ($report)@include('pages.war.train-report')@endif
@else
		<h3>{!! $a('army_train') !!}</h3><hr>
		<form name="trainform" action="" method="post">
			@csrf
			<center>
				Choose today's session:
				<br />
						<div class="taskbuts" id="Weights">
<label class="normalwrk" title="{{ sprintf($tit, 'Weights', 'Strength +1 (damage per hit)<br>'.$lang->getstr('wellness').': '.$wChange[$W]) }}">
<img src="/images/game/train/normal.gif" /><br /><b>Weights</b><br /><font size="1">Strength +1</font>
<input type="radio" id="tWeights" name="train" value="{{ $W }}" style="visibility: hidden;" />
</label>
</div>
						<div class="taskbuts" id="Cardio">
<label class="extrawrk" title="{{ sprintf($tit, 'Cardio', 'Stamina +1 (cheaper fights)<br>'.$lang->getstr('wellness').': '.$wChange[$C]) }}">
<img src="/images/game/train/super.gif" /><br /><b>Cardio</b><br /><font size="1">Stamina +1</font>
<input type="radio" id="tCardio" name="train" value="{{ $C }}" style="visibility: hidden;" />
</label>
</div>
			</center>
@endif
<b>Your body shape</b> &mdash; <i>{{ $shape['name'] }}</i> ({{ $shape['streak'] }} day{{ $shape['streak'] == 1 ? '' : 's' }} in a row)<hr>
<div style="padding-left: 35px">
	<table class="task-stats">
		<tr><th style="text-align: left">Strength</th><td>{!! $meter($shape['strength'], '#CC3333') !!}</td><td>&nbsp; {{ $shape['strength'] }}/{{ $shape['max'] }} &rarr; <b>{{ $shape['damage'] }}</b> damage per hit (bare hands)</td></tr>
		<tr><th style="text-align: left">Stamina</th><td>{!! $meter($shape['stamina'], '#3366CC') !!}</td><td>&nbsp; {{ $shape['stamina'] }}/{{ $shape['max'] }} &rarr; a fight costs <b>{{ $shape['fightCost'] }}</b> wellness</td></tr>
	</table>
	<font size="1">Train every day: each session adds one stage, each missed day takes one away. Weapons multiply your hit; military rank is your prestige and gives your unit its bonus.</font>
</div>
<div style="padding-left: 40px"><hr size="1"></div>
<div style="padding-left: 35px">
	<div class="holder-indicator">
		<div class="title">{!! $a('army_mili_rank') !!}</div>
		<div class="desc-double-e">
			<img src="/images/game/war/mrank/{{ $cit['mRank'] }}.gif" width="50" align="absmiddle" title="{{ Constants::MILI_RANKS[$cit['mRank']] ?? '' }}">
		</div>
		<div class="ind-smaller">
			{!! $vars->viewIndicator(Constants::RANK_DAMAGES[$cit['mRank']] ?? 0, Constants::RANK_DAMAGES[$cit['mRank'] + 1] ?? 0, $cit['total_damage'], 375, "maroon", "Total advance", "%s", 0) !!}
			<div style="clear: both"></div>
		</div>
	</div>
</div>
<div style="padding-left: 40px">
	<a href="{{ $vars->getURL('wars') }}" class="button-blue-1">{!! $a('army_active_wars') !!}</a>
</div>
<hr>
@if ($trained || $trainednow)
			<center>
				<b>{!! $a('army_active_battles') !!}</b>
				<br>
				<blockquote style="text-align: justify">
@if (count($battles) < 1)
							<hr size="1">
							There is no active battle for your country.
@endif
@foreach ($battles as $bat)
						<hr size="1">
						<a href="{{ $vars->getURL('battle', $bat['battleID']) }}">
							<img src="/images/media/att-s.jpg" border="0" align="absmiddle">
							{{ $bat['battle_type'] == 'battle' ? $bat['attName'] : 'Revolt force' }}
							attacked {{ $bat['regionName'] }}, {{ $bat['defName'] }}
						</a>
						<sup>started {!! $session->getDiff($bat['Start']) !!}</sup>
@endforeach
				</blockquote>
			</center>
@else
<div id="supFood" style="display: none;">
<center><strong>Consume food during this task</strong></center>
<blockquote style="text-align: center;">
<hr size="1" />
	Maximum wellness to recover: <span id="maxWN"></span> wellness<br />
	You will recover <span id="wn_count">0</span> wellness.<br />
@for ($i = 1; $i <= 5; $i++)
<div style="display: inline-block; width: 100px; height: 120px; margin: 5px 0; border: 1px solid; border-radius: 5px; text-align: center">
<img src="/images/icons/food.png" /><br />
<img src="/images/game/{{ $i }}_star.gif" /><br />
<input type="text" id="am_{{ $i }}" name="am[{{ $i }}]" size="2" maxlength="2" onkeyup="getChange({{ $i }})" value="{{ $foods[$i] ? '0' : '--' }}" {{ $foods[$i] ? '' : 'disabled' }} style="text-align: center;{{ $foods[$i] ? '' : 'border: 1px solid; background: #ddd' }}" />
</div>
@endfor
</blockquote>
<hr size="1">
</div>
<center>
<input type="submit" name="subwork" class="submit-blue-1" value="Train!" onclick="javascript:return checkTask();" />
</center>
</form>
<script type="text/javascript">
var actOpt = ''; var wnRed = 0; var totFoods = 0;
var wStats = {Weights: {{ $wChange[$W] }}, Cardio: {{ $wChange[$C] }}};
var maxF = new Array(6); var foods = new Array(6);
function checkTask() {
	if (!actOpt) { alert('Choose a session'); return false; }
	else if (totFoods > wnRed) { return confirm('You will waste your foods with this selection. Are you sure you want to do so?'); }
}
@for ($i = 1; $i <= 5; $i++)
maxF[{{ $i }}] = {{ $foods[$i] }};
@endfor
function addStats(wType){
	$("#maxWN").html(-wStats[wType]);
	if (!wnRed) $("#supFood").slideDown(500);
	wnRed = -wStats[wType];
}
function getChange(id) {
	totFoods = 0;
	for (i=1;i<6;i++) {
		flag = document.forms["trainform"].elements["am["+i+"]"];
		if (flag.disabled) continue;
		if (flag.value > maxF[i]){ flag.value = maxF[i]; flag.focus(); flag.select(); }
		if (flag.value != parseInt(flag.value)){ flag.value = 0; flag.focus(); flag.select(); }
		foods[i] = flag.value; totFoods += foods[i] * i;
	}
	if (totFoods > wnRed) $("#wn_count").html("<font color='red'>"+totFoods+"</font>"); else $("#wn_count").html(totFoods);
}
$(document).ready(function(){
	$(".taskbuts").click(function(){
		var cID = $(this).attr("id");
		document.getElementById('t'+cID).checked = true;
		if (actOpt) $("div#"+actOpt).css("border", "3px solid");
		$("div#"+cID).css("border", "3px solid #CC3333");
		actOpt = cID; addStats(cID);
		for (i=1;i<6;i++) { flag = document.forms["trainform"].elements["am["+i+"]"]; if (!flag.disabled) flag.value = 0; foods[i] = 0; }
		totFoods = 0; $("#wn_count").html("0");
	});
});
</script>
@endif
@endsection
