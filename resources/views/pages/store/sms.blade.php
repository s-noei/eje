@extends('layouts.game')
@section('content')
<div class="column-double">
@if ($go === 'smscancel')
<div class="block medium right">
	<div class="top"><h3>Mobile Payment</h3></div>
	<div class="content"><div class="msg"><div class="error">Payment canceled. <span class="close" title="Close">&nbsp;</span></div></div></div>
</div>
@elseif ($go === 'smsright')
<div class="block medium right">
	<div class="top"><h3>Mobile Payment</h3></div>
	<div class="content"><div class="msg"><div class="success">Payment made successfully.<span class="close" title="Close">&nbsp;</span></div></div></div>
</div>
@else
<div class="block medium right">
	<div class="top"><h3>Mobile Payment</h3></div>
	<div class="content">
	<p> Here you can buy sending sms or calling whit your pone, please select one of the next options: </p>
	<center>
<div class="Store-Tala"> <img src="/images/store/5tala.gif" alt="5 TALA Pack" title="5 TALA Pack" width="120px"><p> Price: 1.49$ </p></div>
<script src="http://www.paygol.com/micropayment/js/paygol.js" type="text/javascript"></script>
<form name="pg_frm">
<input type="hidden" name="pg_serviceid" value="{{ $serviceId }}">
<input type="hidden" name="pg_currency" value="USD">
<input type="hidden" name="pg_name" value="5 TALA Pack">
<input type="hidden" name="pg_custom" value="{{ $citInfo['CitizenID'] }}">
<input type="hidden" name="pg_price" value="1.49">
<input type="hidden" name="pg_return_url" value="{{ url('/sms-smsright-en.html') }}">
<input type="hidden" name="pg_cancel_url" value="{{ url('/sms-smscancel-en.html') }}">
<input type="image" name="pg_button" class="paygol" src="http://www.paygol.com/micropayment/img/buttons/150/blue_en_pbm.png" border="0" alt="Make payments with PayGol: the easiest way!" title="Make payments with PayGol: the easiest way!" onClick="if (window.pg_reDirect) pg_reDirect(this.form); return false;">
</form>
	</center>
	</div>
</div>
@endif
</div>
@endsection
