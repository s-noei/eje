<div id="pMembers">
	<h2>Party Presidency candidates</h2>
	<hr>
@if (count($ppCandidates) < 1)
			There are no candidates in this party yet
@endif
@foreach ($ppCandidates as $i => $member)
	<div class="pmember-no">{{ $i + 1 }}</div>
	<div class="pmember-name">
		<a href="{{ $vars->getURL('profile', $member['CitizenID']) }}">
			<img src="{{ $vars->getImgLoc('CitizenAvatar') . $member['Avatar'] }}" class="Avatar-s" align="absmiddle">
			{{ $member['name'] }}
		</a>
	</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
</div>
