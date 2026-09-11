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
		<div class="gym-go">
			<button type="submit" name="subwork" class="gym-train" onclick="return checkTask();" {{ $lowWellness ? 'disabled' : '' }}>⚡ Train</button>
		</div>
	</form>
<script type="text/javascript">
var actOpt = '';
function checkTask() { if (!actOpt) { alert('Choose a session'); return false; } }
$(document).ready(function(){
	$(".gym-card").click(function(){
		var cID = $(this).attr("id");
		document.getElementById('t'+cID).checked = true;
		$(".gym-card").removeClass("sel"); $(this).addClass("sel");
		actOpt = cID;
	});
});
</script>
@endif

</div>
@endsection
