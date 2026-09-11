@extends('layouts.game')
@section('content')
<script>
	var setshow = 0;
	$(document).ready(function(){
			$("div#country").click(function(){
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
				<div class="box-element">
					<a href="{{ $vars->getURL('online', $page, '0') }}"><img src="{{ $vars->getImgLoc('CountryFlag') . 'world.gif' }}" class="Flag-ms" alt="citizens" align="absmiddle"> World</a>
				</div>
@foreach ($countries as $rCoun)
						<div class="box-element">
							<a href="{{ $vars->getURL('online', $page, $rCoun['CountryID']) }}"><img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] . '.gif' }}" class="Flag-ms" alt="{{ $rCoun['cName'] }}" align="absmiddle"> {{ $rCoun['cName'] }}</a>
						</div>
@endforeach
		<div style="clear: both"></div>
	</div>
<table id="rankings">
	<tr><th class="td">No.</th><th class="td">Name</th><th class="td">Country</th><th class="td">Region</th></tr>
	<tr><td colspan="4"><hr></td></tr>
@if (count($rows) < 1)
			<tr><td class="td" colspan="4">There are no citizens online here</td></tr>
@endif
@foreach ($rows as $i => $rPart)
	<tr>
		<td class="td">{{ $start + $i + 1 }}</td>
		<td class="td" style="text-align: center">
			<a href="{{ $vars->getURL('profile', $rPart['CitizenID']) }}">
				<img src="{{ $vars->getImgLoc('CitizenAvatar') . $rPart['Avatar'] }}" class="Avatars" alt="{{ $rPart['name'] }}"><br>
				{{ $rPart['name'] }}
			</a>
		</td>
		<td class="td"><a href="{{ $vars->getURL('country', $rPart['CountryID']) }}"><img src="/images/flags/l/{{ $rPart['Flag'] }}.gif" class="Flags" alt="{{ $rPart['cName'] }}"></a></td>
		<td class="td">{{ $rPart['rName'] }}</td>
	</tr>
@endforeach
	<tr><td colspan="4" class="td">
@if ($page > 1)
		<a href="{{ $vars->getURL('online', $page - 1, $coun) }}" id="buttons">&lt; Back</a>
@endif
@if ($num > $start + 10)
		<a href="{{ $vars->getURL('online', $page + 1, $coun) }}" id="buttons">Next &gt;</a>
@endif
	</td></tr>
</table>
@endsection
