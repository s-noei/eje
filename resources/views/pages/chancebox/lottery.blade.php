@extends('layouts.game')
@section('content')
<script language="javascript">
	function updatePrice(max)
	{
		var amount = document.Buyticket.Amount.value;
		var price = Math.round(amount) * 0.1;
		if (isNaN(price)) { document.Buyticket.Amount.value = '0'; price = 0; }
		var start = '', end = '';
		if (price > max) { start = '<font color="red">'; end = '</font>'; }
		document.getElementById('iPrice').innerHTML = start + price.toFixed(2) + end;
	}
</script>
You can test your chance to win a prize on the first of everyday! Simply you can buy some tickets from the form below.<br>
Each ticket costs 0.1 TALA.
You can buy up to 20 tickets daily.
The draw happens on 00:01 everyday.
<br>
Prizes:
<blockquote>
	1. <b>Chance box with 100 points</b> for the first winner<br>
	2. <b>Chance box with 50 points</b> for the second winner<br>
	3. <b>Chance box with 20 points</b> for the third winner<br>
	4. <b>Chance box with 10 points</b> for the fourth winner<br>
	5. <b>Chance box with 5 points</b> for the fifth winner<br>
</blockquote>
@foreach ($errors as $e)<h3 class="errHandle">{{ $e }}</h3>@endforeach
@foreach ($info as $e)<h3 class="infHandle">{{ $e }}</h3>@endforeach
<form action="" method="post" name="Buyticket">
	@csrf
	Number of tickets you want to buy: <input type="text" name="Amount" size="2" maxlength="2" onkeyup="updatePrice('{{ $oTala }}')">
	(<b><span id="iPrice">0</span> TALA</b>)
	<br>
	<input type="submit" id="submits" value="Buy tickets">
</form>
<hr>
<h3>Your tickets for next draw</h3>
@foreach ($tickets as $i => $ticket)
		<div style="width: 30px; height: 25px; padding: 10px; float: left; text-align: center; border: 2px solid; font-size: 14pt">{{ $ticket['ticketID'] }}</div>
@if (($i + 1) % 10 == 0)
				<div style="clear: both"></div>
@endif
@endforeach
<div style="clear: both"></div>
<hr>
@if ($go != 'results')
			<a href="{{ $vars->getURL('lottery', 'results') }}" id="buttons">Previous results</a>
@else
			<a name="results"></a>
			<h3>Lottery results for day {{ $day }}<hr></h3>
			<form name="result">
			<select name="viewresult" onchange="document.location.href='/lottery-results-'+this.value+'.html#results'">
@foreach ($days as $i)
						<option value="{{ $i }}" @if ($i == $day) selected @endif>Day {{ $i }}</option>
@endforeach
			</select>
@if (!$lotstat)
					No record is available for this day.
@else
					<b>Total tickets:</b> {{ $lotstat['lottery_tickets'] }} ,
					<b>Total citizens:</b> {{ $lotstat['lottery_citizens'] }} ,
					<b>winners:</b> {{ $lotstat['lottery_winners'] }}
					<hr>
					<blockquote>
						<b>Winners<hr color="black" size="2"></b>
@foreach ($winners as $i => $winner)
							{{ $i + 1 }}.
							<a href="{{ $vars->getURL('profile', $winner['winnerID']) }}">
								<img src="{{ $vars->getImgLoc('CitizenAvatar') . $winner['Avatar'] }}" class="Avatar-s" align="absmiddle">
								{{ $winner['name'] }}
							</a>
							(ticket #{{ $winner['ticket'] }})
							, won {{ $winner['Prize'] }}
							<hr>
@endforeach
					</blockquote>
@endif
			</form>
@endif
@endsection
