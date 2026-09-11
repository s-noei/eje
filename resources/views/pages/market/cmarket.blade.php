@extends('layouts.game')
@section('content')
<script language='javascript'>
	$(document).ready(function(){
			$("div #industry").click(function(){ $("div #stars, div #countries").hide(); $("div #industries").fadeIn('500'); });
			$("div #country").click(function(){ $("div #industries, div #stars").hide(); $("div #countries").fadeIn('500'); });
		});
</script>
{!! $msg ?? '' !!}
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'industry', 'head' => 'Industry', 'img' => $database->getIndustryIcon($indID, $vars->getImgLoc('Icon')), 'alt' => $database->getIndustry($indID)])
			<div class="spacer"></div>
			@include('partials.filter-what', ['id' => 'country', 'head' => 'Country', 'img' => $coun ? $vars->getImgLoc('CountryFlag').$coun['Flag'].'.gif' : '/images/elections/select.jpg', 'alt' => $coun['cName'] ?? 'Select'])
		</div>
@if ($logged)
			<div id="tblRanksFLT"><div class="info">{{ $money }}<br>{{ $database->getCurrency(1) }}</div></div>
@endif
<div style="clear: both"></div>
<div id="industries" class="selectbox">
	<b>Industries</b>
	<hr>
@foreach ($industries as $row)
			<div class="box-element">
			<a href="{{ $vars->getURL('cmarket', $row['IndustryID'], $counID) }}">
			<img src="{{ $vars->getImgLoc('Icon') . $row['Icon'] }}.png" class='inlineIMGs' width="50px" align="absmiddle"> {{ $row['iName'] }}</a>
			</div>
@endforeach
	<div style="clear: both"></div>
</div>
@include('partials.filter-country', ['countryLink' => fn($c) => $vars->getURL('cmarket', $indID, $c)])
<hr size="3" color="black">
<div id="cmarket">
	<div class="avatar">&nbsp;</div>
	<div class="company">Company</div>
	<div class="price">Price</div>
	<div class="bid">Current bid</div>
	<div class="buy">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (!$indID)
	Please select an industry.
@elseif (count($rows) < 1)
	Currently no offers in this section.
@endif
@foreach ($rows as $r)
@php $rec = $r['rec']; $recs = $r['comp']; $newbid = $rec['sale_bid_amount'] ? ($rec['sale_bid_amount'] + $rec['sale_step']) : $rec['sale_base']; @endphp
	<form action="" method="post">
	@csrf
	<div class="avatar"><img src="{{ $vars->getImgLoc('CompanyAvatar') . $recs['Avatar'] }}" width="35px" align="absmiddle"></div>
	<div class="company">
		<a href="{{ $vars->getURL('company', $recs['CompanyID']) }}">{{ $recs['Name'] }}</a><br>
		<img src="/images/game/{{ $recs['Stars'] }}_star.gif" alt="{{ $recs['Stars'] }} star(s)" width="60px" class="inlineIMGs" align="absmiddle">
	</div>
	<div class="price">{{ $rec['sale_base'] }} Tala<br>(+{{ $rec['sale_step'] }} Tala)</div>
	<div class="bid">
		{{ $rec['sale_bid_amount'] ? $rec['sale_bid_amount'] . ' Tala' : '---' }}<br>
		{!! $rec['sale_bid_id'] ? '<a href="'.$vars->getURL('profile', $rec['sale_bid_id']).'">'.e($vars->getShortText($rec['sale_bid_name'] ?? '', 10)).'</a>' : '' !!}
	</div>
	<div class="buy">
		<input type="hidden" name="Industry" value="{{ $recs['IndustryID'] }}">
		<input type="hidden" name="actOffer" value="{{ $rec['CompanyID'] }}">
		<input type="hidden" name="token" value="{{ md5($rec['sale_base'] . $rec['CompanyID'] . $rec['IndustryID'] . 'k3y44 l3uy c0mp@ny') }}">
@if ($logged && $recs['ManagerID'] == $citInfo['CitizenID'])
		<a href="{{ $vars->getURL('company', $recs['CompanyID'], 'sell') }}" class="cmdRemove">Manage</a>
@elseif ($logged && $citInfo['CountryID'] == $counID && $isCA && $rec['sale_bid_id'] != $citInfo['CitizenID'])
		<input type="hidden" name="subbuy" value="1">
		<input type="submit" value="Bid" name="bid" id="submits" onclick="return confirm('Do you want to place a bid with {{ $newbid }} Tala?')">
@endif
	</div>
	</form>
	<div style="clear: both"></div>
	<hr>
@endforeach
</div>
@endsection
