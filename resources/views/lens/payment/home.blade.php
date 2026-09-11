@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<table id="table" bordercolor="black" border="1" width="400px">
		<tr><th colspan="2" align="center">Payment status</th></tr>
@foreach ($byCur as $r)
		<tr><td>Sales ({{ $r['currency'] }})</td><td>{{ $r['total'] }} {{ $r['currency'] }}</td></tr>
@endforeach
		<tr><td colspan="2"><hr></td></tr>
@foreach ($byBank as $r)
		<tr><td>Sales in {{ $r['txn_bank'] }} ({{ $r['currency'] }})</td><td>{{ $r['total'] }} {{ $r['currency'] }}</td></tr>
@endforeach
	</table>
</center>
@endsection
