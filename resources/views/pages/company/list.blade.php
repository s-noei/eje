@extends('layouts.game')
@section('content')
@php $c = fn($k) => $lang->getstr($k, 'company'); @endphp
@if ($mode === 'none')
	<h4>{!! $c('company_describe') !!}</h4>
	<a href="{{ $vars->getURL('jobs') }}" class="button-blue-1">{!! $c('company_getjob') !!}</a>&nbsp;&nbsp;&nbsp;
@else
	<h3>{!! $c('company_yours') !!}<hr width="90%"></h3>
@foreach ($companies as $row)
	<div id="companies">
		<div class="industry"><img src="{{ $database->getIndustryIcon($row['IndustryID'], $vars->getImgLoc('Icon')) }}"></div>
		<a href="{{ $vars->getURL('company', $row['CompanyID']) }}">
			<img src="{{ $vars->getImgLoc('CompanyAvatar') . $row['Avatar'] }}" class="Avatar-xs" align="absmiddle">&nbsp;{{ $row['Name'] }}
		</a>
	</div><hr width="90%">
@endforeach
@if ($mode === 'ca')
	<a href="{{ $vars->getURL('create', 'company') }}" class="button-blue-1">{!! $c('company_create') !!}</a>&nbsp;&nbsp;&nbsp;
	<a href="{{ $vars->getURL('cmarket') }}" class="button-blue-1">{!! $c('company_buy') !!}</a>&nbsp;&nbsp;&nbsp;
@endif
@endif
@endsection
