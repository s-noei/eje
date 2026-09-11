@php $pID = 0; $co = 0; @endphp
@foreach ($rows as $dat)
@if ($dat['PartyID'] != $pID)
@php $pID = $dat['PartyID']; $first = true; @endphp
@if ($co)
				</div>
@endif
@php $co++; @endphp
				<div class="cg-party">
					<div class="party-avatar">
						<a href="{{ $vars->getURL('party', $dat['PartyID']) }}"><img src="{{ $vars->getImgLoc('PartyLogo') . $dat['pLogo'] }}" class="Avatars" alt="{{ $dat['pName'] }}" align="absmiddle"></a>
					</div>
					<div class="party-name"><a href="{{ $vars->getURL('party', $dat['PartyID']) }}">{{ $dat['pName'] }}</a></div>
					<hr size="1">
@else
				<hr size="1" color="aliceBlue">
@endif
			<div class="candidate-avatar">
				<a href="{{ $vars->getURL('party', $dat['PartyID']) }}"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $dat['Avatar'] }}" class="Avatar-s" alt="{{ $dat['pName'] }}" align="absmiddle"></a>
			</div>
			<div class="candidate-name">{{ $dat['name'] }}</div>
			<div class="candidate-vote">
@if ($logged && $citInfo['nationality'] == $citInfo['CountryID'] && $citInfo['CountryID'] == $country && $region == $citInfo['regionID'] && !$voted($dat['ElectionID']))
						<form action="" method="post" name="eVotes-{{ $co }}">
							@csrf
							<input type="hidden" name="eID" value="{{ $dat['ElectionID'] }}">
							<input type="hidden" name="eType" value="CG">
							<input type="hidden" name="cID" value="{{ $dat['CandidateID'] }}">
							<input type="hidden" name="token" value="{{ md5($dat['ElectionID'] . 'CG' . $citInfo['CitizenID'] . 'CG' . $dat['CandidateID']) }}">
							<input type="hidden" name="subvote" value="1">
							<input type="submit" id="eVote" value="">
						</form>
@endif
			</div>
@endforeach
@if ($co)
</div>
@endif
