<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
@php
    $isRTL = false;
    $layout = $layout ?? [];
    $coltype = $layout['coltype'] ?? 3;
    $hideups = $layout['hideups'] ?? false;
    $ambient = $layout['ambient'] ?? 'default';
    $actiontype = $layout['actiontype'] ?? 'page';
    $pTitle = $layout['title'] ?? '';
    if (!$logged && $actiontype === 'home') { $ambient = 'main-out'; $coltype = 1; }
    $bodStyle = '';
    if ($logged) {
        if (!empty($citInfo['setting_font']) && $citInfo['setting_font'] !== 'Arial') $bodStyle .= "font-family: {$citInfo['setting_font']};";
        if (($citInfo['setting_bg'] ?? 'Default') === '') $bodStyle .= 'background: white';
        elseif (($citInfo['setting_bg'] ?? 'Default') !== 'Default') $bodStyle .= "background: url('".e($citInfo['setting_bg'])."') no-repeat scroll 50% 0 white";
        else $bodStyle .= "background: url('/images/theme/ambients/{$ambient}.jpg') no-repeat scroll 50% 0 white";
    } else {
        $bodStyle .= "background: url('/images/theme/ambients/{$ambient}.jpg') no-repeat scroll 50% 0 white";
    }
    $startt = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
@endphp
<html>
<head>
<meta http-equiv="Content-Language" content="fa">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ $pTitle !== '' ? $pTitle : $lang->getstr('title_out') }}</title>
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="shortcut icon" href="/favicon.png" />
<link rel="stylesheet" type="text/css" href="/style{{ $isRTL ? '_rtl' : '' }}.css">
<link rel="stylesheet" type="text/css" href="/include/css/buttons.css">
<link rel="stylesheet" type="text/css" href="/include/css/ads.css">
<link rel="stylesheet" type="text/css" href="/include/css/style_main{{ $isRTL ? '_rtl' : '' }}.css">
<link rel="stylesheet" type="text/css" href="/include/css/dock.css">
<link rel="stylesheet" type="text/css" href="/include/css/tasks.css">
<link rel="stylesheet" type="text/css" href="/include/css/box.css">
<link rel="stylesheet" type="text/css" href="/include/css/economy.css">
@stack('styles')
<script src="/include/js/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
spe=500;
na=document.getElementsByTagName("blink");
swi=1;
bringBackBlinky();
function bringBackBlinky() {
if (swi == 1) { sho="visible"; swi=0; } else { sho="hidden"; swi=1; }
for(i=0;i<na.length;i++) { na[i].style.visibility=sho; }
setTimeout("bringBackBlinky()", spe);
}
-->
</script>
<script>
	var search_caption = '{!! addslashes($lang->getstr('search_caption', 'search')) !!}';
	var csrf_token = '{{ csrf_token() }}';
</script>
<script src="/include/js/jquery2.js" type="text/javascript"></script>
<script src="/include/js/pikachoose.js" type="text/javascript"></script>
<script type="text/javascript">
	if (top.location != location) { top.location.href = document.location.href ; }
</script>
<script src="/include/js/ajax.js" type="text/javascript"></script>
<script src="/include/js/BoxOver.js" type="text/javascript"></script>
@if ($actiontype === 'home' && $logged)
<script src="/include/js/chat.js" type="text/javascript"></script>
@endif
@stack('head')
</head>

<body class="{{ ($actiontype === 'home' && $logged) ? 'home26' : '' }}"{!! $bodStyle ? ' style="'.$bodStyle.'"' : '' !!}>
<div class="background">
</div>
<div id="window">
<div id="X">
	<a href="javascript:closeWindow()">
		<img src="/images/theme/close.png" class="inlineIMGs">
	</a>
</div>
<div id="windowContent"></div>
</div>
<div class="topbar">
@include('partials.topbar')
</div>

<div id="loading-div" style="display: none">
	<div class="loader1">
		<div class="loader2">
			<img src="/images/loading.gif">
		</div>
	</div>
</div>
<div id="page">
@if ($logged && empty($citInfo['active']))
	<div class="activation-notifier" style="border: 1px solid black; color: black; background: yellow; position: absolute; width: 1000px; margin-top: -5px">
		{!! $lang->getstr('not_activated_1') !!}
		<br>
		{!! sprintf($lang->getstr('not_activated_2'), $vars->getURL('profile', '', 'activate')) !!}
	</div>
@endif
	<div id="header">
		<div id="logo">
			<a href="{{ $vars->getURL('home') }}">
				<img src="/images/logo.png" class="inlineIMGs">
			</a>
		</div>
		<div id="time">
			<span id="clock">{!! $vars->formatnumbers(date("H:i", time())) !!}</span>
		</div>
		<div id="date">
				{!! sprintf($lang->getstr('date'), $vars->formatnumbers($database->getToday())) !!}
		</div>
		<div id="search">
			<form action="{{ $vars->getURL('search') }}" method="post">
					@csrf
					<input class="search" type="text" id="searchinput" name="search" size="15" value="{{ $lang->getstr('search_caption', 'search') }}">
					<div id="searchfields" style="">
						<input type="radio" name="field" value="citizens" checked> {!! $lang->getstr('citizens', 'search') !!}<br>
						<input type="radio" name="field" value="company"> {!! $lang->getstr('companies', 'search') !!}
					</div>
				<input type="submit" id="btnSearch" value=" " style="background: url('/images/game/find.gif') no-repeat; padding-left: 15px">
			</form>
		</div>
		<div id="top-ad">
		</div>
	</div>
@if (!$hideups)
	<div id="upmenubar"{!! $logged ? '' : ' style="height: 140px; margin-top: 5px"' !!}>
	@if ($logged)
			<div id="menubar">
				<div class="home">
					<a href="javascript:void(0)">
						<img src="/images/theme/menu/home.png" width="24px" align="absmiddle">
						{!! $lang->getstr('menu_home', 'menubar') !!}
					</a>
				</div>
				<div class="myplaces">
					<a href="javascript:void(0)">
						{!! $lang->getstr('menu_myplaces', 'menubar') !!}
					</a>
				</div>
				<div class="economy">
					<a href="javascript:void(0)">
						{!! $lang->getstr('menu_economy', 'menubar') !!}
					</a>
				</div>
				<div class="ranking">
					<a href="javascript:void(0)">
						{!! $lang->getstr('menu_rankings', 'menubar') !!}
					</a>
				</div>
				<div class="info">
					<a href="javascript:void(0)">
						{!! $lang->getstr('menu_information', 'menubar') !!}
					</a>
				</div>
				<div class="extra">
					<a href="javascript:void(0)">
						{!! $lang->getstr('menu_extra', 'menubar') !!}
					</a>
				</div>
			</div>
			<div id="submenubar">@include('partials.topmenu')</div>
	@else
		@include('partials.mini-login')
	@endif
	</div>
@else
		<div style="height: 20px">&nbsp;</div>
@endif
	<div id="game-body">
@if ($coltype == 3)
		<div class="adbar">
			@include('partials.side-left')
		</div>
@endif
@if (($logged || $actiontype !== 'home') && $coltype >= 2)
		<div id="sidebar" class="sidebar">
			@include('partials.side-right')
		</div>
		<div class="contents">
@else
		<div class="contents" style="border: 0; width: {{ ($coltype == 3) ? '570' : (($coltype == 2) ? '686' : '950') }}px">
@endif
@yield('content')
		</div>
	</div>
</div>

<div id="footer">
@php $lang->addPhrases('footer'); @endphp
{!! sprintf($lang->getstr('online_tracker', 'footer'), $vars->formatnumbers($database->numActiveUsers), $vars->formatnumbers($database->numActiveGuests)) !!}
 (<a href="{{ $vars->getURL('online') }}">{!! $lang->getstr('who', 'footer') !!}</a>)
@php $total_time = round((microtime(true) - $startt) * 1000); @endphp
 - {!! sprintf($lang->getstr('gentime', 'footer'), $vars->formatnumbers($total_time)) !!}
@if ($session->isAdmin() || $is_local)
 Total queries: {{ $database->numQueries }}
@endif
		<br>

		{!! $vars->formatnumbers($lang->getstr('copyright', 'footer')) !!}<br>

		<a href="{{ $vars->getURL('laws') }}">{!! $lang->getstr('laws', 'footer') !!}</a>
		|
		<a href="http://blog.ejahan.com" target="_blank">{!! $lang->getstr('blog', 'footer') !!}</a>
		|
		<a href="http://wiki.ejahan.com" target="_blank">{!! $lang->getstr('wiki', 'footer') !!}</a>
		|
		<a href="{{ $vars->getURL('forum') }}">{!! $lang->getstr('forum', 'footer') !!}</a>
		|
		<a href="{{ $vars->getURL('contact') }}">{!! $lang->getstr('contact', 'footer') !!}</a>
		|
		<a href="{{ $vars->getURL('extra') }}">{!! $lang->getstr('about', 'footer') !!}</a>
		<br>
		<a href="http://www.facebook.com/pages/Ejahan-New/565370603482713">
			<img src="/images/facebook.png" title="{{ $lang->getstr('link_facebook', 'footer') }}" width="20" align="absmiddle" border="0">
		</a>
		<a href="https://twitter.com/eJahanGlobal">
			<img src="/images/twitter.png" title="{{ $lang->getstr('link_twitter', 'footer') }}" width="22" align="absmiddle" border="0">
		</a>

		<script>
			$(document).ready(function(){
					$("#citInfo .title").html('{!! addslashes($layout['bar_title'] ?? '') !!}');
					$("#lastNews").hide();
					$("#lTopNews").show();
					$("#ITopNews").hide();
			        $("#sidebar .submenus").hide();
			        $("#change_theme_over").mouseover(function(){ $("#change_theme").slideDown(200); });
			        $("#change_theme").bind("mouseleave", function(){ $("#change_theme").slideUp(200); });
@if ($logged)
			        $("#forhome").show();
					$("#sidebar .menu-cat").click(function() {
						$(this).next($(".submenus")).slideDown(500).siblings(".submenus").slideUp("slow");
					});
@endif
					$(".languages").fadeTo(600, 0.6);
					$(".langbut").fadeTo(600, 0.5);
					$(".langbut").mouseover(function(){ $(this).fadeTo(200, 1) });
					$(".langbut").mouseout(function(){ $(this).fadeTo(400, 0.5) });
				});
			clock('clock', {{ (int) date("H") }}, {{ (int) date("i") }}, {{ (int) date("s") }});
		</script>
	</div>
</div>
@stack('scripts')
</body>
<script type="text/javascript" src="/include/js/juice.js"></script>
<script type="text/javascript" src="/include/js/menubar.js"></script>
<script type="text/javascript" src="/include/js/interface.js"></script>
<script type="text/javascript" src="/include/js/dock.js"></script>
<script src="/include/js/search.js" type="text/javascript"></script>
</html>
