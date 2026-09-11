@extends('layouts.game')
@section('content')
@php $m = fn($k) => $lang->getstr($k, 'market'); $f = fn($k) => $lang->getstr($k, 'filter'); @endphp
<script type="text/javascript" src="/include/js/ajax/market.js"></script>
<script>
	$(document).ready(function(){
			$("div #industry").click(function(){ $("div #stars, div #countries").hide(); $("div #industries").fadeIn('500'); });
			$("div #star").click(function(){ $("div #industries, div #countries").hide(); $("div #stars").fadeIn('500'); });
			$("div #country").click(function(){ $("div #industries, div #stars").hide(); $("div #countries").fadeIn('500'); });
		});
	var iID = {{ $indID }}; var qID = {{ $starID }}; var cID = {{ $counID }};
	no_offers = '{!! addslashes($f('filter_no_offer')) !!}';
</script>
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'industry', 'head' => $f('filter_industry'), 'img' => $database->getIndustryIcon($indID, $vars->getImgLoc('Icon')), 'alt' => $database->getIndustry($indID)])
			<div class="spacer"></div>
			@include('partials.filter-what', ['id' => 'star', 'head' => $f('filter_quality'), 'img' => '/images/game/'.$starID.'_star.gif', 'alt' => $starID.' star(s)'])
			<div class="spacer"></div>
			@include('partials.filter-what', ['id' => 'country', 'head' => $f('filter_country'), 'img' => $coun ? $vars->getImgLoc('CountryFlag').$coun['Flag'].'.gif' : '/images/elections/select.jpg', 'alt' => $coun['cName'] ?? 'Select'])
		</div>
@if ($logged)
			<div id="tblRanksFLT">
				<div class="info"><span id="money-ind">{{ $money }}</span><br>{{ $database->getCurrency($citInfo['CountryID']) }}</div>
				<div class="spacer"></div>
				<div class="info">{!! sprintf($f('filter_free_places'), '<span id="free-ind">'.$free.'</span>') !!}</div>
			</div>
@endif
<div style="clear: both"></div>
<div id="industries" class="selectbox">
	<b>{!! $f('filter_industry') !!}</b>
	<hr>
@foreach ($industries as $row)
			<div class="box-element">
			<a id="{{ $row['IndustryID'] }}" href="{{ $vars->getURL('market', $row['IndustryID'], $starID, $counID) }}">
			<img src="{{ $vars->getImgLoc('Icon') . $row['Icon'] }}.png" class='inlineIMGs' width="50px" align="absmiddle">
					{!! $lang->getstr('industry_' . strtolower($row['iName'])) !!}</a>
			</div>
@endforeach
	<div style="clear: both"></div>
</div>
<div id="stars" class="selectbox">
	<b>{!! $f('filter_quality') !!}</b>
	<hr>
		<div class="box-element">
		<a id="0" href="{{ $vars->getURL('market', $indID, 0, $counID) }}">
		<img src="/images/game/0_star.gif" class="inlineIMGs" align="absmiddle" alt="All companies"><br>{!! $f('filter_all_comps') !!}
		</a>
		</div>
@for ($co = 1; $co <= 5; $co++)
			<div class="box-element">
			<a id="{{ $co }}" href="{{ $vars->getURL('market', $indID, $co, $counID) }}">
			<img src="/images/game/{{ $co }}_star.gif" class="inlineIMGs" align="absmiddle" alt="{{ $co }}-star companies"><br>{!! sprintf($f('filter_star_comps'), $co) !!}
			</a>
			</div>
@endfor
	<div style="clear: both"></div>
</div>
@include('partials.filter-country', ['countryLink' => fn($c) => $vars->getURL('market', $indID, $starID, $c), 'nameKey' => 'Name'])
<div class="ajax-informer" style="font-weight: bold; margin-top: 2px; padding: 5px 2px; border: 2px solid transparent; border-radius: 5px; text-align: center; -webkit-transition: all 400ms linear; display: none">
</div>
<hr size="3" color="black">
<div id="market">
	<div class="provider">{!! $m('market_provider') !!}</div>
	<div class="stars">{!! $m('market_quality') !!}</div>
	<div class="stock">{!! $m('market_stock') !!}</div>
	<div class="price">{!! $m('market_price') !!}</div>
	<div class="buy">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (!$indID)
	<center>{!! $f('filter_select_industry') !!}</center>
@elseif (count($offers) < 1)
	<center>{!! $f('filter_no_offer') !!}</center>
@endif
@foreach ($offers as $o)
@php
	$rec = $o['rec']; $recs = $o['comp'];
	$target = request()->getRequestUri();
	if ($rec['IndustryID'] == 9 && $isCP) $target = $vars->getURL('law', 'new', 'buycli');
	if ($rec['IndustryID'] == 10 && $isCP) $target = $vars->getURL('law', 'new', 'buymun');
@endphp
	<form action="{{ $target }}" id="offer_{{ $rec['OfferID'] }}" method="post">
	@csrf
	<div class="provider"><a href="{{ $vars->getURL('company', $recs['CompanyID']) }}">{{ $recs['Name'] }}</a></div>
	<div class="stars"><img src="/images/game/{{ $rec['Quality'] }}_star.gif" alt="{{ $rec['Quality'] }} star(s)" class="inlineIMGs" align="absmiddle"></div>
	<div class="stock" id="stock_{{ $rec['OfferID'] }}">{{ $rec['Stock'] }}</div>
	<div class="price">{{ $rec['tPrice'] }} {{ $database->getCurrency($rec['CountryID']) }}</div>
	<div class="buy">
@if ($logged && $citInfo['CountryID'] == $counID)
@if ($rec['IndustryID'] != 9 && $rec['IndustryID'] != 10)
		<input type="hidden" name="Industry" value="{{ $recs['IndustryID'] }}">
		<input type="hidden" name="Stars" value="{{ $rec['Quality'] }}">
		<input type="hidden" name="actOffer" value="{{ $rec['OfferID'] }}">
		<input type="hidden" name="token" value="{{ md5($rec['OfferID'] . 'key44 M@rl<eT' . $rec['Quality'] . 'key44 M@rl<eT' . $recs['IndustryID']) }}">
		<input type="text" name="amount" size="4" class="txtAmount" maxlength="3" value="1">
		<input type="button" value="{{ $lang->getstr('buy') }}" onclick="buyProduct({{ $rec['OfferID'] }})" class="submit-blue-0">
@elseif ($isCP)
		<input type="hidden" name="CompanyID" value="{{ $recs['CompanyID'] }}">
		<input type="submit" value="{{ $lang->getstr('buy_for_region') }}" class="submit-blue-0">
@endif
@endif
	</div>
	<div style="clear: both"></div>
	<hr>
	</form>
@endforeach
</div>
@endsection
