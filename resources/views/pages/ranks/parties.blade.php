<div id="rankings">
	<div class="rank-no" style="padding-top: 15px">No.</div>
	<div class="rank-name" style="padding-top: 15px">Name</div>
	<div class="rank-country" style="padding-top: 15px">Country</div>
	<div class="rank-ep" style="padding-top: 15px">EP</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
			There are no parties here
@endif
@foreach ($rows as $i => $rPart)
	<div class="rank-no">{{ $start + $i + 1 }}</div>
	<div class="rank-name">
		<a href="{{ $vars->getURL('party', $rPart['PartyID']) }}">
			<img src="{{ $database->getPartyLogo($rPart['PartyID'], $vars->getImgLoc('PartyLogo')) }}" class="Avatar-s" alt="{{ $rPart['pName'] }}" align="absmiddle">
			{{ $rPart['pName'] }}
		</a>
	</div>
	<div class="rank-country">
		<a href="{{ $vars->getURL('country', $rPart['CountryID']) }}"><img src="/images/flags/l/{{ $rPart['Flag'] }}.gif" class="Flags" alt="{{ $rPart['cName'] }}"></a>
	</div>
	<div class="rank-ep">{{ $rPart['EP'] }}</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('ranking', 'parties', $page - 1, $p1) }}" id="buttons">&lt; Back</a>
@endif
@if ($hasNext)
		<a href="{{ $vars->getURL('ranking', 'parties', $page + 1, $p1) }}" id="buttons">Next &gt;</a>
@endif
	</center>
</div>
