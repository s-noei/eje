@php $cIMG = $vars->getImgLoc('CountryFlag') . $row['Flag'] . '.gif'; @endphp
@foreach ($errors as $e)
<h3 class="errHandle">{!! $e !!}</h3>
@endforeach
	<div class="column-double">
		<div class="column-headcol">
				<img src="{{ $vars->getImgLoc('NPAvatar') . $row['Avatar'] }}" class="Avatar-l" alt="{{ $row['npName'] }}" />
		</div>
		<div class="column-details">
			<div class="column-name">
				<a href="{{ $vars->getURL('newspaper', $row['npID']) }}">{{ $row['npName'] }}</a>
				{!! $vars->getWikiLink('Newspaper', $row['npName']) !!}
			</div>
			<a href="{{ $vars->getURL('country', $row['CountryID']) }}">
				<img src="{{ $cIMG }}" class="Flag-xs" align="absmiddle" title="{{ sprintf($lang->getstr('np_located_in', 'np'), $row['cName']) }}">
			</a>
			{{ $lang->getstr('np_publisher', 'np') }}: <b><a href="{{ $vars->getURL('profile', $row['npAuthorID']) }}">{{ $row['npAuthor'] }}</a></b>
			<br>
			{{ $lang->getstr('np_subs', 'np') }}: <b>{{ $row['npSubs'] }}</b>
			<br><br>
@if ($isAuthor)
					<a href="{{ $vars->getURL('article', 'new') }}" class="button-blue-1">{{ $lang->getstr('np_write', 'np') }}</a>
					&nbsp;
					<a href="{{ $vars->getURL('newspaper', $row['npID'], 'edit') }}" class="button-blue-1">{{ $lang->getstr('np_edit', 'np') }}</a>&nbsp;
@endif
@if ($logged && !$isCA && $citInfo['active'])
					<a href="{{ $vars->getURL('newspaper', $row['npID'], 'sub') }}" class="button-blue-1">{{ $lang->getstr($isSub ? 'np_unsub' : 'np_sub', 'np') }}</a><br>
@endif
		</div>
		<div style="clear: both">&nbsp;</div>
		<hr>
	</div>
