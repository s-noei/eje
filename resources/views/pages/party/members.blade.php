		<hr>
@if (count($members) < 1)
				<div id="pMembers">There are no members in this party</div>
@endif
@foreach ($members as $i => $member)
	<div id="pMembers">
			<div class="pmember-no">{{ $i + 1 }}</div>
			<div class="pmember-name">
				<a href="{{ $vars->getURL('profile', $member['CitizenID']) }}">
					{!! $vars->getAvatar($member, 'Avatar-s') !!}<br>
					{{ $member['name'] }}
				</a>
			</div>
			<div class="pmember-posts">
				<img src="/images/game/profile/pmember-on.gif" width="50px">
@if ($row['PP'] == $member['CitizenID'])<img src="/images/game/profile/cp.gif" width="50px">@endif
@if ($row['CountryID'] == $member['cgCountryID'])<img src="/images/game/profile/congress.gif" width="50px">@endif
			</div>
			<div class="pmember-propose">
@if ($isPP)
				<form action="{{ $vars->getURL('party', $row['pID']) }}" method="post">
					@csrf
					<input type="hidden" name="cpPropID" value="{{ $member['CitizenID'] }}">
					<input type="hidden" name="token" value="{{ md5($member['CitizenID'] . 'thisis @ 30p c@ndid@+e') }}">
					<input type="submit" name="cpprp" id="submits" value="CP Candidate">
@if ($row['PP'] != $member['CitizenID'])
					<input type="submit" name="ppsup" id="submits" value="Vice President">
@endif
				</form>
@endif
			</div>
	</div>
	<div style="clear: both">&nbsp;</div>
	<hr>
@endforeach
