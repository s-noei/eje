<link rel="stylesheet" type="text/css" href="/include/css/donation.css">
<div id="donatehandle">
{!! $msg ?? '' !!}
<script src="/include/js/donation.js" type="text/javascript"></script>
	Maximum number of items you can donate: {{ $maxdon }}<br>
@foreach ($invs as $inv)
		<div class="inventory-item" style="height: 110px;">
            <form action="" name="don_{{ $inv['Type'] }}_{{ $inv['Stars'] }}" method="post">
                @csrf
                <div class="hover">&nbsp;</div>
                <div class="icon"><img src="/images/icons/{{ $inv['Icon'] }}.png"></div>
                <div class="quality"><img src="/images/game/{{ $inv['Stars'] }}_star.gif"></div>
                <div class="amount">{{ $inv['Amount'] }}</div>
                <div class="donate">
                    <input type="text" name="amount" maxlength="3" size="2" style="text-align: center;" />
                    <input type="hidden" name="type" value="{{ $inv['Type'] }}" />
                    <input type="hidden" name="quality" value="{{ $inv['Stars'] }}" />
                    <input type="hidden" name="token" value="{{ md5($inv['Type'] . $inv['Stars'] . $citInfo['CitizenID'] . 'D0nAte It3m') }}" />
                    <input type="submit" name="subdonit" value="->" style="width: 29px" />
                </div>
            </form>
        </div>
@endforeach
	<div class="column-details">
		<b>Donate money</b>
		<hr>
	<form action="" method="post" name="cManage">
			@csrf
			<blockquote>
				<input type="text" maxlength="7" size="7" name="Amount">
				<select name="Type">
@foreach ($currencies as $row3)
@if ($row3['CurID'] != 1 || $citInfo['active'])
					<option value="{{ $row3['CurID'] }}">{{ $database->getCurrency($row3['CurID']) }}</option>
@endif
@endforeach
				</select>
				<input type="submit" value="Donate" id="buttons">
				<input type="hidden" name="subdonate" value="1">
				<input type="hidden" name="To" value="{{ $row['CitizenID'] }}">
				<input type="hidden" name="token" value="{{ md5($row['CitizenID'] . 'key not for donate' . $citInfo['CitizenID']) }}">
			</blockquote>
		</form>
	<div style="clear: both">&nbsp;</div>
</div>
</div>
<script>$(document).ready(function(){ $("div#donatehandle").fadeIn(200); });</script>
