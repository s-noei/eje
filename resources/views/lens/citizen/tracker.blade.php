@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<form action="" method="post">
		@csrf
		Enter a citizen ID to track: <input type="text" name="trackid" value="{{ $id }}"> <input type="submit" value="Track" id="submits">
	</form>
</center>
<hr>
@if ($cit)
@php
	$links = [['Profile link', '<a href="' . $vars->getURL('profile', $cit['CitizenID']) . '" target="_blank">Click</a>']];
	if ($mod['at_transactions']) $links[] = ['Transactions', '<a href="' . $lensUrl('trans', 'citizen', $cit['CitizenID']) . '">Click</a>'];
	$stats = [
		['Citizen ID', $cit['CitizenID']], ['Citizen name', e($cit['name'])],
		['Gender', $cit['accType'] == 'co-account' ? $cit['accType'] : ($cit['female'] ? 'Female' : 'Male')],
		['Email', e($cit['email'])], ['Joined on', $cit['joined'] ? 'Day ' . $cit['joined'] : 'PRE-BETA'],
		['Avatar', '<img src="/uploads/avatars/citizen/' . e($cit['Avatar']) . '" class="Avatar-s">'],
		['About me', $cit['aboutme'] ? e($cit['aboutme']) : '<i>Nothing</i>'],
	];
	$reg = [['Invited by', $cit['refID'] ? '<a href="' . $lensUrl('citizen', 'tracker', $cit['refID']) . '">' . e($cit['refName']) . '</a>' : '<i>Direct registration</i>']];
	foreach ($invites as $i => $inv) $reg[] = ['Invite # ' . ($i + 1), '<a href="' . $lensUrl('citizen', 'tracker', $inv['CitizenID']) . '">' . e($inv['name']) . '</a>'];
@endphp
<center>
@include('lens._kv', ['head' => 'Related links', 'rows' => $links])
@include('lens._kv', ['head' => 'Base stats', 'rows' => $stats])
@if ($money !== null)
@include('lens._kv', ['head' => 'Money accounts', 'rows' => array_map(fn($m) => [e($m['curName']), $m['Amount']], $money)])
@endif
@include('lens._kv', ['head' => 'Registration', 'rows' => $reg])
	<table id="table" bordercolor="black" border="1">
		<tr><th colspan="3">Logging in</th></tr>
		<tr><td>IP</td><td>Times</td><td>Conflict report</td></tr>
@foreach ($logins as $l)
		<tr>
			<td><a href="{{ $lensUrl('ip', 'tracker', $l['Desc']) }}">{{ $l['Desc'] }}</a></td>
			<td>{{ $l['Times'] }}</td>
			<td>{{ $l['conflicts'] }}</td>
		</tr>
@endforeach
	</table>
</center>
@endif
@endsection
