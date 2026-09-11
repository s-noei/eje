<div id="electable">
	<div class="et-no">No.</div>
	<div class="et-name">Name</div>
	<div class="et-votes">Votes</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
			<div>There are no elections/candidates here</div>
@endif
@foreach ($rows as $i => $dat)
	<div class="et-no"><br>{{ $i + 1 }}</div>
	<div class="et-name">
			<a href="{{ $vars->getURL('profile', $dat['CandidateID']) }}">
				<img src="{{ $vars->getImgLoc('CitizenAvatar') . $dat['Avatar'] }}" class="Avatar-s" alt="{{ $dat['name'] }}" align="absmiddle">
				{{ $dat['name'] }}
			</a>
	</div>
	<div class="et-votes">
@if ($voting)
@if ($logged && $citInfo['nationality'] == $citInfo['CountryID'] && $citInfo['CountryID'] == $country && $party == $citInfo['PartyID'] && $citInfo['puberty'] >= 3)
@if ($voted($dat['ElectionID']))
		<br>Voted
@else
							<form action="" method="post" name="eVotes-{{ $i + 1 }}">
								@csrf
								<input type="hidden" name="eID" value="{{ $dat['ElectionID'] }}">
								<input type="hidden" name="eType" value="PP">
								<input type="hidden" name="cID" value="{{ $dat['CandidateID'] }}">
								<input type="hidden" name="token" value="{{ md5($dat['ElectionID'] . 'PP' . $citInfo['CitizenID'] . 'PP' . $dat['CandidateID']) }}">
								<input type="hidden" name="subvote" value="1">
								<input type="submit" id="eVote" value="">
							</form>
@endif
@elseif ($logged && $citInfo['puberty'] < 3)
		<br>Can't vote
@else
		<br>N/A
@endif
@else
		<br>{{ $dat['TotalVotes'] }}
@endif
	</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
</div>
