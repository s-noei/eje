<div id="rankings">
	<div class="rank-no" style="padding-top: 15px">No.</div>
	<div class="rank-name" style="padding-top: 15px">Name</div>
	<div class="rank-country" style="padding-top: 15px">Country</div>
	<div class="rank-ep" style="padding-top: 15px">{{ $typeC }}</div>
	<div style="clear: both"></div>
	<hr>
@foreach ($rows as $i => $rCit)
	<div class="rank-no">{{ $start + $i + 1 }}</div>
	<div class="rank-name">
		<a href="{{ $vars->getURL('profile', $rCit['CitizenID']) }}">
			<img src="{{ $vars->getImgLoc('CitizenAvatar') . $rCit['Avatar'] }}" class="Avatar-s" align="absmiddle" alt="{{ $rCit['name'] }}">
			{{ $rCit['name'] }}
		</a>
	</div>
	<div class="rank-country">
		<a href="{{ $vars->getURL('country', $rCit['CountryID']) }}"><img src="/images/flags/l/{{ $rCit['Flag'] }}.gif" class="Flags" alt="{{ $rCit['cName'] }}"></a>
	</div>
	<div class="rank-ep">{{ $rCit[$type] }}</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
</div>
<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('ranking', 'citizens', $page - 1, $p1, $p2) }}" id="buttons">&lt; Back</a>
@endif
@if ($hasNext)
	<a href="{{ $vars->getURL('ranking', 'citizens', $page + 1, $p1, $p2) }}" id="buttons">Next &gt;</a>
@endif
</center>
