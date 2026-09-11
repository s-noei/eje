<div id="electable">
	<div class="et-no">No.</div>
	<div class="et-name2">Name</div>
	<div class="et-party">Party</div>
	<div class="et-votes">Votes</div>
	<div style="clear: both"></div>
	<hr size="1">
@foreach ($groups as $title => $list)
			<div class="cg-result-head">
				<center>{{ $title }} ({{ count($list) }})<hr></center>
@if (count($list) < 1)
					<div>There are no elections/candidates here</div>
@endif
@foreach ($list as $i => $dat)
			<div class="et-no"><br>{{ $i + 1 }}</div>
			<div class="et-name2">
				<a href="{{ $vars->getURL('profile', $dat['CandidateID']) }}">
					<img src="{{ $vars->getImgLoc('CitizenAvatar') . $dat['Avatar'] }}" class="Avatar-s" alt="{{ $dat['name'] }}" align="absmiddle">
					{{ $dat['name'] }}
				</a>
			</div>
			<div class="et-party">
				<a href="{{ $vars->getURL('party', $dat['PartyID']) }}">
					<img src="{{ $vars->getImgLoc('PartyLogo') . $dat['pLogo'] }}" class="Avatar-s" alt="{{ $dat['pName'] }}" align="absmiddle">
					{{ $dat['pName'] }}
				</a>
			</div>
			<div class="et-votes"><br> {{ $dat['TotalVotes'] }}</div>
			<div style="clear: both"></div>
			<hr size="1" color="aliceBlue">
@endforeach
			</div>
@endforeach
</div>
