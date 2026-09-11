		<center>
			<div id="donations">
				<div class="no" style="padding-top: 0">No.</div>
				<div class="fromto" style="padding-top: 0">From/To</div>
				<div class="with" style="padding-top: 0">With</div>
				<div class="amount" style="padding-top: 0">Amount</div>
				<div class="time" style="padding-top: 0">Day/time</div>
				<div class="clear" style="padding-top: 0"><hr size=2 color=black></div>
@if (count($donations) < 1)
					<div style="text-align: center">There are no donation log for this citizen</div>
@else
@foreach ($donations as $i => $donation)
@php
	$isFrom = $donation['FromID'] == $row['CitizenID'];
	$withID = $isFrom ? $donation['ToID'] : $donation['FromID'];
	$wTyp = $isFrom ? $donation['ToType'] : $donation['FromType'];
	$imgs = ''; $link = '';
	if ($wTyp === 'citizen') { $w = $database->getUserInfoFromID($withID, 0); $imgs = "<img src='".$vars->getImgLoc('CitizenAvatar').($w['Avatar'] ?? '')."' class=\"Avatar-s\"><br>".e($w['name'] ?? ''); $link = $vars->getURL('profile', $withID); }
	elseif ($wTyp === 'country') { $w = $database->getCountryRec($withID); $imgs = "<img src='".$vars->getImgLoc('CountryFlag').($w['Flag'] ?? '').".gif' class=\"Flag-s\"><br>".e($w['cName'] ?? ''); $link = $vars->getURL('country', $withID); }
@endphp
				<div class="no">{{ $start + $i + 1 }}</div>
				<div class="fromto">{{ $isFrom ? 'To' : 'From' }}</div>
				<div class="with"><a href="{{ $link }}"> {!! $imgs !!}</a></div>
				<div class="amount">
					{{ $donation['Amount'] }}
@if ($donation['Type'])
					<img src="{{ $database->getCurrencyIco($donation['Type'], $vars->getImgLoc('CountryFlag')) }}" class="Flag-xs" align="absmiddle" title="{{ $database->getCurrency($donation['Type']) }}">
@else
					item(s)
@endif
				</div>
				<div class="time">{!! $donation['timestamp'] < time() - 86400 ? $database->getToday($donation['timestamp']) : $session->getDiff($donation['timestamp']) !!}</div>
				<div class="clear"><hr></div>
@endforeach
@endif
@if ($pageNo > 1)<a href="{{ $vars->getURL('profile', $req_id, 'donations', $pageNo - 1) }}" id="buttons">{!! $lang->getstr('nav_back') !!}</a>@endif
<a href="{{ $vars->getURL('profile', $req_id, 'donations', $pageNo) }}" id="buttons">{{ $pageNo }}</a>
@if ($size > $start + 20)<a href="{{ $vars->getURL('profile', $req_id, 'donations', $pageNo + 1) }}" id="buttons">{!! $lang->getstr('nav_next') !!}</a>@endif
			</div>
		</center>
