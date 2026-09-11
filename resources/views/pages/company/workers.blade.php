{!! $msg ?? '' !!}
	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
	<hr size="2">
	<div class="column-double">
			<div class="workers">
				<div class="workers-no">No.</div>
				<div class="workers-name">Name</div>
				<div class="workers-skill">Skill</div>
				<div class="workers-wellness">Wellness</div>
				<div class="workers-salary">Salary</div>
				<div class="workers-last">Last Worked</div>
				<div class="workers-oper">&nbsp;</div>
				<div style="clear:both"></div>
				<hr size="2">
@if (count($workers) < 1)
					There are no workers in this company
@else
@foreach ($workers as $co => $worker)
				<form action="" method="post">
				@csrf
				<div class="workers-no">{{ $co + 1 }}</div>
				<div class="workers-name">
					<a href="{{ $vars->getURL('profile', $worker['CitizenID']) }}">{!! $vars->getAvatar($worker, 'Avatar-s') !!}<br>{{ $worker['name'] }}</a>
				</div>
				<div class="workers-skill">{{ $worker['wSkill'] }}</div>
				<div class="workers-wellness">{{ $worker['wellness'] }}</div>
				<div class="workers-salary">
@if ($isManagerW)
					<input type="text" name="Salary" size="2" class="text" value="{{ $worker['Salary'] }}" maxlength="4"> {{ $worker['curName'] }}
@elseif ($logged && ($session->isAdmin() || $worker['CitizenID'] == $citInfo['CitizenID']))
					{{ $worker['Salary'] }} {{ $worker['curName'] }}
@else
					---
@endif
				</div>
				<div class="workers-last">
@if ($worker['LastWorked'] == '-1')
					<font color='red'>Never</font>
@elseif ($worker['LastWorked'] == $database->getToday())
					<font color='green'>Today</font>
@else
					<font color='red'>Day {{ $worker['LastWorked'] }}</font>
@endif
				</div>
				<div class="workers-oper">
@if ($isManagerW)
					<input type="hidden" name="oSalary" value="{{ $worker['Salary'] }}">
					<input type="hidden" name="actOffer" value="{{ $worker['CitizenID'] }}">
					<input type="hidden" name="token" value="{{ md5($worker['CitizenID'] . $req_id . 'F**kingBugs') }}">
					<input type="submit" value="Update" name="cmdUpdate" class="cmdEdit" style="width: 75px">
					<input type="submit" value="Fire" name="cmdFire" class="cmdRemove" style="width: 75px" onclick="return confirm('Are you sure you want to fire this worker?')">
@endif
				</div>
				<div style="clear:both"></div>
				</form>
				<hr size="2">
@endforeach
@if ($acc['can_view_private_data'])
				<form action="" method="post">
					@csrf
					<input type="submit" value="Fire All" name="cmdFireAll" class="cmdRemove" style="width: 75px" onclick="return confirm('Are you sure you want to fire all workers?')">
				</form>
@endif
@endif
</div>
</div>
