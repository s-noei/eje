@php $wtypes = ["", "Normal", "Extra", "Hard"]; @endphp
<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
<hr size="2">
<center>{!! $lang->getstr('company_status_worklog', 'company') !!}</center>
<hr size="1">
<blockquote>
	Productivity per day
	<hr size="1">
	<form action="" method="post" name="selectday" style="text-align: center">
		@csrf
		<b>Select day</b>
		<select name="selectedday" onchange="document.selectday.submit()">
@for ($i = $database->today; $i > $database->today - 30; $i--)
			<option value="{{ $i }}"{{ $i == $selDay ? ' selected' : '' }}>Day {{ $i }}</option>
@endfor
		</select>
	</form>
	<hr size="1">
	<div class="company-status-workers">
		<div class="no">No.</div><div class="name">Name</div><div class="type">Type</div><div class="wellness">Wellness</div>
		<div class="skill">Skill</div><div class="prod">Productivity</div><div class="tool">Tool Deprecation</div><div class="salary">Salary</div>
		<div style="float: none; clear: both"></div>
@php $total = ['product' => 0, 'depr' => 0, 'salary' => 0, 'curName' => '']; @endphp
@foreach ($dayLogs as $co => $log)
@php $total['product'] += $log['products']; $total['depr'] += $log['tooldec']; $total['salary'] += $log['salary']; $total['curName'] = $log['curName']; @endphp
			<div class="no">{{ $co + 1 }}</div>
			<div class="name">
				<a href="{{ $vars->getURL('profile', $log['CitizenID']) }}">{!! $vars->getAvatar($log, 'Avatar-xxs') !!} {!! $vars->lenTrim($log['name'], 8, '', 0, 'Worker name') !!}</a>
			</div>
			<div class="type">{{ $wtypes[$log['type']] ?? '' }}</div>
			<div class="wellness">{{ $log['wellness'] }}</div>
			<div class="skill">{{ $log['skill'] }}</div>
			<div class="prod">{{ $log['products'] }}</div>
			<div class="tool">{{ round($log['tooldec'], 2) }}%</div>
			<div class="salary">{{ $log['salary'] }} {{ $log['curName'] }}</div>
			<div style="float: none; clear: both"></div>
@endforeach
			<div class="no">&nbsp;</div><div class="name">Total</div><div class="type">&nbsp;</div><div class="wellness">&nbsp;</div><div class="skill">&nbsp;</div>
			<div class="prod">{{ $total['product'] }}</div>
			<div class="tool">{{ round($total['depr'], 2) }}%</div>
			<div class="salary">{{ $total['salary'] }} {{ $total['curName'] }}</div>
			<div style="float: none; clear: both"></div>
	</div>
</blockquote>
<blockquote>
	Productivity per worker
	<hr size="1">
	<form action="" method="post" name="selectcit" style="text-align: center">
		@csrf
		<b>Select worker</b>
		<select name="selectedcit" onchange="document.selectcit.submit()">
			<option value="0">--- SELECT A WORKER ---</option>
@foreach ($workerList as $cit)
			<option value="{{ $cit['CitizenID'] }}"{{ $cit['CitizenID'] == $selCit ? ' selected' : '' }}>{{ $cit['name'] }}</option>
@endforeach
		</select>
	</form>
@if ($selCit)
			<hr size="1">
			<div class="company-status-workers">
				<div class="no">No.</div><div class="name">Day</div><div class="type">Type</div><div class="wellness">Wellness</div>
				<div class="skill">Skill</div><div class="prod">Productivity</div><div class="tool">Tool Deprecation</div><div class="salary">Salary</div>
				<div style="float: none; clear: both"></div>
@foreach ($citLogs as $co => $log)
					<div class="no">{{ $co + 1 }}</div>
					<div class="name">{{ $log['Day'] }}</div>
					<div class="type">{{ $wtypes[$log['type']] ?? '' }}</div>
					<div class="wellness">{{ $log['wellness'] }}</div>
					<div class="skill">{{ $log['skill'] }}</div>
					<div class="prod">{{ $log['products'] }}</div>
					<div class="tool">{{ round($log['tooldec'], 2) }}%</div>
					<div class="salary">{{ $log['salary'] }} {{ $log['curName'] }}</div>
					<div style="float: none; clear: both"></div>
@endforeach
			</div>
@endif
</blockquote>
