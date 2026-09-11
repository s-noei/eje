@extends('lens.layout')
@section('content')
@include('lens._navbar')
@if ($sale)
<center>
	<table id="table" bordercolor="black" border="1">
		<tr><td>Purchase ID</td><td>Purchase no. {{ $sale['saleID'] }}</td></tr>
		<tr><td>Item number</td><td>{{ $sale['itemtitle'] }}</td></tr>
		<tr><td>Price</td><td>{{ $sale['price'] }}</td></tr>
		<tr><td>Buyer</td><td>{{ $sale['buyer'] }}</td></tr>
		<tr><td>Buyer Email</td><td>{{ $sale['buyermail'] }}</td></tr>
		<tr><td>Transaction ID</td><td>{{ $sale['txn_id'] }}</td></tr>
		<tr><td>Status</td><td>{{ $sale['transtat'] }}</td></tr>
		<tr><td>Time</td><td>{!! $session->getDiff($sale['timestamp']) !!}</td></tr>
	</table>
@if ($sale['transtat'] == 'Pending')
	<hr>
	<b>Finish this purchase</b>
	<form action="" method="post">
		@csrf
		<table>
			<tr><td>Buyer Email:</td><td><input type="text" name="buyermail"></td></tr>
			<tr><td>Transaction ID:</td><td><input type="text" name="txn_id"></td></tr>
			<tr><td>Bank:</td><td><select name="txn_bank">@foreach (['Paypal', 'Zarinpal', 'Sepehr', 'Siba', 'Other'] as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach</select></td></tr>
			<tr><td></td><td><input type="submit" name="subfinish" value="Submit"></td></tr>
		</table>
	</form>
@endif
</center>
@endif
@endsection
