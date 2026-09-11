@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/tickets.css">
	<a href="{{ $vars->getURL('contact', 'track') }}" class="button-blue-1">Back to tickets</a>
@foreach ($errors as $e)<h3 class="errHandle">{{ $e }}</h3>@endforeach
	<div id="ticket-view">
		<div class="cat">{{ $tInfo['subject'] }}</div>
		<div class="ticket-info">
			By: {{ $tInfo['by_name'] }}<br>
			Department: {{ $sections[$tInfo['reason']] ?? $tInfo['reason'] }}<br>
			Priority: {!! $priors[$tInfo['priority']] ?? '' !!}<br>
		</div>
@if (count($posts) < 1)
					<div class="cat">NO POSTS!</div>
@endif
@foreach ($posts as $ticket)
						<div class="ticket-head">
							<a href="{{ $vars->getURL('profile', $ticket['by_id']) }}">{{ $ticket['by_name'] }}</a>
@if ($ticket['by_id'] != 1)
                                    <div class="ticket-rate">User</div>
@elseif ($ticket['auto_answer'])
                                    <div class="ticket-rate">Robot</div>
@else
                                    <div class="ticket-rate">Moderator</div>
                                    <div class="ticket-rate">
@if (!$ticket['rate'])
                                            <form action="" method="post" name="rate_{{ $ticket['post_id'] }}">
                                            @csrf
@endif
                                    <select name="rate" style="font-size: 7pt;" @if ($ticket['rate']) disabled="disabled" @else onchange="document.rate_{{ $ticket['post_id'] }}.submit()" @endif>
@foreach (['Rate', 'Awful', 'Bad', 'Fair', 'Good', 'Excellent'] as $i => $r)
                                        <option value="{{ $i }}" @if ($ticket['rate'] == $i) selected="selected" @endif>{{ $r }}</option>
@endforeach
                                    </select>
@if (!$ticket['rate'])
                                                <input type="hidden" name="postID" value="{{ $ticket['post_id'] }}" />
                                                <input type="hidden" name="token" value="{{ md5($ticket['post_id'] . 'TiCK3T') }}" />
                                            </form>
@endif
                                    </div>
@endif
						</div>
						<div class="ticket-body">
							{!! nl2br(e($ticket['body'])) !!}
@if ($ticket['proof'])
									<hr size="1">
									Proof: <a href="{{ $ticket['proof'] }}" target="_blank">{{ $ticket['proof'] }}</a>
@endif
@if ($ticket['auto_answer'])
									<center style="color: red;">This is an automatic answer created by eJahan ticket system</center>
@endif
							<hr size="1">
							<i>&nbsp;&nbsp;&nbsp;wrote {!! $session->getDiff($ticket['timestamp']) !!}</i>
						</div>
@endforeach
	</div>
@if ($citInfo['CitizenID'] == $tInfo['by_id'])
			<form name="closeT" action="" method="post">
				@csrf
				<input type="hidden" name="subclose" value="1">
				<a href="javascript:void(0)" onclick="document.closeT.submit()" class="button-blue-1">Close the ticket</a>
			</form>
			<form name="reply" action="" method="post" enctype="multipart/form-data">
				@csrf
				<br>
				<b>Reply</b><hr>
				<textarea name="message" cols="70" rows="8"></textarea>
				<br>
				Proof (optional):<br>
				<input type="file" name="proof" size="40">
                <br />JPEG Images<br />Max. size: 1024KB<br />
				<input type="hidden" name="subreply" value="1">
				<a href="javascript:void(0)" onclick="document.reply.submit()" class="button-blue-1">Submit reply</a>
			</form>
@endif
@endsection
