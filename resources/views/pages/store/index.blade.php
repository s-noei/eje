@extends('layouts.game')
@section('content')
<div class="column-double">
<a href="{{ $vars->getURL('ejstore', 'my') }}" id="buttons">You active payments</a>
<a href="{{ $vars->getURL('sms', 'sms') }}" id="buttons">Mobile payment</a><br>
	<center style="font-weight: bold;"><font size="5">TALA</font></center>
	<hr>
	<blockquote>
		eJahan TALA is the main currency of eJahan. It's used to create companies and parties, fighting in battles and ....
		Citizens can exchang their local currency to TALA in Monetary Market, also they can buy TALA from eJahan Store.
		<form action="{{ $vars->getURL('ejstore', 'process') }}" method="post" style="text-align: center">
			@csrf
@foreach ($talaItems as $item)
    			<div class="Store-Tala">
    				<img src="/images/store/{{ $item['itemimage'] }}" width="120px" alt="{{ $item['itemtitle'] }}" title="{{ $item['itemtitle'] }}">
@if ($happyTime)
            				Price: <s>{{ $item['itemprice'] }}$</s> <b title="LIMITED TIME OFFER!" style="color: red; padding: 0 5px">{{ $item['itempriceht'] }}$</b><br>
@else
            				Price: {{ $item['itemprice'] }}$<br>
@endif
    				<input type="radio" name="itemno" value="{{ $item['itemno'] }}">
    			</div>
@endforeach
			<center><input type="submit" id="submits" value="Buy selected TALA Pack"></center>
		</form>
		<div style="clear: both"></div>
	</blockquote>

	<center style="font-weight: bold;"><font size="5">PRO account</font></center>
	<hr>
	<blockquote>
		eJahan is free to play, but if you want some advantages, you can upgrade your account to PRO account.
			<div id="upgacc">
				<div class="feature">Feature</div><div class="free">FREE</div><div class="pro">PRO</div><br />
				<div class="feature">Inventory size</div><div class="free">40</div><div class="pro">200</div><br />
				<div class="feature">Working productivity</div><div class="free">100%</div><div class="pro">110%</div><br />
				<div class="feature">Daily tasks</div><div class="free">3</div><div class="pro">5</div><br />
				<div class="feature">PM inbox pages</div><div class="free">3</div><div class="pro">Unlimited</div><br />
				<div class="feature">PM quota</div><div class="free">30</div><div class="pro">Unlimited</div><br />
				<div class="feature">Chatbox msg/24h</div><div class="free">20</div><div class="pro">30/40</div><br />
				<div class="feature">PM read report</div><div class="free">NO</div><div class="pro">YES</div><br />
				<div class="feature">Select all in PM list</div><div class="free">NO</div><div class="pro">YES</div><br />
				<div class="feature" style="padding-top: 38px; height: 54px; text-align: center">Order</div>
				<div class="free" style="height: 90px"></div>
				<div class="pro" style="height: 90px">
@foreach ($proItems as $co => $item)
    					<div class="buy">
                            <form action="{{ $vars->getURL('ejstore', 'process') }}" method="post">
                                @csrf
                                {{ $months[$co] ?? '' }}<br />
@if ($happyTime)
                                        <s>{{ $item['itemprice'] }}$</s><br /><b title="LIMITED TIME OFFER!" style="color: red; padding: 0 5px">{{ $item['itempriceht'] }}$</b><br>
@else
                                        {{ $item['itemprice'] }}$<br />
@endif
        						<input type="hidden" name="itemno" value="{{ $item['itemno'] }}">
        						<input type="hidden" name="token" value="{{ md5($item['itemno'] . 'key for purch@$e') }}">
        						<input type="submit" id="submits" value="Buy">
        					</form>
                        </div>
@endforeach
				</div>
                <br />
			</div>
	</blockquote>
<hr>
Payments made by <img src="/images/store/paypal_logo.jpg" width="200px" align="absmiddle"><br>
Make sure to read eJahan's 10th law about the paid services.
</div>
@endsection
