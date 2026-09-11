<div id=inbox style='width: 550px'>
	<div class="message-table"><form action="" method="post">
		@csrf
		<div class="message-header">{{ $lang->getstr('pm_inbox', 'pm') }}</div>
			<div class="message-from">{{ $lang->getstr('pm_from', 'pm') }}</div>
			<div class="message-subject">{{ $lang->getstr('pm_subject', 'pm') }}</div>
			<div class="message-date">{{ $lang->getstr('pm_date', 'pm') }}</div>
			<div class="message-oper">{{ $lang->getstr('pm_remove', 'pm') }}</div>
		<div style="clear: both"><hr size="2" width="640"></div>
@if (!$size)
		<div>{{ $lang->getstr('pm_nothing', 'pm') }}</div>
@endif
@foreach ($rows as $row)
		<div style="{{ !$row['isRead'] ? 'background: #fff79a; ' : '' }}height: 90px">
			<div class="message-from">
				<a href="{{ $vars->getURL('profile', $row['fromID']) }}">
					<img src="{{ $vars->getImgLoc('CitizenAvatar') . $row['fromAvatar'] }}" class="Avatar-s"><br>
					{{ $row['fromName'] }}
				</a>
			</div>
			<div class="message-subject">
				<a href="{{ $vars->getURL('mail', 'view', $row['pmID']) }}">@if (!$row['isRead'])<b>{{ $row['Subject'] }}</b>@else{{ $row['Subject'] }}@endif</a>
				<div style="font-size: 8pt; margin-top: 5px">{{ $vars->lenTrim(strip_tags($row['Body']), 100, '', 0) }}</div>
			</div>
			<div class="message-date">{!! $session->getDiff($row['timestamp']) !!}</div>
			<div class="message-oper"><input type="checkbox" name="mail[{{ $row['pmID'] }}]"></div>
		</div>
		<div style="clear: both"><hr size="2" width="640"></div>
@endforeach
	<center>
@if ($havePro || $havePlus)
			<input type="checkbox" name="checkall" onclick="checkUncheck(this)"> {{ $lang->getstr('pm_select_all', 'pm') }}
@endif
	<input type="submit" name="subdeli" value="{{ $lang->getstr('pm_remove_selected', 'pm') }}" onclick="return confirm('{{ $lang->getstr('confirm_remove_pm', 'msgs') }}')" id="submits">
	</center>
	</form></div>
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('mail', 'inbox', $page - 1) }}" id="buttons">{{ $lang->getstr('nav_back') }}</a>
@endif
		<a href="{{ $vars->getURL('mail', 'inbox', $page) }}" id="buttons">{{ $page }}</a>
@if ($size > $start + $nums && (($page < 3) || ($havePlus && $page < 5) || $havePro))
		<a href="{{ $vars->getURL('mail', 'inbox', $page + 1) }}" id="buttons">{{ $lang->getstr('nav_next') }}</a>
@endif
	</center>
	<h4>{{ sprintf($lang->getstr('pm_quota', 'pm'), $size, ($havePro ? $lang->getstr('unlimited') : ($havePlus ? '50' : '30'))) }}</h4>
</div>
