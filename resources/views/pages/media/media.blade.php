@extends('layouts.game')
@section('content')
<script>
	$(document).ready(function(){
			$("div #country").click(function(){ $("div #types").hide(); $("div #countries").fadeIn('500'); });
			$("div #type").click(function(){ $("div #countries").hide(); $("div #types").fadeIn('500'); });
		});
</script>
		<div id="tblRanksFLT">
				@include('partials.filter-what', ['id' => 'country', 'head' => $lang->getstr('filter_country', 'filter'), 'img' => $flag, 'alt' => $cName])
				<div class="spacer"></div>
				@include('partials.filter-what', ['id' => 'type', 'head' => $lang->getstr('filter_type', 'filter'), 'img' => "/images/media/$type.jpg", 'alt' => $type])
			</div>
<div style="clear: both"></div>
<div id="types" class="selectbox">
	<b>{{ $lang->getstr('filter_type', 'filter') }}</b>
	<hr>
@foreach (['top' => 'filter_top_rated', 'new' => 'filter_latest', 'eve' => 'filter_events'] as $k => $v)
	<div class="box-element">
		<a href="{{ $vars->getURL('media', $country, $k) }}"><img src="/images/media/{{ $k }}.jpg" class='inlineIMGs' width="50px" align=absmiddle> {{ $lang->getstr($v, 'filter') }}</a>
	</div>
@endforeach
	<div style="clear: both"></div>
</div>
<div id="countries" class="selectbox">
	<b>{{ $lang->getstr('filter_country', 'filter') }}</b>
	<hr>
	<div class="box-element">
		<a href="{{ $vars->getURL('media', '0', $type) }}"><img src="{{ $vars->getImgLoc('CountryFlag') . 'l/world.gif' }}" class="Flag-ms" alt="World" align="absmiddle"> {{ $lang->getstr('filter_world', 'filter') }}</a>
	</div>
@foreach ($allCountries as $rCoun)
					<div class="box-element">
						<a href="{{ $vars->getURL('media', $rCoun['CountryID'], $type) }}"><img src="{{ $vars->getImgLoc('CountryFlag') . $rCoun['Flag'] . '.gif' }}" class="Flag-ms" alt="{{ $rCoun['cName'] }}" align="absmiddle"> {{ $rCoun['cName'] }}</a>
					</div>
@endforeach
	<div style="clear: both"></div>
</div>
<hr size="3" color="black">
@if ($type != 'eve')
<div id="media-list">
	<div class="media-votes" style="padding-top: 15px">{{ $lang->getstr('mcenter_votes', 'mcenter') }}</div>
	<div class="media-title">{{ $lang->getstr('mcenter_art_title', 'mcenter') }}</div>
	<div class="media-np">{{ $lang->getstr('mcenter_np', 'mcenter') }}</div>
	<div class="media-created">{{ $lang->getstr('mcenter_created', 'mcenter') }}</div>
	<div style="clear: both"></div>
	<hr>
@if (count($arts) < 1)
				<center>{{ $lang->getstr('filter_no_article_match', 'filter') }}</center>
@endif
@foreach ($arts as $art)
					<div class="media-votes"><div class="media-votebox">{{ $art['aVotes'] }}</div></div>
					<div class="media-title"><a href="{{ $vars->getURL('article', $art['aID']) }}">{{ strip_tags($art['aTitle']) }}</a></div>
					<div class="media-np"><a href="{{ $vars->getURL('newspaper', $art['npID']) }}">{{ $art['npName'] }}</a></div>
					<div class="media-created">{!! $session->getDiff($art['timestamp']) !!}</div>
					<div style="clear: both"></div>
					<hr>
@endforeach
</div>
@else
<div id="media-list">
	<div class="media-type" style="padding-top: 15px"></div>
	<div class="media-desc">{{ $lang->getstr('mcenter_description', 'mcenter') }}</div>
	<div class="media-created">{{ $lang->getstr('mcenter_occured', 'mcenter') }}</div>
	<div style="clear: both"></div>
	<hr>
@if (count($events) < 1)
	<div>There is no event matching your criteria</div>
@endif
@foreach ($events as $event)
					<div class="media-type"><img src="/images/media/{{ $event['Type'] }}.jpg"></div>
					<div class="media-desc"><a href="{{ $vars->getURL($event['ref'], $event['refID']) }}">{{ $event['text'] }}</a></div>
					<div class="media-created">{!! $session->getDiff($event['timestamp']) !!}</div>
					<div style="clear: both"></div>
					<hr>
@endforeach
</div>
@endif
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('media', $country, $type, $page - 1) }}" id="buttons">{{ $lang->getstr('filter_nav_prev', 'filter') }}</a>
@endif
		<a href="{{ $vars->getURL('media', $country, $type, $page) }}" id="buttons">{{ $page }}</a>
@if ($nums > $start + $count)
		<a href="{{ $vars->getURL('media', $country, $type, $page + 1) }}" id="buttons">{{ $lang->getstr('filter_nav_next', 'filter') }}</a>
@endif
	</center>
@endsection
