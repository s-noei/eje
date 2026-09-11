@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/gym.css">@endpush
@php
	use App\Game\Support\Constants;
	$a = fn($k) => $lang->getstr($k, 'army');
	$W = Constants::TRAIN_WEIGHTS; $C = Constants::TRAIN_CARDIO;
	$lowWellness = $cit['wellness'] <= Constants::TRAIN_WELLNESS[$C];
	$avatar = $trainednow ? 'avatar-victory' : ($trained ? 'avatar-rest' : ($lowWellness ? 'avatar-tired' : 'shape-'.$shape['stage']));
	$meter = function (int $v, string $cls) {
		$out = '<div class="gym-meter">';
		for ($i = 1; $i <= Constants::SHAPE_MAX; $i++) $out .= '<span class="'.($i <= $v ? $cls : 'off').'"></span>';
		return $out.'</div>';
	};
@endphp
{!! $msg ?? '' !!}
<div id="gym">
	<div class="gym-scene">
		<div class="gym-hud">
			<div class="gym-name">{{ $shape['name'] }}</div>
			<div class="gym-streak">{{ $shape['streak'] > 0 ? '🔥' : '🌫️' }} Day {{ min($shape['streak'], Constants::SHAPE_MAX) }} of {{ Constants::SHAPE_MAX }}{{ $shape['streak'] > Constants::SHAPE_MAX ? ' · '.$shape['streak'].' days in a row' : '' }}</div>
		</div>
		<img class="gym-avatar" src="/images/game/gym/{{ $avatar }}.png" alt="">
		<div class="gym-stats">
			<div class="gym-stat"><span class="gym-ico">🏋️</span><b>Strength</b> {!! $meter($shape['strength'], 'str') !!}<span class="gym-val">{{ $shape['strength'] }}/{{ Constants::SHAPE_MAX }}</span><span class="gym-eff">hit {{ $shape['damage'] }}</span></div>
			<div class="gym-stat"><span class="gym-ico">💓</span><b>Stamina</b> {!! $meter($shape['stamina'], 'sta') !!}<span class="gym-val">{{ $shape['stamina'] }}/{{ Constants::SHAPE_MAX }}</span><span class="gym-eff">fight {{ $shape['fightCost'] }} wellness</span></div>
		</div>
		<div class="gym-banner">
@if ($trainednow)
			💪 Session done! Come back tomorrow to keep your shape.
@elseif ($trained)
			💤 You already trained today — come back tomorrow.
@elseif ($lowWellness)
			🥵 Too tired to train — eat or drink something first.
@else
			Pick today's session. Every day adds a stage, every missed day takes one away.
@endif
		</div>
	</div>

@if ($trained)
	@if ($report)@include('pages.war.train-report')@endif
@else
	<form name="trainform" action="" method="post">
		@csrf
		<div class="gym-sessions">
			<label class="gym-card" id="Weights">
				<input type="radio" id="tWeights" name="train" value="{{ $W }}">
				<img src="/images/game/gym/session-weights.png" alt="">
				<div class="gym-card-title">🏋️ Weights</div>
				<div class="gym-card-eff">{{ $shape['strength'] >= Constants::SHAPE_MAX ? 'keeps Strength at max' : '+1 Strength' }}</div>
				<div class="gym-card-sub">Hit harder · {{ $wChange[$W] }} wellness</div>
			</label>
			<label class="gym-card" id="Cardio">
				<input type="radio" id="tCardio" name="train" value="{{ $C }}">
				<img src="/images/game/gym/session-cardio.png" alt="">
				<div class="gym-card-title">🏃 Cardio</div>
				<div class="gym-card-eff">{{ $shape['stamina'] >= Constants::SHAPE_MAX ? 'keeps Stamina at max' : '+1 Stamina' }}</div>
				<div class="gym-card-sub">Cheaper fights · {{ $wChange[$C] }} wellness</div>
			</label>
		</div>
		<div id="supFood" class="gym-food" style="display: none;">
			<b>🍔 Eat during the session</b> — recover up to <span id="maxWN"></span> wellness (you will recover <span id="wn_count">0</span>)
			<div class="gym-foods">
@for ($i = 1; $i <= 5; $i++)
				<div class="gym-foodbox">
					<img src="/images/icons/food.png" width="36"><br>
					<img src="/images/game/{{ $i }}_star.gif" width="50"><br>
					<input type="text" id="am_{{ $i }}" name="am[{{ $i }}]" size="2" maxlength="2" onkeyup="getChange({{ $i }})" value="{{ $foods[$i] ? '0' : '--' }}" {{ $foods[$i] ? '' : 'disabled' }}>
					<div class="gym-foodcount">×{{ $foods[$i] }}</div>
				</div>
@endfor
			</div>
		</div>
		<div class="gym-go">
			<button type="submit" name="subwork" class="gym-train" onclick="return checkTask();" {{ $lowWellness ? 'disabled' : '' }}>⚡ Train</button>
		</div>
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
	$(".gym-card").click(function(){
		var cID = $(this).attr("id");
		document.getElementById('t'+cID).checked = true;
		$(".gym-card").removeClass("sel"); $(this).addClass("sel");
		actOpt = cID; $("#maxWN").html(-wStats[cID]); wnRed = -wStats[cID]; $("#supFood").slideDown(300);
		for (i=1;i<6;i++) { flag = document.forms["trainform"].elements["am["+i+"]"]; if (!flag.disabled) flag.value = 0; foods[i] = 0; }
		totFoods = 0; $("#wn_count").html("0");
	});
});
</script>
@endif

	<div class="gym-rank">
		<div class="gym-rank-head">
			<img src="/images/game/war/mrank/{{ $cit['mRank'] }}.gif" width="60" align="absmiddle" title="{{ Constants::MILI_RANKS[$cit['mRank']] ?? '' }}">
			<b>{{ Constants::MILI_RANKS[$cit['mRank']] ?? '' }}</b> &nbsp;·&nbsp; total advance {{ $cit['total_damage'] }} / {{ Constants::RANK_DAMAGES[$cit['mRank'] + 1] ?? '—' }}
			<a href="{{ $vars->getURL('wars') }}" class="button-blue-1" style="float: right">{!! $a('army_active_wars') !!}</a>
		</div>
@php $rFrom = Constants::RANK_DAMAGES[$cit['mRank']] ?? 0; $rTo = Constants::RANK_DAMAGES[$cit['mRank'] + 1] ?? $rFrom; $rPct = $rTo > $rFrom ? max(0, min(100, ($cit['total_damage'] - $rFrom) / ($rTo - $rFrom) * 100)) : 100; @endphp
		<div class="gym-rank-bar"><div class="gym-rank-fill" style="width: {{ round($rPct) }}%"></div></div>
		<div class="gym-rank-note">🏆 Rank is prestige: it lets you lead a military unit and sets your unit's battle bonus. Next rank rewards {{ Constants::RANK_UP_TALA * ($cit['mRank'] + 1) }} Tala + a 5-star food.</div>
	</div>
</div>
<hr>
<center>
	<b>{!! $a('army_active_battles') !!}</b>
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
@endsection
