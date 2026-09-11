@extends('layouts.game')
@section('content')
<script>
	var setshow = 0;
	function setShow() { setshow = 1 - setshow; }
	$(document).ready(function(){
			$("#select_country").click(function(){
					if (setshow) $("div #countries").fadeOut('500'); else $("div #countries").fadeIn('500');
					setShow();
				});
		});
</script>
@foreach ($errors as $e)
<h3 class="errHandle">{!! $e !!}</h3>
@endforeach
@foreach ($info ?? [] as $e)
<h3 class="infHandle">{!! $e !!}</h3>
@endforeach
<div class="column-double">
	<div id="country" class="column-headcol">
		<img class="Flag-xl" src="{{ $vars->getImgLoc('CountryAFlag') . '/' . $row['Flag'] }}.gif" align="absmiddle" style="border: 2px solid green; border-radius: 5px">
		<a class="button-blue-0" id="select_country" href="javascript:void(0)">{{ $lang->getstr('country_select', 'countryinfo') }}</a>
	</div>
	<div class="column-details">
		<div class="column-name">
			<a href="{{ $vars->getURL('country', $row['CountryID']) }}">{{ ($row['Prefix'] ? $row['Prefix'] . ' of ' : '') . $row['cName'] }}</a>
			@if ($row['Prefix'])<sup style="font-size: 8pt"><i>({{ $lang->getstr($row['shortName'], 'country') }})</i></sup>@endif
			<font size="1">{{ $lang->getstr('country_rank', 'countryinfo') }}: {{ $vars->formatnumbers($rank) }}</font>
			{!! $vars->getWikiLink('Country', $row['cName']) !!}
			<a href="javascript:void(0)" onclick="javascript:window.open('/map-{{ $row['shortName'] }}.html','map_ejahan','status=yes,scrollbars=yes,toolbar=no,menubar=no,location=no ,width=520px,height=500px')">
				<img src="/images/onmap.png" alt="On the map" align="absmiddle" style="border: 0">
			</a>
		</div>
			<h3><font size=4>{{ $lang->getstr('country_navigation', 'countryinfo') }}</font></h3><hr>
				<center>
					<a href="{{ $vars->getURL('country', $row['CountryID'], 'society') }}" class="button-blue-0">{{ $lang->getstr('country_society', 'countryinfo') }}</a>
					<a href="{{ $vars->getURL('country', $row['CountryID'], 'economy') }}" class="button-blue-0">{{ $lang->getstr('country_economy', 'countryinfo') }}</a>
					<a href="{{ $vars->getURL('country', $row['CountryID'], 'politics') }}" class="button-blue-0">{{ $lang->getstr('country_policy', 'countryinfo') }}</a>
					<a href="{{ $vars->getURL('country', $row['CountryID'], 'military') }}" class="button-blue-0">{{ $lang->getstr('country_military', 'countryinfo') }}</a>
					<a href="{{ $vars->getURL('congress', $row['CountryID']) }}" class="button-blue-0">{{ $lang->getstr('country_congress', 'countryinfo') }}</a>
				</center>
			<hr>
	</div>
	<div style="clear: both"></div>
</div>
		<div id="countries" class="selectbox">
			<b>{{ $lang->getstr('country_select_country', 'countryinfo') }}</b>
			<hr>
@php $go2 = ($go != 'law' && $go != 'region') ? $go : ''; @endphp
@foreach ($countries as $row10)
							<div class="box-element">
								<a href="{{ $vars->getURL('country', $row10['CountryID'], $go2) }}">
							<img src="{{ $vars->getImgLoc('CountryFlag') . $row10['Flag'] }}.gif" class='Flag-s' align=absmiddle>
									{{ $row10['Name'] }}</a>
							</div>
@endforeach
			<div style="clear: both"></div>
		</div>
		<hr>
@if ($go != 'congress' && $go != 'law')
<div class="column-double">
	<div class="column-headcol">
@if ($go != 'region')
			&nbsp;
@else
			<img src="/region-{{ $regionID }}.gif" width="90px" style="border: 1px solid; -moz-border-radius: 5px">
@endif
	</div>
	<div class="column-details">
@else
<div class="column-double">
@endif
@include('pages.country.' . (in_array($go, ['economy','politics','military','congress','requests','region','law']) ? $go : 'society'))
@if ($go != 'congress' && $go != 'law')
	</div>
	<div style="clear: both"></div>
</div>
@else
</div>
@endif
@endsection
