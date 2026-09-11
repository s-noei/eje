@extends('layouts.game')
@section('content')
@php
	$muID = $unit['mID']; $me = $citInfo['CitizenID'];
	$rowStyle = 'border-bottom:1px solid #F0F0F0;font-family: Trebuchet MS, Myriad Pro, Arial,sans-serif;font-size:12px;font-weight:bold;color:#707070;text-align:left;padding-left:20px;';
	$status = function ($id) use ($unit) {
		if ($id == $unit['mCommander']) return ['#F2FFDD', 'Commander'];
		if ($id == $unit['mCaptain']) return ['#FFFCDF', 'Captain'];
		return ['#F8F8F8', 'Soldier'];
	};
	$avatar = fn($c) => str_replace('Living in: , <br>', '', $vars->getAvatar($c, 'Avatar-s'));
@endphp
<div class="column-double">
@foreach ($errors as $e)
<h3 class="errHandle">{{ $e }}</h3>
@endforeach
<br>
<table width="100%" height="110" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="background:#F8F8F8;border-bottom:1px solid #F0F0F0;">
<td width="100%" height="110">
<div class="column-double">
	<div class="column-headcol">
		<img src="{{ $vars->getImgLoc('MULogo') . $unit['mLogo'] }}" class="Avatar-l" style="border: 2px solid rgb(0, 100, 0); border-radius: 5px;" alt="{{ $unit['mName'] }}" align="absmiddle">
	</div>
	<div class="column-details">
		<div class="column-name">
			<span style="font-family: Arial,Helvetica,sans-serif;font-size: 18px;color: rgb(51, 51, 51);text-shadow: 0px 1px 0px rgb(255, 255, 255);"><a href="{{ $vars->getURL('military-unit', $muID) }}">{{ $unit['mName'] }}</a></span>
			<span style="font-family: Arial,Helvetica,sans-serif;font-size: 16px;font-weight: normal;line-height: 18px;color: rgb(144, 146, 115);padding-left: 6px;margin-left: 10px;text-shadow: 0px 1px 0px rgb(255, 255, 255);border-left: 1px solid rgba(255, 255, 255, 0.9);box-shadow: -1px 0px 0px rgba(0, 0, 0, 0.1);"> {{ $unit['mMembers'] }} Members </span>
		</div>
		&nbsp;Location: <img src="{{ $vars->getImgLoc('CountryFlag') . $unit['Flag'] . '.gif' }}" class="Flag-xs" align="absmiddle"> <b>{{ $unit['cName'] }}</b>
		| Commander: <b><a href="{{ $vars->getURL('profile', $unit['mCommander']) }}">{{ $commander['name'] ?? '' }}</a></b>
<span style="clear: both">
@if ($citInfo['military_unit'] == $muID && $me != $unit['mCommander'])
| <b><a href="javascript:void(0)" onclick='if (confirm("Are you sure?")) document.doUnit.submit()'>Resign </a></b>
@endif
@if ($citInfo['nationality'] == $unit['mCountryID'] && $citInfo['military_unit'] == 0)
| <b><a href="javascript:void(0)" onclick='document.doUnit.submit()'>Join </a></b>
@endif
@if ($me == $unit['mCommander'])
| <b><a href="#unit-edit" onclick="mushow('unit-edit'); return false;">Edit </a></b>
@endif
</span>
		<br><span style="font-size: 11px;color: rgb(28, 56, 44);font-style: italic;text-shadow: 0px 1px 0px rgb(255, 255, 255);">{{ $unit['mText'] }}</span>
	</div>
	<div style="clear: both"></div>
</div>
</td></tr></table>

<form name="doUnit" action="" method="post">
@csrf
@if ($citInfo['military_unit'] == $muID && $me != $unit['mCommander'])
<input type="hidden" name="Resign" value="Resign">
@endif
@if ($citInfo['nationality'] == $unit['mCountryID'] && $citInfo['military_unit'] == 0)
<input type="hidden" name="Join" value="Join">
@endif
</form>

<script type="text/javascript">
function mushow(what) {
	var el = document.getElementById('tab-' + what);
	el.style.display = (el.style.display == 'block') ? 'none' : 'block';
}
</script>

<div id="tab-unit-edit" style="display: none;">
<h3>Edit a military unit</h3>
<table width="100%" height="190" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="background:#F8F8F8;border-bottom:1px solid #F0F0F0;opacity:0.8;">
<td width="100%" height="190">
<form name="Edit" action="" method="post" enctype="multipart/form-data">
	@csrf
	<table>
		<tr><td>Name:</td><td><input type="text" name="mName" size="20" class="text" value="{{ $unit['mName'] }}"></td></tr>
		<tr><td rowspan="2">Avatar:</td><td rowspan="1"><input type="file" name="mLogo" id="mLogo" /></td></tr>
		<tr><td class="style"><b>Restrictions: .jpg &amp; .jpeg <sup>Below 50 KBs</sup></b></td></tr>
		<tr><td>Text:</td><td><textarea cols="50" rows="3" name="mText">{{ $unit['mText'] }}</textarea></td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" name="Edit" value="Edit" id="buttons"></td></tr>
	</table>
</form>
</td></tr></table>
</div>

<h3>Order of the day</h3>
<table width="100%" height="50" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="border-top:1px solid #F0F0F0;{{ $rowStyle }}opacity:0.7;">
<td width="2%" height="70" style="background:#F8F8F8;">&nbsp;</td>
<td width="38%" style="background:#F8F8F8;">
<link rel="stylesheet" type="text/css" href="/include/css/home.css">
		<div class="home-box-content" style="display: block;">
@if (!$order)
							<center>There is no orders.</center>
@else
    					<a href="{{ $vars->getURL('battle', $order['battleID']) }}" align="absmiddle" class="battle-region-link" title="started {{ $session->getDiff($order['Start']) }}">
                            <div class="battles-holder">
                                <div class="battle-attacker-flag">
                                    <img src="/images/flags/s/{{ $order['battle_type'] == 'battle' ? $order['attName'] : 'revolt' }}.gif">
                                </div>
                                <div class="battle-region">{{ $order['regionName'] }}</div>
                                <div class="battle-attacker-flag">
                                    <img src="/images/flags/s/{{ $order['defName'] }}.gif" align="absmiddle">
                                </div>
                            </div>
    					</a>
@endif
		</div>
</td>
<td width="15%" style="background:#F8F8F8;"><center><b><a href="#unit-battle" onclick="mushow('unit-battle'); return false;">Battle <br>statistics</a></b></center></td>
<td width="10%">&nbsp;</td>
<td width="15%" style="background:#F8F8F8;"><div style="padding: 10px; float: left; text-align: center; overflow: hidden"><a href="{{ $vars->getURL('profile', $unit['mCommander']) }}">{!! $commander ? $avatar($commander) : '' !!}</a></div></td>
<td width="20%" style="background:#F8F8F8;"><i>Commander</i> <br><span style="color: rgb(78, 114, 143);font-weight: bold;font-size: 14px;">{{ $commander['name'] ?? '' }}</span></td>
</tr>
<tr style="{{ $rowStyle }}opacity:0.7;">
<td width="2%" height="70" style="background:#F8F8F8;">&nbsp;</td>
<td width="38%" style="background:#F8F8F8;">
@if ($isCC)
<form action="" name="addBattle" method="post">
@csrf
Select an active battle: <select name="battleID">
<option value="" selected="selected">--- Select battle ---</option>
@foreach ($activeBattles as $b)
			<option value="{{ $b['battleID'] }}">{{ $b['regionName'] }}</option>
@endforeach
</select>
@else
&nbsp;
@endif
</td>
<td width="15%" style="background:#F8F8F8;">@if ($isCC)<center><input type="submit" name="addBattle" value="Order" id="buttons"></center></form>@else &nbsp; @endif</td>
<td width="10%">&nbsp;</td>
<td width="15%" style="background:#F8F8F8;"><div style="padding: 10px; float: left; text-align: center; overflow: hidden">@if ($captain)<a href="{{ $vars->getURL('profile', $unit['mCaptain']) }}">{!! $avatar($captain) !!}</a>@endif</div></td>
<td width="20%" style="background:#F8F8F8;" ><i>Captain</i> <br><span style="color: rgb(78, 114, 143);font-weight: bold;font-size: 14px;">{{ $captain['name'] ?? '' }}</span></td>
</tr>
</table>

<div id="tab-unit-battle" style="display: none;">
<h3>Battle statistics</h3>
<table width="100%" height="50" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="background:#F8F8F8;{{ $rowStyle }}opacity:0.8;">
<td width="5%" height="30"><center><i>No.</i></center></td>
<td width="15%">&nbsp;</td>
<td width="35%"><i>Member name</i></td>
<td width="15%"><center><i>Fight</i></center></td>
<td width="15%"><center><i>Damage</i></center></td>
<td width="15%"><center><i>Status</i></center></td>
</tr>
@foreach ($stats as $i => $b)
@php [$bg, $st] = $status($b['CitizenID']); @endphp
			<tr style="background:{{ $bg }};{{ $rowStyle }}">
			<td width="5%" height="70"><center> {{ $i + 1 }} </center></td>
			<td width="15%"><div style="padding: 10px; float: left; text-align: center; overflow: hidden"><a href="{{ $vars->getURL('profile', $b['CitizenID']) }}"><div style="position: absolute; margin-left: -5px; margin-top: -5px; color: white; padding: 3px 0px; width: 20px; height: 14px; font-weight: bold; font-size: 8pt; border-radius: 5px; text-align:center; background: rgb(0, 100, 0)">{{ $b['puberty'] + 1 }}</div><img src="{{ $vars->getImgLoc('CitizenAvatar') . $b['Avatar'] }}" class="Avatar-s" style="border: 2px solid rgb(0, 100, 0); border-radius: 5px;" alt="{{ $b['name'] }}" align="absmiddle"></div></a></div></td>
			<td width="35%">{{ $b['name'] }}<br><a href="{{ $vars->getURL('mail', 'compose', $b['CitizenID']) }}"><img src="/images/game/icon_sendmsg.gif" class="inlineIMGs" align="absmiddle"></a></td>
			<td width="15%" style="color:#FF9900;"><center>{{ $b['UNITFIGHT'] }} <sup style="color:#707070;font-size:10px;font-weight:normal;">Fight(s)</sup></center></td>
			<td width="15%" style="color:#FF9900;"><center>{{ $b['UNITDMG'] }} <sup style="color:#707070;font-size:10px;font-weight:normal;">DMG</sup></center></td>
			<td width="15%"><center>{{ $st }}</center></td></tr>
@endforeach
</table>
</div>

<h3>Members ({{ $unit['mMembers'] }})</h3>
<table width="100%" height="50" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="background:#F8F8F8;{{ $rowStyle }}opacity:0.8;">
<td width="5%" height="30"><center><i>No.</i></center></td>
<td width="15%">&nbsp;</td>
<td width="33%"><i>Member name</i></td>
<td width="2%">&nbsp;</td>
<td width="15%"><center><i>Wellness</i></center></td>
<td width="15%"><center><i>Experience</i></center></td>
<td width="15%"><center><i>Status</i></center></td>
</tr>
@foreach ($members as $i => $m)
@php
	[$bg, $st] = $status($m['CitizenID']);
	$mine = $citInfo['military_unit'] == $muID && $me != $m['CitizenID'];
	$canCaptain = $mine && $me == $unit['mCommander'] && $unit['mCaptain'] != $m['CitizenID'];
	$canRemove = $mine && $isCC;
@endphp
			<tr style="background:{{ $bg }};{{ $rowStyle }}">
			<td width="5%" height="70"><center> {{ $i + 1 }} </center></td>
			<td width="15%"><div style="padding: 10px; float: left; text-align: center; overflow: hidden"><a href="{{ $vars->getURL('profile', $m['CitizenID']) }}">{!! $avatar($m) !!}</a></div></td>
			<td width="33%">{{ $m['name'] }}<br><a href="{{ $vars->getURL('mail', 'compose', $m['CitizenID']) }}"><img src="/images/game/icon_sendmsg.gif" class="inlineIMGs" align="absmiddle"></a></td>
			<td width="2%">
@if ($canCaptain)
<form action="" name="CaptainCC" method="post">@csrf<input type="hidden" name="MemberID" value="{{ $m['CitizenID'] }}"> <input type="submit" name="CaptainCC" value="" style="background: url(/images/game/add-this.png) no-repeat;width:24px;height:24px;border:0;"></form>
@endif
@if ($canRemove)
<form action="" name="ResignCC" method="post">@csrf<input type="hidden" name="MemberID" value="{{ $m['CitizenID'] }}"> <input type="submit" name="ResignCC" value="" style="background: url(/images/game/icon_remove.gif) no-repeat;width:24px;height:24px;border:0;"></form>
@else
&nbsp;
@endif
			</td>
			<td width="15%" style="color:#FF9900;"><center>{{ $m['wellness'] }}</center></td>
			<td width="15%" style="color:#FF9900;"><center>{{ $m['ep'] }}  <sup style="color:#707070;font-size:10px;font-weight:normal;">Exp</sup></center></td>
			<td width="15%"><center>{{ $st }}</center></td></tr>
@endforeach
</table>
</div>
@endsection
