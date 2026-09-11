<div id=notes style='width: 640px'>
	<div class="message-table">
		<div class="message-header">{{ $lang->getstr('pm_reqs', 'pm') }}</div>
		<div class="req-name">{{ $lang->getstr('pm_subject', 'pm') }}</div>
		<div class="note-date">{{ $lang->getstr('pm_date', 'pm') }}</div>
		<div class="note-oper">{{ $lang->getstr('pm_remove', 'pm') }}</div>
		<div style="clear: both"><hr size="2" width="530"></div>
@if (!$rows)
		<div>{{ $lang->getstr('pm_nothing', 'pm') }}</div>
@endif
@foreach ($rows as $row)
		<div class="req-name">
			<a href="{{ $vars->getURL('profile', $row['CitizenID']) }}"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $row['Avatar'] }}" class="Avatar-xs" align="absmiddle"> {{ $row['name'] }}</a>
			wants to be your friend.
		</div>
		<div class="note-date">{!! $session->getDiff($row['timestamp']) !!}</div>
		<div class="note-oper">
			<a href="/friendship-accept-{{ $row['CitizenID'] }}.html" class="button-blue-0">Accept</a>
			<a href="/friendship-reject-{{ $row['CitizenID'] }}.html" class="button-red-0">Reject</a>
		</div>
		<div style="clear: both"><hr size="2" width="530"></div>
@endforeach
	</div>
</div>
