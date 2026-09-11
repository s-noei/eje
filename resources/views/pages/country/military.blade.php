			<b>Military</b>
			<hr width=90%>
			<blockquote>
				<b>Active wars</b><hr width=90%>
@foreach ($wars as $aWar)
		<div id="warstable-s">
@if ($aWar['Type'] != 'revolt')
			<div class="flag-att"><img src="{{ $vars->getImgLoc('CountryFlag') . $aWar['attFlag'] }}.gif" width="50px" alt="Attacker" class="inlineIMGs" align="absmiddle"></div>
			<div class="name-att"><a href="{{ $vars->getURL('country', $aWar['Attacker']) }}">{{ $aWar['attName'] }}</a></div>
@else
			<div class="flag-att"><img src="/images/flags/revolt.gif" alt="Revolt" title="Revolt" style="width: 50px; height: 40px"></div>
			<div class="name-att"><b><a href="{{ $vars->getURL('region', $aWar['revoltReg']['RegionID'] ?? 0) }}">{{ $aWar['revoltReg']['rName'] ?? '' }}</a></b></div>
@endif
			<div class="versus"><a href="{{ $vars->getURL('war', $aWar['warID']) }}"><img src="/images/game/war/versus.png" style="border: 0"></a></div>
			<div class="name-def"><a href="{{ $vars->getURL('country', $aWar['Defender']) }}">{{ $aWar['defName'] }}</a></div>
			<div class="flag-def"><img src="{{ $vars->getImgLoc('CountryFlag') . $aWar['defFlag'] }}.gif" width="50px" alt="Defender" class="inlineIMGs" align="absmiddle"></div>
</div>
<div style="clear:both"></div>
@endforeach
				<a href="{{ $vars->getURL('wars') }}" id="buttons">Show all wars</a>
				<br>
				<b>Allies</b><hr width=90%>
				<blockquote>
@forelse ($allies as $ally)
				<img src="{{ $vars->getImgLoc('CountryFlag') . $ally['Flag'] }}.gif" class="Flag-s" align="absmiddle">&nbsp;
				<a href="{{ $vars->getURL('country', $ally['Country2']) }}">{{ $ally['cName'] }}</a>
				 (Expires on day {{ $ally['Expire'] }})<br>
@empty
						This country doesn't have any allies.
@endforelse
				</blockquote>
			</blockquote>
