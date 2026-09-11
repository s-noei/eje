@extends('layouts.game')
@section('content')
<div id="invite-head">
	<div class="title">Invite your friends</div>
	You can invite your friends into eJahan and receive 10 TALA after they reached Social Puberty Level 2.
	The only thing you should do is publishing your personal referral link to eJahan!
@if ($citInfo['puberty'] >= 1)
	Your personal referral link is:
	<a href="{{ url('/referrer-' . $citInfo['CitizenID'] . '.html') }}">{{ url('/referrer-' . $citInfo['CitizenID'] . '.html') }}</a>
	<hr>
	<b>Your invited citizens</b>
@foreach ($invited as $i => $row)
	<br>{{ $i + 1 }}. Currently playing as <a href="{{ $vars->getURL('profile', $row['CitizenID']) }}">{{ $row['name'] }}</a>
@endforeach
@else
	<h3 class="errHandle">You must reach the Social Puberty Level 1 to be able to send invites</h3>
@endif
</div>
@endsection
