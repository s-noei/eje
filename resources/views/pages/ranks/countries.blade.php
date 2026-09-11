<div id="rankings">
	<div class="rank-no" style="padding-top: 15px">No.</div>
	<div class="rank-name" style="padding-top: 15px">Name</div>
	<div class="rank-pop" style="padding-top: 15px">{{ $typeC }}</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
			<div>There are no countries here</div>
@endif
@foreach ($rows as $i => $rCoun)
	<div class="rank-no">{{ $start + $i + 1 }}</div>
	<div class="rank-name">
		<a href="{{ $vars->getURL('country', $rCoun['CountryID']) }}">
			<img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] }}.gif" class="Flags" alt="{{ $rCoun['cName'] }}" align="absmiddle"> {{ $rCoun['cName'] }}
		</a>
	</div>
	<div class="rank-pop"><br>{{ ($rCoun['val'] == -10000 && $type == 'inflation') ? 'N/A' : $rCoun['val'] }}</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('ranking', 'countries', $page - 1, $p1, $p2) }}" id="buttons">&lt; Back</a>
@endif
@if ($hasNext)
		<a href="{{ $vars->getURL('ranking', 'countries', $page + 1, $p1, $p2) }}" id="buttons">Next &gt;</a>
@endif
	</center>
</div>
