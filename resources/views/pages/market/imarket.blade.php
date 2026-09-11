@extends('layouts.game')
@section('content')
<script>
	$(document).ready(function(){
			$("div #industry").click(function(){ $("div #stars, div #addoffer").hide(); $("div #industries").fadeIn('500'); });
			$("div #star").click(function(){ $("div #industries, div #addoffer").hide(); $("div #stars").fadeIn('500'); });
@if (!$isCA)
			$(".addoffer").click(function(){ $("div #industries, div #stars").hide(); $("div #addoffer").fadeIn('500'); });
@endif
		});
</script>
{!! $msg ?? '' !!}
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'industry', 'head' => 'Industry', 'img' => $database->getIndustryIcon($indID, $vars->getImgLoc('Icon')), 'alt' => $database->getIndustry($indID)])
			<div class="spacer"></div>
			@include('partials.filter-what', ['id' => 'star', 'head' => 'Stars', 'img' => '/images/game/'.$starID.'_star.gif', 'alt' => $starID.' star(s)'])
		</div>
			<div id="tblRanksFLT">
				<div class="info">{{ $money }}<br>{{ $database->getCurrency(1) }}</div>
				<div class="spacer"></div>
				<div class="info">{{ $free }}<br>FREE<br>Places</div>
@if (!$isCA)
				<div class="spacer"></div>
				<div class="info">{{ $used }}/5 Used<br>Offers</div>
@endif
			</div>
<div style="clear: both"></div>
@if (!$isCA)
@if ($go !== 'my')
        			<a id="buttons" href="javascript:void(0)" class="addoffer">Add an offer</a>
        			<a id="buttons" href="{{ $vars->getURL('imarket', 'my') }}">View my offers</a>
@else
        			<a id="buttons" href="{{ $vars->getURL('imarket') }}">View all offers</a>
@endif
@endif
<div id="industries" class="selectbox">
	<b>Industries</b>
	<hr>
@foreach ($industries as $row)
			<div class="box-element">
			<a href="{{ $vars->getURL('imarket', $row['IndustryID'], $starID) }}">
			<img src="{{ $vars->getImgLoc('Icon') . $row['Icon'] }}.png" class="inlineIMGs" width="50px" align="absmiddle"> {{ $row['iName'] }}</a>
			</div>
@endforeach
	<div style="clear: both"></div>
</div>
<div id="stars" class="selectbox">
	<b>Stars</b>
	<hr>
		<div id="sName" class="box-element">
		<a href="{{ $vars->getURL('imarket', $indID, 0) }}"><img src="/images/game/0_star.gif" class="inlineIMGs" align="absmiddle" alt="All companies"><br>All companies</a>
		</div>
@for ($co = 1; $co <= 5; $co++)
			<div id="sName" class="box-element">
			<a href="{{ $vars->getURL('imarket', $indID, $co) }}"><img src="/images/game/{{ $co }}_star.gif" class="inlineIMGs" align="absmiddle" alt="{{ $co }}-star companies"><br>{{ $co }}-star companies</a>
			</div>
@endfor
	<div style="clear: both"></div>
</div>
@if ($go === 'my')
<hr size="3" color="black">
<div id="market">
	<div class="provider">Type</div><div class="stars">Quality</div><div class="price">Price</div><div class="buy">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (count($offs) < 1)
	Currently no offers in this section.
@endif
@foreach ($offs as $rec)
	<form action="" method="post">
	@csrf
	<div class="provider">{{ $rec['Quality'] }}-star {{ $rec['iName'] }}</div>
	<div class="stars"><img src="/images/game/{{ $rec['Quality'] }}_star.gif" alt="{{ $rec['Quality'] }} star(s)" class="inlineIMGs" align="absmiddle"></div>
	<div class="price">{{ $rec['Price'] }} Tala</div>
	<div class="buy">
		<input type="hidden" name="actID" value="{{ $rec['OfferID'] }}">
		<input type="submit" name="remOffer" value="Remove" class="cmdRemove">
	</div>
	<div style="clear: both"></div>
	<hr>
	</form>
@endforeach
</div>
@else
@if (!$isCA)
<div id="addoffer" class="selectbox">
	<b>Add your offer</b>
	<hr>
	<script type="text/javascript" src="/include/js/numberChecks.js"></script>
	<form action="" method="post">
		@csrf
		Add my
		<select name="invID">
@foreach ($myInventory as $inv)
			<option value="{{ $inv['pID'] }}">{{ $inv['Stars'] }}-star {{ $inv['iName'] }}</option>
@endforeach
		</select>
		for
		<input name="price" size="4" onkeypress="return checkNumber('int', event)" onkeyup="upkey(event, this)" maxlength="3"> Tala.
		<br>
		<input type="submit" name="subadd" value="Add my product" id="submits">
	</form>
	<div style="clear: both"></div>
</div>
@endif
<hr size="3" color="black">
<div id="market">
	<div class="provider">Provider</div><div class="stars">Quality</div><div class="price">Price</div><div class="buy">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (!$indID)
	Please select an industry.
@elseif (count($offs) < 1)
	Currently no offers in this section.
@endif
@foreach ($offs as $rec)
	<form action="" method="post">
	@csrf
	<div class="provider"><a href="{{ $vars->getURL('profile', $rec['SellerID']) }}">{{ $rec['name'] }}</a></div>
	<div class="stars"><img src="/images/game/{{ $rec['Quality'] }}_star.gif" alt="{{ $rec['Quality'] }} star(s)" class="inlineIMGs" align="absmiddle"></div>
	<div class="price">{{ $rec['Price'] }} Tala</div>
	<div class="buy">
		<input type="hidden" name="actOffer" value="{{ $rec['OfferID'] }}">
		<input type="hidden" name="token" value="{{ md5($rec['OfferID'] . 'key44 IM@rl<eT' . $rec['Price']) }}">
		<input type="submit" value="Buy" id="submits">
	</div>
	<div style="clear: both"></div>
	<hr>
	</form>
@endforeach
</div>
@endif
@endsection
