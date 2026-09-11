@extends('layouts.game')
@section('content')
<script>
	var setshow = 0;
	$(document).ready(function(){
			$("#country").click(function(){
					if (setshow) $("div #countries").fadeOut('500'); else $("div #countries").fadeIn('500');
					setshow = 1 - setshow;
				});
		});
</script>
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'country', 'head' => 'Country', 'img' => $flag, 'alt' => $cName])
		</div>
		<div style="clear: both"></div>
	<div id="countries" class="selectbox">
		<b>Country</b>
		<hr>
@foreach ($allCountries as $rCoun)
						<div class="box-element">
							<a href="{{ $vars->getURL('country', $rCoun['CountryID'], 'cpcandidates') }}">
								<img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] . '.gif' }}" class="Flag-ms" alt="{{ $rCoun['cName'] }}" align="absmiddle">
								{{ $rCoun['cName'] }}
							</a>
						</div>
@endforeach
		<div style="clear: both"></div>
	</div>

<table id="rankings">
	<tr><th class="td">No.</th><th class="td">Name</th><th class="td">Party</th></tr>
	<tr><td colspan="4"><hr></td></tr>
@if (count($cands) < 1)
			<tr><td class="td" colspan="4">There are no candidates here</td></tr>
@endif
@foreach ($cands as $i => $cpCand)
	<tr>
		<td class="td">{{ $i + 1 }}</td>
		<td class="td">
			<a href="{{ $vars->getURL('profile', $cpCand['CitizenID']) }}">
				<img src="{{ $vars->getImgLoc('CitizenAvatar') . $cpCand['Avatar'] }}" class="Avatar-s" alt="{{ $cpCand['name'] }}"><br>
				{{ $cpCand['name'] }}
			</a>
		</td>
		<td class="td">
			<a href="{{ $vars->getURL('party', $cpCand['pID']) }}">
				<img src="{{ $vars->getImgLoc('PartyLogo') . $cpCand['pLogo'] }}" class="Avatar-s" alt="{{ $cpCand['pName'] }}"><br>
				{{ $cpCand['pName'] }}
			</a>
		</td>
	</tr>
@endforeach
</table>
@endsection
