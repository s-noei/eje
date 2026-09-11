<div id="electable">
	<div class="et-no">No.</div>
	<div class="et-name2">Name</div>
	<div class="et-party">Party</div>
	<div class="et-votes">Votes</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
			<div>There are no elections/candidates here</div>
@endif
@foreach ($rows as $i => $dat)
	<div class="et-no">{{ $i + 1 }}</div>
	<div class="et-name2">
		<a href="{{ $vars->getURL('profile', $dat['CandidateID']) }}">
			<img src="{{ $vars->getImgLoc('CitizenAvatar') . $dat['Avatar'] }}" class="Avatar-s" alt="{{ $dat['name'] }}"><br>
			{{ $dat['name'] }}
		</a>
	</div>
	<div class="et-party">
		<a href="{{ $vars->getURL('party', $dat['PartyID']) }}">
			<img src="{{ $vars->getImgLoc('PartyLogo') . $dat['pLogo'] }}" class="Avatar-s" alt="{{ $dat['pName'] }}"><br>
			{{ $dat['pName'] }}
		</a>
	</div>
	<div class="et-votes">
@if ($voting)
@if ($logged && $citInfo['nationality'] == $country && $citInfo['puberty'] >= 3)
@if ($voted($dat['ElectionID']))
		Voted
@else
						<form action="" method="post" name="eVotes{{ $i + 1 }}">
							@csrf
							<input type="hidden" name="eID" value="{{ $dat['ElectionID'] }}">
							<input type="hidden" name="eType" value="cp">
							<input type="hidden" name="cID" value="{{ $dat['CandidateID'] }}">
							<input type="hidden" name="token" value="{{ md5($dat['ElectionID'] . 'cp' . $citInfo['CitizenID'] . 'cp' . $dat['CandidateID']) }}">
							<input type="hidden" name="subvote" value="1">
							<a href="#" onclick="document.eVotes{{ $i + 1 }}.submit(); return false;" id="eVote"></a>
						</form>
@endif
@elseif ($logged && $citInfo['puberty'] < 3)
		<br>Can't vote
@else
		N/A
@endif
@else
		{{ $dat['TotalVotes'] }}
@endif
	</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
</div>
