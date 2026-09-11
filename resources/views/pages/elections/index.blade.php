@extends('layouts.game')
@section('content')
<script>
	$(document).ready(function(){
			function showOnly(id) { $("div #parties, div #regionlist, div #dates, div #countries, div #types").hide(); $("div #" + id).fadeIn('500'); }
			$("div #type").click(function(){ showOnly('types'); });
			$("div #party").click(function(){ showOnly('parties'); });
			$("div #date").click(function(){ showOnly('dates'); });
			$("div #country").click(function(){ showOnly('countries'); });
			$("div #region").click(function(){ showOnly('regionlist'); });
		});
</script>
@foreach ($errors as $e)
<h3 class="errHandle">{{ $e }}</h3>
@endforeach
@php $u = fn($w, $c, $p, $y, $m) => $vars->getURL('elections', $w, $c, $p, $y, $m); $dPic = "$year$month"; @endphp
	<div id="tblRanksFLT">
		@include('partials.filter-what', ['id' => 'type', 'head' => 'Type', 'img' => "/images/elections/$what.jpg", 'alt' => $what])
		<div class="spacer"></div>
		@include('partials.filter-what', ['id' => 'country', 'head' => 'Country', 'img' => $flag, 'alt' => $cName])
		<div class="spacer"></div>
@if ($what == 'pp')
		@include('partials.filter-what', ['id' => 'party', 'head' => 'Party', 'img' => $pLogo, 'alt' => ''])
		<div class="spacer"></div>
@elseif ($what == 'cg' && $country)
		@include('partials.filter-what', ['id' => 'region', 'head' => 'Region', 'img' => '/images/elections/select.jpg', 'alt' => ''])
		<div class="spacer"></div>
@endif
		@include('partials.filter-what', ['id' => 'date', 'head' => 'Date', 'img' => "/images/elections/dates/$dPic.jpg", 'alt' => "$year/$month"])
	</div>
	<div style="clear: both">&nbsp;</div>

	<div class="selectbox" id="types">
		<b>Select type</b>
		<hr>
@foreach ([['cg', 'Congress', 'congress'], ['cp', 'Country President', 'country president'], ['pp', 'Party President', 'party president']] as [$k, $v, $alt])
		<div class="box-element-long">
			<a href="{{ $u($k, $country, $party, $year, $month) }}"><img src="/images/elections/{{ $k }}.jpg" class="whatIMG" alt="{{ $alt }}" align="absmiddle"> {{ $v }}</a>
		</div>
@endforeach
		<div style="clear: both"></div>
	</div>
	<div class="selectbox" id="countries">
		<b>Select country</b>
		<hr>
@foreach ($countries as $rCoun)
					<div class="box-element">
						<a href="{{ $u($what == 'select' ? '' : $what, $rCoun['CountryID'], '0', $year, $month) }}">
							<img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] . '.gif' }}" class="Flag-ms" alt="{{ $rCoun['Name'] }}" align="absmiddle">
							{{ $rCoun['Name'] }}
						</a>
					</div>
@endforeach
		<div style="clear: both"></div>
	</div>
	<div class="selectbox" id="dates">
		<b>Select date</b>
		<hr>
@foreach ($dates as $rDate)
@php $date = $session->getTodayArray($rDate['timestamp']); $dp = "{$date['Year']}{$date['Month']}"; @endphp
				<div class="box-element">
					<a href="{{ $u($what == 'select' ? '' : $what, $country, $party, $date['Year'], $date['Month']) }}">
						<img src="/images/elections/dates/{{ $dp }}.jpg" class="whatIMG" alt="{{ $date['Year'] }}/{{ $date['Month'] }}" align="absmiddle">
						{{ $date['Year'] }}/{{ $date['Month'] }}
					</a>
				</div>
@endforeach
		<div style="clear: both"></div>
	</div>
@if ($what == 'pp')
	<div class="selectbox" id="parties">
		<b>Select party</b>
		<hr>
@foreach ($parties as $rParty)
				<div class="box-element">
					<a href="{{ $u('pp', $country, $rParty['pID'], $year, $month) }}">
						<img src="{{ $vars->getImgLoc('PartyLogo') . $rParty['pLogo'] }}" class="whatIMG" alt="{{ $rParty['pName'] }}" align="absmiddle">
						{{ $rParty['pName'] }}
					</a>
				</div>
@endforeach
		<div style="clear: both"></div>
	</div>
@elseif ($what == 'cg' && $country)
	<div class="selectbox" id="regionlist">
		<b>Select region</b>
		<hr>
@foreach ($regions as $reg)
				<div class="box-element"><a href="{{ $u('cg', $country, $reg['RegionID'], $year, $month) }}">{{ $reg['rName'] }}</a></div>
@endforeach
		<div style="clear: both"></div>
	</div>
@endif
@if ($what == 'pp')
@include('pages.elections.pp')
@elseif ($what == 'cp')
@include('pages.elections.cp')
@elseif ($what == 'cg')
@include($voting ? 'pages.elections.cg-shuffle' : 'pages.elections.cg-results')
@endif
@endsection
