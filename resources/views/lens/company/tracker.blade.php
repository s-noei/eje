@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<form action="" method="post">
		@csrf
		Enter a company ID to track: <input type="text" name="trackid" value="{{ $id }}"> <input type="submit" value="Track" id="submits">
	</form>
</center>
<hr>
@if ($comp)
@php
	$cid = $comp['CompanyID'];
	$links = [['Company page', '<a href="' . $vars->getURL('company', $cid) . '" target="_blank">Click</a>']];
	if ($mod['at_transactions']) $links[] = ['Transactions', '<a href="' . $lensUrl('trans', 'company', $cid) . '">Click</a>'];
	$links[] = ['Sales history', '<a href="' . $lensUrl('company', 'sales', $cid) . '">Click</a>'];
	if ($mod['at_company'] == 3) $links[] = ['Actions', '<a href="' . $lensUrl('company', 'actions', $cid) . '">Click</a>'];
	$stats = [['Company ID', $cid], ['Company name', e($comp['Name'])], ['Located in', e($comp['CountryName']) . ', ' . e($comp['RegionName'])],
		['Avatar', '<img src="/uploads/avatars/company/' . e($comp['Avatar']) . '" class="Avatar-s">'],
		['Message', $comp['company_message'] ? nl2br(e($comp['company_message'])) : '<i>Nothing</i>']];
@endphp
<center>
@include('lens._kv', ['head' => 'Related links', 'rows' => $links])
@include('lens._kv', ['head' => 'Base stats', 'rows' => $stats])
@if ($money !== null)
@include('lens._kv', ['head' => 'Money accounts', 'rows' => array_map(fn($m) => [e($m['curName']), $m['Amount']], $money)])
@endif
@foreach ([['Managers (by collecting money)', $collectors], ['Managers (by investing money)', $investors]] as [$head, $list])
	<table id="table" bordercolor="black" border="1">
		<tr><th colspan="3">{{ $head }}</th></tr>
		<tr><td>ID</td><td>Name</td>@if ($mod['at_company'] == 3)<td>Give it!</td>@endif</tr>
@foreach ($list as $l)
		<tr>
			<td>{{ $l['CitizenID'] }}</td>
			<td><a href="{{ $lensUrl('citizen', 'tracker', $l['CitizenID']) }}">{{ $l['name'] }}</a></td>
@if ($mod['at_company'] == 3)
			<td><form action="" method="post">@csrf<input type="hidden" name="manager" value="{{ $l['CitizenID'] }}"><input type="submit" name="submanager" value="Give"></form></td>
@endif
		</tr>
@endforeach
	</table>
	&nbsp;
@endforeach
</center>
@endif
@endsection
