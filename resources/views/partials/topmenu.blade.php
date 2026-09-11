@php
	$lang->addPhrases('menubar');
	$m = fn($k) => $lang->getstr($k, 'menubar');
	$li = function($class, $href, $key) use ($m) { return '<li class="'.$class.'"><a href="'.$href.'" title="'.$m($key).'">'.$m($key).'</a></li>'; };
@endphp
<div class="dock" style="overflow-Y: hidden; height: 100px; width: 900px">
	<ul class="nav-shadow" id="Home" style="display: none">
	</ul>
	<ul class="nav-shadow" id="MyPlaces" style="display: none">
@if (!empty($can_access_lens))
		{!! $li('lens', '/lens', 'home_lens') !!}
@endif
		{!! $li('profile', $vars->getURL('profile', $citInfo['CitizenID']), 'myplaces_profile') !!}
		{!! $li('company', $vars->getURL('company'), 'myplaces_company') !!}
@if (!$isCA)
		{!! $li('army', $vars->getURL('army'), 'myplaces_army') !!}
		{!! $li('mines', $vars->getURL('mines'), 'myplaces_explore') !!}
		{!! $li('military-unit', $vars->getURL('military-unit'), 'myplaces_militaryunit') !!}
@endif
		{!! $li('newspaper', $vars->getURL('newspaper'), 'myplaces_newspaper') !!}
@if (!$isCA)
		{!! $li('party', $vars->getURL('party'), 'myplaces_party') !!}
@endif
		{!! $li('advertise', $vars->getURL('ads'), 'myplaces_ads') !!}
	</ul>
	<ul class="nav-shadow" id="Economy" style="display: none">
		{!! $li('eco-market', $vars->getURL('market'), 'economy_market') !!}
		{!! $li('eco-monetary', $vars->getURL('exchange'), 'economy_exchange') !!}
		{!! $li('eco-jobs', $vars->getURL('jobs'), 'economy_jobs') !!}
		{!! $li('eco-imarket', $vars->getURL('imarket'), 'economy_imarket') !!}
		{!! $li('eco-cmarket', $vars->getURL('cmarket'), 'economy_cmarket') !!}
	</ul>
	<ul class="nav-shadow" id="Rankings" style="display: none">
		{!! $li('rank-citizens', $vars->getURL('ranking', 'citizens'), 'ranks_citizens') !!}
		{!! $li('rank-countries', $vars->getURL('ranking', 'countries', 1, 1), 'ranks_countries') !!}
		{!! $li('rank-newspapers', $vars->getURL('ranking', 'newspapers'), 'ranks_newspapers') !!}
		{!! $li('rank-parties', $vars->getURL('ranking', 'parties'), 'ranks_parties') !!}
		{!! $li('rank-battles', $vars->getURL('ranking', 'battles'), 'ranks_battles') !!}
	</ul>
	<ul class="nav-shadow" id="Information" style="display: none">
		{!! $li('info-media', $vars->getURL('media'), 'info_mcenter') !!}
		{!! $li('info-social', $vars->getURL('country', $citInfo['CountryID']), 'info_social') !!}
		{!! $li('info-economy', $vars->getURL('country', $citInfo['CountryID'], 'economy'), 'info_economical') !!}
		{!! $li('info-politics', $vars->getURL('country', $citInfo['CountryID'], 'politics'), 'info_political') !!}
		{!! $li('info-military', $vars->getURL('country', $citInfo['CountryID'], 'military'), 'info_martial') !!}
		{!! $li('info-congress', $vars->getURL('congress', $citInfo['CountryID']), 'info_congress') !!}
		{!! $li('info-map', $vars->getURL('map'), 'info_worldmap') !!}
	</ul>
	<ul class="nav-shadow" id="Extra" style="display: none">
		{!! $li('ext-elections', $vars->getURL('elections'), 'extra_elections') !!}
@if (!$isCA)
		{!! $li('ext-invite', $vars->getURL('invite'), 'extra_invite') !!}
		{!! $li('ext-chancebox', $vars->getURL('chancebox'), 'extra_chancebox') !!}
@endif
		{!! $li('ext-lottery', $vars->getURL('lottery'), 'extra_lottery') !!}
		{!! $li('ext-special', $vars->getURL('special'), 'extra_special') !!}
		{!! $li('ext-store', $vars->getURL('ejstore'), 'extra_store') !!}
		{!! $li('ext-forum', $vars->getURL('forum'), 'extra_forum') !!}
		{!! $li('ext-contact', $vars->getURL('contact'), 'extra_contact') !!}
		{!! $li('ext-wiki', 'http://wiki.ejahan.com', 'extra_wiki') !!}
		{!! $li('ext-credits', $vars->getURL('extra'), 'extra_credits') !!}
	</ul>
	<div id="menu-title" style="font-size: 18pt; position: absolute; margin-left: 730px; margin-top: -40px; width: 55px; overflow: hidden; display: none">
		Home
	</div>
</div>
