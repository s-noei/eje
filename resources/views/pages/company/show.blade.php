@extends('layouts.game')
@section('content')
@push('styles')
<link rel="stylesheet" type="text/css" href="/include/css/company.css">
<link rel="stylesheet" type="text/css" href="/include/css/company-new.css">
@endpush
@php $c = fn($k) => $lang->getstr($k, 'company'); @endphp
<script>
	var showed = 0;
	$(document).ready(function(){
			$(".company-logo").click(function(){
					if (showed == 1) $("div #mycompanies").slideUp(500); else $("div #mycompanies").slideDown(500);
					showed = 1 - showed
				});
		});
</script>
	<div class="company-page">
		<div class="company-header">
			<div class="company-logo">
				<img src="{{ $aIMG }}" class="Avatars" alt="{{ $row['Name'] }}">
			</div>
			<div class="company-details">
				<font size="3">
					<a href="{{ $vars->getURL('company', $req_id) }}"><b>{!! $vars->lenTrim($row['Name'], 20, 'char', 0, "Name") !!}</b></a>
				</font>
				{!! $vars->getWikiLink('Company', $row['Name']) !!}
				{!! $row['sale_base'] ? " <sup style='color: red'>" . $c('company_for_sale') . "</sup>" : '' !!}
				<br>
				<img src="{{ $cIMG }}" class="Flag-xs" align="absmiddle">
				{!! $c('company_manager') !!}:
				<a href="{{ $vars->getURL('profile', $row['ManagerID']) }}">{{ $row['ManagerName'] ?: 'Suspended' }}</a>
				<br>
				<img src="/images/game/{{ $row['Stars'] }}_star.gif" width="75px" title="{{ sprintf($c('company_quality'), $row['Stars']) }}">
@if ($isManager)
				<div class="company-manage">
					<a href="{{ $vars->getURL('company', $row['CompanyID'], 'edit') }}" class="company-edit" title="{{ $c('company_edit') }}"><img src="/images/pages/company/edit.png"></a>
					<a href="{{ $vars->getURL('company', $row['CompanyID'], 'sell') }}" class="company-sell" title="{{ $c('company_sell') }}"><img src="/images/pages/company/sell.png"></a>
				</div>
@endif
			</div>
			<div class="company-message">
				<b>{!! $c('company_message') !!}:</b><br>
				{!! $row['company_message'] ? nl2br(e($row['company_message'])) : '<i>' . $c('company_no_message') . '</i>' !!}
			</div>
		</div>
@if ($isManager)
			<div id="mycompanies" style="display: none; border: 1px solid; padding: 5px;">
@foreach ($myCompanies as $cmp)
				<a href="{{ $vars->getURL('company', $cmp['CompanyID']) }}">
					<img src="{{ $vars->getImgLoc('CompanyAvatar') . $cmp['Avatar'] }}" align="absmiddle" class="Avatar-xs">
					<font size="1">{{ $cmp['Name'] }}</font>
				</a>
				<br>
@endforeach
			</div>
@endif
@include('pages.company.'.$sub)
	</div>
@endsection
