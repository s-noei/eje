@extends('lens.layout')
@section('content')
@include('lens.tickets._navbar')
<center>
	<table class="tickets" border="1px">
		<tr><th>No.</th><th>Sender</th><th>Violation title</th><th>Time</th></tr>
@foreach ($rows as $i => $t)
		<tr>
			<td>{{ $i + 1 }}</td>
			<td><a href="{{ $vars->getURL('profile', $t['sender']) }}" target="_blank">{{ $t['name'] }}</a></td>
			<td><a href="{{ $lensUrl('tickets', 'appeal', $t['ID']) }}">{{ $t['Title'] }}</a></td>
			<td>{!! $session->getDiff($t['timestamp']) !!}</td>
		</tr>
@endforeach
	</table>
</center>
@endsection
