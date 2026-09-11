@extends('layouts.game')
@section('content')
@php
	$type = ($baninf['due'] ?? '') === 'PERMANENTLY' ? 'suspended' : 'arrested';
@endphp
<div style="float: left">
	<img src="/images/game/jail.png">
</div>
<div style="float: right; width: 680px">
	Unfortunately, your account is {{ $type }} by eJahan moderation team...<br><br>
	The message posted about this action:
	<blockquote>
		<b>
			Your account is {{ $type }} for {{ $baninf['ban_reason'] }}.
@if ($type === 'arrested')
			You will be free again in {!! $session->getDiffF($baninf['ban_due'], '', 0) !!}.
@endif
		</b>
	</blockquote>
	<p>You can view your violations below. You have <b>A SINGLE CHANCE</b> to write an appeal and try to unban yourself with proof.</p>
	<p>Regards,<br>eJahan team</p>
</div>
<div style="clear: both"></div>
@include('partials.violations', ['vUser' => $citInfo])
@endsection
