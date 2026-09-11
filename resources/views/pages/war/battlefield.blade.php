@extends('layouts.game')
@section('content')
@push('styles')
<link rel="stylesheet" type="text/css" href="/include/css/battle.css" media="screen">
<link rel="stylesheet" type="text/css" href="/include/css/fight.css">
@endpush
<script language="javascript" src="/include/js/wpack.js"></script>
<script language="javascript" src="/include/js/war/viewbattle.js"></script>
<script>
	$(document).ready(function(){ $(".ally_link").click(function(){ $('.ally_list').slideToggle('fast'); }); });
</script>
@php
	$fl = fn($f) => $vars->getImgLoc('CountryFlag') . $f . '.gif';
	$status = in_array($rWar['Result'], ['conq', 'defreat']) ? 1 : (in_array($rWar['Result'], ['secu', 'attreat']) ? 2 : 0);
@endphp
<div class="column-double">
<div id="battletable">
			<script>
				var battleID = {{ $rWar['battleID'] }};
				var defForce = {{ $rWar['wall'] }};
				var secPoint = {{ $rWar['sPoint'] }};
				var canView  = {{ $rWar['Result'] ? 'false' : 'true' }};
				var canFight = {{ ($logged && ($citInfo['wellness'] ?? 0) >= 20) ? '1' : '0' }};
				var Phase = {{ $rWar['phase'] }};
				var Status = {{ $status }};
				dueTime = {{ $rWar['End'] }};
			</script>
@if ($rWar['Type'] != 'revolt')
					<div class="flag-att"><img src="{{ $fl($rWar['attFlag']) }}" alt="Attacker" style="width: 60px; height: 50px"></div>
					<div class="name-att">
						<a href="{{ $vars->getURL('country', $rWar['Attacker']) }}">{{ $rWar['attName'] }}</a>
@if (count($alliesAtt))
								<br>
								<font style="font-size: 8pt"><a class="ally_link" href="javascript:void(0)">+ {{ count($alliesAtt) . (count($alliesAtt) == 1 ? ' ally' : ' allies') }}</a></font>
								<div class="ally_list" style="position: absolute; border: 1px white solid; width: 150px; font-size: 8pt; background: black; display: none">
@foreach ($alliesAtt as $aly)
										<img src="{{ $fl($aly['Flag']) }}" class="Flag-xs" align="absmiddle">
										<a href="{{ $vars->getURL('country', $aly['CountryID']) }}">{{ $aly['cName'] }}</a><br>
@endforeach
								</div>
@endif
					</div>
@else
					<div class="flag-att"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $rWar['StarterAvatar'] }}" alt="Revolt" title="Revolt" style="width: 60px; height: 60px"></div>
					<div class="name-att">
						Revolt
						<font size="1">By <a href="{{ $vars->getURL('profile', $rWar['StarterID']) }}">{{ $rWar['Starter'] }}</a></font>
					</div>
@endif
			<div class="versus">
				<img src="/images/game/war/versus.png" style="border: 0"><br>
				<strong><a href="{{ $vars->getURL('region', $rWar['regionID']) }}">{{ $rWar['rName'] }}</a></strong>
			</div>
			<div class="name-def">
				<a href="{{ $vars->getURL('country', $rWar['defID']) }}">{{ $rWar['defName'] }}</a>
@if (count($alliesDef))
						<br>
						<font style="font-size: 8pt"><a class="ally_link" href="javascript:void(0)">+ {{ count($alliesDef) . (count($alliesDef) == 1 ? ' ally' : ' allies') }}</a></font>
						<div class="ally_list" style="position: absolute; border: 1px white solid; width: 150px; font-size: 8pt; background: black; display: none">
@foreach ($alliesDef as $aly)
								<a href="{{ $vars->getURL('country', $aly['CountryID']) }}">{{ $aly['cName'] }}</a>
								<img src="{{ $fl($aly['Flag']) }}" class="Flag-xs" align="absmiddle"><br>
@endforeach
						</div>
@endif
				</div>
				<div class="flag-def"><img src="{{ $fl($rWar['defFlag']) }}" style="width: 60px; height: 50px"></div>
				<div style="clear: both"></div>
				<div style="float: left">
@if ($rWar['Type'] != 'revolt')
					<a href="{{ $vars->getURL('war', $rWar['warID']) }}" id="buttons">Back to war info</a>
@else
					<a href="{{ $vars->getURL('wars') }}" id="buttons">Back to war list</a>
@endif
				</div>
@if ($canRetreat)
					<div style="float: right">
						<form action="" method="post" name="retreat">
							@csrf
							<input type="hidden" name="subretreat" value="1">
							<a href="javascript:void(0)" class="cmdRemove" onclick="if (confirm('Are you sure you want to retreat from battlefield? Retreating will cost 25 Tala')) document.retreat.submit()">Retreat from battlefield</a>
						</form>
					</div>
@endif
				<div style="clear: both"></div>
{{-- battle_view.php --}}
@if (!$rWar['Result'])
<script language="javascript">
	function showTime(nowt, target) {
		sTime = battlevars.dueTime - nowt;
		target2 = 'timer'+batID;
		if (sTime > '0') {
				var lTime = sTime; var dSec = lTime % 60; if (dSec < '10') dSec = '0' + dSec;
				lTime -= dSec; lTime /= 60; var dMin = lTime % 60; if (dMin < '10') dMin = '0' + dMin;
				lTime -= dMin; lTime /= 60; var dHour = lTime; if (dHour < '10') dHour = '0' + dHour;
				dStart = ''; dEnd = '';
				if (dHour == '00' && dMin < '10') { dStart = '<font color="red">'; dEnd = '</font>'; }
				document.getElementById(target).innerHTML = '';
				document.getElementById(target2).innerHTML = dStart + dHour + ":" + dMin + ":" + dSec + dEnd;
				if (sTime == '0') document.getElementById('showTime').innerHTML = dStart + 'CLOSED' + dEnd;
				if (sTime >= '0') { nowt++; setTimeout("showTime('" + nowt + "', '" + target2 + "')", 1000); }
			}else{
				document.getElementById(target).innerHTML = '<font color="red">CLOSED</font>';
			}
	}
</script>
@endif
<div class="battleView" style="display: none">
<center>
@if (!$rWar['Result'])
<div class="battle-timer-container">
		<script>
			var batID = {{ $rWar['battleID'] }};
            var citID = {{ (int) ($citInfo['CitizenID'] ?? 0) }};
			var dTime{{ $rWar['battleID'] }} = {{ $rWar['phase'] > 1 ? time() : (time() + 48*3600) }};
			$(document).ready(function(){ showTime(dTime{{ $rWar['battleID'] }}, 'timer{{ $rWar['battleID'] }}'); });
		</script>
</div>
<div style="clear: both"></div>
	<div class="battle-time" id="left{{ $rWar['battleID'] }}">
        <span id="phase{{ $rWar['battleID'] }}" class="battle-phase">Phase {{ $rWar['phase'] }}</span>
        <span class="timer-ind" id="timer{{ $rWar['battleID'] }}">00:00:00</span>
    </div>
    <div class="battle-field">
		<div class="battle-ind">
			<span style="float:  left;">{{ $rWar['sPoint'] }}</span>
			<span style="float:  right;">{{ $rWar['extra'] }}</span>
			<span id="wall-remain">&nbsp;</span>
		</div>
		<div class="battle-occupied" style="width: 215px"></div>
	</div>
    <div class="fighters">
		<div id="fighters-att">
			<div id="fighter-att-temp" class="fighter-hold" style="display: none">
				<div class="fighter-att" style="display: none">
					<div class="fighter-img"><img src="/uploads/avatars/citizen/noavatar.gif"></div>
					<div class="fighter-name">fighter</div>
					<div class="fighter-force">220</div>
				</div>
			</div>
		</div>
		<div id="fighters-def">
			<div id="fighter-def-temp" class="fighter-hold" style="display: none">
				<div class="fighter-def" style="display: none">
					<div class="fighter-img"><img src="/uploads/avatars/citizen/noavatar.gif"></div>
					<div class="fighter-name">fighter</div>
					<div class="fighter-force">220</div>
				</div>
			</div>
		</div>
    </div>
@endif
</center>
<hr>
{{-- fight_arena.php --}}
<script type="text/javascript" src="/include/js/clinic.js"></script>
<script type="text/javascript">
    CliToken = '{{ $cliToken }}';
    var canclickC = {{ ($logged && ($citInfo['LastDayClinic'] ?? 0) < $maxcli) ? 1 : 0 }};
</script>
<center>
	<div class="fight-arena">
		<div class="heroes-att">
			<div style="color: red; margin-bottom: 2px; font-weight: bold"><font face="Comic Sans ms">HEROES</font></div>
@foreach ([1 => '13pt', 2 => '11pt', 3 => '9pt'] as $h => $fs)
			<div class="hero-{{ $h }}" style="{{ $h > 1 ? 'margin-top: 8px; ' : '' }}display: none; font-size: {{ $fs }}">
				<img src="/uploads/avatars/citizen/noavatar.gif" id="attHeroAvatar-{{ $h }}" class="hero{{ $h }}"><br>
				<a href="#" id="attHeroName-{{ $h }}">hero{{ $h }}</a>
				<div class="force">-100</div>
			</div>
@endforeach
		</div>
        <div class="fight-area">
@include('pages.war.fight-report')
@include('pages.war.bat-stats')
@if ($rWar['Result'])
	@include('pages.war.fight-area-end')
@elseif ($errbat)
			<br><br><br>
			<h3 class="errHandle">{{ $errbat }}</h3>
@elseif ($rWar['Type'] == 'revolt' && !$for)
			<br>
			<hr>
			<blockquote>
			    <div style="float: left;"><a href="{{ $vars->getURL('battle', $rWar['battleID'], 'att') }}" class="button-blue-1">Join the revolt force</a></div>
			    <div style="float: right;"><a href="{{ $vars->getURL('battle', $rWar['battleID'], 'def') }}" class="button-blue-1">Join country</a></div>
			    <div style="clear:  both;"></div>
			</blockquote>
			<hr>
			<br><br><br><br><br><br><br><br><br>
@else
	@include('pages.war.fight-area')
@endif
<div class="battle-stats">
	<a href="javascript:void(0)" id="batStats"><img src="/images/game/war/stats.png" alt="Stats" title="Battle Statistics"></a>
</div>
        </div>
		<div class="heroes-def">
			<div style="color: green; margin-bottom: 2px; font-weight: bold"><font face="Comic Sans ms">HEROES</font></div>
@foreach ([1 => '13pt', 2 => '12pt', 3 => '11pt'] as $h => $fs)
			<div class="hero-{{ $h }}" style="{{ $h > 1 ? 'margin-top: 8px; ' : '' }}display: none; font-size: {{ $fs }}">
				<img src="/uploads/avatars/citizen/noavatar.gif" id="defHeroAvatar-{{ $h }}" class="hero{{ $h == 1 ? 1 : 3 }}"><br>
				<a href="#" id="defHeroName-{{ $h }}">hero{{ $h }}</a>
				<div class="force">-100</div>
			</div>
@endforeach
		</div>
	</div>
<div style="clear: both; height: 3px"></div>
</center>
@if ($canRage)
@include('pages.war.fightmenu')
@endif
</div>
</div>
</div>
@endsection
