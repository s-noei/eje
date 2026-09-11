@extends('layouts.game')
@section('content')
<script>
	$(document).ready(function(){
			$("a#becandidate").click(function(){ $("#candidateprop").slideToggle(200); });
		});
</script>
@php $pID = $row['pID']; $cIMG = $vars->getImgLoc('CountryFlag') . $country['Flag'] . '.gif'; $me = $citInfo['CitizenID'] ?? 0; @endphp
@foreach ($errors as $e)
<h3 class="errHandle">{!! $e !!}</h3>
@endforeach
<div class="column-double">
	<div class="column-headcol">
		<img src="{{ $vars->getImgLoc('PartyLogo') . $row['pLogo'] }}" class="Avatar-l" alt="{{ $row['pName'] }}">
	</div>
	<div class="column-details">
		<div class="column-name">
			<a href="{{ $vars->getURL('party', $pID) }}">{{ $row['pName'] }}</a>
			{!! $vars->getWikiLink('Party', $row['pName']) !!}
		</div>
		<img src="{{ $cIMG }}" class="Flag-xs" align="absmiddle">
		Party President: <b><a href="{{ $vars->getURL('profile', $row['PP']) }}">{{ $pp['name'] ?? '' }}</a></b>
@if ($isPP)
				<form action="" method="post" name="pManage">
				@csrf
				<input type="hidden" name="editParty" value="1">
				<a href="#" class="button-blue-1" onclick="document.pManage.submit(); return false;">Edit Party details</a>
				</form>
@endif
	</div>
	<div style="clear: both"></div>
	<hr>
</div>
@if ($editForm)
					<a href="{{ $vars->getURL('party', $pID) }}" class="button-blue-1">Party page</a>
					<form action="{{ $vars->getURL('party', $pID) }}" method=post id=forms enctype="multipart/form-data">
						@csrf
						<h3>Edit party details</h3>
						<table>
							<tr><td>Name:</td><td><input type=text size=20 name=pName class=text value="{{ $row['pName'] }}"></td></tr>
							<tr><td rowspan="2">Avatar</td><td rowspan="1"><img src="{{ $vars->getImgLoc('PartyLogo') . '/' . $row['pLogo'] }}" width=75> <input type="file" name="partyLogo" id="partyLogo" /></td></tr>
							<tr><td class="tdstyle"><b>Restrictions:<br>.jpg &amp; .jpeg<br>Below 50 KBs</b></td></tr>
							<tr><td><input type=submit name=editOk value="Save changes" class="button-blue-1"></td></tr>
						</table>
					</form>
@elseif ($go === 'members')
@include('pages.party.members')
@elseif ($go === 'ppCandidates')
@include('pages.party.pp-candidates')
@elseif ($go === 'cgCandidates')
@include('pages.party.cg-candidates')
@else
<div class="column-double">
	<div class="column-headcol">Social</div>
	<div class="column-details">
			<b>Account</b><hr>
			<blockquote>
					<div style="float: left">{{ $amount }} <img src="/images/flags/s/eJahan.gif" align="absmiddle"></div>
				<div style="clear: both"></div>
			</blockquote>
			<b>Orientation</b><hr>
			<blockquote>{{ $orientation }}</blockquote>
			<b>Members</b><hr>
			<blockquote>
				Members: <font size="6">{{ $memberCount }}</font>
			<br><br>
			<form name="doParty" action="" method="post">
			@csrf
			<a href='{{ $vars->getURL('party', $pID, 'members') }}' class="button-blue-1">View all members</a>
@if ($logged && (($citInfo['cName'] ?? '') == $country['cName'] || $isMember))
@if ($isMember)
			<input type="hidden" name="Resign" value="Resign">
			<a href="javascript:void(0)" class="button-red-1" onclick='if (confirm("Are you sure?")) document.doParty.submit()'>Resign</a>
@else
			<input type="hidden" name="Join" value="Join">
			<a href="javascript:void(0)" class="button-blue-1" onclick='document.doParty.submit()'>Join</a>
@endif
@endif
			</form>
			</blockquote>
	</div>
	<div style="clear: both"></div>
	<hr>
</div>
<div class="column-double">
	<div class="column-headcol"><img src="{{ $cIMG }}" class="inlineIMGs" alt="{{ $country['cName'] }}" /></div>
	<div class="column-details">Located in <b><a href="{{ $vars->getURL('country', $country['CountryID']) }}">{{ $country['cName'] }}</a></b></div>
	<div style="clear: both"></div>
	<hr>
</div>
<div class="column-double">
	<div class="column-headcol">Party Presidency</div>
	<div class="column-details">
			<b>Current</b><hr>
			<blockquote>
			<h2>
@if ($pp)
			<a href="{{ $vars->getURL('profile', $row['PP']) }}">{!! $vars->getAvatar($pp, 'Avatar-s') !!} {{ $pp['name'] }}</a>
@else
			No party president
@endif
			</h2>
			<form name="ppWork" action="" method="post">
				@csrf
				<a href="{{ $lastPPLink }}" class="button-blue-1">Last election</a>
@if ($isPP)
						<input type="hidden" name="resignPP" value="1">
						<a href="javascript:void(0)" class="button-red-1" onclick="if (confirm('Are you sure you want to resign presidency?')) document.ppWork.submit()">Resign presidency</a>
@endif
			</form>
			</blockquote>
			<b>Vice President</b><hr>
			<blockquote>
			<h2>
@if ($coPP)
					<a href="{{ $vars->getURL('profile', $row['coPP']) }}">{!! $vars->getAvatar($coPP, 'Avatar-s') !!} {{ $coPP['name'] }}</a>
@else
					No vice president selected
@endif
			</h2>
@if ($isPP)<a href='{{ $vars->getURL('party', $pID, 'members') }}' class="button-blue-1">Select a VP</a>@endif
			</blockquote>
			<b>Next election</b><hr>
			<blockquote>
				<h2>{{ $nextPP }} <sup>{{ $ppCandCount }} candidate(s)</sup></h2>
@if ($nextPP == 'Now')
				<a href="{{ $vars->getURL('elections', 'pp', $row['CountryID'], $pID, $now['Year'], $now['Month']) }}" class="button-blue-1">Election table</a>
@else
				<form action="" method=post name=ppelections>
				@csrf
				<a href="{{ $vars->getURL('party', $pID, 'ppCandidates') }}" class="button-blue-1">Show candidates</a>
				<input type=hidden name=candidate value=pp>
@if ($logged && $citInfo['PartyID'] == $pID)
@if ($isPPCandidate)
					<input type="hidden" name="do" value="re">
					<a href="javascript:void(0)" class="button-red-1" onclick="document.ppelections.submit()">Resign candidacy</a>
@else
					<input type="hidden" name="do" value="be">
					<a href="javascript:void(0)" class="button-blue-1" onclick="if (confirm('You must have 1 Tala in your account and you must be a member of the party for more than 3 days in order to become a PP candidate for this party. Do you want to do so?')) document.ppelections.submit()">Be a candidate</a>
@endif
@endif
				</form>
@endif
			</blockquote>
	</div>
	<div style="clear: both"></div>
	<hr>
</div>
<div class="column-double">
	<div class="column-headcol">In congress</div>
	<div class="column-details">
			<b>Current</b><hr>
			<blockquote>
			<font size="6">{{ $cgSeats }}</font> seat(s) of congress <br>
			<font size="6">{{ $cgGained }}</font> seat(s) gained in elections <br><br>
			<a href="{{ $lastCGLink }}" class="button-blue-1">Last election</a>
			</blockquote>
			<b>Next election</b><hr>
			<blockquote>
			<h2>{{ $nextCG }} <sup>{{ $cgCandCount }} candidate(s)</sup></h2>
@if ($nextCG == 'Now')
				<a href="{{ $vars->getURL('party', $pID, 'cgCandidates') }}" class="button-blue-1">Show candidates</a>
				<a href="{{ $vars->getURL('elections', 'cg', $row['CountryID'], '', $now['Year'], $now['Month']) }}" class="button-blue-1">Election table</a>
@else
				<form action="" method=post name=cgelections>
				@csrf
@if ($isPP && $day == $CG_SORT_DAY)
				<a href="{{ $vars->getURL('party', $pID, 'cgCandidates') }}" class="button-blue-1">Sort candidates</a>
@else
				<a href="{{ $vars->getURL('party', $pID, 'cgCandidates') }}" class="button-blue-1">Show candidates</a>
@endif
@if ($day >= $CG_PROPOSE_START && $day <= $CG_PROPOSE_DUE)
				<input type=hidden name=candidate value=cg>
@if ($logged && $citInfo['PartyID'] == $pID)
@if ($isCGCandidate)
				<input type=hidden name=do value=re>
				<a href='#' class=button-red-1 onclick="document.cgelections.submit(); return false;">Resign candidacy</a>
@else
				<input type="hidden" name="do" value="be">
				<a href="javascript:void(0)" class="button-blue-1" id="becandidate">Be a candidate</a>
				<div id="candidateprop" class="party-cg-candidate">
					Document URL: <input type="text" name="docURL" size="60"><br>
					<a href="javascript:void(0)" class="button-blue-1" onclick="document.cgelections.submit()">Submit</a>
				</div>
@endif
@endif
@endif
				</form>
@endif
			</blockquote>
	</div>
	<div style="clear: both"></div>
	<hr>
</div>
<div class="column-double">
	<div class="column-headcol">Country Presidency</div>
	<div class="column-details">
			<b>Current</b><hr>
			<blockquote>
			<h2>
@if ($cp)
					<a href="{{ $vars->getURL('profile', $cp['CitizenID']) }}">{!! $vars->getAvatar($cp, 'Avatar-s') !!} {{ $cp['name'] }}</a>
@else
					No country president
@endif
			</h2>
			<a href="{{ $lastCPLink }}" class="button-blue-1">Last election</a>
			</blockquote>
			<b>Party's proposed candidate</b><hr>
			<blockquote>
			<h2>
@if ($cpProposed)
					<a href="{{ $vars->getURL('profile', $row['cpProposed']) }}">{!! $vars->getAvatar($cpProposed, 'Avatar-s') !!} {{ $cpProposed['name'] }}</a>
@else
					No candidate proposed
@endif
			</h2>
@if ($isPP)<a href='{{ $vars->getURL('party', $pID, 'members') }}' class="button-blue-1">Propose candidate</a>@endif
			</blockquote>
			<b>Next election</b><hr>
			<blockquote>
			<h2>{{ $nextCP }}</h2>
@if ($nextCP == 'Now')
					<a href="{{ $vars->getURL('elections', 'cp', $row['CountryID'], '', $now['Year'], $now['Month']) }}" class="button-blue-1">Elections</a>
@else
					<a href='{{ $vars->getURL('country', $row['CountryID'], 'cpcandidates') }}' class="button-blue-1">Show candidates</a>
@endif
			</blockquote>
	</div>
	<div style="clear: both">&nbsp;</div>
</div>
@endif
@endsection
