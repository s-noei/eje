@extends('layouts.game')
@section('content')
@php $fl = fn($f) => $vars->getImgLoc('CountryFlag') . $f . '.gif'; @endphp
<script>
	var actDiv = '';
	$(document).ready(function(){
			$("#country").click(function(){ $("div #types, div #statuses").hide(); $("div #countries").fadeIn('500'); });
			$("#status").click(function(){ $("div #types, div #countries").hide(); $("div #statuses").fadeIn('500'); });
			$("#type").click(function(){ $("div #countries, div #statuses").hide(); $("div #types").fadeIn('500'); });
		});
	function openAllies(divID) {
		if (actDiv != '') $("#"+actDiv).slideUp('fast');
		if (actDiv == divID) actDiv = ''; else { actDiv = divID; $("#"+divID).slideDown('fast') }
	}
</script>
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'country', 'head' => 'Involved', 'img' => $coun ? $fl($coun['Flag']) : $vars->getImgLoc('CountryFlag').'l/world.gif', 'alt' => $coun['cName'] ?? 'World'])
			@include('partials.filter-what', ['id' => 'status', 'head' => 'Status', 'img' => '/images/game/war/filter/act-'.$act.'.gif'])
			@include('partials.filter-what', ['id' => 'type', 'head' => 'Type', 'img' => '/images/game/war/filter/type-'.$type.'.gif'])
		</div>
		<div style="clear: both"></div>
	<div id="countries" class="selectbox">
		<b>Country</b>
		<hr>
				<div class="box-element">
					<a href="{{ $vars->getURL('wars', '0', $act, $type, $page) }}"><img src="{{ $vars->getImgLoc('CountryFlag') . 'l/world.gif' }}" class="Flag-ms" alt="World" align="absmiddle"> World</a>
				</div>
@foreach ($countries as $rCoun)
				<div class="box-element">
					<a href="{{ $vars->getURL('wars', $rCoun['CountryID'], $act, $type, $page) }}"><img src="{{ $fl($rCoun['Flag']) }}" class="Flag-ms" alt="{{ $rCoun['cName'] }}" align="absmiddle"> {{ $rCoun['cName'] }}</a>
				</div>
@endforeach
		<div style="clear: both"></div>
	</div>
	<div id="statuses" class="selectbox">
		<b>Status</b>
		<hr>
@foreach ([['act', 'Active wars'], ['end', 'Ended wars'], ['all', 'All wars']] as [$k, $v])
		<div class="box-element">
			<a href="{{ $vars->getURL('wars', $inv, $k, $type, $page) }}"><img src="/images/game/war/filter/act-{{ $k }}.gif" class="inlineIMGs" width="32" align="absmiddle"> {{ $v }}</a>
		</div>
@endforeach
		<div style="clear: both"></div>
	</div>
	<div id="types" class="selectbox">
		<b>Type</b>
		<hr>
@foreach ([['war', 'Wars'], ['rev', 'Revolts'], ['all', 'All wars']] as [$k, $v])
		<div class="box-element">
			<a href="{{ $vars->getURL('wars', $inv, $act, $k, $page) }}"><img src="/images/game/war/filter/type-{{ $k }}.gif" class="inlineIMGs" width="32" align="absmiddle"> {{ $v }}</a>
		</div>
@endforeach
		<div style="clear: both"></div>
	</div>
<div id="warstable">
	<div class="flag-att">&nbsp;</div>
	<div class="name-att">Attacker</div>
	<div class="versus">VS</div>
	<div class="name-def">Defender</div>
	<div class="flag-def">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
	<div style="text-align: center">There are no wars here</div>
@endif
@foreach ($rows as $rWar)
@if ($rWar['Type'] != 'revolt')
	<div class="flag-att"><img src="{{ $fl($rWar['attFlag']) }}" alt="Attacker" style="width: 60px; height: 50px"></div>
	<div class="name-att">
		<a href="{{ $vars->getURL('country', $rWar['Attacker']) }}">{{ $rWar['attName'] }}</a>
@if (count($rWar['alliesAtt']))
		<br>
		<font style="font-size: 8pt"><a class="ally_link" href="javascript:void(0)" onclick="openAllies('a{{ $rWar['warID'] }}')">+ {{ count($rWar['alliesAtt']) . (count($rWar['alliesAtt']) == 1 ? ' ally' : ' allies') }}</a></font>
		<div class="ally_list" id="a{{ $rWar['warID'] }}" style="position: absolute; border: 1px lime solid; width: 150px; font-size: 8pt; background: white; display: none">
@foreach ($rWar['alliesAtt'] as $aly)
			<img src="{{ $fl($aly['Flag']) }}" class="Flag-xs" align="absmiddle"> <a href="{{ $vars->getURL('country', $aly['CountryID']) }}">{{ $aly['cName'] }}</a><br>
@endforeach
		</div>
@endif
	</div>
@else
	<div class="flag-att"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $rWar['StarterAvatar'] }}" alt="Revolt" title="Revolt" style="width: 60px; height: 60px"></div>
	<div class="name-att">
		Revolt in <b><a href="{{ $vars->getURL('region', $rWar['revoltReg']['RegionID'] ?? 0) }}">{{ $rWar['revoltReg']['rName'] ?? '' }}</a></b>
		<br>
		<font size="1">Started by <a href="{{ $vars->getURL('profile', $rWar['StarterID']) }}">{{ $rWar['Starter'] }}</a></font>
	</div>
@endif
	<div class="versus"><a href="{{ $vars->getURL('war', $rWar['warID']) }}"><img src="/images/game/war/versus.gif" style="border: 0"></a></div>
	<div class="name-def">
		<a href="{{ $vars->getURL('country', $rWar['Defender']) }}">{{ $rWar['defName'] }}</a>
@if (count($rWar['alliesDef']))
		<br>
		<font style="font-size: 8pt"><a class="ally_link" href="javascript:void(0)" onclick="openAllies('d{{ $rWar['warID'] }}')">+ {{ count($rWar['alliesDef']) . (count($rWar['alliesDef']) == 1 ? ' ally' : ' allies') }}</a></font>
		<div class="ally_list" id="d{{ $rWar['warID'] }}" style="position: absolute; border: 1px lime solid; width: 150px; font-size: 8pt; background: white; display: none">
@foreach ($rWar['alliesDef'] as $aly)
			<a href="{{ $vars->getURL('country', $aly['CountryID']) }}">{{ $aly['cName'] }}</a> <img src="{{ $fl($aly['Flag']) }}" class="Flag-xs" align="absmiddle"><br>
@endforeach
		</div>
@endif
	</div>
	<div class="flag-def"><img src="{{ $fl($rWar['defFlag']) }}" style="width: 60px; height: 50px"></div>
	<div style="clear: both"></div>
	<hr>
@endforeach
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('wars', $inv, $act, $type, $page - 1) }}" id="buttons">&lt; Back</a>
@endif
	<span id="buttons">{{ $page }}</span>
@if ($num > $start + 10)
		<a href="{{ $vars->getURL('wars', $inv, $act, $type, $page + 1) }}" id="buttons">Next &gt;</a>
@endif
</center>
</div>
@endsection
