@php
	use App\Game\Support\Constants;
	$p = fn($k) => $lang->getstr($k, 'profile');
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$npHtml = $np ? '<a href="'.$vars->getURL('newspaper', $np['npID']).'"><img src="'.$vars->getImgLoc('npAvatar').$np['Avatar'].'" class="Avatar-xs" alt="np" align="absmiddle">&nbsp;'.e($np['npName']).'</a>' : '';
	$working = $wComp ? '<a href="'.$vars->getURL('company', $wComp['CompanyID']).'"><img src="'.$vars->getImgLoc('CompanyAvatar').$wComp['Avatar'].'" class=Avatar-xs align=absmiddle>&nbsp;'.e($wComp['Name']).'</a>' : '';
	$managingHtml = '';
	foreach ($managing as $mc) $managingHtml .= '<a href="'.$vars->getURL('company', $mc['CompanyID']).'"><img src="'.$vars->getImgLoc('CompanyAvatar').$mc['Avatar'].'" class=Avatar-xs align=absmiddle>&nbsp;'.e($mc['Name']).'</a><br>';
	$pmember = $party ? '<a href="'.$vars->getURL('party', $party['pID']).'"><img src="'.$vars->getImgLoc('PartyLogo').$party['pLogo'].'" class=Avatar-xs align=absmiddle>&nbsp;'.e($party['pName']).'</a>' : '';
@endphp
<script type="text/javascript" src="/include/js/profile.js"></script>
<center>
@if ($showData)
	<a href="javascript:void(0)" class="button-blue-1" id="data">{!! $p('profile_menu_data') !!}</a>
@endif
	<a href="javascript:void(0)" class="button-blue-0" id="career">{!! $p('profile_menu_career') !!}</a>
@if (!$is_ca && !$is_nca)
	<a href="javascript:void(0)" class="button-blue-1" id="bio">{!! $p('profile_menu_ebio') !!}</a>
@endif
	<a class="button-blue-1" href="{{ $vars->getURL('profile', $row['CitizenID'], 'donations') }}">{!! $p('profile_menu_donations') !!}</a>
	<a href="{{ $vars->getURL('profile', $row['CitizenID'], 'friends') }}" class="button-blue-0">{!! $p('profile_menu_friends') !!}</a>
	<div style="clear: both"></div>
	<hr>
</center>
<script>
	var activeBox = '#box-{{ $showData ? 'data' : 'career' }}';
</script>
@if ($showData)
<div class="column-double">
	<div id="box-data">
	<div class="column-headcol">{!! $p('profile_menu_data') !!}</div>
	<div class="column-details">
@if ($acc['can_view_private_data'])
				<b>{!! $p('profile_email') !!}</b>
				<hr>
				<div>{{ $row['email'] }}</div>
@endif
			<b>{!! $p('profile_money') !!}</b>
			<hr>
			<div>
@foreach ($money as $row3)
				<div style="float: left; padding-right: 5px;"><img src="{{ $database->getCurrencyIco($row3['CurID'], $vars->getImgLoc('CurrencyIcon')) }}" class="inlineIMGs" align="absmiddle">  {{ round($row3['Amount'], 2) }} {{ $database->getCurrency($row3['CurID']) }}</div>
@endforeach
@if ($canSeeInv)
					<div style="clear: both">&nbsp;</div>
				</div>
				<b>{!! $p('profile_inventory') !!} ({{ $invCount }}/{{ $max }})</b>
            <a href="{{ $vars->getURL('ejstore') }}#pro"><em>Expand inventory</em></a>
				<hr>
@foreach ($inventory as $inv)
								<div class="inventory-item">
                                <div class="hover">&nbsp;</div>
                                <div class="icon"><img src="/images/icons/{{ $inv['Icon'] }}.png"></div>
                                <div class="quality"><img src="/images/game/{{ $inv['Stars'] }}_star.gif"></div>
                                <div class="amount">{{ $inv['Amount'] }}</div>
                            </div>
@endforeach
@if (!$invCount)
                            The inventory is empty!
@endif
	<div style="clear: both">{!! $vars->getAccPaid($row) !!}</div>
@else
			</div>
@endif
	</div>
	<div style="clear: both">&nbsp;</div>
	</div>
</div>
@endif
<div class="column-double">
	<div id="box-career"{!! $showData ? ' style="display: none"' : '' !!}>
	<div class="column-headcol">{!! $p('profile_menu_career') !!}</div>
	<div class="column-details">
@if (!$is_ca && !$is_nca)
				<div class="profile-holder">
					<center>
						<b>{!! $p('profile_trophy') !!}</b>
						(<a href="{{ $vars->getURL('profile', $row['CitizenID'], 'diary') }}">{!! $p('profile_menu_diary') !!}</a>)
					</center>
					<hr>
@php
	$trophy = function($key, $col) use ($row, $tit, $p) {
		$n = (int) $row['medals_'.$col];
		return '<div class="trophy-det" style="background: url(\'/images/game/profile/trophy/'.$key.($n ? '' : '-off').'.gif\')" title="'.sprintf($tit, $p('profile_trophy_'.$key.'_title'), $p('profile_trophy_'.$key.'_desc')).'">'.($n ?: '&nbsp;').'</div>';
	};
@endphp
					<div id="trophy">
						<div class="trophy-holder" style="vertical-align: top">
							{!! $trophy('wf', 'wf') !!}{!! $trophy('lm', 'lm') !!}{!! $trophy('gg', 'gg') !!}
						</div>
						<div class="trophy-holder" style="vertical-align: top">
							{!! $trophy('is', 'is') !!}{!! $trophy('rs', 'revolt') !!}{!! $trophy('bh', 'hero') !!}
@if ($row['medals_mh']){!! $trophy('mh', 'mh') !!}@endif
						</div>
						<div class="trophy-holder" style="vertical-align: top">
							{!! $trophy('pp', 'pp') !!}{!! $trophy('cg', 'cg') !!}{!! $trophy('cp', 'cp') !!}
						</div>
						<div class="trophy-holder" style="vertical-align: top">
							{!! $trophy('mp', 'mp') !!}{!! $trophy('ap', 'ap') !!}{!! $trophy('am', 'am') !!}
						</div>
					</div>
				</div>
				&nbsp;
				<div class="profile-holder">
					<center><b>{!! $p('profile_qstate') !!}</b><hr></center>
					<blockquote style="text-align: center">
@if ($working)
						<img src="/images/game/profile/worker-on.gif" title="{{ sprintf($tit, $p('profile_qstate_eco_title'), $p('profile_qstate_eco_working')) }}">
@elseif ($managingHtml)
						<img src="/images/game/profile/manager.gif" title="{{ sprintf($tit, $p('profile_qstate_eco_title'), $p('profile_qstate_eco_manager')) }}">
@else
						<img src="/images/game/profile/worker-off.gif" title="{{ sprintf($tit, $p('profile_qstate_eco_title'), $p('profile_qstate_eco_unemployee')) }}">
@endif
@if ($pmember)
						<img src="/images/game/profile/pmember-on.gif" title="{{ sprintf($tit, $p('profile_qstate_pol_title'), $p('profile_qstate_pol_pmem')) }}">
@else
						<img src="/images/game/profile/pmember-off.gif" title="{{ sprintf($tit, $p('profile_qstate_pol_title'), $p('profile_qstate_pol_pno')) }}">
@endif
@if ($iscg)
						<img src="/images/game/profile/congress.gif" title="{{ sprintf($tit, $p('profile_qstate_cong_title'), $p('profile_qstate_cong_desc')) }}">
@endif
@if ($iscp)
						<img src="/images/game/profile/cp.gif" title="{{ sprintf($tit, $p('profile_qstate_cp_title'), $p('profile_qstate_cp_desc')) }}">
@endif
@if ($npHtml)
						<img src="/images/game/profile/director-on.gif" title="{{ sprintf($tit, $p('profile_qstate_np_title'), $p('profile_qstate_np_on')) }}">
@else
						<img src="/images/game/profile/director-off.gif" title="{{ sprintf($tit, 'Media Status', $p('profile_qstate_np_off')) }}">
@endif
					</blockquote>
				</div>
@endif
		&nbsp;
		<div class="profile-holder">
			<center><b>{!! $p('profile_cstate') !!}</b><hr></center>
@if (!$is_ca && !$is_nca)
	@if ($working)
			<b>{!! $p('profile_cstate_working') !!}: </b><blockquote>{!! $working !!}</blockquote>
	@elseif ($managingHtml)
			<b>{!! $p('profile_cstate_managing') !!}: </b><blockquote>{!! $managingHtml !!}</blockquote>
	@else
			<b>{!! $p('profile_cstate_working') !!}: </b> {!! $p('profile_cstate_no_comp') !!}<br>
	@endif
	@if ($pmember)
			<b>{!! $p('profile_qstate_pol_title') !!}: </b><blockquote>{!! $pmember !!}<br><b>{!! $ispp ? $p('profile_cstate_pp') : $p('profile_cstate_pm') !!}</b><br></blockquote>
	@else
			<b>{!! $p('profile_cstate_pm') !!}: </b> {!! $p('profile_cstate_no_activity') !!}<br>
	@endif
	@if ($unit)
			<b>Military Unit: </b><blockquote><a href="{{ $vars->getURL('military-unit', $unit['mID']) }}"><img src="/uploads/avatars/military-unit/{{ $unit['mLogo'] }}" class=Avatar-xs align=absmiddle>&nbsp;{{ $unit['mName'] }}</a></blockquote>
	@endif
@else
	@if ($managingHtml)
			<b>{!! $p('profile_cstate_managing') !!}: </b><blockquote>{!! $managingHtml !!}</blockquote>
	@else
			<b>{!! $p('profile_cstate_managing') !!}: </b> {!! $p('profile_cstate_no_comp') !!}<br>
	@endif
@endif
@if ($npHtml)
			<b>{!! $p('profile_cstate_media') !!}: </b><blockquote>{!! $npHtml !!}</blockquote>
@else
			<b>{!! $p('profile_cstate_media') !!}: </b> {!! $p('profile_cstate_no_activity') !!}<br>
@endif
	</div>
	<div style="clear: both">&nbsp;</div>
	</div>
@if (!$is_ca && !$is_nca)
</div>
<div class="column-double">
	<div id="box-bio" style="display: none">
	<div class="column-headcol">{!! $p('profile_menu_ebio') !!}</div>
	<div class="column-details">
		<div class="profile-holder">
			<center><b>{!! $p('profile_experience') !!}</b></center><hr>
			<div class="profile-box">
				<b>{!! $p('profile_joined_day') !!} </b><br><font size="6">{{ $row['joined'] ?: $p('profile_prebeta') }}</font>
			</div>
			<div class="profile-box">
				<b>{!! $p('profile_ep') !!}</b><br><font size="6">{{ $row['ep'] }}</font>
			</div>
			<div class="profile-box">
				<b>{!! $lang->getstr('wellness') !!}</b><br>
				<div id="wellness" class="infobox" style="float: none; display: inline-block; width: 30px">
					<div id="wnmeter" style="background: transparent url('/images/welind-{{ $row['female'] ? 'f' : 'm' }}.png') no-repeat scroll 3px top" title="{{ $row['wellness'] }}">
						<div id="holder-wellness" class="meter" style="height: {{ 100 - $row['wellness'] }}%">
							<img src="/images/welbase-{{ $row['female'] ? 'f' : 'm' }}.png" alt="{{ $row['wellness'] }}" title="{{ $row['wellness'] }}">
						</div>
					</div>
				</div>
			</div>
			<div style="clear: both"></div>
			<b>{!! $p('profile_puberty') !!}:</b> <font size="4" style="color: {{ Constants::PUB_COLORS[$row['puberty']] ?? '' }}">{{ Constants::PUB_RANKS[$row['puberty']] ?? '' }}</font><br>
		</div>
		<br>
		<div class="profile-holder">
			<center><b>{!! $p('profile_skills') !!}</b></center>
			<hr>
			<div class="holder-indicator">
				<div class="title">Strength</div>
				<div class="desc">{{ $row['strength'] ?? 0 }}</div>
				<div class="ind">
					{!! $vars->viewIndicator(0, Constants::SHAPE_MAX, $row['strength'] ?? 0, 400, "#CC3333", "Body shape", "%s of ".Constants::SHAPE_MAX." — ".(Constants::SHAPE_NAMES[(int) floor((($row['strength'] ?? 0) + ($row['stamina'] ?? 0)) / 2)] ?? '')) !!}
					<div style="clear: both"></div>
				</div>
			</div>
			<div class="holder-indicator">
				<div class="title">Stamina</div>
				<div class="desc">{{ $row['stamina'] ?? 0 }}</div>
				<div class="ind">
					{!! $vars->viewIndicator(0, Constants::SHAPE_MAX, $row['stamina'] ?? 0, 400, "RoyalBlue", "Body shape", "%s of ".Constants::SHAPE_MAX) !!}
					<div style="clear: both"></div>
				</div>
			</div>
			<hr size="1">
			<div class="holder-indicator">
				<div class="title">{!! $p('profile_working') !!}</div>
				<div class="desc">{{ $row['wSkill'] }}</div>
				<div class="ind">
					{!! $vars->viewIndicator(Constants::SP_CPS[$row['wSkill']] ?? 0, Constants::SP_CPS[$row['wSkill'] + 1] ?? 0, $row['wSP'], 400, "RoyalBlue", "Skill points", "%s<br>Total skill points: {$row['wSP']}") !!}
					<div style="clear: both"></div>
				</div>
			</div>
			<hr size="1">
			<div class="holder-indicator">
				<div class="title">{!! $p('profile_mrank') !!}</div>
				<div class="desc-double-e">
					<img src="/images/game/war/mrank/{{ $row['mRank'] }}.png" width="50" align="absmiddle" title="{{ Constants::MILI_RANKS[$row['mRank']] ?? '' }}">
				</div>
				<div class="ind-smaller">
					{!! $vars->viewIndicator(Constants::RANK_DAMAGES[$row['mRank']] ?? 0, Constants::RANK_DAMAGES[$row['mRank'] + 1] ?? 0, $row['total_damage'], 375, "maroon", $p('profile_totadv'), "%s", 0) !!}
					<div style="clear: both"></div>
				</div>
			</div>
			<center>
				<div class="profile-box">
					<b>{!! $p('profile_totfights') !!}</b><br><font size="6">{{ $row['fight_count'] }}</font>
				</div>
				<div class="profile-box">
					<b>{!! $p('profile_avgadv') !!}</b><br><font size="6">{{ $row['fight_count'] ? round($row['total_damage'] / $row['fight_count'], 2) : 0 }}</font>
				</div>
			</center>
			<div style="clear: both"></div>
		</div>
@endif
	</div>
	<div style="clear: both">&nbsp;</div>
	</div>
</div>
@if (isset($friends))
@php $numFri = count($friends); $total = min(5, $numFri); @endphp
<div class="column-double">
	<div class="column-headcol">{!! $p('profile_menu_friends') !!}</div>
	<div class="column-details">
		<font size=2><b>{!! sprintf($p('profile_friends_qview'), $total, $numFri) !!}</b></font><hr>
		<blockquote>
		<div style="width: 100%">
@foreach (array_slice($friends, 0, 5) as $part)
			<div style="width: 19%;float:left;padding:0px;text-align: center">
				<a href="{{ $vars->getURL('profile', $part['CitizenID']) }}"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $part['Avatar'] }}" alt="{{ $part['name'] }}" class="Avatar-xs"><br>{{ $part['name'] }}</a>
			</div>
@endforeach
		<div style="clear: both">&nbsp;</div>
		<div style="float: right"><a href="{{ $vars->getURL('profile', $row['CitizenID'], 'friends') }}">{!! $p('profile_friends_view') !!}</a></div>
		</div>
		</blockquote>
	</div>
	<div style="clear: both">&nbsp;</div>
</div>
@endif
