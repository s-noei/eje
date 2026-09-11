@extends('layouts.game')
@section('content')
@php $mo = fn($k) => $lang->getstr($k, 'monetary'); $f = fn($k) => $lang->getstr($k, 'filter'); @endphp
<script>
	$(document).ready(function(){
			$("div #sell").click(function(){ $("div #buybox, div #addbox, div #accbox").hide(); $("div #sellbox").fadeIn('500'); });
			$("div #buy").click(function(){ $("div #sellbox, div #addbox, div #accbox").hide(); $("div #buybox").fadeIn('500'); });
			$("div #acc").click(function(){ $("div #sellbox, div #addbox, div #buybox").hide(); $("div #accbox").fadeIn('500'); });
			$(".add").click(function(){ $("div #buybox, div #sellbox, div #accbox").hide(); $("div #addbox").fadeIn('500'); });
		});
	function buyMoney(amount, rate, buy_cur, sell_cur) {
		var price = Math.round(amount * rate * 100) / 100;
		if (amount) return confirm('Do you want to buy ' + amount + ' ' + buy_cur + ' for ' + price + ' ' + sell_cur + '?'); else return false;
	}
</script>
{!! $msg ?? '' !!}
	<div id="tblRanksFLT">
		@include('partials.filter-what', ['id' => 'sell', 'head' => $f('filter_sell'), 'img' => $database->getCurrencyIco($sellID, $vars->getImgLoc('CountryFlag'))])
		<div class="spacer"></div>
		@include('partials.filter-what', ['id' => 'buy', 'head' => $f('filter_buy'), 'img' => $database->getCurrencyIco($buyID, $vars->getImgLoc('CountryFlag'))])
@if ($isCA)
		<div class="spacer"></div>
		@include('partials.filter-what', ['id' => 'acc', 'head' => $f('filter_account'), 'img' => $accInfo['avatar'], 'alt' => $accInfo['name']])
@endif
	</div>
	<div id="tblRanksFLT">
		<div class="info">{{ round($sellHave, 2) }}<br>{{ $database->getCurrency($sellID) }}</div>
		<div class="spacer"></div>
		<div class="info">{{ round($buyHave, 2) }}<br>{{ $database->getCurrency($buyID) }}</div>
	</div>
	<div style="clear: both"></div>
@if ($buyID != 1 || $citInfo['active'])
			<a href="{{ $vars->getURL('exchange', $buyID, $sellID) }}" id="buttons">{!! $mo('monetary_swap') !!}</a>
			&nbsp;
@endif
<a href="javascript:void(0)" class="add" id="buttons">{!! $mo('monetary_add_offer') !!}</a>
&nbsp;
<a href="{{ $vars->getURL('exchange', 'my') }}" id="buttons">{!! $mo('monetary_view_mine') !!}</a><br><br>
<div id="addbox" class="selectbox">
	<b>{!! $mo('monetary_add') !!}</b><hr>
	<form id="addoffer" action="" method="post" name="addOffer">
		@csrf
		{!! $mo('monetary_add_sell') !!}:
		<input name="amount" type="text" maxlength="5" size="5"> {{ $database->getCurrency($buyID) }}
		<input type="hidden" name="bCur" value="{{ $sellID }}">
		<input type="hidden" name="sCur" value="{{ $buyID }}">
		<input type="hidden" name="accID" value="{{ $accID }}">
		<input type="hidden" name="accType" value="{{ $accType }}">
		<input type="hidden" name="subaddoffer" value="1">
		<input type="hidden" name="token" value="{{ md5($citInfo['CitizenID'] . $buyID . $sellID . $accID . $accType . 'add 0ff3r k3y') }}">
		{!! $mo('monetary_add_rate') !!} (1 {{ $database->getCurrency($buyID) }} = <input name="rate" type="text" maxlength="5" size="5"> {{ $database->getCurrency($sellID) }})
		<a href="#" id="buttons" onclick="document.addOffer.submit(); return false">{!! $mo('monetary_add_submit') !!}</a>
	</form>
</div>
<div id="sellbox" class="selectbox">
	<b>{!! $mo('monetary_select_sell') !!}</b><hr>
@foreach ($myCurrencies as $row)
@if ($row['CurID'] != 1 || $citInfo['active'])
				<div class="box-element-s">
					<a href="{{ $vars->getURL('exchange', $row['CurID'], $buyID) }}">
					<img class="inlineIMGs" align="absmiddle" src="{{ $vars->getImgLoc('CurrencyIcon') . $row['Flag'] }}.gif" alt="1"> {{ $row['curName'] }}</a>
				</div>
@endif
@endforeach
	<div style="clear: both"></div>
</div>
<div id="buybox" class="selectbox">
	<b>{!! $mo('monetary_select_buy') !!}</b><hr>
@foreach ($allCurrencies as $row)
		<div class="box-element-s">
		<a href="{{ $vars->getURL('exchange', $sellID, $row['CountryID']) }}">
		<img class="inlineIMGs" align="absmiddle" src="/images/flags/s/{{ $row['Flag'] }}.gif" alt="{{ $row['Flag'] }}"> {{ $row['curName'] }}</a>
		</div>
@endforeach
	<div style="clear: both"></div>
</div>
<div id="accbox" class="rankBox" style="display: none; text-align: center; width: 700px">
	<b>{!! $mo('monetary_select_accounts') !!}</b><hr>
	<div id="cName" style="width: 245px; height: 55px; text-align: justify">
		<a href="{{ $vars->getURL('exchange', $sellID, $buyID) }}">
			<img src="{{ $vars->getImgLoc('CitizenAvatar') . $citInfo['Avatar'] }}" title="{{ $citInfo['name'] }}" class="Avatar-xs" align="absmiddle"> {{ $citInfo['name'] }}
		</a>
	</div>
@foreach ($myCompanies as $comps)
			<div id="cName" style="width: 245px; height: 55px; text-align: justify">
				<a href="{{ $vars->getURL('exchange', $sellID, $buyID, $comps['CompanyID']) }}">
					<img src="{{ $vars->getImgLoc('CompanyAvatar') . $comps['Avatar'] }}" title="{{ $comps['Name'] }}" class="Avatar-xs" align="absmiddle"> {{ $comps['Name'] }}
				</a>
			</div>
@endforeach
</div>
<div id="mmarket">
	<div class="provider">{!! $mo('monetary_table_provider') !!}</div>
	<div class="amount">{!! $mo('monetary_table_amount') !!}</div>
	<div class="erate">{!! $mo('monetary_table_rate') !!}</div>
	<div class="buy">{!! $mo('monetary_table_buy') !!}</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
	{!! $f('filter_no_offer') !!}
@endif
@foreach ($rows as $rec)
	<form name="exchange_{{ $rec['mOfferID'] }}" action="" method="post">
	@csrf
	<div class="provider"><a href="{{ $vars->getURL($rec['sellerType'] == 'citizen' ? 'profile' : 'company', $rec['sellerID']) }}">{{ $rec['providerName'] }}</a></div>
	<div class="amount">{{ $rec['Amount'] }} {{ $database->getCurrency($rec['sCurID']) }}</div>
	<div class="erate">1 {{ $database->getCurrency($rec['sCurID']) }} = {{ $rec['eRate'] }} {{ $database->getCurrency($rec['bCurID']) }}</div>
	<div class="buy">
@if ($rec['sellerID'] != $accID || $rec['sellerType'] != $accType)
				<input type="hidden" name="actOffer" value="{{ $rec['mOfferID'] }}">
				<input type="hidden" name="accID" value="{{ $accID }}">
				<input type="hidden" name="accType" value="{{ $accType }}">
				<input type="hidden" name="token" value="{{ md5($rec['mOfferID'] . $accID . $accType . 'A key for monetary markett...!!') }}">
				<input type="text" name="amount" size="5" class="txtAmount-4" maxlength="6" align="absmiddle"> {{ $database->getCurrency($buyID) }}
				<a id="buttons" href="#" onclick="if(buyMoney(document.exchange_{{ $rec['mOfferID'] }}.amount.value, {{ $rec['eRate'] }}, '{{ $database->getCurrency($rec['sCurID']) }}', '{{ $database->getCurrency($rec['bCurID']) }}')) document.exchange_{{ $rec['mOfferID'] }}.submit(); return false;">Buy</a>
@endif
	</div>
	<div style="clear: both"></div>
	<hr>
	</form>
@endforeach
</div>
@endsection
