<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="stylesheet" type="text/css" href="/lens/style.css">
	<script type="text/javascript" src="/include/js/jquery.js"></script>
	<title>eJahan LENS - Semi automated abuser finder and lookup tool</title>
</head>
<body>
@php $b = ' style="font-weight: bold"'; $u = $lensUrl; @endphp
@if ($mod)
	<div id="header">
		<span id="title">{{ $lensTitle }}</span>
		<div class="line"></div>
		<div class="navbar">
			<a href="{{ $u('index') }}"{!! ($lensAction == 'index' || !$lensAction) ? $b : '' !!}>Home</a>
@if ($mod['at_citizen'])
			| <a href="{{ $u('citizen') }}"{!! $lensAction == 'citizen' ? $b : '' !!}>Citizen</a>
@endif
@if ($mod['at_company'])
			| <a href="{{ $u('company') }}"{!! $lensAction == 'company' ? $b : '' !!}>Company</a>
@endif
@if ($mod['at_elections'])
			| <a href="{{ $u('election') }}"{!! $lensAction == 'election' ? $b : '' !!}>Election</a>
@endif
@if ($mod['at_transactions'])
			| <a href="{{ $u('trans') }}"{!! $lensAction == 'trans' ? $b : '' !!}>Transactions</a>
@endif
@if ($mod['at_multrack'])
			| <a href="{{ $u('multi') }}"{!! $lensAction == 'multi' ? $b : '' !!}>Multi tracker</a>
@endif
@if ($ulevel > \App\Http\Controllers\Lens\LensController::ACCESS_LOCAL)
			| <a href="{{ $u('ip') }}"{!! $lensAction == 'ip' ? $b : '' !!}>IP</a>
@endif
@if ($mod['at_tickets'])
			| <a href="{{ $u('tickets') }}"{!! $lensAction == 'tickets' ? $b : '' !!}>Tickets</a>@if ($pendingTickets && $lensAction != 'tickets')<sup style="background: red; padding: 1px 3px; border-radius: 3px; color: white; font-weight: bold">{{ $pendingTickets }}</sup>@endif
@endif
@if ($mod['at_payments'])
			| <a href="{{ $u('payment') }}"{!! $lensAction == 'payment' ? $b : '' !!}>Payment</a>
@endif
@if ($mod['at_mods'])
			| <a href="{{ $u('modmgr') }}"{!! $lensAction == 'modmgr' ? $b : '' !!}>Mod management</a>@if ($pendingRegs && $lensAction != 'modmgr')<sup style="background: red; padding: 1px 3px; border-radius: 3px; color: white; font-weight: bold">{{ $pendingRegs }}</sup>@endif
@endif
@if ($mod['at_ads'])
			| <a href="{{ $u('ads') }}"{!! $lensAction == 'ads' ? $b : '' !!}>Ads</a>
@endif
			| <a href="{{ $u('personal') }}"{!! $lensAction == 'personal' ? $b : '' !!}>Personal</a>
			| <a href="{{ $u('about') }}"{!! $lensAction == 'about' ? $b : '' !!}>About Lens</a>
			| <a href="{{ $u('logout') }}" onclick="return confirm('Are you sure you want to log out?')">Logout</a>
			| <a href="/">Back to eJahan</a>
		</div>
	</div>
@endif
	<center>
	<hr width="97%">
	<div id="page">
@yield('content')
	</div>
	</center>
</body>
</html>
