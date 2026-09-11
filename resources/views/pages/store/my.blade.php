@extends('layouts.game')
@section('content')
<div class="column-double">
<a href="{{ $vars->getURL('ejstore') }}" id="buttons">Back to store</a>
<center><b>Your active payments</b></center>
<hr>
@if (count($rows) < 1)
			<center>You have no active payment</center>
@else
			<div id="payments-box">
				<div class="no">No.</div><div class="number">Purchase #</div><div class="title">Title</div><div class="price">Price</div><div class="time">Time</div><div class="stat">Status</div><br />
@foreach ($rows as $i => $row)
				<div class="no">{{ $i + 1 }}</div>
				<div class="number">{{ $row['saleID'] }}</div>
				<div class="title">{{ $row['itemtitle'] }}</div>
				<div class="price">{{ $row['price'] }}$</div>
				<div class="time">{!! $session->getDiff($row['timestamp']) !!}</div>
				<div class="stat">{{ $row['transtat'] }}</div>
				<br />
@endforeach
			</div>
@endif
</div>
@endsection
