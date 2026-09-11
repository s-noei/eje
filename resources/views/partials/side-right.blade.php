@if (!$logged)
		<img src="/images/ads/ad{{ rand(1, 3) }}.gif" width="110px">
@else
@php
	$helpables = ['home'];
	$actiontype = $layout['actiontype'] ?? '';
	$ads = $database->rows("SELECT * FROM ads WHERE cost > '0' AND (viewin = '0' OR viewin = ?) AND status = '1' ORDER BY RAND() LIMIT 3", [$citInfo['CountryID'] ?? 0]);
@endphp
@if (in_array($actiontype, $helpables))
			{!! $vars->getWikiLink('Help', $actiontype, $lang->getstr('gethelp'), 'wiki-help.png', 25) !!}
			<hr size="1">
@endif
@foreach ($ads as $ad)
@php
	$database->exec('UPDATE ads SET views = views + 1, cost = ? WHERE adID = ?', [$ad['cost'] - 0.0002, $ad['adID']]);
	$link = $vars->getURL('ads', 'click', $ad['admd5']);
@endphp
		<div class="citAd" style="{{ $ad['rtl'] ? 'direction: rtl;' : '' }}">
			<div class="ad-img">
				<a href="{{ $link }}">
					<img src="/uploads/ads/{{ $ad['pic'] }}">
				</a>
			</div>
			<div class="ad-title">
				<a href="{{ $link }}">
					{{ $ad['title'] }}
				</a>
			</div>
			<div class="ad-content">
				{{ $ad['desc'] }}{!! $session->isAdmin() ? '<sub>Ad # '.$ad['adID'].'</sub>' : '' !!}
			</div>
		</div>
        <hr size="1">
@endforeach
@endif
