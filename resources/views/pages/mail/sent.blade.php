<div id=sent style='width: 550px'>
	<div class="message-table">
		<div class="message-header">{{ $lang->getstr('pm_sent', 'pm') }}</div>
			<div class="message-from">{{ $lang->getstr('pm_to', 'pm') }}</div>
			<div class="message-subject">{{ $lang->getstr('pm_subject', 'pm') }}</div>
			<div class="message-date">{{ $lang->getstr('pm_date', 'pm') }}</div>
			<div class="message-oper">{{ $lang->getstr('pm_remove', 'pm') }}</div>
		<div style="clear: both"><hr size="2" width="530"></div>
@if (!$rows)
			<div>{{ $lang->getstr('pm_nothing', 'pm') }}</div>
@endif
@foreach ($rows as $row)
@php $bold = !$row['isRead'] && ($havePlus || $havePro); @endphp
			<div class="message-from">
				<a href="{{ $vars->getURL('profile', $row['toID']) }}">
					<img src="{{ $vars->getImgLoc('CitizenAvatar') . $row['toAvatar'] }}" class="Avatar-s"><br>
					@if ($bold)<b>{{ $row['toName'] }}</b>@else{{ $row['toName'] }}@endif
				</a>
			</div>
			<div class="message-subject">@if ($bold)<b>@endif<a href='{{ $vars->getURL('mail', 'view', $row['pmID']) }}'>{{ $row['Subject'] }}</a>@if ($bold)</b>@endif</div>
			<div class="message-date">@if ($bold)<b>@endif{!! $session->getDiff($row['timestamp']) !!}@if ($bold)</b>@endif</div>
			<div class="message-oper">
				<a href="{{ $vars->getURL('mail', 'delete_s', $row['pmID']) }}" onclick="return confirm('{{ $lang->getstr('confirm_remove_pm', 'msgs') }}')"><img src="/images/game/icon_remove.gif" class="inlineIMGs"></a>
			</div>
		<div style="clear: both"><hr size="2" width="530"></div>
@endforeach
	</div>
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('mail', 'sent', $page - 1) }}" id="buttons">{{ $lang->getstr('nav_back') }}</a>
@endif
		<a href="{{ $vars->getURL('mail', 'sent', $page) }}" id="buttons">{{ $page }}</a>
@if ($size > $start + $nums)
		<a href="{{ $vars->getURL('mail', 'sent', $page + 1) }}" id="buttons">{{ $lang->getstr('nav_next') }}</a>
@endif
	</center>
</div>
