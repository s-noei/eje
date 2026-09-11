@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/home2026.css">@endpush
@php
	use App\Game\Support\Constants;
	$h = fn($k) => $lang->getstr($k, 'home');
	$t = fn($k) => $lang->getstr($k, 'tasks');
	$ep = (float) ($citInfo['ep'] ?? 0);
	$pub = (int) ($citInfo['puberty'] ?? 0);
	$cur = Constants::PUB_EPS[$pub] ?? 0;
	$next = Constants::PUB_EPS[$pub + 1] ?? $cur;
	$xpPct = $next > $cur ? max(0, min(100, round(($ep - $cur) / ($next - $cur) * 100))) : 100;
	$wel = (float) ($citInfo['wellness'] ?? 0);
	$rankName = Constants::PUB_RANKS[$pub] ?? '';
	$tala = $citMoney[1] ?? 0;
	$cid = $citInfo['CountryID'] ?? 0;
	$local = $citMoney[$cid] ?? 0;
	$curName = $citInfo['curName'] ?? '';
	$flag = fn($n) => '/images/flags/s/' . $n . '.gif';
	$voteDay = in_array($now['Day'], $electionDays) && !$isCA;
@endphp
<div id="home26">
<div class="h26-main">

	{{-- ===== Player card ===== --}}
	<div class="h26-card h26-hero">
		<div class="h26-avatar" style="--xp: {{ $xpPct }}%">
			<a href="{{ $vars->getURL('profile', $citInfo['CitizenID']) }}"><img src="/uploads/avatars/citizen/{{ $citInfo['Avatar'] }}" alt="{{ $citInfo['name'] }}"></a>
@if (!$isCA)
			<div class="h26-level" title="{{ $rankName }}">{{ $pub + 1 }}</div>
@endif
		</div>
		<div>
			<div class="h26-hero-name"><a href="{{ $vars->getURL('profile', $citInfo['CitizenID']) }}">{{ $citInfo['name'] }}</a></div>
			<div class="h26-hero-rank">
				{{ $isNCA ? 'National CA' : ($isCA ? 'Co-Account' : $rankName) }}
				&middot;
				<a href="{{ $vars->getURL('country', $cid) }}"><img src="{{ $flag($citInfo['cName'] ?? '') }}" alt=""> {{ $citInfo['cName'] ?? '' }}</a>
				&middot; <a href="{{ $vars->getURL('region', $citInfo['regionID'] ?? 0) }}">{{ $citInfo['RegionName'] ?? '' }}</a>
			</div>
@if (!$isCA)
			<div class="h26-bars">
				<div class="h26-bar">
					<span class="lbl">XP</span>
					<div class="track"><div class="fill" style="width: {{ $xpPct }}%"></div></div>
					<span class="val">{{ $vars->formatnumbers($ep) }} / {{ $vars->formatnumbers($next) }}</span>
				</div>
				<div class="h26-bar">
					<span class="lbl">Wellness</span>
					<div class="track"><div class="fill wel" style="width: {{ $wel }}%"></div></div>
					<span class="val">{{ $wel }} / 100</span>
				</div>
			</div>
@endif
		</div>
		<div class="h26-stats">
			<div class="h26-chip"><img src="/images/tala.gif" alt=""> <b>{{ $vars->formatnumbers(round($tala, 2)) }}</b> <small>Tala</small></div>
@if ($cid && $curName)
			<div class="h26-chip"><img src="{{ $flag($citInfo['cName'] ?? '') }}" alt=""> <b>{{ $vars->formatnumbers(round($local, 2)) }}</b> <small>{{ $curName }}</small></div>
@endif
			<div class="h26-chip"><img src="/images/xp_icon.png" alt=""> <b>#{{ $vars->formatnumbers($citInfo['stat_rank_int'] ?? '-') }}</b> <small>world rank</small></div>
		</div>
	</div>

	{{-- ===== Quest board: vote / daily reward / unit order ===== --}}
@if ($voteDay || $canDaily || $unitBattle || ($dailyError ?? false))
	<div class="h26-card">
		<div class="h26-head">
			<div class="h26-title"><span class="ico">⚡</span> Quests</div>
			<span class="h26-sub">Today, day {{ $vars->formatnumbers($now['Day']) }}</span>
		</div>
		{!! $dailyError ?? '' !!}
@if ($voteDay)
@php
	if ($now['Day'] == $electionDays['cg']) { $vUrl = $vars->getURL('elections', 'cg', $cid, $citInfo['regionID'], $now['Year'], $now['Month']); $vDesc = $t('vote_cg_desc'); }
	elseif ($now['Day'] == $electionDays['cp']) { $vUrl = $vars->getURL('elections', 'cp', $cid, '', $now['Year'], $now['Month']); $vDesc = $t('vote_cp_desc'); }
	else { $vUrl = $vars->getURL('elections', 'pp', $cid, $citInfo['PartyID'] ?? 0, $now['Year'], $now['Month']); $vDesc = $t('vote_pp_desc'); }
@endphp
		<div class="h26-quest vote">
			<div class="qi"><img src="/images/game/tasks/vote.png" alt=""></div>
			<div><div class="qt">{!! $t('vote_title') !!}</div><div class="qd">{!! sprintf($t('vote_desc'), $vDesc) !!}</div></div>
			<a href="{{ $vUrl }}" class="h26-btn gold">Vote</a>
		</div>
@endif
@if ($canDaily)
		<form action="" method="post" enctype="multipart/form-data" class="h26-quest">
			@csrf
			<input type="hidden" name="id" value="{{ $citInfo['CitizenID'] }}">
			<div class="qi"><img src="/images/game/tasks/food.png" alt=""></div>
			<div>
				<div class="qt">Daily tasks completed</div>
				<div class="qd">Claim today's reward</div>
				<div class="h26-rewards" style="margin-top: 6px">
					<div class="h26-reward" title="Food"><img src="/images/icons/food.png" alt=""><img src="/images/game/5_star.gif" width="36" alt="">1</div>
					<div class="h26-reward" title="Experience Points"><img src="/images/xp_icon.png" alt="">+5 EP</div>
				</div>
			</div>
			<button type="submit" name="DailyReward" value="1" class="h26-btn">Get reward</button>
		</form>
@endif
@if ($unitBattle)
		<a href="{{ $vars->getURL('battle', $unitBattle['battleID']) }}" class="h26-quest" title="started {{ $session->getDiff($unitBattle['Start']) }}">
			<div class="qi"><img src="{{ $flag($unitBattle['battle_type'] == 'battle' ? $unitBattle['attName'] : 'revolt') }}" alt=""></div>
			<div><div class="qt">Military unit order</div><div class="qd">Fight in {{ $unitBattle['regionName'] }} &mdash; {{ $unitBattle['battle_type'] == 'battle' ? $unitBattle['attName'] : 'Revolt' }} vs {{ $unitBattle['defName'] }}</div></div>
			<span class="h26-btn ghost">To battle</span>
		</a>
@endif
	</div>
@endif

	{{-- ===== Active battles ===== --}}
	<div class="h26-card">
		<div class="h26-head">
			<div class="h26-title"><span class="ico">⚔</span> {!! $h('active_battles') !!}</div>
@if (count($battles))
			<span class="h26-live">Live &middot; {{ count($battles) }}</span>
@endif
		</div>
		<div class="home-box-content" style="display: none">
@if (count($battles) < 1)
			<div class="h26-empty">There is no active battle for your country.</div>
@else
			<div class="h26-battles">
@foreach ($battles as $bat)
				<a href="{{ $vars->getURL('battle', $bat['battleID']) }}" class="h26-battle" title="started {{ $session->getDiff($bat['Start']) }}">
					<img src="{{ $flag($bat['battle_type'] == 'battle' ? $bat['attName'] : 'revolt') }}" alt="">
					<div class="rg">{{ $bat['regionName'] }}<small>{{ $bat['battle_type'] == 'battle' ? $bat['attName'] : 'Revolt' }} vs {{ $bat['defName'] }}</small></div>
					<img src="{{ $flag($bat['defName']) }}" alt="">
				</a>
@endforeach
			</div>
@endif
		</div>
	</div>

	{{-- ===== Military events ===== --}}
	<div class="h26-card">
		<div class="h26-head">
			<div class="h26-title"><span class="ico">📡</span> {!! $h('mili_events') !!}</div>
			<span class="h26-sub"><a class="h26-link" href="{{ $vars->getURL('media', '0', 'eve') }}">{!! $h('show_mili_events') !!}</a> &middot; <a class="h26-link" href="{{ $vars->getURL('wars') }}">{!! $h('show_active_wars') !!}</a></span>
		</div>
		<div class="home-box-content" style="display: none">
			<ul class="nButtons">
				<li id="elBut" class="snButton"><a href="javascript:void(0)">{{ $citInfo['cName'] }}</a></li>
				<li id="eiBut"><a href="javascript:void(0)">{!! $h('news_international') !!}</a></li>
			</ul>
			<div id="mili-handler" class="newsBox"></div>
		</div>
	</div>

	{{-- ===== News ===== --}}
	<div class="h26-card">
		<div class="h26-head">
			<div class="h26-title"><span class="ico">📰</span> {!! $h('newshead') !!}</div>
			<a id="mcenterlink" class="h26-link" href="{{ $vars->getURL('media', $cid) }}">{!! $h('mcenter') !!}</a>
		</div>
		<div class="home-box-contents">
			<ul class="nButtons">
				<li id="ltBut" class="snButton"><a href="javascript:void(0)">{!! $h('news_top') !!}</a></li>
				<li id="lBut"><a href="javascript:void(0)">{!! $h('news_latest') !!}</a></li>
				<li id="itBut"><a href="javascript:void(0)">{!! $h('news_international') !!}</a></li>
@if (!$isCA)
				<li id="subBut"><a href="javascript:void(0)">{!! $h('news_mysubs') !!}</a></li>
@endif
			</ul>
@foreach ([['lastNews', 'news_latest2', $latest, 'none'], ['lTopNews', 'news_top2', $topL, 'block'], ['ITopNews', 'news_international2', $topI, 'none'], ['subsNews', 'news_mysubs', $subs, 'none']] as [$id, $title, $arts, $disp])
@if ($id !== 'subsNews' || !$isCA)
			<div id="{{ $id }}" class="newsBox" style="display: {{ $disp }}">
				<div>{!! $h($title) !!}</div>
@if (count($arts) < 1)
				<div class="h26-empty">Nothing here yet.</div>
@endif
@foreach ($arts as $artNew)
				<div class="h26-article">
					<div class="h26-votes">{{ $artNew['aVotes'] }}<small>VOTES</small></div>
					<div>
						<div class="t"><a href="{{ $vars->getURL('article', $artNew['aID']) }}">{!! $artNew['aTitle'] ? strip_tags($artNew['aTitle']) : '----' !!}</a></div>
						<div class="m">{!! sprintf($lang->getstr('wrote2'), $session->getDiff($artNew['timestamp'])) !!} &middot; {!! $lang->getstr('inn') !!} <a href="{{ $vars->getURL('newspaper', $artNew['npID']) }}">{{ $artNew['npName'] }}</a></div>
					</div>
				</div>
@endforeach
			</div>
@endif
@endforeach
		</div>
	</div>

	{{-- ===== Around eJahan ===== --}}
	<div class="h26-card h26-around">
		<div class="h26-head"><div class="h26-title"><span class="ico">🌍</span> {!! $h('around_ej') !!}</div></div>
@foreach ($adminNews as $artAdmin)
		<a href="{{ $vars->getURL('article', $artAdmin['aID']) }}">
			<img src="/images/logo.gif" alt="Around eJahan">
@if ($artAdmin['timestamp'] > time() - (24*3600*2))<span class="h26-new">NEW</span>@endif
			<span>{!! $artAdmin['aTitle'] ? strip_tags($artAdmin['aTitle']) : '----' !!}</span>
		</a>
@endforeach
	</div>
</div>

{{-- ===== Chatbox ===== --}}
<div class="h26-side">
	<div class="h26-card h26-chat">
		<div class="h26-head"><div class="h26-title"><span class="ico">💬</span> {!! $h('chatbox') !!}</div></div>
		<div class="home-box-content" style="display: none;">
			<div class="send-pm">
				<form action="" method="post">
					<textarea name="yourmessage" id="yourmessage" rows="2" placeholder="Say something to {{ $citInfo['cName'] ?? 'everyone' }}…"></textarea>
					<div class="send-row">
						<input type="submit" class="h26-btn" id="addmessage" value="{{ $h('chatbox_send') }}" onclick="return false">
						<img src="/images/chatloader.gif" id="chatter-loader" alt="">
					</div>
				</form>
			</div>
			<div id="chatters-hold">
				<div id="chat-msg"></div>
				<div id="chatter-announce" class="chatters" style="display: none">
					<div class="chat-sender"><img src="/uploads/avatars/citizen/no-avatar-m.gif" class="chat-avatar"></div>
					<div class="chat-message">&nbsp;</div>
					<div style="clear: both"></div>
				</div>
				<div id="chatter-sample" class="chatters" style="display: none">
					<div class="chat-sender"><img src="/uploads/avatars/citizen/no-avatar-m.gif" class="chat-avatar"></div>
					<div class="chat-message">&nbsp;</div>
					<div style="clear: both"></div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
<script>
	var actTab = 'ltBut';
	$(document).ready(function(){
			var urlLT = '{{ $vars->getURL('media', $cid) }}';
			var urlLN = '{{ $vars->getURL('media', $cid, 'new') }}';
			var urlIT = '{{ $vars->getURL('media') }}';
			function sw(show, tab, url) {
				$("div #lastNews, div #ITopNews, div #subsNews, div #lTopNews").hide();
				$("div #"+show).fadeIn('500');
				$("#"+actTab).toggleClass("snButton"); actTab = tab; $("#"+actTab).toggleClass("snButton");
				$("#mcenterlink").attr("href", url);
			}
			$("#ltBut").click(function(){ sw('lTopNews', 'ltBut', urlLT); });
			$("#lBut").click(function(){ sw('lastNews', 'lBut', urlLN); });
			$("#itBut").click(function(){ sw('ITopNews', 'itBut', urlIT); });
			$("#subBut").click(function(){ sw('subsNews', 'subBut', urlLT); });
			$("div .todo").fadeIn('300');
			$("div .home-box-content").fadeIn('300');
			actMil = {{ (int) $cid }};
			$("#elBut").click(function(){ $("#elBut").addClass("snButton"); $("#eiBut").removeClass("snButton"); actMil = {{ (int) $cid }}; getMili(actMil); });
			$("#eiBut").click(function(){ $("#eiBut").addClass("snButton"); $("#elBut").removeClass("snButton"); actMil = 0; getMili(actMil); });
			getMili(actMil);
		});
	function getMili(location) {
		$("#mili-handler").slideUp(200, function(){
			$.getJSON("/getevents-"+location+".html", function(data) {
				$("#mili-handler").text("");
				var co = 0;
				if (data.noeve) {
					$("#mili-handler").html('<div class="h26-empty">' + data.noeve + '</div>');
				}else{
					$.each(data['event'], function(idx, event) {
						co++;
						$("#mili-handler").append("<a href='" + event.link + "' id='a"+co+"'></a>");
						$("#mili-handler #a"+co).append("<img src='" + event.icon + "' align='absmiddle'>");
						$("#mili-handler #a"+co).append("<span>"+event.title+"</span>");
					});
				}
				$("#mili-handler").slideDown(200);
			});
		});
	}
</script>
@endsection
