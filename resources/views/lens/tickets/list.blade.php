@extends('lens.layout')
@section('content')
@include('lens.tickets._navbar')
<center>
@if (!$atype)
Select a type
@else
@if ($localCountry)
		<b>Note:</b> Because you are a local moderator of {{ $localCountry }}, you are viewing tickets sent in {{ $localCountry }} only.
@endif
<table class="tickets" border="1px">
	<tr><th>No.</th><th>By</th><th>Subject</th><th>Priority</th><th>Create time</th><th>Last reply</th><th>Reply by</th></tr>
@if (count($rows) < 1)
			<tr><td colspan="7">There is no pending ticket(s) here!</td></tr>
@endif
@foreach ($rows as $i => $t)
			<tr>
				<td>{{ $i + 1 }}</td>
				<td><a href="{{ $vars->getURL('profile', $t['by_id']) }}" target="_blank">{{ $t['name'] }}</a></td>
				<td><a href="{{ $lensUrl('tickets', 'view', $t['ticket_id']) }}">{{ $t['subject'] }}</a></td>
				<td>{!! $priors[$t['priority']] ?? '' !!}</td>
				<td>{!! $session->getDiff($t['started_time']) !!}</td>
				<td>{!! $session->getDiff($t['last_reply']) !!}</td>
				<td>{{ $t['last_replier'] }}</td>
			</tr>
@endforeach
</table>
@endif
</center>
@endsection
