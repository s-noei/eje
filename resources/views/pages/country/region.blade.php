<style>
	#travel-menu { border: 1px solid; padding: 3px; border-radius: 8px 4px; }
</style>
@php $rowR = $reg; $canTravel = $logged && $rowR['RegionID'] != $citInfo['regionID']; @endphp
@if ($canTravel)
			<script>
				var showed = 0;
				$(document).ready(function(){
						$("#travel").click(function(){
								if (showed == 0) $("#travel-menu").fadeIn(); else $("#travel-menu").fadeOut();
								showed = 1 - showed;
							});
					});
			</script>
			<div id="travel-menu" style="display: none">
				<b>Travel here</b>
				<blockquote>
@if ($travel['fix'])
					<a href="{{ $travel['fix'] }}" class="button-blue-1">Fix it!</a>
@endif
@if ($citInfo['puberty'] < 1 && !$isCA)
					<h3 class="errHandle">You must pass Social Puberty level 1 (15 EP) to be able to travel.</h3>
@elseif ($citInfo['travel_due'] >= $travel['time'])
                    <h3 class="errHandle">You have just travelled a few time ago and you need to rest {!! $session->getDiffF($citInfo['travel_due'], $travel['time'], 0) !!} to be able to travel again.</h3>
@else
							Your current location:
							<a href="{{ $vars->getURL('region', $citInfo['RegionID']) }}">{{ $citInfo['RegionName'] }}</a>,
							<a href="{{ $vars->getURL('country', $citInfo['CountryID']) }}">{{ $citInfo['cName'] }}</a>
							<br>
							<center>Travel with tickets<hr size="1" color="black"></center>
							<form action="" method="post" name="travel">
								@csrf
@if (count($travel['tickets']) < 1)
										<h3 class="errHandle">You have no tickets in your inventory.<br>You can buy tickets from <a href="{{ $vars->getURL('market', '2', '0') }}">market</a>.</h3>
@else
										Ticket type:
										<select name="ticket" class="style">
@foreach ($travel['tickets'] as $ticket)
											<option value="{{ $ticket['Stars'] }}" @if ($travel['actTicket'] == $ticket['Stars']) selected="selected" @endif>{{ $ticket['Stars'] }}-star ticket</option>
@endforeach
										</select>
										<input type="hidden" name="subtravel" value="1">
										<input type="submit" class="submit-blue-0" value="Move">
										<br>
										<b>Ticket effects on your wellness:</b>
										<blockquote>
											<b>1-star:</b> Inside (<span style="color: red; font-weight: bold">-2</span>) - Outside (<span style="color: red; font-weight: bold">Not availabe</span>) - Time to rest: 5 hours<br>
											<b>2-star:</b> Inside (<span style="color: green; font-weight: bold">+2</span>) - Outside (<span style="color: red; font-weight: bold">-5</span>) - Time to rest: 4 hours<br>
											<b>3-star:</b> Inside (<span style="color: red; font-weight: bold">-2</span>) - Outside (<span style="color: red; font-weight: bold">-3</span>) - Time to rest: 3 hours<br>
											<b>4-star:</b> Inside (<span style="color: green; font-weight: bold">+2</span>) - Outside (<span style="color: green; font-weight: bold">+1</span>) - Time to rest: 2 hours<br>
											<b>5-star:</b> Inside (<span style="color: green; font-weight: bold">+5</span>) - Outside (<span style="color: green; font-weight: bold">+5</span>) + <span style="color: green; font-weight: bold">pass away travel embargo</span> - Time to rest: 1 hour<br>
										</blockquote>
@endif
							</form>
							<center>Travel without tickets<hr size="1" color="black"></center>
							<form action="" method="post" name="travel2">
								@csrf
										Travelling without tickets is an easy and fast way to travel between regions.
										You will pay an amount of your current country's local currency and you don't need any tickets.
										By the way, your wellness will not changed during travel and you don't have rest time with this method!
										<br>
										Travel price: {{ $travel['price'] }}
										<img src="/images/flags/s/{{ $citInfo['Flag'] }}.gif" class="flag-s" align="absmiddle">
										<input type="hidden" name="subtravel2" value="1">
										<input type="submit" class="submit-blue-0" value="Move">
										<br>
										<b>Travel fee:</b>
										<blockquote>
											<b>Inside country:</b> 25 local money<br>
											<b>Outside country:</b> 50 local money<br>
											<b>Pass away travel embargo:</b> 75 local money
										</blockquote>
							</form>
@endif
				</blockquote>
			</div>
@endif
			<div class="info-holder" style="margin-top: 0">
				<div class="holder-title">
					<b style="font-size: 13pt">
						{{ $lang->getstr("region_{$rowR['RegionID']}", 'regions') }}
						{!! $vars->getWikiLink('Region', $rowR['rName']) !!}
					</b>
@if ($canTravel)
						<a href="javascript:void(0)" id="travel" title="Travel here"><img src="/images/profile/travel.png" border="0" align="absmiddle"></a>
@endif
				</div>
				<div class="box-info"><div class="title">{{ $lang->getstr('region_population', 'countryinfo') }}</div><div class="content">{{ $vars->formatnumbers($rowR['stat_pop']) }}</div></div>
				<div class="box-info"><div class="title">{{ $lang->getstr('region_companies', 'countryinfo') }}</div><div class="content">{{ $vars->formatnumbers($rowR['stat_comps']) }}</div></div>
				<div class="box-info"><div class="title">{{ $lang->getstr('region_value', 'countryinfo') }}</div><div class="content">{{ $vars->formatnumbers($rowR['stat_value']) }}<br><img src="/images/flags/s/eJahan.gif" align="absmiddle" width="18px"></div></div>
				<div class="box-info"><div class="title">{{ $lang->getstr('region_cfactor', 'countryinfo') }}</div><div class="content">{{ $vars->formatnumbers($rowR['stat_cfactor']) }}</div></div>
			</div>

			<a name="constructions"></a>
			<div class="info-holder">
				<div class="holder-title">{{ $lang->getstr('region_constructions', 'countryinfo') }}</div>
				<div class="box-info"><div class="title"><img src="/images/icons/clinic-s.png" align="absmiddle"></div><div class="content">{{ $rowR['Clinic'] }}</div></div>
				<div class="box-info"><div class="title"><img src="/images/icons/municipality-s.png" align="absmiddle"></div><div class="content"><img src="/images/game/{{ $rowR['Munic'] }}_star.gif" height="10px" align="absmiddle"></div></div>
			</div>
			<div class="info-holder">
				<div class="holder-title">{{ $lang->getstr('region_goddesses', 'countryinfo') }}</div>
				<div class="holder-body">
@if ($gdtype = $rowR['goddessType'])
							<img src="/images/game/gods/god-{{ $gdtype }}-s.png" alt="{{ $gods[$gdtype] }}" title="{{ $gods[$gdtype] }}" align="absmiddle">
							<b>{{ $gods[$gdtype] }}</b>
@else
							{{ $lang->getstr('region_no_goddess', 'countryinfo') }}
@endif
				</div>
			</div>
			<div class="info-holder">
				<div class="holder-title">{{ $lang->getstr('region_revolt', 'countryinfo') }}</div>
				<div class="holder-body">
@php $bel = '<img src="' . $vars->getImgLoc('CountryFlag') . ($origCountry['Flag'] ?? '') . '.gif" class="Flag-xs" align="absmiddle"> <a href="' . $vars->getURL('country', $rowR['oCountryID']) . '">' . e($lang->getstr($origCountry['shortName'] ?? '', 'country')) . '</a>'; @endphp
					{!! sprintf($lang->getstr('region_belong', 'countryinfo'), $bel) !!}
					<br>
@if ($rowR['CountryID'] != $rowR['oCountryID'])
							<form name="revolt" action="" method="post">
								@csrf
								{{ $lang->getstr('region_revolt_cost', 'countryinfo') }}: {{ $RWPrice }}
								<img src="/images/flags/s/eJahan.gif" align="absmiddle">
@if ($logged && $rowR['RegionID'] == $citInfo['regionID'] && $citInfo['accType'] == 'citizen')
										<input type="hidden" name="subRevolt" value="1">
										<input type="hidden" name="token" value="{{ md5($citInfo['CitizenID'] . 'l w@nnA $tar+ @ rev0lt' . $rowR['stat_pop'] . $rowR['RegionID']) }}">
										<br>
										<a href="javascript:void(0)" onclick="document.revolt.submit()" class="button-blue-1">{{ $lang->getstr('region_revolt_start', 'countryinfo') }}</a>
@endif
							</form>
@endif
				</div>
			</div>
			<div class="info-holder">
				<div class="holder-title">{{ $lang->getstr('region_neighbors', 'countryinfo') }}</div>
@foreach ($neighbors as $rec)
				<div class="box-info">
					<div class="title">
						<a href="{{ $vars->getURL('country', $rec['CountryID']) }}">
							<img class="Flag-xs" align="absmiddle" src="/images/flags/l/{{ $rec['Flag'] }}.gif" title="{{ $lang->getstr($rec['shortName'], 'country') }}">
						</a>
					</div>
					<div class="content" style="font-size: 9pt">
						<a href="{{ $vars->getURL('region', $rec['Region2']) }}">{{ $lang->getstr("region_{$rec['RegionID']}", 'regions') }}</a>
					</div>
				</div>
@endforeach
			</div>
