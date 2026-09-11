@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/tickets.css">
	<div id="tickets">
		<div class="ticket-subject">Subject</div>
		<div class="ticket-reason">Department</div>
		<div class="ticket-started">Priority</div>
		<div class="ticket-last">Last reply</div>
		<div class="ticket-status">Status</div>
		<div style="clear: both; border: 0; height: 0; padding: 0;"></div>
@if (count($tickets) < 1)
					<div>You didn't send any tickets.</div>
@endif
@foreach ($tickets as $ticket)
						<div class="ticket-subject">
							<a href="{{ $vars->getURL('contact', 'view', $ticket['ticket_id']) }}">{{ $ticket['subject'] }}</a>
							<br>
							Started by <a href="{{ $vars->getURL('profile', $ticket['by_id']) }}">{{ $ticket['by_name'] }}</a>
						</div>
						<div class="ticket-reason">{{ $sections[$ticket['reason']] ?? $ticket['reason'] }}</div>
						<div class="ticket-started">{!! $priors[$ticket['priority']] ?? '' !!}</div>
						<div class="ticket-last">{!! $session->getDiff($ticket['last_reply']) !!}</div>
						<div class="ticket-status">{!! $stats[$ticket['status']] ?? '' !!}</div>
@endforeach
	</div>
@endsection
