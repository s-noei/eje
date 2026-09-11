@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/chanceboxes.css">
@if ($bg)
			<script type="text/javascript">
				$(document).ready(function(){
						$("#game-body, #footer").css("background", "{{ $bg }}").css("color", "white");
						$("#footer").css("-moz-border-radius", "0 0 5px 5px");
					});
			</script>
@endif
<center>
	<div style="text-align: right"><a href="/index.html" class="button-blue-1">{{ $lang->getstr('goto_eJ') }}</a></div>
	<h3>{{ $lang->getstr('title_chancebox', 'title') }}</h3>
	<hr>
</center>
@foreach ($errors as $e)<h3 class="errHandle">{!! $e !!}</h3>@endforeach
@foreach ($info as $e)<h3 class="infHandle">{!! $e !!}</h3>@endforeach
@if ($do === 'view')
<center><b>{{ $lang->getstr('cb_sealed_yours', 'chancebox') }}</b></center>
<div class="chancebox-new">
@if (count($boxes) < 1)
			{{ $lang->getstr('cb_sealed_zero', 'chancebox') }}
@endif
@foreach ($boxes as $row)
				<a href="{{ $vars->getURL('chancebox', 'open', $row['cbID']) }}">
					<div class="chancebox-details">
						<img src="/images/game/chancebox/egg-unknown.png">
						<br>
						{{ sprintf($lang->getstr('cb_awarded_on', 'chancebox'), $vars->formatnumbers($row['day'])) }}
						<br>
						{{ $lang->getstr('cb_reason', 'chancebox') }}: {{ $lang->getstr("cb_reason_{$row['reason']}", 'chancebox') }}
						<br>
						{{ $lang->getstr('cb_points', 'chancebox') }}: {{ $vars->formatnumbers($row['points']) }}
					</div>
				</a>
@endforeach
</div>
@elseif ($do === 'buy')
<script type="text/javascript" src="/include/js/slider.js"></script>
@if ($happyTime2)
        <div id="happyTime">
        	<center style="font-weight: bold;"><font style="font-size: 30px">HAPPY TIME!</font></center>
            <font color="red">Receive 50% more points!</font>
            <br />The happy time will end in
            <br />
            <span id="htDue">00:00:00</span>
        </div>
@endif
<center><b>{{ $lang->getstr('cb_buy', 'chancebox') }}</b></center>
<div class="chancebox-new" style="text-align: justify">
	<form action="" name="buyCB" method="post" style="margin: 0 120px">
		@csrf
		<center>
			{{ $lang->getstr('cb_buy_points', 'chancebox') }}<br>
			<input type="text" name="pts" size="2" style="text-align: center">
		</center>
		<script language="JavaScript">
		var A_INIT = { 's_form' : 'buyCB', 's_name' : 'pts', 'n_minValue' : 1, 'n_maxValue' : {{ $happyTime2 ? 100 : 50 }}, 'n_value' : 1, 'n_step' : 1 };
		var A_TPL = { 'b_vertical' : false, 'b_watch': true, 'n_controlWidth': 710, 'n_controlHeight': 16, 'n_sliderWidth': 15, 'n_sliderHeight': 16,
			'n_pathLeft' : 1, 'n_pathTop' : 1, 'n_pathLength' : 700, 's_imgControl': '/images/slider/blueh_bg.gif', 's_imgSlider': '/images/slider/blueh_sl.gif', 'n_zIndex': 1 };
		if (window.slider) new slider(A_INIT, A_TPL);
		</script>
		<center>
			<hr size="1">
			{{ $happyTime2 ? '1 Point = 0.05 Tala' : $lang->getstr('cb_buy_pt2tala', 'chancebox') }}<br>
			<input type="submit" name="subbuy" value="{{ $lang->getstr('cb_buy_button', 'chancebox') }}" class="submit-blue-1">
		</center>
	</form>
</div>
@if ($happyTime2)
            <script type="text/javascript">
                var dTime = {{ $htDue }};
                function refreshTime()
                {
                    var tflag = dTime, hours = Math.floor(tflag / 3600); tflag -= hours * 3600;
                    var minutes = Math.floor(tflag / 60); tflag -= minutes * 60; var seconds = tflag;
                    var pad = function(n){ return n < 10 ? "0"+n : n; };
                    $("#htDue").text(pad(hours)+":"+pad(minutes)+":"+pad(seconds));
                    dTime--;
                	if (dTime >= 0) setTimeout("refreshTime()", 1000);
                }
                refreshTime();
            </script>
@endif
@elseif ($do === 'open')
<script type="text/javascript">
	var canClick = true;
	function showCB(number, color)
	{
		if (canClick) {
				canClick = false;
				$(".chancebox-eggs").fadeTo(500, 0.25);
				$.post("/getCBresult.html", { cbID: {{ $id }}, number: number, token: '{{ $token }}' }, function(data){
						if (data && data != 'rest') {
								$(".egg-top").css("background-image", "url('/images/game/chancebox/egg-"+color+"-250.png')");
								$(".egg-bottom").css("background-image", "url('/images/game/chancebox/egg-"+color+"-250.png')");
								$(".egg-contain").animate({"height": "30px"}, 500, function(){
										$(".egg-prize").css("display", "block");
										$(".egg-prize").animate({margin: "-50px 0 0 445px"}, 700, function(){
												$(".egg-prize").animate({margin: "-150px 0 0 245px", "height": "300px", width: "400px"}, 500, function(){
														$(".egg-prize").children("div").fadeIn(500, function(){ $(".egg-prize-body").html(data); });
													});
											});
									});
							}else if(data == 'rest')
								location.href = '{{ request()->getRequestUri() }}';
					});
			}
	}
</script>
<div class="chancebox-new">
@if ($state === 'expired')
					<div style="height: 100px"></div>{{ $lang->getstr('cb_expired', 'chancebox') }}<div style="height: 100px"></div>
@elseif ($state === 'finished')
					<div style="height: 100px"></div>{{ $lang->getstr('cb_finished', 'chancebox') }}<div style="height: 100px"></div>
@elseif ($state === 'used')
					<div style="height: 100px"></div>{{ $lang->getstr('cb_used', 'chancebox') }}<div style="height: 100px"></div>
@else
					<div class="egg-top"></div>
					<div class="egg-contain">
						<div class="egg-prize">
							<div class="egg-prize-head"><img src="/images/game/chancebox/congrats.png"></div>
							<div class="egg-prize-body-icon"><img src="/images/game/chancebox/egg-broken.png"></div>
							<div class="egg-prize-body-top">This egg contains...</div>
							<div class="egg-prize-body">&nbsp;</div>
							<div class="egg-prize-body-bottom"><a href="{{ $vars->getURL('chancebox', 'view') }}" class="button-blue-1">Claim the reward</a></div>
						</div>
					</div>
					<div class="egg-bottom"></div>
					<br>
@if ($cb['canGetTala'])
							{!! sprintf($lang->getstr('cb_prize1', 'chancebox'), $cb['points'], $cb['points'] * 0.1) !!}
							<br>
							<a onclick="javascript:showCB(6, 'yellow')" href="javascript:void(0)">
								<div class="chancebox-eggs"><img src="/images/game/chancebox/egg-yellow.png" width="100px" style="border: 0"></div>
							</a>
							<br>
							{{ $lang->getstr('cb_prize2', 'chancebox') }}
@else
							{!! sprintf($lang->getstr('cb_prize', 'chancebox'), $cb['points']) !!}
@endif
					<table align="center" class="cb_prizes" width="100%">
						<tr>
							<td>{{ $cb['points'] * 0.15 }} <img src="/images/tala.gif" align="absmiddle"></td>
							<td>{{ sprintf($lang->getstr('cb_prize_ep', 'chancebox'), round($cb['points'] * 1)) }}</td>
							<td>{{ sprintf($lang->getstr('cb_prize_wsp', 'chancebox'), $cb['points'] * 2) }}</td>
							<td>{{ sprintf($lang->getstr('cb_prize_msp', 'chancebox'), $cb['points'] * 1) }}</td>
							<td>{{ sprintf($lang->getstr('cb_prize_pro', 'chancebox'), $cb['points'] * 0.25) }}</td>
						</tr>
					</table>
@foreach ($eggs as $i => $egg)
						<a onclick="javascript:showCB({{ $i + 1 }}, '{{ $egg }}')" href="javascript:void(0)">
							<div class="chancebox-eggs"><img src="/images/game/chancebox/egg-{{ $egg }}.png" width="100px" style="border: 0"></div>
						</a>
@endforeach
@endif
</div>
@else
{{ $lang->getstr('cb_desc_1', 'chancebox') }}
<br>
{{ $lang->getstr('cb_desc_2', 'chancebox') }}
<br><br>
{{ $lang->getstr('cb_desc_3', 'chancebox') }}
<div class="chancebox-new">
@if ($sealed < 1)
			<div style="height: 100px"></div>
			{{ $lang->getstr('cb_sealed_zero', 'chancebox') }}
			<div style="height: 100px"></div>
@else
			{!! sprintf($lang->getstr('cb_sealed_count', 'chancebox'), '<div class="new-count">' . $vars->formatnumbers($sealed) . '</div>') !!}
			<br><br>
			<a href="{{ $vars->getURL('chancebox', 'view') }}" class="button-blue-1">{{ $lang->getstr('cb_sealed_open', 'chancebox') }}</a>
@endif
	<a href="{{ $vars->getURL('chancebox', 'buy') }}" class="button-red-1">{{ $lang->getstr('cb_buy', 'chancebox') }}</a>
</div>
@endif
@endsection
