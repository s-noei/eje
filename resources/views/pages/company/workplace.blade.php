<link rel="stylesheet" type="text/css" href="/include/css/gym.css">
@php
	use App\Game\Support\Constants;
	$w = fn($k) => $lang->getstr($k, 'workplace');
	$S = Constants::WORK_SHIFT; $T = Constants::WORK_STUDY;
	$worked = $cit['LastWorked'] >= $database->today;
	$lowWellness = $cit['wellness'] <= Constants::workWellnessCost((int) $row['Stars'], (int) ($cit['efficiency'] ?? 0), $T);
	$art = fn($name, $fallback) => file_exists(public_path("images/game/work/$name.png")) ? "/images/game/work/$name.png" : $fallback;
	$avatar = $nowWorked ? $art('avatar-done', '/images/game/gym/avatar-victory.png')
		: ($worked ? $art('avatar-rest', '/images/game/gym/avatar-rest.png')
		: ($lowWellness ? $art('avatar-tired', '/images/game/gym/avatar-tired.png') : $art('craft-'.$craft['stage'], '/images/game/gym/shape-'.$craft['stage'].'.png')));
	$bg = file_exists(public_path('images/game/work/work-bg.jpg')) ? '/images/game/work/work-bg.jpg' : null;
	$meter = function (int $v, string $cls) {
		$out = '<div class="gym-meter">';
		for ($i = 1; $i <= Constants::SHAPE_MAX; $i++) $out .= '<span class="'.($i <= $v ? $cls : 'off').'"></span>';
		return $out.'</div>';
	};
	$shift = $sessions[0]; $study = $sessions[1];
@endphp
<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
<br>
{!! $view_info ?? '' !!}
<div id="gym" class="workshop">
	<div class="gym-scene" @if ($bg) style="background-image: url('{{ $bg }}')" @endif>
		<div class="gym-hud">
			<div class="gym-name">{{ $craft['name'] }}</div>
			<div class="gym-streak">{{ $craft['streak'] > 0 ? '🔥' : '🌫️' }} Day {{ min($craft['streak'], Constants::SHAPE_MAX) }} of {{ Constants::SHAPE_MAX }}{{ $craft['streak'] > Constants::SHAPE_MAX ? ' · '.$craft['streak'].' days in a row' : '' }}</div>
			<div class="gym-company"><img src="/uploads/avatars/company/{{ $row['Avatar'] }}" width="22" align="absmiddle"> {{ $row['Name'] }} · {{ $database->getIndustry($row['IndustryID']) }} <img src="/images/game/{{ $row['Stars'] }}_star.gif" align="absmiddle"></div>
		</div>
		<img class="gym-avatar" src="{{ $avatar }}" alt="">
		<div class="gym-stats">
			<div class="gym-stat"><span class="gym-ico">🛠️</span><b>Craft</b> {!! $meter($craft['craft'], 'str') !!}<span class="gym-val">{{ $craft['craft'] }}/{{ Constants::SHAPE_MAX }}</span><span class="gym-eff">×{{ $craft['factor'] }} output</span></div>
			<div class="gym-stat"><span class="gym-ico">⚡</span><b>Efficiency</b> {!! $meter($craft['efficiency'], 'sta') !!}<span class="gym-val">{{ $craft['efficiency'] }}/{{ Constants::SHAPE_MAX }}</span><span class="gym-eff">shift costs {{ -$shift['wellness'] }} wellness</span></div>
		</div>
		<div class="gym-banner">
@if ($nowWorked)
			🧾 Shift done! Come back tomorrow to keep your craft.
@elseif ($worked)
			💤 You already worked today — come back tomorrow.
@elseif ($lowWellness)
			🥵 Too tired to work — eat or drink something first.
@else
			Pick today's session. Every day adds a stage, every missed day takes one away.
@endif
		</div>
	</div>

@if ($worked)
	@if ($report)@include('pages.company.work-report')@endif
@else
	<form name="workform" action="" method="post">
		@csrf
		<input type="hidden" name="token" value="{{ md5($citInfo['CitizenID'] . $row['CompanyID'] . 'key4 w0rkIng') }}">
		<div class="gym-sessions">
			<label class="gym-card" id="Shift">
				<input type="radio" id="wShift" name="work" value="{{ $S }}">
				@if (file_exists(public_path('images/game/work/session-shift.png')))<img src="/images/game/work/session-shift.png" alt="">@else<div class="gym-card-emoji">🏭</div>@endif
				<div class="gym-card-title">🏭 Shift</div>
				<div class="gym-card-eff">{{ $craft['craft'] >= Constants::SHAPE_MAX ? 'keeps Craft at max' : '+1 Craft' }}</div>
				<div class="gym-card-sub">{{ $shift['production'] }} products · {{ $shift['salary'] }} {{ $sCur }} · {{ $shift['wellness'] }} wellness</div>
			</label>
			<label class="gym-card" id="Study">
				<input type="radio" id="wStudy" name="work" value="{{ $T }}">
				@if (file_exists(public_path('images/game/work/session-study.png')))<img src="/images/game/work/session-study.png" alt="">@else<div class="gym-card-emoji">📚</div>@endif
				<div class="gym-card-title">📚 Study</div>
				<div class="gym-card-eff">{{ $craft['efficiency'] >= Constants::SHAPE_MAX ? 'keeps Efficiency at max' : '+1 Efficiency' }}</div>
				<div class="gym-card-sub">{{ $study['production'] }} products · {{ $study['salary'] }} {{ $sCur }} · {{ $study['wellness'] }} wellness</div>
			</label>
		</div>
		<div class="gym-go">
			<button type="submit" name="subwork" class="gym-train" onclick="return checkWork();" {{ $lowWellness ? 'disabled' : '' }}>🧾 Work</button>
		</div>
	</form>
<script type="text/javascript">
var actOpt = '';
function checkWork() { if (!actOpt) { alert('Choose a session'); return false; } }
$(document).ready(function(){
	$(".gym-card").click(function(){
		var cID = $(this).attr("id");
		document.getElementById('w'+cID).checked = true;
		$(".gym-card").removeClass("sel"); $(this).addClass("sel");
		actOpt = cID;
	});
});
</script>
@endif
</div>
