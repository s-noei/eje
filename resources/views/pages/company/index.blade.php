@php $c = fn($k) => $lang->getstr($k, 'company'); @endphp
{!! $msg ?? '' !!}
		<div class="page-box">
			<div class="head"><div class="holder-title-1">{!! $c('company_economy') !!}</div></div>
@if ($uInf || $isManager)
			<div class="company-priv">
@if ($uInf)
						<div class="holder-title-3">{!! $c('company_workplace') !!}</div>
						<form action="" method="post" name="resign" style="text-align: center; padding-top: 12px">
							@csrf
@if ($citInfo['LastWorked'] == $database->getToday())
									<center><b>{!! $c('company_worked') !!}</b></center>
									<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'workplace') }}">{!! $c('company_view_report') !!}</a>
@else
									<center><b>{!! $c('company_not_worked') !!}</b></center>
									<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'workplace') }}">{!! $c('company_workplace') !!}</a>
@endif
							<input type="hidden" name="Resign" value="1">
							<a href="javascript:void(0)" class="button-red-0" onclick="if (confirm('{!! addslashes($lang->getstr('confirm_resign_comp', 'msgs')) !!}')) document.resign.submit()">{!! $lang->getstr('resign') !!}</a>
						</form>
@elseif ($isManager)
						<div class="holder-title-3">{!! $c('company_accounts') !!}</div>
			<center>
@foreach ($accounts as $row2)
@if ($row2['CurID'] == 1 || $row2['CurID'] == $row['CountryID'])
										<div style="display: inline-block; padding-right: 8px;">
											<img src="{{ $database->getCurrencyIco($row2['CurID'], $vars->getImgLoc('CurrencyIcon')) }}" class="Flag-xs" align="absmiddle"> {{ round($row2['Amount'], 2) }}
										</div>
@endif
@endforeach
			</center>
						<center><a href="{{ $vars->getURL('company', $row['CompanyID'], 'finance') }}" class="button-blue-1">{!! $c('company_finance') !!}</a></center>
						<div style="clear: both"></div>
@endif
			</div>
@endif
			<div class="company-workers">
				<div class="holder-title-3">{!! $c('company_workers') !!}</div>
				&nbsp;&nbsp;&nbsp;&nbsp;{!! $c('company_workers') !!}: {{ count($workers) }}/{{ $maxworkers }}<br>
				<a href="{{ $vars->getURL('company', $row['CompanyID'], 'workers') }}" class="button-blue-1">{!! $c('company_view_workers') !!}</a>
			</div>
			<div style="clear: both"><br></div>
			<div class="child-box">
				<div class="head"><div class="holder-title-2">{!! $c('company_status') !!}</div></div>
				<div class="tiny-box">
					<b>{!! $c('company_industry') !!}</b><br>
					<img src="/images/icons/{{ strtolower($indName) }}.png" title="{{ $indName }}" width="45px">
				</div>
				<div class="tiny-box">
					<b>{!! $c('company_stock') !!}</b><br>
					<font size="4">{{ $row['Stock'] }}</font>
				</div>
				<div class="tiny-box" style="text-align: justify">
					<b>{!! $c('company_next_product') !!}</b><br>
					<img src="/images/icons/{{ strtolower($indName) }}.png" title="{{ $indName }}" width="45px" style="opacity: {{ $row['Unit'] ? round($row['Products']/$row['Unit']*0.9, 2) + 0.1 : 1 }}">
					<div class="company-next-progress">
						{{ $row['Products'] . '/' . $row['Unit'] }}
						<div class="progress-e"><div class="progress-f" style="width: {{ $row['Unit'] ? round($row['Products']*100/$row['Unit'], 2) : 0 }}%"></div></div>
					</div>
				</div>
				<div class="tiny-box">
@php $eff = round(100 - $row['tool_e'], 2); $eff2 = round($eff * 0.8 + 20, 2); @endphp
					<div id="indmeter" style="background: transparent url('/images/icons/tools.png') no-repeat scroll 3px top; width: 65px; height: 58px;" title="{{ 100 - $eff }}%">
						<div class="meter" style="height: {{ $eff2 }}%; width: 62px"><img src="/images/icons/tools-e.png" title="{{ 100 - $eff }}%"></div>
					</div>
					<div class="tool-q"><img src="/images/game/{{ $row['tool_q'] }}_star.gif" width="64px" title="{{ $row['tool_q'] }}-star Tool"></div>
				</div>
				<div class="tiny-box" id="company-location" style="width: 150px; text-align: justify">
					<b>{!! $c('company_located') !!}</b><br>
					<div class="flag"><img src="{{ $cIMG }}" class="Flags" alt="{{ $cName }}"></div>
					<div class="loc">
						<a href="{{ $vars->getURL('country', $row['CountryID']) }}">{{ $cName }}</a>
						<br>
						<a href="{{ $vars->getURL('region', $row['RegionID']) }}">{!! $vars->lenTrim($row['regionName'], 12, 'char', 0, $c('company_located')) !!}</a>
					</div>
				</div>
			</div>
@if ($isManager)
					<center>
						<a href="{{ $vars->getURL('company', $row['CompanyID'], 'quality') }}" class="button-blue-1">{!! $c('company_upgrade') !!}</a>
						<a href="{{ $vars->getURL('company', $row['CompanyID'], 'license') }}" class="button-blue-1">{!! $c('company_buy_license') !!}</a>
						<a href="{{ $vars->getURL('company', $row['CompanyID'], 'status') }}" class="button-blue-1">{!! $c('company_status') !!}</a>
@if ($row['IndustryID'] == 2)
						<a href="{{ $vars->getURL('company', $row['CompanyID'], 'migrate') }}" class="button-red-0">{!! $c('company_migrate') !!}</a>
@endif
					</center>
					<div class="company-tools">
						<div class="holder-title-2">{!! $c('company_secondary_tool') !!}</div>
						<div class="info-div" style="background:none">
							<div class="info-title"><img src="/images/game/{{ $row['tool_a'] }}_star.gif" width="64px"></div>
							<div id="indmeter" style="background: transparent url('/images/icons/tools.png') no-repeat scroll 3px top; width: 65px; height: 65px;"></div>
						</div>
						<div style="float: left; padding-top: 10px; padding-left: 10px">
							<form action="" method="post">
								@csrf
@if ($row['tool_a'])
										<input type="submit" name="installtool" class="submit-blue-1" value="{{ $c('company_install_tool') }}">
@else
										<b>{!! $c('company_insert_tool') !!}</b><br>
										<select name="toolq">
@if (count($tools) < 1)
											<option value="0">-- {!! $c('company_buy_tool') !!} --</option>
@endif
@foreach ($tools as $tq)
											<option value="{{ $tq['Stars'] }}">{{ $tq['Stars'] }}-star tool</option>
@endforeach
										</select>
										<input type="submit" name="inserttool" class="submit-blue-1" value="{{ $c('company_insert_tool_2') }}">
@endif
							</form>
						</div>
						<div style="clear: both"></div>
					</div>
@endif
		</div>
@if ($row['IndustryID'] != 11)
			<div class="page-box">
				<div class="head"><div class="holder-title-2">{!! $c('company_licenses') !!}</div></div>
				<center><div style="width: 618px; display: inline-block">
@foreach ($licenses as $lic)
@php
	$Offs = $lic['offs']; $status = $lic['status'];
	if ($isManager) {
		$msgS = '<font color="green">'.$c('company_lic_stat_active').'</font>';
		if ($status == 1) $msgS = '<font color="red">'.$c('company_lic_stat_embargo').'</font>';
		elseif ($status == 2) $msgS = '<font color="red">'.$c('company_lic_stat_war').'</font>';
		elseif ($lic['noOffer']) $msgS = '<font color="green">'.$c('company_lic_stat_no').'</font>';
	} else {
		$msgS = '<font color="green">Active</font>';
		if ($status == 1) $msgS = '<font color="red">Trading embargo</font>';
		elseif ($status == 2) $msgS = '<font color="red">In war</font>';
		elseif ($lic['noOffer']) $msgS = '<font color="green">No offer</font>';
	}
@endphp
@if ($isManager)
							<div class="license-div">
								<form action="" method="post">
									@csrf
									<input type="hidden" name="actOffer" value="{{ $Offs['OfferID'] }}">
									<input type="hidden" name="actCountry" value="{{ $lic['CountryID'] }}">
									<input type="hidden" name="token" value="{{ md5($Offs['OfferID'] . $row['CompanyID'] . $lic['CountryID'] . 'key4 offer making') }}">
									<center><img src="/images/flags/l/{{ $lic['Flag'] }}.gif" class="Flags" title="{{ $lic['cName'] }}" align="absmiddle"></center>
									<br>
									<div class="lic-title">Status:</div>
									<b>{!! $msgS !!}</b>
									<div style="clear: both"></div>
@if (!$status)
											<div class="lic-title">Amount:</div>
											<input type="text" name="oAmount" class="inputs" size="3" value="{{ $Offs['Stock'] }}">
											<div style="clear: both"></div>
											<div class="lic-title">Price:</div>
											<input type="text" name="oPrice" class="inputs" size="3" value="{{ $Offs['Price'] }}"
													onkeyup="javascript:document.getElementById('pHolder-{{ $lic['ID'] }}').innerHTML = Math.round(oPrice.value * (1 + {{ $lic['tax'] }}) * 100) / 100">
											{{ $lic['curName'] }}
											<div style="clear: both"></div>
											<div class="lic-title">+ Tax:</div>
											<label id="pHolder-{{ $lic['ID'] }}" class="licOPrice" style="padding-bottom: 10px; padding-top: 1px; padding-left: 15px;">{{ $Offs['tPrice'] ?? '0' }}</label>
											<div style="clear: both"></div>
@endif
									<div class="licSubmit" style="text-align: center">
										<a class="button-blue-0" href="{{ $vars->getURL('market', $row['IndustryID'], '', $lic['CountryID']) }}" target="_blank">{!! $lang->getstr('market') !!}</a>
@if (!$status)
												<input type="submit" name="oSubmit" class="submit-blue-0" value="{{ $lang->getstr('submit') }}">
@endif
									</div>
								</form>
							</div>
@else
							<div class="license-div">
								<center><img src="/images/flags/l/{{ $lic['Flag'] }}.gif" class="Flags" title="{{ $lic['cName'] }}" align="absmiddle"></center>
								<div class="details">
									<label class="lic-status">{!! $msgS !!}</label>
									<br>
@if (!$status)
												<input type="text" name="oAmount" disabled class="inputs" size="3" value="{{ $Offs['Stock'] }}">
												<br>
												<input type="text" name="oPrice" disabled class="inputs" size="3" value="{{ $Offs['Price'] }}">
												{{ $lic['curName'] }}
												<br>
												<div id="pHolder-{{ $lic['ID'] }}" class="licOPrice" style="padding-bottom: 10px; padding-top: 1px; padding-left: 15px;">{{ $Offs['tPrice'] ?? '0' }}</div>
											</div>
											<div class="licSubmit" style="text-align: center">
												<a class="button-blue-1" href="{{ $vars->getURL('market', $row['IndustryID'], '', $lic['CountryID']) }}" target="_blank">{!! $c('goto_market') !!}</a>
											</div>
@else
											</div>
@endif
								</div>
@endif
@endforeach
			</div></div></center>
@endif
		<br><br>
		<div class="page-box">
			<div class="head"><div class="holder-title-1">{!! $c('company_job_offers') !!}</div></div>
			<b>{!! $c('company_job_offers_title') !!}</b>
			<hr>
@if (count($actJobs) == 0)
					<h3 class="errHandle">{!! $lang->getstr('error_no_active_job', 'msgs') !!}</h3>
@else
					<table class="style">
						<tr>
							<th class="style" style="width: 10%">{!! $c('company_job_offer_amount') !!}</th>
							<th class="style" style="width: 20%">{!! $c('company_job_offer_salary') !!}</th>
							<th class="style" style="width: 10%"></th>
						</tr>
@foreach ($actJobs as $aJob)
							<tr>
								<th class="style" style="width: 10%">{{ $aJob['Amount'] }}</th>
								<th class="style" style="width: 20%">{{ $aJob['Salary'] }} {{ $database->getCurrency($aJob['CountryID']) }}</th>
								<th class="style" style="width: 10%">
@if ($isManager)
										<form action="" method="post" name="offer{{ $aJob['joID'] }}">
											@csrf
											<input type="hidden" name="actJoID" value="{{ $aJob['joID'] }}">
											<a href="javascript:void(0)" class="cmdRemove" onclick="if (confirm('Are you sure you want to remove this job offer?')) document.offer{{ $aJob['joID'] }}.submit()">Remove</a>
										</form>
@endif
								</th>
							</tr>
@endforeach
@if ($acc['can_view_private_data'] && $isManager)
								<form action="" method="post" name="offerAll">
									@csrf
									<input type="hidden" name="delJoID" value="1">
									<a href="javascript:void(0)" class="cmdRemove" onclick="if (confirm('Are you sure you want to remove all job offers?')) document.offerAll.submit()">Remove all</a>
								</form>
@endif
					</table>
@endif
@if ($isManager)
					<center>
						<a href="#" class="button-blue-1" onclick="document.getElementById('addJob').style.display='block';return false">{!! $c('company_job_offer_add') !!}</a>
						<a href="{{ $vars->getURL('jobs') }}" class="button-blue-1">{!! $c('company_job_offer_view') !!}</a>
					</center>
					<div id="addJob" style="display: none">
						<form action="" method="post">
							@csrf
							<table class="style">
								<tr>
									<th class="style">{!! $c('company_job_offer_amount') !!}</th>
									<th class="style">{!! $c('company_job_offer_salary') !!}</th>
									<th class="style"></th>
								</tr>
								<tr>
									<th class="style"><input type="text" class="txtAmount-4" size="2" maxlength="2" name="wAmount"></th>
									<th class="style"><input type="text" class="txtAmount-4" size="4" maxlength="4" name="wSalary" align="absmiddle"> {{ $localCur }}</th>
									<th class="style">
										<input type="hidden" name="SaveOffer" value="1">
										<input type="submit" class="submit-blue-0" value="{{ $lang->getstr('submit') }}">
									</th>
								</tr>
								<tr><td colspan="3">{!! $c('company_job_offer_note') !!}</td></tr>
							</table>
						</form>
					</div>
@endif
		</div>
