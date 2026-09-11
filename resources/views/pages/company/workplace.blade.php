<link rel="stylesheet" type="text/css" href="/include/css/donation.css">
@php
	use App\Game\Support\Constants;
	$w = fn($k) => $lang->getstr($k, 'workplace');
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
@endphp
		<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
		<br>
@if ($report)
@include('pages.company.work-report')
@endif
        <b>{!! $w('work_stat_title') !!}</b><hr>
        <div style="padding-left: 35px">
        	<div class="holder-indicator">
        		<div class="title">{!! $w('work_stat_skill') !!}</div>
        		<div class="desc">{{ $cit['wSkill'] }}</div>
        		<div class="ind">
        			{!! $vars->viewIndicator(Constants::SP_CPS[$cit['wSkill']] ?? 0, Constants::SP_CPS[$cit['wSkill'] + 1] ?? 0, $cit['wSP'], 400, "RoyalBlue", "Skill points", "%s<br>Total skill points: {$cit['wSP']}") !!}
        			<div style="clear: both"></div>
        		</div>
        	</div>
        </div>
        <div style="padding-left: 40px">{!! $w('work_stat_dsalary') !!}: {{ $cit['Salary'] . ' ' . $sCur }}</div>
        <div style="padding-left: 40px"><hr size="1" /></div>
@if ($cit['LastWorked'] < $database->today)
		<b>{!! $w('work_workplace') !!}</b><hr>
{!! $view_info ?? '' !!}
						<form action="" method="post" name="workform">
							@csrf
							<center>
								{!! $w('work_choose') !!}
								<br />
										<div class="taskbuts" id="Normal">
                                            <label class="normalwrk" title="{{ sprintf($tit, $w('work_normal'), $lang->getstr('wellness') . ': ' . $wChange[1] . '<br>' . $w('work_prod_given') . ': 100%') }}">
                                                <img src="/images/game/work/normal.gif" /><br />{!! $w('work_normal') !!}<br />
                                                <input type="radio" id="wNormal" name="work" value="{{ $w('work_normal') }}" style="visibility: hidden;" />
                                            </label>
                                        </div>
@if ($cit['puberty'] > 0)
										<div class="taskbuts" id="Extra">
                                            <label class="extrawrk" title="{{ sprintf($tit, $w('work_extra'), $lang->getstr('wellness') . ': ' . $wChange[2] . '<br>' . $w('work_prod_given') . ': 150%') }}">
                                                <img src="/images/game/work/extra.gif" /><br />{!! $w('work_extra') !!}<br />
                                                <input type="radio" id="wExtra" name="work" value="{{ $w('work_extra') }}" style="visibility: hidden;" />
                                            </label>
                                        </div>
@endif
@if ($cit['puberty'] > 1)
										<div class="taskbuts" id="Hard">
                                            <label class="hardwrk" title="{{ sprintf($tit, $w('work_hard'), $lang->getstr('wellness') . ': ' . $wChange[3] . '<br>' . $w('work_prod_given') . ': 200%') }}">
                                                <img src="/images/game/work/hard.gif" /><br />{!! $w('work_hard') !!}<br />
                                                <input type="radio" id="wHard" name="work" value="{{ $w('work_hard') }}" style="visibility: hidden;" />
                                            </label>
                                        </div>
@endif
							</center>
							<input type="hidden" name="token" value="{{ md5($citInfo['CitizenID'] . $row['CompanyID'] . 'key4 w0rkIng') }}">
							<hr size="1">
<div style="padding-left: 40px">
	<table class="task-stats">
		<tr>
			<th><center>{!! $w('work_stat_prod') !!}</center></th>
			<th><center>{!! $w('work_stat_salary') !!}</center></th>
			<th><center>{!! $w('work_stat_sp') !!}</center></th>
			<th><center>{!! $lang->getstr('wellness') !!}</center></th>
		</tr>
		<tr>
			<td><center><span id="stat-prod">--</span></center></td>
			<td><center><span id="stat-salary">--</span> {{ $sCur }}</center></td>
			<td><center><span id="stat-sp">--</span></center></td>
			<td><center><span id="stat-wn">--</span></center></td>
		</tr>
	</table>
</div>
<hr>
<div id="supFood" style="display: none;">
    <center><strong>Consume food during this task</strong></center>
    <blockquote style="text-align: center;">
        <hr size="1" />
        	Maximum wellness to recover: <span id="maxWN"></span> wellness<br />
        	You will recover <span id="wn_count">0</span> wellness.<br />
@for ($i = 1; $i <= 5; $i++)
                <div style="display: inline-block; width: 100px; height: 120px; margin: 5px 0; border: 1px solid; border-radius: 5px; text-align: center">
                    <img src="/images/icons/food.png" /><br />
                    <img src="/images/game/{{ $i }}_star.gif" /><br />
                    <input type="text" id="am_{{ $i }}" name="am[{{ $i }}]" size="2" maxlength="2" onkeyup="getChange({{ $i }})" value="{{ $foods[$i] ? '0' : '--' }}" {{ $foods[$i] ? '' : 'disabled' }} style="text-align: center;{{ $foods[$i] ? '' : 'border: 1px solid; background: #ddd' }}" />
                </div>
@endfor
    </blockquote>
    <hr size="1">
</div>
<center>
    <input type="submit" name="subwork" class="submit-blue-1" value="Work!" onclick="javascript:return checkWork();" />
</center>
</form>
<script type="text/javascript">
    var actOpt = ''; var wnRed = 0; var totFoods = 0;
    var wStats = {!! json_encode($wStats) !!};
    var maxF = new Array(6); var foods = new Array(6);
    function checkWork() {
        if (!actOpt) { alert('Choose a working type'); return false; }
        else if (totFoods > wnRed) { return confirm('You will waste your foods with this selection. Are you sure you want to do so?'); }
    }
@for ($i = 1; $i <= 5; $i++)
        maxF[{{ $i }}] = {{ $foods[$i] }};
@endfor
    function addStats(wType){
        var wNum = wType == 'Normal' ? 0 : (wType == 'Extra' ? 1 : 2);
        $("#stat-prod").html(wStats[wNum][0]); $("#stat-salary").html(wStats[wNum][1]);
        $("#stat-sp").html(wStats[wNum][2]); $("#stat-wn").html(wStats[wNum][3]);
        $("#maxWN").html((-wStats[wNum][3]));
        if (!wnRed) $("#supFood").slideDown(500);
        wnRed = -wStats[wNum][3];
    }
    function getChange(id) {
        totFoods = 0;
        for (i=1;i<6;i++) {
            flag = document.forms["workform"].elements["am["+i+"]"];
            if (flag.disabled) continue;
            if (flag.value > maxF[i]){ flag.value = maxF[i]; flag.focus(); flag.select(); }
            if (flag.value != parseInt(flag.value)){ flag.value = 0; flag.focus(); flag.select(); }
            foods[i] = flag.value; totFoods += foods[i] * i;
        }
        if (totFoods > wnRed) $("#wn_count").html("<font color='red'>"+totFoods+"</font>"); else $("#wn_count").html(totFoods);
    }
    $(document).ready(function(){
        $(".taskbuts").click(function(){
            var cID = $(this).attr("id");
            document.getElementById('w'+cID).checked = true;
            if (actOpt) $("div#"+actOpt).css("border", "3px solid");
            $("div#"+cID).css("border", "3px solid #CC3333");
            actOpt = cID; addStats(cID);
            for (i=1;i<6;i++) { flag = document.forms["workform"].elements["am["+i+"]"]; if (!flag.disabled) flag.value = 0; foods[i] = 0; }
            totFoods = 0; $("#wn_count").html("0");
        });
    });
</script>
@endif
