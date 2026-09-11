@extends('layouts.game')
@section('content')
@php $mo = fn($k) => $lang->getstr($k, 'monetary'); @endphp
<script>$(document).ready(function(){ $("div #acc").click(function(){ $("div #accbox").fadeIn('500'); }); });</script>
@if ($isCA)
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'acc', 'head' => 'Account', 'img' => $accInfo['avatar'], 'alt' => $accInfo['name']])
		</div>
@endif
		<div id="tblRanksFLT"><div class="info">Your offers</div></div>
		<div style="clear: both"></div>
<a href="{{ $vars->getURL('exchange') }}" id="buttons">View all offers</a><br><br>
<div id="accbox" class="rankBox" style="display: none; text-align: center; width: 700px">
	<b>{!! $mo('monetary_select_accounts') !!}</b><hr>
	<div id="cName" style="width: 245px; height: 55px; text-align: justify">
		<a href="{{ $vars->getURL('exchange', 'my') }}"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $citInfo['Avatar'] }}" title="{{ $citInfo['name'] }}" class="Avatar-xs" align="absmiddle"> {{ $citInfo['name'] }}</a>
	</div>
@foreach ($myCompanies as $comps)
			<div id="cName" style="width: 245px; height: 55px; text-align: justify">
				<a href="{{ $vars->getURL('exchange', 'my', $comps['CompanyID']) }}"><img src="{{ $vars->getImgLoc('CompanyAvatar') . $comps['Avatar'] }}" title="{{ $comps['Name'] }}" class="Avatar-xs" align="absmiddle"> {{ $comps['Name'] }}</a>
			</div>
@endforeach
</div>
<div id="mmarket">
	<div class="my-sell">Sell</div>
	<div class="my-buy">Buy</div>
	<div class="my-amount">Amount</div>
	<div class="my-erate">Exchange Rate</div>
	<div class="my-oper">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (count($offs) < 1)
	<div>Currently no offers in this section.</div>
@endif
@foreach ($offs as $rec)
@if ($rec['Amount'] != 0)
	<form action="" method="post" name="del{{ $rec['mOfferID'] }}" onsubmit="return confirm('Are you sure?')">
		@csrf
		<div class="my-sell">{{ $database->getCurrency($rec['sCurID']) }}</div>
		<div class="my-buy">{{ $database->getCurrency($rec['bCurID']) }}</div>
		<div class="my-amount">{{ $rec['Amount'] }} {{ $database->getCurrency($rec['sCurID']) }}</div>
		<div class="my-erate">1 {{ $database->getCurrency($rec['sCurID']) }} = {{ $rec['eRate'] }} {{ $database->getCurrency($rec['bCurID']) }}</div>
		<div class="my-oper">
			<input type="hidden" name="delOffer" value="{{ $rec['mOfferID'] }}">
			<input type="hidden" name="accID" value="{{ $accID }}">
			<input type="hidden" name="accType" value="{{ $accType }}">
			<input type="hidden" name="token" value="{{ md5($rec['mOfferID'] . $accID . $accType . 'k3y4 d3l3+3 0ff3r') }}">
			<input type="submit" value="Remove" class="cmdRemove">
		</div>
		<div style="clear: both"></div>
		<hr>
	</form>
@endif
@endforeach
</div>
@endsection
