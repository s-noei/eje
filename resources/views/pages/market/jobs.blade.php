@extends('layouts.game')
@section('content')
@php $j = fn($k) => $lang->getstr($k, 'jobs'); $f = fn($k) => $lang->getstr($k, 'filter'); @endphp
<script language='javascript'>
	$(document).ready(function(){
			$("div #quality").click(function(){ $("div #countries").hide(); $("div #qualities").fadeIn('500'); });
			$("div #country").click(function(){ $("div #qualities").hide(); $("div #countries").fadeIn('500'); });
		});
</script>
{!! $msg ?? '' !!}
		<div id="tblRanksFLT">
			@include('partials.filter-what', ['id' => 'country', 'head' => $f('filter_country'), 'img' => $coun ? $vars->getImgLoc('CountryFlag').$coun['Flag'].'.gif' : '/images/elections/select.jpg', 'alt' => $coun['cName'] ?? 'Select'])
			@include('partials.filter-what', ['id' => 'quality', 'head' => $f('filter_quality'), 'img' => '/images/game/'.($quality ?: 0).'_star.gif', 'alt' => $quality.' star(s)'])
		</div>
@if (!$isCA)
			<div id="tblRanksFLT">
				<div class="info">{!! $f('filter_skill') !!}:<br>{{ $citInfo['wSkill'] }}</div>
			</div>
@endif
<div style="clear: both"></div>
@include('partials.filter-country', ['countryLink' => fn($c) => $vars->getURL('jobs', '0', $c)])
<div id="qualities" class="selectbox">
	<b>{!! $f('filter_quality') !!}</b>
	<hr>
		<div id="sName" class="box-element">
		<a href="{{ $vars->getURL('jobs', 0, $counID) }}">
		<img src="/images/game/0_star.gif" class="inlineIMGs" align="absmiddle" alt="All companies"><br>{!! $f('filter_all_comps') !!}
		</a>
		</div>
@for ($co = 1; $co <= 5; $co++)
			<div id="sName" class="box-element">
			<a href="{{ $vars->getURL('jobs', $co, $counID) }}">
			<img src="/images/game/{{ $co }}_star.gif" class="inlineIMGs" align="absmiddle" alt="{{ $co }}-star companies"><br>{!! sprintf($f('filter_star_comps'), $co) !!}
			</a>
			</div>
@endfor
	<div style="clear: both"></div>
</div>
<hr size="3" color="black">
<div id="jobs">
	<div class="company">{!! $j('jobs_company') !!}</div>
	<div class="salary">{!! $j('jobs_salary') !!}</div>
	<div class="apply">&nbsp;</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
	{!! $f('filter_no_offer') !!}
@endif
@foreach ($rows as $r)
@php $rec = $r['rec']; $recs = $r['comp']; @endphp
	<form name="offer_{{ $rec['joID'] }}" action="" method="post">
	@csrf
	<div class="cAvatar">
		<a href="{{ $vars->getURL('company', $recs['CompanyID']) }}"><img src="{{ $vars->getImgLoc('CompanyAvatar') . $recs['Avatar'] }}" alt="{{ $recs['Name'] }}" class="Avatar-xs" align="absmiddle"></a>
	</div>
	<div class="cName">
		<a href="{{ $vars->getURL('company', $recs['CompanyID']) }}">{{ $recs['Name'] }}</a><br>
		<sub>{!! sprintf($j('jobs_quality'), $recs['Stars']) !!}</sub><br>
		<sup>{{ $recs['tool_q'] }}-star Tool ({{ round($recs['tool_e'], 2) }}%)</sup>
	</div>
	<div class="salary">{{ $r['salaryText'] }} {{ $rec['curName'] }}</div>
	<div class="apply">
		<input type="hidden" name="actOffer" value="{{ $rec['joID'] }}">
		<input type="hidden" name="compID" value="{{ $recs['CompanyID'] }}">
		<input type="hidden" name="Salary" value="{{ $rec['Salary'] }}">
		<input type="hidden" name="token" value="{{ md5($rec['joID'] . $recs['CompanyID'] . $citInfo['CitizenID'] . $rec['Salary'] . 'k3y4 j0b 0o0off3r$') }}">
@if ($canApply)
		<a href="#" onclick="document.offer_{{ $rec['joID'] }}.submit(); return false" id="buttons">{!! $j('jobs_apply') !!}</a>
@endif
	</div>
	<div style="clear: both"></div>
	<hr>
	</form>
@endforeach
		<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('jobs', $quality, $counID, $page - 1) }}" id="buttons">{!! $lang->getstr('nav_back') !!}</a>
@endif
	<span id="buttons">{{ $page }}</span>
@if ($jo_count > $start + $count)
		<a href="{{ $vars->getURL('jobs', $quality, $counID, $page + 1) }}" id="buttons">{!! $lang->getstr('nav_next') !!}</a>
@endif
	</center>
</div>
@endsection
