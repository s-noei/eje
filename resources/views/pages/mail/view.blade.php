<div class="column-double">
	<div class="td-title">{{ $lang->getstr('pm_from', 'pm') }}:</div>
	<div class="td-content">
		<a href="{{ $vars->getURL('profile', $pm['fromID']) }}">
			<img src="{{ $vars->getImgLoc('CitizenAvatar') . $pm['fromAvatar'] }}" alt="{{ $pm['fromName'] }}" class="Avatar-s" align="absmiddle">
			{{ $pm['fromName'] }}
		</a>
	</div>
	<div style="clear: both"></div>
	<div class="td-title">{{ $lang->getstr('pm_to', 'pm') }}:</div>
	<div class="td-content"><a href="{{ $vars->getURL('profile', $pm['toID']) }}">{{ $pm['toName'] }}</a></div>
	<div style="clear: both"></div>
	<div class="td-title">{{ $lang->getstr('pm_subject', 'pm') }}:</div>
	<div class="td-content"><label>{{ $pm['Subject'] }}</label></div>
	<div style="clear: both"></div>
	<div class="td-title">{{ $lang->getstr('pm_date', 'pm') }}:</div>
	<div class="td-content"><label>{!! $session->getDiff($pm['timestamp']) !!}</label></div>
	<div style="clear: both"><hr></div>
	<div class="pmBody">{!! nl2br($pm['Body']) !!}</div>
	<div style="clear: both"><hr></div>
	<div>
@if ($inbox)
		<h2>{{ $lang->getstr('pm_reply', 'pm') }}</h2>
@include('pages.mail.compose')
@endif
	</div>
</div>
