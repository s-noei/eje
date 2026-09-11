@extends('layouts.game')
@section('content')
<script>
	$(document).ready(function(){
			$("div #field").click(function(){ $("div #countries, div #cit-sorts").hide(); $("div #fields").fadeIn('500'); });
			$("div #country").click(function(){ $("div #fields, div #cit-sorts").hide(); $("div #countries").fadeIn('500'); });
			$("div #ctype").click(function(){ $("div #fields, div #countries").hide(); $("div #cit-sorts").fadeIn('500'); });
			$("div #coun-sort").click(function(){ $("div #fields").hide(); $("div #coun-sorts").fadeIn('500'); });
		});
</script>
@php $u = fn($w, $pg, $a = 0, $b = 0) => $vars->getURL('ranking', $w, $pg, $a, $b); @endphp
	<div id="tblRanksFLT">
		<div id="field" class="what">
			<div class="what-head">What?</div>
			<div class="what-body"><a href="javascript:void(0)"><img src="/images/ranks/{{ $what }}.jpg" class="whatIMG" alt="{{ $what }}"><br></a></div>
			<div class="what-foot"><img src="/images/arrow-down.jpg" class="selectIMG" alt="citizens"></div>
		</div>
@if ($what != 'countries')
		<div id="country" class="what">
			<div class="what-head">Country</div>
			<div class="what-body"><a href="javascript:void(0)"><img src="{{ $flag }}" class="whatIMG" alt="{{ $cName }}"><br></a></div>
			<div class="what-foot"><img src="/images/arrow-down.jpg" class="selectIMG" alt="citizens"></div>
		</div>
@else
		<div id="coun-sort" class="what">
			<div class="what-head">Sort</div>
			<div class="what-body"><a href="javascript:void(0)"><img src="/images/ranks/countries-sort.jpg" class="whatIMG"><br></a></div>
			<div class="what-foot"><img src="/images/arrow-down.jpg" class="selectIMG" alt="citizens"></div>
		</div>
@endif
@if ($what == 'citizens')
		<div id="ctype" class="what">
			<div class="what-head">Sort</div>
			<div class="what-body"><a href="javascript:void(0)"><img src="/images/ranks/citizens-sort.jpg" class="whatIMG"><br></a></div>
			<div class="what-foot"><img src="/images/arrow-down.jpg" class="selectIMG" alt="citizens"></div>
		</div>
@endif
	</div>
	<div style="clear: both"></div>
		<div class="selectbox" id="fields">
			<b>Select</b>
			<hr>
@foreach (['citizens' => 'Citizens', 'countries' => 'Countries', 'parties' => 'Parties', 'newspapers' => 'Newspapers', 'battles' => 'Battles'] as $k => $v)
					<div class="box-element">
						<a href="{{ $u($k, 1, 0, $p2) }}"><img src="/images/ranks/{{ $k }}.jpg" class="whatIMG" alt="{{ $k }}" align="absmiddle"> {{ $v }}</a>
					</div>
@endforeach
			<div style="clear: both"></div>
		</div>
		<div id="countries" class="selectbox">
			<b>Country</b>
			<hr>
					<div class="box-element">
						<a href="{{ $u($what, 1, 0, $p2) }}"><img src="{{ $vars->getImgLoc('CountryFlag') . 'l/world.gif' }}" class="whatIMG" alt="citizens" align="absmiddle"> World</a>
					</div>
@foreach ($countries as $rCoun)
							<div class="box-element">
								<a href="{{ $u($what, 1, $rCoun['CountryID'], $p2) }}"><img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] . '.gif' }}" class="whatIMG" alt="country" align="absmiddle"> {{ $rCoun['cName'] }}</a>
							</div>
@endforeach
			<div style="clear: both"></div>
		</div>
		<div id="coun-sorts" class="selectbox">
			<b>Sort by</b>
			<hr>
@foreach (['EP', 'Population', 'Average Military Skill', 'Average Working Skill', 'Average EP', 'Number of companies', 'Average production cost', 'Inflation'] as $i => $v)
					<div class="box-element"><a href="{{ $u($what, 1, $i + 1, $p2) }}">{{ $v }}</a></div>
@endforeach
			<div style="clear: both"></div>
		</div>
		<div id="cit-sorts" class="selectbox">
			<b>Sort citizens by</b>
			<hr>
@foreach (['EP', 'Military Skill', 'Working Skill', 'Lucky Miners', 'Golden Genealogies', 'World Fames', 'Imperishable Soldiers', 'Successful Revolts', 'Battle Heroes', 'Memorable Battle Heroes', 'Party Presidencies', 'Congress Membership', 'Country Presidencies', 'Media Powers', 'Article Powers', 'Ambassadors', 'Total Fights', 'Total Advance', 'Average Force', 'Max. Advance'] as $i => $v)
					<div class="box-element"><a href="{{ $u($what, 1, $p1, $i + 1) }}">{{ $v }}@if ($i == 19) <sup style="color: red;">NEW</sup>@endif</a></div>
@endforeach
			<div style="clear: both"></div>
		</div>
@include('pages.ranks.' . $what)
@endsection
