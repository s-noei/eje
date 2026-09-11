@extends('lens.layout')
@section('content')
@include('lens._navbar')
@if (!$searched)
			<center>
				Search
				<form action="" method="post">
					@csrf
					<table>
						<tr><td>Citizen ID:</td><td><input type="text" name="citID" value=""></td></tr>
						<tr><td>Item ID:</td><td><input type="text" name="itemID" value=""></td></tr>
						<tr><td>Status:</td><td><select name="stat"><option value="Pending">Pending</option><option value="Completed">Completed</option></select></td></tr>
						<tr><td></td><td><input type="submit" name="subsearch" value="Search" id="submits"></td></tr>
					</table>
				</form>
			</center>
			<hr>
@else
			<center>
				<a href="{{ $lensUrl('payment', 'search') }}">Start a new search</a>
				<hr>
@if ($citID)
@include('lens._kv', ['head' => 'Related links', 'rows' => [['Profile link', '<a href="' . $vars->getURL('profile', $citID) . '" target="_blank">Click</a>'], ['Transactions', '<a href="' . $lensUrl('trans', 'citizen', $citID) . '">Click</a>']]])
@endif
				<table id="table" bordercolor="black" border="1">
					<tr><th>Purchase ID</th><th>Item number</th><th>Price</th><th>Buyer</th><th>Buyer Email</th><th>Transaction ID</th><th>Status</th><th>Time</th></tr>
@foreach ($rows as $pur)
					<tr>
						<td><a href="{{ $lensUrl('payment', 'view', $pur['saleID']) }}">Purchase no. {{ $pur['saleID'] }}</a></td>
						<td>{{ $pur['itemno'] }}</td><td>{{ $pur['price'] }}</td><td>{{ $pur['buyer'] }}</td><td>{{ $pur['buyermail'] }}</td>
						<td>{{ $pur['txn_id'] }}</td><td>{{ $pur['transtat'] }}</td><td>{!! $session->getDiff($pur['timestamp']) !!}</td>
					</tr>
@endforeach
				</table>
			</center>
@endif
@endsection
