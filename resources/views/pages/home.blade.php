@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/home.css">@endpush
@php
	$h = fn($k) => $lang->getstr($k, 'home');
	$t = fn($k) => $lang->getstr($k, 'tasks');
@endphp
@if (in_array($now['Day'], $electionDays) && !$isCA)
				<div class="vote-handler">
@if ($now['Day'] == $electionDays['cg'])
						<a href="{{ $vars->getURL('elections', 'cg', $citInfo['CountryID'], $citInfo['regionID'], $now['Year'], $now['Month']) }}">
							<img src="/images/game/tasks/vote.png" class="inlineIMGs" align="absmiddle" width="30px">
							{!! $t('vote_title') !!} - {!! sprintf($t('vote_desc'), $t('vote_cg_desc')) !!}
						</a>
@elseif ($now['Day'] == $electionDays['cp'])
						<a href="{{ $vars->getURL('elections', 'cp', $citInfo['CountryID'], '', $now['Year'], $now['Month']) }}">
							<img src="/images/game/tasks/vote.png" class="inlineIMGs" align="absmiddle" width="30px">
							{!! $t('vote_title') !!} - {!! sprintf($t('vote_desc'), $t('vote_cp_desc')) !!}
						</a>
@else
						<a href="{{ $vars->getURL('elections', 'pp', $citInfo['CountryID'], $citInfo['PartyID'] ?? 0, $now['Year'], $now['Month']) }}">
							<img src="/images/game/tasks/vote.png" class="inlineIMGs" align="absmiddle" width="30px">
							{!! $t('vote_title') !!} - {!! sprintf($t('vote_desc'), $t('vote_pp_desc')) !!}
						</a>
@endif
				</div>
@endif

<div class="column-left">
{!! $dailyError ?? '' !!}
@if ($canDaily)
		<div class="home-box-title">
			Daily tasks completed
			<hr>
		</div>
		<div class="home-box-content">
		<center>
		<div style="display: inline-block; text-align: center; width: 40px; border: 1px solid; border-radius: 3px; margin: 1px"> <img src="/images/icons/food.png" width="40px" title="Food"> <img src="/images/game/5_star.gif" style="border-bottom: 1px solid; border-top: 1px solid" width="40px"> 1 </div>
		<div style="display: inline-block; text-align: center; width: 40px; border: 1px solid; border-radius: 3px; margin: 1px"> <img src="/images/xp_icon.png" width="40px" title="Experience Points"> <img src="/images/game/0_star.gif" style="border-bottom: 1px solid; border-top: 1px solid" width="40px"> 5 EP </div>
		<div style="clear: both"></div>
		<br>
		<form action="" method="post" enctype="multipart/form-data">
			@csrf
			<input type="hidden" name="id" size="10" value="{{ $citInfo['CitizenID'] }}">
			<input type="submit" name="DailyReward" value="Get reward" class="submit-blue-1">
		</form>
		</center>
		</div>
        <br />
@endif
@if ($unitBattle)
			<div class="home-box-title">
				Military Unit<hr>
			</div>
			<div class="home-box-content">
    			<a href="{{ $vars->getURL('battle', $unitBattle['battleID']) }}" align="absmiddle" class="battle-region-link" title="started {{ $session->getDiff($unitBattle['Start']) }}">
                	<div class="battles-holder">
                    	<div class="battle-attacker-flag">
                        	<img src="/images/flags/s/{{ $unitBattle['battle_type'] == 'battle' ? $unitBattle['attName'] : 'revolt' }}.gif">
                        </div>
                       	<div class="battle-region">{{ $unitBattle['regionName'] }}</div>
                		<div class="battle-attacker-flag">
                        	<img src="/images/flags/s/{{ $unitBattle['defName'] }}.gif" align="absmiddle">
                        </div>
                    </div>
    			</a>
    		</div>
    		<br />
@endif
		<div class="home-box-title">
			{!! $h('active_battles') !!}
			<hr>
		</div>
		<div class="home-box-content" style="display: none">
@if (count($battles) < 1)
							There is no active battle for your country.
@endif
@foreach ($battles as $bat)
    					<a href="{{ $vars->getURL('battle', $bat['battleID']) }}" align="absmiddle" class="battle-region-link" title="started {{ $session->getDiff($bat['Start']) }}">
                            <div class="battles-holder">
                                <div class="battle-attacker-flag">
                                    <img src="/images/flags/s/{{ $bat['battle_type'] == 'battle' ? $bat['attName'] : 'revolt' }}.gif">
                                </div>
                                <div class="battle-region">{{ $bat['regionName'] }}</div>
                                <div class="battle-attacker-flag">
                                    <img src="/images/flags/s/{{ $bat['defName'] }}.gif" align="absmiddle">
                                </div>
                            </div>
    					</a>
@endforeach
		</div>
        <br />
		<div class="home-box-title">
			{!! $h('mili_events') !!}
		</div>
		<div class="home-box-content" style="display: none">
			<ul class="nButtons">
				<li id="elBut" class="snButton"><a href="javascript:void(0)">{{ $citInfo['cName'] }}</a></li>
				<li id="eiBut"><a href="javascript:void(0)">{!! $h('news_international') !!}</a></li>
			</ul>
		<div id="mili-handler" class="newsBox" style="font-size: 8pt; padding: 5px 2px">
		</div>
		</div>
		<center>
			<a href="{{ $vars->getURL('media', '0', 'eve') }}">{!! $h('show_mili_events') !!}</a>
            |
			<a href="{{ $vars->getURL('wars') }}">{!! $h('show_active_wars') !!}</a>
		</center>
		&nbsp;
		<div class="home-box-title">
			 {!! $h('newshead') !!}
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
		<div style="font-size: 14pt;text-align: center">
			{!! $h($title) !!}
		</div>
@foreach ($arts as $artNew)
						<div class="article-points">{{ $artNew['aVotes'] }}</div>
						<div class="article-title">
							<a href="{{ $vars->getURL('article', $artNew['aID']) }}">
								{!! $artNew['aTitle'] ? strip_tags($artNew['aTitle']) : '----' !!}
							</a>
						</div>
						<div class="article-time">
							{!! sprintf($lang->getstr('wrote2'), $session->getDiff($artNew['timestamp'])) !!}
						</div>
						<div class="article-np">
							{!! $lang->getstr('inn') !!} <a href="{{ $vars->getURL('newspaper', $artNew['npID']) }}">{{ $artNew['npName'] }}</a>
						</div>
						<div style="clear: both"></div>
@endforeach
		</div>
@endif
@endforeach
		</div>
	<div style="clear: both; text-align: center">
		<a id="mcenterlink" href="{{ $vars->getURL('media', $citInfo['CountryID']) }}">{!! $h('mcenter') !!}</a><br><br>
	</div>
		<div class="home-box-title">
			{!! $h('around_ej') !!}
            <hr size="1" />
		</div>
		<div class="home-box-content">
@foreach ($adminNews as $artAdmin)
					<a href="{{ $vars->getURL('article', $artAdmin['aID']) }}">
                        <img src="/images/logo.gif" alt="Around eJahan" width="50px" align="absmiddle" />
    						{!! ($artAdmin['timestamp'] > time() - (24*3600*2)) ? '<blink style="color: red">NEW</blink>' : '' !!}
    							{!! $artAdmin['aTitle'] ? strip_tags($artAdmin['aTitle']) : '----' !!}
					</a>
					<div style="clear: both"></div>
@endforeach
	</div>
</div>
<div class="column-right">
		<div class="home-box-title">
			{!! $h('chatbox') !!}<hr size="1">
		</div>
		<div class="home-box-content" style="display: none;">
				<div style="border: 1px solid; border-radius: 5px">
    				<div class="send-pm">
    					<form action="" method="post">
    						<textarea name="yourmessage" id="yourmessage" cols="55" rows="2" style="font-family: tahoma; font-size: 9pt"></textarea>
    						<br>
    						<div style="text-align: left; padding-left: 8px">
    						<input type="submit" class="submit-blue-1" id="addmessage" value="{{ $h('chatbox_send') }}" onclick="return false">
    							<img src="/images/chatloader.gif" id="chatter-loader" align="absmiddle">
    						</div>
    					</form>
    				</div>
					<div id="chatters-hold" style="border-top: 1px solid; margin-top: 3px">
                        <div id="chat-msg">&nbsp;</div>
    					<div id="chatter-announce" class="chatters" style="display: none">
    						<div class="chat-sender"><img src="/test.jpg" class="chat-avatar"></div>
    						<div class="chat-message">&nbsp;</div>
    						<div style="clear: both"><hr width="90%"></div>
    					</div>
    					<div id="chatter-sample" class="chatters" style="display: none">
    						<div class="chat-sender"><img src="/test.jpg" class="chat-avatar"></div>
    						<div class="chat-message">&nbsp;</div>
    						<div style="clear: both"><hr size="1" width="90%"></div>
    					</div>
                    </div>
				</div>
		</div>
</div>
<script>
	var actTab = 'ltBut';
	$(document).ready(function(){
            var urlLT = '{{ $vars->getURL('media', $citInfo['CountryID']) }}';
            var urlLN = '{{ $vars->getURL('media', $citInfo['CountryID'], 'new') }}';
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
            actMil = {{ (int) $citInfo['CountryID'] }};
			$("#elBut").click(function(){ $("#elBut").addClass("snButton"); $("#eiBut").removeClass("snButton"); actMil = {{ (int) $citInfo['CountryID'] }}; getMili(actMil); });
			$("#eiBut").click(function(){ $("#eiBut").addClass("snButton"); $("#elBut").removeClass("snButton"); actMil = 0; getMili(actMil); });
            getMili(actMil);
		});
    function getMili(location) {
        $("#mili-handler").slideUp(200, function(){
            	$.getJSON("/getevents-"+location+".html", function(data) {
                        $("#mili-handler").text("");
                        co = 0;
						if (data.noeve) {
							$("#mili-handler").text(data.noeve);
						}else{
							$.each(data['event'], function(idx, event) {
								co++;
								$("#mili-handler").append("<a href='" + event.link + "' id='a"+co+"'></a>");
								$("#mili-handler #a"+co).append("<img src='" + event.icon + "' align='absmiddle'>");
								$("#mili-handler #a"+co).append("&nbsp;"+event.title);
								if (co < 5) $("#mili-handler").append("<hr size='1'>");
							});
						}
                        $("#mili-handler").slideDown(200);
            		});
            });
    }
</script>
@endsection
