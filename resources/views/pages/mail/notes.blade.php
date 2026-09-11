<script>changeElement('note', 0)</script>
<div id=notes style='width: 640px'>
	<div class="message-table">
		<div class="message-header">{{ $lang->getstr('pm_note', 'pm') }}</div>
		<div class="note-subject">{{ $lang->getstr('pm_subject', 'pm') }}</div>
		<div class="note-date">{{ $lang->getstr('pm_date', 'pm') }}</div>
		<div class="note-oper">{{ $lang->getstr('pm_remove', 'pm') }}</div>
		<div style="clear: both"><hr size="2" width="530"></div>
@if (!$rows)
		<div>{{ $lang->getstr('pm_no_note', 'pm') }}</div>
@endif
@foreach ($rows as $row)
@php $bold = $row['isRead'] == 0; @endphp
		<div class="note-subject">@if ($bold)<b>@endif{!! $row['parsed'] !!}@if ($bold)</b>@endif</div>
		<div class="note-date">@if ($bold)<b>@endif{!! $session->getDiff($row['timestamp']) !!}@if ($bold)</b>@endif</div>
		<div class="note-oper">
			<a href="{{ $vars->getURL('mail', 'delete_n', $row['NoteID']) }}" onclick="return confirm('{{ $lang->getstr('confirm_remove_note', 'msgs') }}')"><img src="/images/game/icon_remove.gif" class="inlineIMGs"></a>
		</div>
		<div style="clear: both"><hr size="2" width="530"></div>
@endforeach
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('mail', 'notes', $page - 1) }}" class="button-blue-1">{{ $lang->getstr('nav_back') }}</a>
@endif
		<a href="{{ $vars->getURL('mail', 'notes', $page) }}" class="button-blue-0">{{ $page }}</a>
@if ($size > $start + $nums)
		<a href="{{ $vars->getURL('mail', 'notes', $page + 1) }}" class="button-blue-1">{{ $lang->getstr('nav_next') }}</a>
@endif
	</center>
	</div>
	{{ $lang->getstr('pm_note_alert', 'pm') }}
</div>
