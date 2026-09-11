<form name="fight" action="{{ $vars->getURL('fight', $rWar['battleID']) }}" method="post">
	@csrf
	<div class="select-wep">
		<center>
			<b>Select your weapon type</b>
			<br>
			<div class="weapon-select">
				<img src="/images/icons/weapon.png"><br>
				<div style="margin-top: -10px;"><img id="weapQ" src="/images/game/{{ $weapStar }}_star.gif"></div>
				<input type="hidden" name="weapon" value="{{ $weapStar }}" />
				<a class="fightbuts" href="javascript:void(0)" id="changeweap"><img src="/images/game/war/change.gif" alt="C"></a>
			</div>
			<br>
			<input type="hidden" name="bID" value="{{ $rWar['battleID'] }}">
			<input type="hidden" name="for" value="{{ $for }}">
			<input type="hidden" name="token" value="{{ md5($citInfo['CitizenID'] . $for . $rWar['battleID'] . 'k3yy4 f1+lng') }}">
		</center>
	</div>
	<div class="info">
		Wellness
		<div class="wellness"><div id="welmeter" style="width: {{ $citInfo['wellness'] * 1.88 }}px;"></div></div>
		<a class="fightbuts" href="javascript:void(0)" id="drjuicebf"><img src="/images/game/war/juice.png" id="juice-img" alt="J"></a>
		<div class="tooltip" style="margin-left: 53px" id="jTT">
			You can recover wellness by drinking your best juice in your inventory.
			<hr size="1">
			Wellness left to recover: <span class="recleft">{{ 200 - $citInfo['LastJuiceWellness'] }}</span>
		</div>
@php $canClinic = ($citInfo['LastDayClinic'] < $maxcli) && ($citInfo['Clinic'] ?? 0) > 0; @endphp
		<a id="useclinic" class="fightbuts" href="javascript:void(0)"{!! !$canClinic ? ' style="display: none"' : '' !!}>
			<img src="/images/game/war/clinic.png" alt="C" title="{{ $maxcli - $citInfo['LastDayClinic'] }}">
		</a>
		<div class="tooltip" style="margin-left: 83px" id="cTT">
			You can recover wellness from clinic whenever you fight.
			<hr size="1">
			Wellness packs left: <span class="recleft">{{ $maxcli - $citInfo['LastDayClinic'] }}</span>
		</div>
		<a class="fightbuts" href="javascript:void(0)" id="buyWP" title=""><img src="/images/game/war/tala.png" alt="W" title="{{ $wpCost }}"></a>
		<div class="tooltip" style="margin-left: 113px" id="wpTT">
			You can buy wellness packs to recover your wellness.
			<hr size="1">
			Wellness packs left: <span class="recleft">{{ (($citInfo['proExpire'] >= time()) ? 90 : 60) - $citInfo['LastDayWP'] }}</span><br>
			Price: <span class="wpPrice">{{ $wpCost }}</span>
			<img src="/images/game/war/tala.png" align="absmiddle">
		</div>
	</div>
	<div class="fight">
		<div class="my-inf">My advance: <span id="self-inf">{{ $myInf ?: 0 }}</span>m</div>
		<br />
		<div id="fightload" style="display: none; position: absolute; margin-left: 270px; width: 120px; height: 50px">
			<img src="/images/loading.gif" height="45px" width="120px">
		</div>
		<a href="javascript:void(0)" class="fightbut{{ $citInfo['wellness'] < 20 ? '-dis' : '' }}" id="fightbutton">Fight</a>
	</div>
</form>
