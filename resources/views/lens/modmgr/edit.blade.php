@extends('lens.layout')
@section('content')
@include('lens._navbar')
<style> select { width: 200px } </style>
<center>
	<form action="" method="post">
		@csrf
		Enter citizen ID to change access: <input type="text" size="5" name="citID" value="{{ $id ?: '' }}" style="text-align: center"> <input type="submit" name="subedit" value="Go!">
	</form>
	<hr>
{!! $msg !!}
@if ($id && !$cit)
	This citizen has no access to lens.
	<form action="" method="post">
		@csrf
		<input type="hidden" name="modID" value="{{ $id }}" />
		<select name="access">@foreach ([4, 5, 6, 7, 8, 9] as $a)<option value="{{ $a }}">{{ $accs[$a] }}</option>@endforeach</select>
		<input type="submit" name="subinvite" value="Send invite to this citizen!" />
	</form>
@elseif ($cit)
@php $A = (int) $cit['Access']; $sel = fn($f, $v) => (string) $cit[$f] === (string) $v ? ' selected="selected"' : ''; @endphp
	<form action="" method="post">
		@csrf
		<table id="table" bordercolor="black" border="1" width="400px">
			<tr><td>Citizen ID</td><td>{{ $id }}</td></tr>
			<tr><td>Citizen name</td><td>{{ $cit['name'] }}</td></tr>
			<tr><td>Current access</td><td>
				<select name="access">
@if ($A == 1)<option value="1" disabled="disabled" selected="selected">Pending approval</option>@endif
@foreach ([4, 5, 6, 7, 8, 9] as $a)<option value="{{ $a }}"{!! $sel('Access', $a) !!}>{{ $accs[$a] }}</option>@endforeach
				</select>
			</td></tr>
			<tr><td>Access data</td><td><input type="text" name="AccessD" value="{{ $cit['AccessD'] }}" size="2" maxlength="2" /></td></tr>
			<tr><th colspan="2"><input type="submit" name="subchange" value="Change!" /></th></tr>
		</table>
	</form>
@if ($A > 1)
	&nbsp;
	<form name="accessd" action="" method="post">
		@csrf
		<table id="table" bordercolor="black" border="1" width="400px">
			<tr><th colspan="2">Access details</th></tr>
@foreach (['at_citizen' => 'Citizens', 'at_company' => 'Companies', 'at_country' => 'Countries'] as $f => $l)
			<tr><td>{{ $l }}</td><td><select name="{{ $f }}">
				<option value="0"{!! $sel($f, 0) !!}>No access</option>
				<option value="1"{!! $sel($f, 1) !!}>View stats</option>
@if ($A >= 5)<option value="2"{!! $sel($f, 2) !!}>Track</option>@endif
@if ($A >= 7)<option value="3"{!! $sel($f, 3) !!}>Edit</option>@endif
			</select></td></tr>
@endforeach
			<tr><td>Elections</td><td><select name="at_elections"><option value="0"{!! $sel('at_elections', 0) !!}>No access</option>@if ($A >= 7)<option value="1"{!! $sel('at_elections', 1) !!}>Have access</option>@endif</select></td></tr>
			<tr><td>Multi Tracker</td><td><select name="at_multrack"><option value="0"{!! $sel('at_multrack', 0) !!}>No access</option>@if ($A >= 7)<option value="1"{!! $sel('at_multrack', 1) !!}>Access - Symbols</option>@endif @if ($A >= 8)<option value="2"{!! $sel('at_multrack', 2) !!}>Full Access</option>@endif</select></td></tr>
			<tr><td>Transactions</td><td><select name="at_transactions"><option value="0"{!! $sel('at_transactions', 0) !!}>No access</option>@if ($A >= 6)<option value="1"{!! $sel('at_transactions', 1) !!}>See names</option>@endif @if ($A >= 7)<option value="2"{!! $sel('at_transactions', 2) !!}>Full access</option>@endif @if ($A >= 8)<option value="3"{!! $sel('at_transactions', 3) !!}>Full access + Refund</option>@endif</select></td></tr>
			<tr><td>Tickets</td><td><select name="at_tickets">
				<option value=""{!! $sel('at_tickets', '') !!}>No access</option>
@foreach (['abuse' => 'Abuse', 'appeal' => 'Appeals', 'bugreport' => 'Bug reports', 'feedback' => 'Feedback', 'supgame' => 'Game support', 'multi' => 'Report multiple accounts', 'support' => 'Support eJahan'] as $k => $v)
				<option value="{{ $k }}"{!! $sel('at_tickets', $k) !!}>{{ $v }}</option>
@endforeach
				<option value="Special" disabled="disabled" style="color: white; background-color: maroon;">Special access</option>
@foreach (['smod' => 'Supermod', 'guard' => 'Guard', 'admin' => 'Admin'] as $k => $v)
				<option value="{{ $k }}"{!! $sel('at_tickets', $k) !!}>{{ $v }}</option>
@endforeach
			</select></td></tr>
@foreach (['at_payments' => 'Payments', 'at_ads' => 'Ads', 'at_mods' => 'Mod management'] as $f => $l)
			<tr><td>{{ $l }}</td><td><select name="{{ $f }}"><option value="0"{!! $sel($f, 0) !!}>No access</option>@if ($A >= 8)<option value="1"{!! $sel($f, 1) !!}>Have access</option>@endif</select></td></tr>
@endforeach
			<tr><td>Punishment</td><td><select name="at_punishment"><option value="0"{!! $sel('at_punishment', 0) !!}>No access</option>@if ($A >= 5)<option value="1"{!! $sel('at_punishment', 1) !!}>Predefined violations</option>@endif @if ($A >= 6)<option value="2"{!! $sel('at_punishment', 2) !!}>Predefined + Custom violations</option>@endif @if ($A >= 7)<option value="3"{!! $sel('at_punishment', 3) !!}>Arrest</option>@endif @if ($A >= 8)<option value="4"{!! $sel('at_punishment', 4) !!}>Ban</option>@endif</select></td></tr>
			<tr><td>View money</td><td><select name="at_viewmoney"><option value="0"{!! $sel('at_viewmoney', 0) !!}>No access</option>@if ($A >= 7)<option value="1"{!! $sel('at_viewmoney', 1) !!}>Have access</option>@endif</select></td></tr>
			<tr><th colspan="2"><input type="submit" name="subeditdone" value="Save access details" /></th></tr>
		</table>
	</form>
@endif
@endif
</center>
@endsection
