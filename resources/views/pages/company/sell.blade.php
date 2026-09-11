	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
	<form action="" method="post" id="forms">
		@csrf
@if ($row['sale_due'] < time())
				<h3>Sell Company</h3>
				Base price: <input type="text" size="6" name="bPrice" class="text" value=""> Tala<br>
				Bid step: <input type="text" size="3" name="sPrice" class="text" value=""> Tala<br>
				<br>
				<input type="submit" name="sellOk" value="Put for sale" class="submit-blue-1">
				<br>
@elseif ($bid && $bid['sale_bid_id'])
				<h3>Company in company market</h3>
				<b>Base price:</b> {{ $bid['sale_base'] }} Tala<br>
				<b>Bid step:</b> {{ $bid['sale_step'] }} Tala<br>
				<b>Deadline:</b> {!! $session->getDiffF($row['sale_due']) !!}<br>
				<hr>
				<b>Highest bid by:</b> <a href="{{ $vars->getURL('profile', $bid['sale_bid_id']) }}">{{ $bid['name'] }}</a><br>
				<b>Bid amount:</b> {{ $bid['sale_bid_amount'] }} Tala<br>
				<input type="submit" name="submitSell" class="submit-blue-1" value="Sell company to this buyer">
@else
				<h3>Company in company market</h3>
				<b>Base price:</b> {{ $bid['sale_base'] ?? $row['sale_base'] }} Tala<br>
				<b>Bid step:</b> {{ $bid['sale_step'] ?? $row['sale_step'] }} Tala<br>
				<b>Deadline:</b> {!! $session->getDiffF($row['sale_due']) !!}<br>
				<hr>
				<i>Nobody placed a bid yet.</i><br>
				<hr>
				<input type="submit" name="removeSell" class="cmdRemove" value="Remove from market">
@endif
	</form>
