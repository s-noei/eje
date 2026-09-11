@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/profile.css">@endpush
<script language="javascript" type="text/javascript">
function form_submit(){ document.cManage.submit(); }
</script>
<script type="text/javascript" src="/include/js/juice.js"></script>
@php
	$p = fn($k) => $lang->getstr($k, 'profile');
	$aIMG = $vars->getImgLoc('CitizenAvatar') . $row['Avatar'];
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
@endphp
	<div class="column-double">
@if (($bandata['type'] ?? 0) == 1)
					<div class="column-infrigments">Account arrested for {{ $bandata['reason'] }}</div>
@elseif (($bandata['type'] ?? 0) == 2)
					<div class="column-infrigments">Account suspended for {{ $bandata['reason'] }}</div>
@endif
@if ($database->usernameHibernated($row['CitizenID']))
					<div class="column-infrigments">{!! $p('profile_hibernated') !!}</div>
@endif
		<div id="head-left">
			<div class="cit-avatar" style="background: none" title="Click to see avatar in a new window"
				onclick="javascript:window.open('{{ $aIMG }}','citizen_avatar','status=yes,scrollbars=yes,toolbar=no,menubar=no,location=no ,width=520px,height=500px')">
				<img src="{{ $aIMG }}" style="background: url('');" width="118px" height="118px">
				<img src="/images/profile/avatar{{ ($bandata['type'] ?? 0) ? '-j' : '' }}.png" style="position: absolute; margin-top: -123px">
			</div>
			<div class="cit-name">
@if ($online)
				<img src="/images/game/online.gif" align="absmiddle" title="{{ $p('profile_online') }}" alt="online">
@else
				<img src="/images/game/offline.gif" align="absmiddle" title="{{ $p('profile_offline') }}" alt="offline">
@endif
				<a href="{{ $vars->getURL('profile', $req_id) }}">
					{{ $row['name'] }}
					{!! $hasPro ? ' <font color="GoldenRod" title="'.$p('profile_acc_pro').'"><b>*</b></font>' : ($hasPlus ? ' <font color="Red" title="'.$p('profile_acc_plus').'"><b>+</b></font>' : '') !!}
				</a>
				{!! $vars->getWikiLink('Citizen', $row['name']) !!}
			</div>
			<div class="cit-about">
@if ($row['aboutme'])
				{!! nl2br(e($row['aboutme'])) !!}
@else
				<i>{!! sprintf($p('profile_no_about'), ($row['female'] ? $lang->getstr('her') : $lang->getstr('his'))) !!}</i>
@endif
			</div>
			<div style="clear: left"></div>
@if (!$is_ca && !$is_nca && !($bandata['type'] ?? 0))
					<div class="cit-rank">
						<div class="rank-t">{!! $p('profile_rank') !!}:</div>
						<div class="rank-l">
@if ($row['stat_rank_coun_id'] == $row['CountryID'])
									<img src="{{ $vars->getImgLoc('CountryFlag') . $row['cName'] }}.gif" class="Flag-xs" align="absmiddle">
									{{ $row['stat_rank_coun'] }}
@endif
						</div>
						<div class="rank-i">
							<img src="{{ $vars->getImgLoc('CountryFlag') }}world.gif" class="Flag-xs" align="absmiddle">
							{{ $row['stat_rank_int'] }}
						</div>
					</div>
@endif
		</div>
		<div id="head-right">
@if ($row['oldname'] && !$is_nca)
					{!! $p('profile_old_name') !!}:<b>{{ $row['oldname'] }}</b><hr>
@endif
			<div class="cit-living">
				<b>{!! $p('profile_living') !!} </b><br>
				<a href="{{ $vars->getURL('country', $row['CountryID']) }}">
					<img src="{{ $database->getCountryFlagC($row['CountryID'], $vars->getImgLoc('CountryFlag')) }}" title="{{ $row['cName'] }}" class="Flag-xs" align="absmiddle">
				</a>
				<a href="{{ $vars->getURL('region', $row['regionID']) }}">
					{!! $vars->lenTrim($row['RegionName'], 15, 'char', 0, $p('profile_living')) !!}
				</a>
			</div>
			<div class="cit-nationality">
@if (!$is_ca && !$is_nca)
@php $natC = $database->getCountryC($row['nationality']); @endphp
						{!! $p('profile_nationality') !!}<br>
						<a href="{{ $vars->getURL('country', $row['nationality']) }}">
							<img src="{{ $database->getCountryFlagC($row['nationality'], $vars->getImgLoc('CountryFlag')) }}" class="Flag-xs" align="absmiddle" title="{{ $natC }}">
							{!! $vars->lenTrim($natC, 15, 'char', 0, $p('profile_nationality')) !!}
						</a>
@elseif ($is_nca)
						{!! $p('profile_nca') !!} - {!! $p('profile_owner') !!}: <a href="{{ $vars->getURL('country', $row['accOwner']) }}">#{{ $row['accOwner'] }}</a>
@else
						{!! $p('profile_ca') !!} - {!! $p('profile_owner') !!}: <a href="{{ $vars->getURL('profile', $row['accOwner']) }}">{{ $row['accOwnerName'] }}</a>
@endif
			</div>
@if ($vpts !== null)
					<div class="cit-vio">
						{!! $p('profile_vpoints') !!}: {{ $vpts }}/10
						(<a href="{{ $vars->getURL('profile', $row['CitizenID'], 'violations') }}">{!! $p('profile_vios') !!}</a>)
					</div>
@endif
			<div class="cit-links">
				<form action="{{ $vars->getURL('contact') }}" method="post" name="uManage">
				@csrf
@if ($view_own && !$is_ca && !$is_nca)
						<a href="{{ $vars->getURL('profile', $citInfo['CitizenID'], 'consume') }}"><img src="/images/profile/food.png" title="{{ $p('profile_usefood') }}"></a>
						<a href="{{ $vars->getURL('profile', $citInfo['CitizenID'], 'healthkit') }}"><img src="/images/profile/health-kit.png" title="{{ $p('profile_usehealthkit') }}"></a>
@elseif (!$is_ca && !$is_nca && $logged)
						<script>
							targetCit = {{ $row['CitizenID'] }};
							token = '{{ md5($row['CitizenID'] . $citInfo['CitizenID'] . config('ejahan.salts.juice')) }}';
						</script>
						<a href="javascript:void(0)" class="ofjuice"><img src="/images/profile/juice.png" title="{{ $p('profile_drjuice') }}"></a>
@endif
@if ($view_own)
						<a href="{{ $vars->getURL('profile', '', 'edit') }}"><img src="/images/profile/edit.png" title="{{ $p('profile_edit') }}"></a>
	@if (!$is_ca && !$is_nca)
							<a href="{{ $vars->getURL('profile', $citInfo['CitizenID'], 'nationality') }}"><img src="/images/profile/nationality.png" title="{{ $p('profile_nationality') }}"></a>
							<a href="{{ $vars->getURL('profile', $citInfo['CitizenID'], 'coaccounts') }}"><img src="/images/profile/coacc.png" title="{{ $p('profile_cas') }}"></a>
	@endif
@else
	@if ($logged)
								<a href="{{ $vars->getURL('mail', 'compose', $row['CitizenID']) }}"><img src="/images/profile/msg.png" title="{{ $p('profile_sendmsg') }}"></a>
	@endif
	@if ($logged && !$isCA && !$is_ca && $row['active'] && !empty($citInfo['active']))
		@if (!$isFriend)
										<a href="{{ $vars->getURL('profile', $row['CitizenID'], 'add') }}"><img src="/images/profile/add.png" title="{{ $p('profile_friend_add') }}"></a>
		@else
										<a href="{{ $vars->getURL('profile', $citInfo['CitizenID'], 'remove', $row['CitizenID']) }}"><img src="/images/profile/remove.png" title="{{ $p('profile_friend_remove') }}"></a>
		@endif
	@endif
	@if ($logged)
						<a href="{{ $vars->getURL('profile', $row['CitizenID'], 'donate') }}"><img src="/images/profile/donate.png" title="{{ $p('profile_donate') }}"></a>
						<input type="hidden" name="reason" value="Report abuse">
						<input type="hidden" name="message" value="Abuse URL is: {{ $vars->getURL('profile', $row['CitizenID']) }}">
		@if (!$acc['can_ban_citizens'])
								<a href="javascript:void(0)" onclick="document.uManage.submit()"><img src="/images/profile/report.png" title="{{ $p('profile_report') }}"></a>
		@elseif (!$is_nca)
								<a href="javascript:void(0)" onclick="document.getElementById('banuser').style.display = 'block'"><img src="/images/profile/setvio.png" title="Submit violation"></a>
		@endif
		@if ($acc['can_define_mods'] && !$is_ca && !$row['ModID'])
							<a href="{{ $vars->getURL('profile', $row['CitizenID'], 'lensinvite') }}"><img src="/images/profile/add.png" title="Send invite for Lens"></a>
		@endif
	@endif
@endif
				</form>
			</div>
		</div>
		<div style="clear: both"></div>
@if ($acc['can_view_citizen_comments'])
@php $totcms = count($modcms); $lastcm = $modcms[0] ?? null; @endphp
				<div class="profile-modcomments">
					<hr>
@if (!$totcms)
							No moderation comments (<a href="javascript:void(0)" id="viewmodcms">send one</a>)
@else
							Last mod comment: {!! $vars->lenTrim($lastcm['Body'], 50, '', 0) !!} <b>by {{ $lastcm['ByMod'] ?: $lastcm['ByName'] }}</b>
							(<a href="javascript:void(0)" id="viewmodcms">view all {{ $totcms }}</a>)
@endif
				</div>
				<div id="modcms" style="position: absolute; top:0; left: 0; width: 98%; height: 250px; border: 1px solid; padding: 5px; background:white; z-index: 1000; display: none">
					<b>Mod comments for this citizen</b> (<a href="javascript:void(0)" id="closemodcms">close</a>)
					<hr>
					<div style="height: 100px; overflow-Y: auto">
@foreach ($modcms as $i => $comment)
								<div style="background: #{{ (($i + 1) % 2) ? 'D0D0D0' : 'F0F0F0' }}; padding: 2px">
								{{ $comment['ByMod'] ?: $comment['ByName'] }}: {{ $comment['Body'] }} <sub>wrote {!! $session->getDiff($comment['timestamp']) !!}</sub>
								</div>
@endforeach
					</div>
					<hr>
					<b>Write a comment</b>
					<form action="" method="post">
						@csrf
						<textarea cols="50" rows="3" name="modcm"></textarea>
						<input type="submit" name="addmodcm" value="Add comment" id="submits" align="absmiddle">
					</form>
				</div>
@endif
	</div>
@if ($acc['can_ban_citizens'] && !$is_nca)
@include('pages.profile.banform')
@endif
	<hr size="2">
	<div id="juicemsg" style="text-align: center; display: none"></div>
@if (!empty($subtitle))
	<div class="column-headcol">{!! $subtitle !!}</div>
	<div class="column-details"><b>{!! $subhead !!}</b><hr>
@if ($sub === 'friends')
		<a href="{{ $vars->getURL('profile', $row['CitizenID']) }}" id="buttons">{!! $p('profile_back') !!}</a>
@endif
@endif
@include('pages.profile.'.$sub)
@if (!empty($subtitle))
	</div>
@endif
<script>
	$(document).ready(function(){
			$("#viewmodcms").click(function(){ $("#modcms").fadeIn(500) });
			$("#closemodcms").click(function(){ $("#modcms").fadeOut(500) });
		});
</script>
@endsection
