<link rel="stylesheet" type="text/css" href="/include/css/violations.css">
@php
	$vUser = $vUser ?? $citInfo;
	$vOwn = ($citInfo['CitizenID'] ?? 0) == $vUser['CitizenID'];
	$viols = $database->rows("SELECT * FROM forfeit_forfeits WHERE citID = ? AND Active = '1' ORDER BY timestamp DESC", [$vUser['CitizenID']]);
	$today = $database->getToday();
@endphp
<center><h3>Your violations</h3></center><hr>
@foreach ($viols as $viol)
			<div class="viol-box">
				<div class="viol-head">{{ $viol['Title'] }}</div>
				<div class="viol-body">
					{{ $viol['Description'] }}
@if ($viol['appeal'])
					<hr><b>Your appeal:</b><br>{{ $viol['appeal'] }}
@endif
@if ($viol['appeal_reply'])
					<hr><b>Reply for your appeal:</b><br>{{ $viol['appeal_reply'] }}
@endif
				</div>
				<div class="viol-details">
					{{ $viol['Points'] }} point(s) -
@if (!$viol['Expire'])
					Forfeit will remain permanently.
@elseif ($viol['Expire'] <= $today)
					Forfeit is expired.
@else
					Forfeit will be removed on day {{ $viol['Expire'] }}.
@endif
				</div>
				<div class="viol-tail">
@if ($vOwn)
	@if ($viol['appeal'] && $viol['appeal_reply'])
							Sent an appeal before
	@elseif ($viol['appeal'])
							Appeal sent, wait for reply
	@else
							<a href="javascript:void(0)" id="addappeal-{{ $viol['ID'] }}">Write an appeal</a>
							<script>
								$(document).ready(function(){ $("#addappeal-{{ $viol['ID'] }}").click(function(){ $("#appeal-{{ $viol['ID'] }}").fadeIn(500) }); });
							</script>
							<div class="viol-appeal" id="appeal-{{ $viol['ID'] }}">
								<form action="/appeal.html" method="post">
									@csrf
									<textarea cols="40" rows="3" name="body"></textarea><br>
									<input type="hidden" name="violID" value="{{ $viol['ID'] }}">
									<input type="hidden" name="token" value="{{ md5($viol['ID'] . $vUser['CitizenID'] . config('ejahan.salts.appeal')) }}">
									<input type="submit" value="Submit appeal" id="submits">
								</form>
							</div>
	@endif
@else
					<a href="/lens/tickets-appeal-{{ $viol['ID'] }}.html">View this violation in lens</a>
@endif
				</div>
			</div>
@endforeach
