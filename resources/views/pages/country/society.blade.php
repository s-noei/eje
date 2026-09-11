<style>
	.lostreg { display: none }
</style>
@php $id = $row['CountryID']; $f = fn($n) => $vars->formatnumbers($n); @endphp
<b>{{ $lang->getstr('country_society', 'countryinfo') }}</b>
<hr>
<blockquote>
	<table style="width: 100%">
		<tr><td>&nbsp;</td><td>{{ $lang->getstr($row['shortName'], 'country') }}</td><td>{{ $lang->getstr('world') }}</td></tr>
		<tr><td>{{ $lang->getstr('society_population', 'countryinfo') }}</td><td>{{ $f($row['pop']) }}</td><td>{{ $society['numMembers'] }}</td></tr>
		<tr><td>{{ $lang->getstr('society_born_today', 'countryinfo') }}</td><td>{{ $f($society['bornToday'][0]) }}</td><td>{{ $f($society['bornToday'][1]) }}</td></tr>
		<tr><td>{{ $lang->getstr('society_born_yesterday', 'countryinfo') }}</td><td>{{ $f($society['bornYesterday'][0]) }}</td><td>{{ $f($society['bornYesterday'][1]) }}</td></tr>
		<tr><td>{{ $lang->getstr('society_hib_today', 'countryinfo') }}</td><td>{{ $f($society['hibs'][0]) }}</td><td>{{ $f($society['hibs'][1]) }}</td></tr>
		<tr><td>{{ $lang->getstr('society_avg_ep', 'countryinfo') }}</td><td>{{ $f(round($row['avgEP'])) }}</td><td>{{ $society['avgEP'] }}</td></tr>
		<tr>
			<td>{{ $lang->getstr('society_online', 'countryinfo') }}</td>
			<td>{{ $f($society['online'][0]) }} (<a href="{{ $vars->getURL('online', 1, $row['CountryID']) }}">{{ $lang->getstr('who') }}</a>)</td>
			<td>{{ $f($society['online'][1]) }} (<a href="{{ $vars->getURL('online') }}">{{ $lang->getstr('who') }}</a>)</td>
		</tr>
		<tr><td>{{ $lang->getstr('society_native', 'countryinfo') }}</td><td>{{ $f($row['native']) }}%</td><td>--</td></tr>
	</table>
</blockquote>

<b>{{ $lang->getstr('country_goddesses', 'countryinfo') }}</b>
<hr>
<blockquote>
	<table style="width: 100%">
		<tr>
@for ($i = 1; $i <= 6; $i++)
				<td style="text-align: center"><img src="/images/game/gods/god-{{ $i }}-s.png" alt="{{ $gods[$i] }}" title="{{ $gods[$i] }}"></td>
@endfor
		</tr>
		<tr>
@for ($i = 1; $i <= 6; $i++)
				<td style="text-align: center"><font size="6">{{ $godCounts[$i] }}</font></td>
@endfor
			<td></td>
		</tr>
	</table>
</blockquote>
		<br><b>{!! sprintf($lang->getstr('society_regions', 'countryinfo')
			, $f($totO) . ' ' . $lang->getstr('society_original', 'countryinfo')
			, '<font color="green">' . $f($totH) . ' ' . $lang->getstr('society_conquered', 'countryinfo') . '</font>'
			, '<font color="maroon">' . $f($totL) . ' ' . $lang->getstr('society_lost', 'countryinfo') . '</font>'
			, $f($totC) . ' ' . $lang->getstr('society_total', 'countryinfo')) !!}
			<a href="javascript:void(0)" onclick="javascript:$('.lostreg').slideToggle(500)">Show/hide lost regions</a>
		</b><hr>
@foreach ($regions as $row1)
@php
	$lost = $row1['CountryID'] != $id && $row1['oCountryID'] == $id;
	if ($row1['CountryID'] == $id && $row1['oCountryID'] == $id) { $flg = $row1['OFlag']; $tit = $row1['OName']; $col = ''; }
	elseif ($row1['CountryID'] == $id) { $flg = $row1['OFlag']; $tit = $row1['OName']; $col = 'green'; }
	else { $flg = $row1['CFlag']; $tit = $row1['CName']; $col = 'maroon'; }
@endphp
			<div id="regions"{!! $lost ? ' class="lostreg"' : '' !!}>
				<div id="flag"><img class="Flag-xs" src="/images/flags/s/{{ $flg }}.gif" title="{{ $tit }}" alt="{{ $tit }}" align="absmiddle"></div>
				<div id="name">
					<a href="{{ $vars->getURL('region', $row1['RegionID']) }}">
						<font color="{{ $col }}">{{ $row1['Name'] }}</font>
@if ($row1['capFor'] == $row1['CountryID'])
						<sup style="background: #444;color: white; padding: 2px; font-size: 7pt">{{ $lang->getstr('society_capital', 'countryinfo') }}</sup>
@endif
					</a>
				</div>
				<div id="faraway">
@if (!$row1['hasRoute'] && $row1['CountryID'] == $id)
					<img src="/images/game/danger.png" width="20px" title="{{ $lang->getstr('region_faraway', 'countryinfo') }}" align="absmiddle">
@else
					&nbsp;
@endif
				</div>
				<div id="god">
@if ($gdtype = $row1['goddessType'])
					<img src="/images/game/gods/god-{{ $gdtype }}-s.png" alt="{{ $gods[$gdtype] }}" title="{{ $gods[$gdtype] }}">
@else
					&nbsp;
@endif
				</div>
				<div id="clinic">{{ $row1['Clinic'] ?: '' }}&nbsp;</div>
				<div id="muni">
@if ($row1['Munic'])
					<img src="/images/game/hasmunic.png" alt="{{ str_repeat('*', (int) $row1['Munic']) }}" title="{{ str_repeat('*', (int) $row1['Munic']) }}">
@else
					&nbsp;
@endif
				</div>
			</div>
			<div style="clear: both; float: none"{!! $lost ? ' class="lostreg"' : '' !!}>
				<hr size="1" color="#dddddf">
			</div>
@endforeach
