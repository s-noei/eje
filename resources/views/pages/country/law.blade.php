@if ($id === 'new')
			<h2>Propose a new law</h2>
@if ($lawForm)
@include('pages.country.law.' . $lawForm)
@endif
@else
@php $lawVotes = $lawVotes; $d = $detail; $p = $d['p']; @endphp
			<h3>Law approval
			<div style="float: right; padding-left: 10px; text-align: center">
				<div class="law-timer">
@if (!$law['Status'])
							Time until voting:<br>
							<div id="showTime" style="font-weight: bold; font-size: 14pt"><b>{{ $law['dTime'] }}</b></div>
@else
							{!! $stats[$law['Status']] !!}
@endif
				</div>
				<div class="clear">&nbsp;</div>
				<div class="law-voting">
@if ($law['Status'])
							<b>{!! $stats[$law['Status']] !!}</b>
@elseif ($haveVoteRights)
									Do you agree?<br>
									<div style="display: inline-block; width: 35px; text-align: center">
										<form action="{{ $vars->getURL('law', $law['lawID'], 'yes') }}" name="lawYes" method="post">
											@csrf
											<input type="hidden" name="lawID" value="{{ $law['lawID'] }}">
											<input type="hidden" name="sublaw" value="1">
											<input type="hidden" name="token" value="{{ $voteToken }}">
											<a href="javascript:void(0)" onclick="document.lawYes.submit()">YES</a>
											{{ $lawVotes['YES'] }}
										</form>
									</div>
									<div style="display: inline-block; width: 35px; text-align: center">
										<form action="{{ $vars->getURL('law', $law['lawID'], 'no') }}" name="lawNo" method="post">
											@csrf
											<input type="hidden" name="lawID" value="{{ $law['lawID'] }}">
											<input type="hidden" name="sublaw" value="1">
											<input type="hidden" name="token" value="{{ $voteToken }}">
											<a href="javascript:void(0)" onclick="document.lawNo.submit()">NO</a>
											{{ $lawVotes['NO'] }}
										</form>
									</div>
@else
									<div style="display: inline-block; width: 35px; text-align: center">YES<br>{{ $lawVotes['YES'] }}</div>
									<div style="display: inline-block; width: 35px; text-align: center">NO<br>{{ $lawVotes['NO'] }}</div>
@endif
				</div>
				<div class="newline">&nbsp;</div>
			</div>
			<script language="javascript">
				var dTime = {{ $law['dTime'] - time() }};
				function showTime(sTime)
				{
					if (sTime > '0') {
							var lTime = sTime; var dSec = lTime % 60; if (dSec < '10') dSec = '0' + dSec;
							lTime -= dSec; lTime /= 60; var dMin = lTime % 60; if (dMin < '10') dMin = '0' + dMin;
							lTime -= dMin; lTime /= 60; var dHour = lTime; if (dHour < '10') dHour = '0' + dHour;
							dStart = ''; dEnd = '';
							if (dHour == '00' && dMin < '10') { dStart = '<font color="red">'; dEnd = '</font>'; }
							document.getElementById('showTime').innerHTML = dStart + dHour + ":" + dMin + ":" + dSec + dEnd;
							if (dTime == '0') document.getElementById('showTime').innerHTML = dStart + 'CLOSED' + dEnd;
							dTime -= 1;
							if (dTime >= '0') setTimeout("showTime(dTime)", 1000);
						}else{
							var el = document.getElementById('showTime');
							if (el) el.innerHTML = '<font color="red">CLOSED</font>';
						}
				}
				showTime(dTime);
			</script>

			-> {{ $names[$law['Type']] ?? $law['Type'] }}</h3>
			Proposed by <a href="{{ $vars->getURL('profile', $law['byID']) }}">{{ $law['byName'] }}</a>
			@if (strlen($law['Debate']) > 8)<a href="{{ $law['Debate'] }}" target="_blank" id="buttons">Debate location</a>@endif
			<blockquote>
@switch($law['Type'])
@case('Tax')
					<font size="4">Industry: {{ $d['industry'] }}</font>
					<div id="law">
						<div class="law">Taxes</div><div class="law">New</div><div class="law">Old</div>
						<div style="clear: left"></div><hr>
						<div class="law">Income Tax</div><div class="law">{{ $p[1] ?? 0 ?: '0' }}%</div><div class="law">{{ $p[4] ?? 0 ?: '0' }}%</div>
						<div style="clear: left"></div><hr>
						<div class="law">Import Tax</div><div class="law">{{ $p[2] ?? 0 ?: '0' }}%</div><div class="law">{{ $p[5] ?? 0 ?: '0' }}%</div>
						<div style="clear: left"></div><hr>
						<div class="law">VAT Tax</div><div class="law">{{ $p[3] ?? 0 ?: '0' }}%</div><div class="law">{{ $p[6] ?? 0 ?: '0' }}%</div>
						<div style="clear: left"></div><hr>
					</div>
@break
@case('Fee')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to change citizen fee from {{ ($p[0] ?? '') . ' ' . $d['cur'] }} to {{ ($p[1] ?? '') . ' ' . $d['cur'] }} ?</font>
@break
@case('Issue')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to issue {{ $d['raw'] . ' ' . $d['cur'] }} for {{ $d['raw'] * 0.002 + 15 }} TALA from treasury ?</font>
@break
@case('Donate')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to donate {{ ($p[0] ?? '') . ' ' . $d['cur'] }} from treasury to <a href="{{ $vars->getURL('profile', $p[2] ?? 0) }}">{{ $p[3] ?? '' }}</a> ?</font>
@break
@case('Impeach')
					<font style="font-size: 11pt; font-family: Arial">Do you think that current president of this country should end his/her office?<br><br><i><b>Note:</b> This law needs %66 approval to be accepted.</i></font>
@break
@case('Inds')
					<font style="font-size: 11pt; font-family: Arial">Do you accept industries below as important industries for your country?
						<blockquote>@foreach ($d['inds'] as $ind){{ $ind['iName'] }}<br>@endforeach</blockquote>
					</font>
@break
@case('Ministry')
@php $mposts = ['War' => 'war', 'FA' => 'foreign affairs', 'E' => 'economy']; @endphp
					<font style="font-size: 11pt; font-family: Arial">Do you accept <a href="{{ $vars->getURL('profile', $p[0] ?? 0) }}">{{ $p[1] ?? '' }}</a> as the ministry of {{ $mposts[$p[2] ?? ''] ?? '' }} for your country?</font>
@break
@case('BuyClinic')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to buy a clinic having {{ ($p[0] ?? 0) * 50000 }} wellness packs
						from <a href="{{ $vars->getURL('company', $p[2] ?? 0) }}">{{ $p[3] ?? '' }}</a>
						for <a href="{{ $vars->getURL('region', $p[4] ?? 0) }}">{{ $p[5] ?? '' }}</a>
						with a price of {{ ($p[1] ?? '') . ' ' . $d['cur'] }}?</font>
@break
@case('BuyMunic')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to buy a {{ $p[0] ?? '' }}-star municipality
						from <a href="{{ $vars->getURL('company', $p[2] ?? 0) }}">{{ $p[3] ?? '' }}</a>
						for <a href="{{ $vars->getURL('region', $p[4] ?? 0) }}">{{ $p[5] ?? '' }}</a>
						with a price of {{ ($p[1] ?? '') . ' ' . $d['cur'] }}?</font>
@break
@case('Alliance')
					<font style="font-size: 11pt; font-family: Arial">The president of <a href="{{ $vars->getURL('country', $d['counID']) }}">{{ $d['coun'] }}</a> wants to sign a {{ $d['dur'] }}-day
						alliance with <a href="{{ $vars->getURL('country', $d['tarID']) }}">{{ $d['tarName'] }}</a>. Do you agree?</font>
@if ($d['supLaw'])
							<a href="{{ $vars->getURL('law', $d['supLaw']) }}" id="buttons">The law in {{ $d['coun'] }}</a>
@endif
@break
@case('DeclareWar')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to declare war to <a href="{{ $vars->getURL('country', $p[1] ?? 0) }}">{{ $p[0] ?? '' }}</a> for {{ $p[2] ?? '' }} Tala?</font>
@break
@case('ProposePeace')
					<font style="font-size: 11pt; font-family: Arial">The president of <a href="{{ $vars->getURL('country', $d['counID']) }}">{{ $d['coun'] }}</a> has offered {{ $d['price'] }} Tala to return
						into peace with <a href="{{ $vars->getURL('country', $d['tarID']) }}">{{ $d['tarName'] }}</a>. Do you agree?</font>
@if ($d['supLaw'])
							<a href="{{ $vars->getURL('law', $d['supLaw']) }}" id="buttons">The law in {{ $d['coun'] }}</a>
@endif
@break
@case('Notrade')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to stop trading with <a href="{{ $vars->getURL('country', $p[1] ?? 0) }}">{{ $p[0] ?? '' }}</a>?</font>
@break
@case('Notravel')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to stop travel with <a href="{{ $vars->getURL('country', $p[1] ?? 0) }}">{{ $p[0] ?? '' }}</a>?</font>
@break
@case('nFee')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to change the nationality fee from {{ $p[0] ?? '' }} TALA to {{ $p[1] ?? '' }} TALA ?</font>
@break
@case('Prefix')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to change the name of country from <strong>{{ ($p[0] ?? '') ? "{$p[0]} of {$row['cName']}" : $row['cName'] }}</strong>
							to <strong>{{ ($p[1] ?? '') ? "{$p[1]} of {$row['cName']}" : $row['cName'] }}</strong>?<br><br>
						<i><b>Note:</b> This law needs %75 approval to be accepted.</i></font>
@break
@case('WelMsg')
					<font style="font-size: 11pt; font-family: Arial">Do you agree to change the welcome message to this message?
						<blockquote style="font-size: 9pt">{!! $d['raw'] !!}</blockquote></font>
@break
@case('Warca')
					<font style="font-size: 11pt; font-family: Arial">Do you accept <a href="{{ $vars->getURL('profile', $p[0] ?? 0) }}">{{ $p[1] ?? '' }}</a> as your country's war Co-Account?</font>
@break
@endswitch
			</blockquote>
@endif
