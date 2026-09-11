@extends('lens.layout')
@section('content')
@include('lens.tickets._navbar')
<blockquote style="border: 1px solid; padding: 20px; margin: 0 200px 0 200px">
	<div style="text-align: justify">
		<table>
			<tr><td>By:</td><td><a href="{{ $vars->getURL('profile', $tInfo['sender']) }}">{{ $tInfo['name'] }}</a></td></tr>
			<tr><td>Subject:</td><td>{{ $tInfo['subject'] }}</td></tr>
			<tr><td>Priority:</td><td>{!! $priors[$tInfo['priority']] ?? '' !!}</td></tr>
			<tr><td>Created time:</td><td>{!! $session->getDiff($tInfo['started_time']) !!}</td></tr>
			<tr><td>Last reply:</td><td>{!! $session->getDiff($tInfo['last_reply']) !!}</td></tr>
			<tr><td>Proof:</td><td>{{ $tInfo['proof'] ?: 'Not specified' }}</td></tr>
			<tr><td>Status:</td><td>{!! $stats[$tInfo['status']] ?? '' !!}</td></tr>
		</table>
	</div>
	<hr>
@foreach ($posts as $p)
		<div style="margin-left: 100px; margin-right: 100px">
			<div style="text-align: justify">
				<a href="{{ $vars->getURL('profile', $p['by_id']) }}">{{ $p['by_name'] }}</a> wrote {!! $session->getDiff($p['timestamp']) !!}
			</div>
			<hr>
			<div style="text-align: justify">
@if ($p['proof'])
					<a href="{{ $p['proof'] }}" target="_blank"><img src="{{ $p['proof'] }}" align="middle" width="100px" height="100px" alt="Proof" style="border: 0"></a>
@endif
				{!! nl2br(e($p['body'])) !!}
			</div>
		</div>
		<hr>
@endforeach
@if ($tInfo['status'] != 0)
	Reply is not possible!
@else
	<form action="" method="post">
		@csrf
		Your reply:<br>
		<textarea name="reply" cols="50" rows="5"></textarea><br>
		<input type="checkbox" name="opened"> Do not close the ticket (wait for sender's reply)<br>
		<input type="hidden" name="writer" value="{{ $tInfo['by_id'] }}">
		<input type="submit" name="subreply" value="Submit reply">
	</form>
	<hr size="1">
	<form action="" method="post">
		@csrf
		Move this ticket to
		<select name="newsec">
@foreach (['abuse', 'bugreport', 'multi', 'modreport', 'supgame', 'feedback', 'payment', 'support'] as $k)
			<option value="{{ $k }}">{{ $secs[$k] }}</option>
@endforeach
		</select>
		<input type="submit" name="submove" value="Move!">
	</form>
@endif
</blockquote>
<hr>
<a href="{{ $lensUrl('tickets', $tInfo['reason']) }}">Back to tickets</a>
@endsection
