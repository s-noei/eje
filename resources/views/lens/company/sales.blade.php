@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<form action="" method="post">
		@csrf
		Enter a company ID to view: <input type="text" name="trackid" value="{{ $id }}"> <input type="submit" value="View" id="submits">
	</form>
</center>
<hr>
@if ($comp)
@php $cid = $comp['CompanyID']; $tot = 0; @endphp
<center>
@include('lens._kv', ['head' => 'Related links', 'rows' => [
	['Company page', '<a href="' . $vars->getURL('company', $cid) . '" target="_blank">Click</a>'],
	['Transactions', '<a href="' . $lensUrl('trans', 'company', $cid) . '">Click</a>'],
	['Tracker', '<a href="' . $lensUrl('company', 'tracker', $cid) . '">Click</a>'],
]])
	<table id="table" bordercolor="black" border="1">
		<tr><th colspan="6">Sales history</th></tr>
		<tr><td>No.</td><td>Day</td><td>Country ID</td><td>Quality</td><td>Amount</td><td>Price</td></tr>
@foreach ($sales as $i => $s)
@php $tot += $s['Amount']; @endphp
		<tr><td>{{ $i + 1 }}</td><td>{{ $s['Day'] }}</td><td>{{ $s['CountryID'] }}</td><td>{{ $s['Stars'] }}</td><td title="From 1st sale: {{ $tot }}">{{ $s['Amount'] }}</td><td>{{ $s['Price'] }}</td></tr>
@endforeach
	</table>
</center>
@endif
@endsection
