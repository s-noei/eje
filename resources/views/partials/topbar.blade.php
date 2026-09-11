@if (!$logged)
		<div class="login">
			<form action="/loginprocess.html" method="POST">
				@csrf
				<input type="text" name="user" maxlength="30">
				<input type="password" name="pass" maxlength="30">
				<input type="checkbox" name="remember">
				<font size="2">Remember me</font>
				<input type="hidden" name="sublogin" value="1">
				<input type="hidden" name="redto" value="{{ request()->getRequestUri() }}">
				<input type="submit" value="Login" class="submit-blue-0">
				<a href="/forgotpass.html" class="button-blue-1">Forgot password?</a>
			</form>
		</div>
@else
@php
	$cID = $citInfo['CountryID'] ?? 0;
	$wel = $citInfo['wellness'] ?? 0;
	$canjuice = ($wel < 100 && ($citInfo['LastJuiceWellness'] ?? 0) < 200);
@endphp
		<div class="citInfo">
			<div class="allround" id="avatar">
				<a href="{{ $vars->getURL('profile', $citInfo['CitizenID']) }}">
					<img src="/uploads/avatars/citizen/{{ $citInfo['Avatar'] }}">
				</a>
			</div>
			<div class="allround" id="name-ep">
				<div class="noround" id="name">
					<a href="{{ $vars->getURL('profile', $citInfo['CitizenID']) }}">
						{{ $citInfo['name'] }}
					</a>
				</div>
				<div class="noround" id="ep">
@if (!$isCA)
					<div style="display: inline-block; padding: 0 2px; font-size: 8pt; border: 2px solid rgb(0, 100, 0); background: rgb(0, 100, 0); border-radius: 5px;">
						{{ ($citInfo['puberty'] ?? 0) + 1 }}
					</div>
@endif
					{!! $isNCA ? 'National CA' : ($isCA ? 'Co-Account' : $vars->viewPub($citInfo, 1)) !!}
					<a href="{{ $vars->getURL('country', $cID) }}">
						<img src="/images/flags/s/{{ $citInfo['cName'] ?? '' }}.gif" height="18px" align="absmiddle">
					</a>
				</div>
			</div>
@if (!$isCA)
			<div class="allround" id="wellness-holder">
				<div class="noround" id="wellness-ind">
					<div id="wellness-hummy">
						<img src="/images/welind-{{ ($citInfo['female'] ?? 0) ? 'f' : 'm' }}.png" height="50px">
					</div>
					<div id="wellness-bar">
						<div id="wellness-view" style="width: {{ $wel }}%">&nbsp;</div>
						<div id="wellness-cap">{{ $wel }}</div>
					</div>
				</div>
				<div class="noround" id="wellness-imp">
					<div id="wellness-but">
						<script>
							OwnCit = {{ $citInfo['CitizenID'] }};
							OwnToken = '{{ md5($citInfo['CitizenID'] . $citInfo['CitizenID'] . config('ejahan.salts.juice')) }}';
							CanDrJuice = {{ $canjuice ? 1 : 0 }};
							juiceLink = '{{ $vars->getURL('market', 3, 0, $cID) }}';
						</script>
@if ($canjuice)
	@if (!empty($citInfo['inventory'][3]['Amount']))
								<a href="javascript:void(0)" id="drjuice">
									<img id="juiceD" src="/images/theme/juice-drink.png" border="0" align="absmiddle" />
								</a>
	@else
								<a href="{{ $vars->getURL('market', 3, 0, $cID) }}" id="drjuice">
									<img id="juiceD" src="/images/theme/juice-buy.png" border="0" align="absmiddle" />
								</a>
	@endif
							<div id="juiceTog" style="display: none; position: absolute; width: 200px; background: wheat; color: black; z-index: 999000; padding: 5px; border: 1px solid gold; border-radius: 3px">
								{!! $lang->getstr('juice_info_1') !!}
								{!! sprintf($lang->getstr('juice_info_2'), '<span id="arem" style="font-weight: bold;">N/A</span>', '<span id="wrem" style="font-weight: bold;">'.(200 - ($citInfo['LastJuiceWellness'] ?? 0)).'</span>') !!}
							</div>
@else
							<a href="javascript:void(0)" id="drjuice">
								<img id="juiceD" src="/images/theme/juice-end.png" border="0" />
							</a>
							<div id="juiceTog" style="display: none; position: absolute; width: 200px; background: wheat; color: black; padding: 5px; border: 1px solid gold; border-radius: 3px">
								{!! $lang->getstr('juice_info_1') !!}
								{!! sprintf($lang->getstr('juice_info_2'), '<span id="arem" style="font-weight: bold;">N/A</span>', '<span id="wrem" style="font-weight: bold;">'.(50 - ($citInfo['LastJuiceWellness'] ?? 0)).'</span>') !!}
							</div>
@endif
					</div>
				</div>
			</div>
@endif
			<div class="allround" id="money-holder">
				<div class="noround" id="money-tala">
					<img class="curIco" align="absmiddle" src="/images/tala.gif">
					<span id="holder-tala">{!! $vars->formatnumbers($session->getRound($citMoney[1] ?? 0)) !!}</span>
				</div>
				<div class="noround" id="money-local">
					<img class="curIco" align="absmiddle" src="{{ $database->getCurrencyIco($cID, $vars->getImgLoc('CurrencyIcon')) }}">
					<span id="holder-local">{!! $vars->formatnumbers($session->getRound($citMoney[$cID] ?? 0)) !!}</span>
				</div>
@php $co = 0; @endphp
@foreach ($citMoney as $cur => $amount)
	@if ($cur != 1 && $cur != $cID && $amount >= 1)
						<div class="noround money-additional">
							<img class="curIco" align="absmiddle" src="{{ $database->getCurrencyIco($cur, $vars->getImgLoc('CurrencyIcon')) }}">
							<span id="holder-additional">{!! $vars->formatnumbers($session->getRound($amount)) !!}</span>
						</div>
		@php $co++; @endphp
	@endif
@endforeach
				<style>
					div#money-holder:hover { height: {{ 45 + ($co * 21) }}px }
				</style>
			</div>
			<div class="allround" id="notes-holder">
				<script>
					var showedB = 0;
					$(document).ready(function(){
						$("#reqsFR").click(function(){
							if (showedB == 1) $("#freqs").slideUp(250); else $("#freqs").slideDown(250);
							showedB = 1 - showedB;
						});
					});
				</script>
				<div class="verbox">
					<a href="{{ $vars->getURL('mail') }}" title="You have {{ $newPM }} new messages.">
						<img id="holder-img-pm" class="msgIco" align="absmiddle" src="/images/theme/{{ $newPM > 0 ? 'new_pm' : 'no_new_pm' }}.png">
					</a>
					<br>
					<a href="{{ $vars->getURL('mail') }}" id="msg-lnk-pm" title="You have {{ $newPM }} new messages.">
						<label id="holder-pm">{!! $vars->formatnumbers($newPM) !!}</label>
					</a>
					<div class="msg-in-box" id="pm">
						<div class="in-box-pm">
						</div>
					</div>
				</div>
				<div class="verbox">
					<a href="{{ $vars->getURL('mail', 'notes') }}" title="You have {{ $newNote }} new notes.">
						<img id="holder-img-note" class="msgIco" align="absmiddle" src="/images/theme/{{ $newNote > 0 ? 'new_note' : 'no_new_note' }}.png">
					</a>
					<br>
					<a href="{{ $vars->getURL('mail', 'notes') }}" title="You have {{ $newNote }} new notes.">
						<label id="holder-note">{!! $vars->formatnumbers($newNote) !!}</label>
					</a>
					<div class="msg-in-box" id="note">
						&nbsp;
					</div>
				</div>
@if (!$isCA && !empty($citInfo['active']))
@php $newReq = count($friendRequests); @endphp
				<div class="verbox" id="reqs">
					<a href="javascript:void(0)" id="reqsFR" title="You have {{ $newReq }} unapproved requests.">
						<img class="msgIco" align="absmiddle" src="/images/theme/{{ $newReq > 0 ? 'new_req' : 'no_new_req' }}.png">
						<br>
						<label id="holder-req">{!! $vars->formatnumbers($newReq) !!}</label>
					</a>
					<div class="msg-in-box" id="freqs" style="color: black">
						<center>Pending friendship requests</center>
@if ($newReq < 1)
							<div style="clear: both; padding-top: 2px"><hr size="1"></div>
							No new requests.
@endif
@foreach (array_slice($friendRequests, 0, 5) as $fri)
@php $token = md5($fri['CitizenID'] . config('ejahan.salts.friend')); @endphp
							<div style="clear: both; padding-top: 2px"><hr size="1"></div>
							<div class="fri-holder" id="fri_{{ $fri['CitizenID'] }}">
                                <div class="fri-wait">
                                    <img src="/images/loading.gif" width="180px" />
                                </div>
								<div class="fri-avatar">
									<img src="{{ $vars->getImgLoc('CitizenAvatar') . $fri['avatar'] }}">
								</div>
								<div class="fri-name">
									<a href="{{ $vars->getURL('profile', $fri['CitizenID']) }}" style="color: #000000">
										{{ $fri['name'] }}
									</a>
								</div>
								<div class="fri-opers">
									<a href="javascript:void(0)" onclick="javascript:FRAct({{ $fri['CitizenID'] }}, 'accept', '{{ $token }}')" class="button-blue-0">
										{!! $lang->getstr('fri_accept') !!}
									</a>
									<a href="javascript:void(0)" onclick="javascript:FRAct({{ $fri['CitizenID'] }}, 'reject', '{{ $token }}')" class="button-red-0">
										{!! $lang->getstr('fri_reject') !!}
									</a>
								</div>
							</div>
@endforeach
					</div>
				</div>
@endif
</div>
@if (($layout['actiontype'] ?? '') === 'home')
			<div class="allround" id="languages">
@foreach ([['en','UK','English'],['fa','Iran','Farsi'],['hu','Hungary','Magyar'],['ro','Romania','Română'],['pl','Poland','Polski']] as $l)
				<a href="/index-{{ $l[0] }}.html" class="langbut" id="{{ $l[0] }}" name="{{ $l[2] }}" title="{{ $l[2] }}">
					<img src="/images/flags/s/{{ $l[1] }}.gif" align="absmiddle">
				</a>
@endforeach
				<br>
@foreach ([['rs','Serbia','Srpski'],['hr','Croatia','Hrvatski'],['si','Slovenia','Slovenščina'],['es','Spain','Español'],['fr','France','Français']] as $l)
				<a href="/index-{{ $l[0] }}.html" class="langbut" id="{{ $l[0] }}" name="{{ $l[2] }}" title="{{ $l[2] }}">
					<img src="/images/flags/s/{{ $l[1] }}.gif" align="absmiddle">
				</a>
@endforeach
				<br>
@foreach ([['it','Italy','Italiano'],['tr','Turkey','Türkçe'],['pt','Portugal','Português'],['ru','Russia','Русский'],['me','Macedonia','Македонски']] as $l)
				<a href="/index-{{ $l[0] }}.html" class="langbut" id="{{ $l[0] }}" name="{{ $l[2] }}" title="{{ $l[2] }}">
					<img src="/images/flags/s/{{ $l[1] }}.gif" align="absmiddle">
				</a>
@endforeach
			</div>
@endif
			<div class="noround" id="logout">
				<center>
					<a href="/logout.html" style="color: white">
						<img src="/images/theme/logout.png" align="absmiddle" style="border: 0">
						<br>
						Logout
					</a>
				</center>
			</div>
		</div>
@endif
