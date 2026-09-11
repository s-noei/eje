{{-- Country selector box used by jobs/market/cmarket. Expects $countries, $countryLink (closure), optional $nameKey --}}
<div id="countries" class="selectbox">
	<b>{!! $lang->getstr('filter_country', 'filter') !!}</b>
	<hr>
@foreach ($countries as $rCoun)
	<div class="box-element">
		<a href="{{ $countryLink($rCoun['CountryID']) }}">
			<img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] . '.gif' }}" class="Flag-ms" alt="{{ $rCoun[$nameKey ?? 'cName'] }}" align="absmiddle">
			{{ $rCoun[$nameKey ?? 'cName'] }}
		</a>
	</div>
@endforeach
	<div style="clear: both"></div>
</div>
