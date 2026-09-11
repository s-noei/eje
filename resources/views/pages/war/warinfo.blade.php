@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/battle.css">@endpush
@php $fl = fn($f) => $vars->getImgLoc('CountryFlag') . $f . '.gif'; @endphp
<script language="javascript">
	function showTime(sTime, target) {
		if (sTime > '0') {
				var lTime = sTime; var dSec = lTime % 60; if (dSec < '10') dSec = '0' + dSec;
				lTime -= dSec; lTime /= 60; var dMin = lTime % 60; if (dMin < '10') dMin = '0' + dMin;
				lTime -= dMin; lTime /= 60; var dHour = lTime; if (dHour < '10') dHour = '0' + dHour;
				dStart = ''; dEnd = '';
				if (dHour == '00' && dMin < '10') { dStart = '<font color="red">'; dEnd = '</font>'; }
				document.getElementById(target).innerHTML = dStart + dHour + ":" + dMin + ":" + dSec + dEnd;
				if (sTime >= '0') setTimeout("showTime('" + (sTime - 1) + "', '" + target + "')", 1000);
			}else{
				var el = document.getElementById(target); if (el) el.innerHTML = '<font color="red">CLOSED</font>';
			}
	}
</script>
<div class="column-double">
<div id="warstable">
{!! $msg ?? '' !!}
	<div class="flag-att"><img src="{{ $fl($rWar['attFlag']) }}" alt="Attacker" style="width: 60px; height: 50px"></div>
	<div class="name-att"><a href="{{ $vars->getURL('country', $rWar['Attacker']) }}">{{ $rWar['attName'] }}</a></div>
	<div class="versus"><a href="{{ $vars->getURL('war', $rWar['warID']) }}"><img src="/images/game/war/versus.png" style="border: 0"></a></div>
	<div class="name-def"><a href="{{ $vars->getURL('country', $rWar['Defender']) }}">{{ $rWar['defName'] }}</a></div>
	<div class="flag-def"><img src="{{ $fl($rWar['defFlag']) }}" style="width: 60px; height: 50px"></div>
	<div style="clear: both"></div>
	<a href="{{ $vars->getURL('wars') }}" class="button-blue-1">Back to war list</a>
	<hr>
	<center>
		{{ date("M jS, Y", $rWar['Start']) }} - {{ $rWar['End'] ? date("M jS, Y", $rWar['End']) : 'Now' }}
		<br>
		{{ $totFights }} fights
@if ($go != 'details')
		<div style="float: left"><a class="button-blue-1" href="{{ $vars->getURL('war', $warID, 'details') }}">War details</a></div>
		<div style="clear: both"></div>
@endif
	</center>
	<hr>
@if ($go == 'finished')
	<div style="float: right"><a href="{{ $vars->getURL('war', $warID) }}" id="buttons">Active battles</a></div>
	<div style="clear: both"></div>
<center>
<b>Finished battles</b>
@if (count($battles) < 1)
<hr>
			There is no finished battle
</center>
@else
			<br>{{ count($battles) }} finished battle(s)
			</center><hr>
@foreach ($battles as $rBatt)
<div id="battlestable">
	<div class="region">
		<img src="{{ $fl($rBatt['defFlag']) }}" class="Flag-xs" align="absmiddle">
		<a href="{{ $vars->getURL('region', $rBatt['regionID']) }}">{{ $rBatt['rName'] }}</a>
		<sub>Started on {{ date("M jS, Y", $rBatt['Start']) }}</sub>
	</div>
	<hr>
	<div>
		<a href="{{ $vars->getURL('battle', $rBatt['battleID']) }}" id="buttons">Goto battlefield</a>
		{{ ['conq' => 'Attacker has been conquered this region', 'secu' => 'Defender has been secured this region', 'defreat' => 'Defender has been retreated the battlefield. The region is conquered.', 'attreat' => 'Attacker has been retreated the battlefield. The region is secured.'][$rBatt['Result']] ?? '' }}
	</div>
	<div style="clear: both"></div>
</div>
<hr>
@endforeach
@endif
@elseif ($go == 'details')
	<div style="float: left"><a href="{{ $vars->getURL('war', $warID) }}" id="buttons">Active battles</a></div>
	<div style="clear: both"></div>
<center>
<hr>
<div id="wardets">
<b>Total Force sorted by countries</b>
<hr>
@foreach ($details as $det)
		<div id="war-log">
			<div class="country-flag"><img src="{{ $fl($det['Flag']) }}" class="Flag-s" align="absmiddle"></div>
			<div class="country-name"><a href="{{ $vars->getURL('country', $det['CountryID']) }}">{{ $det['cName'] }}</a></div>
			<div class="country-fights">{{ $det['totFight'] }} fights</div>
			<div class="country-forces">{{ $det['totForce'] }}m</div>
			<div style="clear: both"></div>
			<hr>
		</div>
@endforeach
</div>
</center>
@else
	<div style="float: right"><a href="{{ $vars->getURL('war', $warID, 'finished') }}" class="button-blue-1">Finished battles</a></div>
	<div style="clear: both"></div>
<center>
<b>Active battles</b>
@if (count($battles) < 1)
<hr>
			There is no active battle
@else
			<br>{{ count($battles) }} active battle(s)
			</center><hr>
@foreach ($battles as $rBatt)
<div id="battlestable">
	<div class="region">
		<img src="{{ $fl($rBatt['defFlag']) }}" class="Flag-xs" align="absmiddle">
		<a href="{{ $vars->getURL('region', $rBatt['regionID']) }}">{{ $rBatt['rName'] }}</a>
		<sub>Started {!! $session->getDiff($rBatt['Start']) !!}</sub>
	</div>
	<hr>
	<div class="time" id="left{{ $rBatt['battleID'] }}">FINISHED</div>
	<div class="force">{{ $rBatt['wall'] }}/{{ $rBatt['sPoint'] }}</div>
	<div class="buts"><a href="{{ $vars->getURL('battle', $rBatt['battleID']) }}" id="buttons">Goto battlefield</a></div>
	<div style="clear: both"></div>
<script>
	var dTime{{ $rBatt['battleID'] }} = {{ $rBatt['End'] - time() }};
	showTime(dTime{{ $rBatt['battleID'] }}, 'left{{ $rBatt['battleID'] }}');
</script>
</div>
<hr>
@endforeach
<center>
@endif
{{-- start_battle.php --}}
@if ($seedMode === 'list')
	<hr>
@if ($seedBlocked)
	You cannot start a new seed of war, because your country is involved into a battle as defender.
@elseif (count($seedRegions))
	<blockquote>
		You can start a new seed of war to the following regions:
		<hr>
@foreach ($seedRegions as $reg)
			<div class="battlebox">
				<form action="" name="startA" method="post">
					@csrf
					<div class="regname"><b><a href="{{ $vars->getURL('region', $reg['RegionID']) }}">{{ $reg['rName'] }}</a></b></div>
					<div class="trust">{{ $reg['stat_value'] }} <img src="/images/flags/s/eJahan.gif" align="absmiddle"></div>
					<div class="wall">{{ $reg['wallText'] }}</div>
					<div class="start">
						<input type="hidden" name="regID" value="{{ $reg['RegionID'] }}">
						<input type="hidden" name="Trust" value="{{ $reg['stat_value'] }}">
						<input type="hidden" name="token" value="{{ md5($reg['RegionID'] . $reg['stat_value'] . 'RegTru$t') }}">
						<input type="submit" id="submits" name="substart" value="Attack" onclick="return confirm('Are you sure to attack {{ addslashes($reg['rName']) }}?')">
					</div>
				</form>
			</div>
			<hr>
@endforeach
	</blockquote>
@endif
@elseif ($winDue)
	<hr>
	You can start a new seed of war if no attack occurs from the other side within {!! $session->getDiffF($winDue, '', '') !!}.
@endif
</center>
@endif
</div>
</div>
@endsection
