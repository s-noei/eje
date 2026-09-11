@extends('layouts.game')
@section('content')
@php
	use App\Game\Support\Constants;
	$a = fn($k) => $lang->getstr($k, 'army');
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
@endphp
{!! $msg ?? '' !!}
@if ($trainednow && $report)
	@include('pages.war.train-report')
@elseif ($trained)
	<h3 class="infHandle">You have trained today!<br>Please come back tomorrow</h3>
	<hr>
	@if ($report)@include('pages.war.train-report')@endif
@else
		<h3>{!! $a('army_train') !!}</h3><hr>
		<form name="trainform" action="" method="post">
			@csrf
			<center>
				{!! $a('army_choose') !!}
				<br />
						<div class="taskbuts" id="Normal">
                            <label class="normalwrk" title="{{ sprintf($tit, $a('army_normal'), $lang->getstr('wellness') . ': ' . $wChange[1] . '<br>' . $a('army_skill_gain') . ': 100%') }}">
                                <img src="/images/game/train/normal.gif" /><br />{!! $a('army_normal') !!}<br />
                                <input type="radio" id="tNormal" name="train" value="1" style="visibility: hidden;" />
                            </label>
                        </div>
@if ($cit['puberty'] > 0)
						<div class="taskbuts" id="Extra">
                            <label class="extrawrk" title="{{ sprintf($tit, $a('army_extra'), $lang->getstr('wellness') . ': ' . $wChange[2] . '<br>' . $a('army_skill_gain') . ': 150%') }}">
                                <img src="/images/game/train/extra.gif" /><br />{!! $a('army_extra') !!}<br />
                                <input type="radio" id="tExtra" name="train" value="2" style="visibility: hidden;" />
                            </label>
                        </div>
@endif
@if ($cit['puberty'] > 1)
						<div class="taskbuts" id="Super">
                            <label class="hardwrk" title="{{ sprintf($tit, $a('army_super'), $lang->getstr('wellness') . ': ' . $wChange[3] . '<br>' . $a('army_skill_gain') . ': 200%') }}">
                                <img src="/images/game/train/super.gif" /><br />{!! $a('army_super') !!}<br />
                                <input type="radio" id="tSuper" name="train" value="3" style="visibility: hidden;" />
                            </label>
                        </div>
@endif
			</center>
@endif
<b>{!! $a('army_mili_stats') !!}</b><hr>
<div style="padding-left: 35px">
	<div class="holder-indicator">
		<div class="title">{!! $a('army_mili_skill') !!}</div>
		<div class="desc">{{ $cit['mSkill'] }}</div>
		<div class="ind">
			{!! $vars->viewIndicator(Constants::SP_CPS[$cit['mSkill']] ?? 0, Constants::SP_CPS[$cit['mSkill'] + 1] ?? 0, $cit['mSP'], 400, "RoyalBlue", "Skill points", "%s<br>Total skill points: {$cit['mSP']}") !!}
			<div style="clear: both"></div>
		</div>
	</div>
</div>
<div style="padding-left: 35px">
	<div class="holder-indicator">
		<div class="title">{!! $a('army_mili_rank') !!}</div>
		<div class="desc-double-e">
			<img src="/images/game/war/mrank/{{ $cit['mRank'] }}.gif" width="50" align="absmiddle" title="{{ Constants::MILI_RANKS[$cit['mRank']] ?? '' }}">
		</div>
		<div class="ind-smaller">
			{!! $vars->viewIndicator(Constants::RANK_DAMAGES[$cit['mRank']] ?? 0, Constants::RANK_DAMAGES[$cit['mRank'] + 1] ?? 0, $cit['total_damage'], 375, "maroon", "Total advance", "%s", 0) !!}
			<div style="clear: both"></div>
		</div>
	</div>
</div>
<div style="padding-left: 40px"><hr size="1"></div>
<div style="padding-left: 40px">
	<table class="task-stats">
		<tr>
			<td>&nbsp;</td>
			<td>{!! $a('army_normal') !!}</td>
@if ($cit['puberty'] > 0)<td>{!! $a('army_extra') !!}</td>@endif
@if ($cit['puberty'] > 1)<td>{!! $a('army_super') !!}</td>@endif
		</tr>
		<tr>
			<th>{!! $a('train_stat_sp') !!}</th>
			<td>{{ $skills[1] }}</td>
@if ($cit['puberty'] > 0)<td>{{ $skills[2] }}</td>@endif
@if ($cit['puberty'] > 1)<td>{{ $skills[3] }}</td>@endif
		</tr>
	</table>
</div>
<div style="padding-left: 40px">
	<a href="{{ $vars->getURL('wars') }}" class="button-blue-1">{!! $a('army_active_wars') !!}</a>
</div>
<hr>
@if ($trained || $trainednow)
			<center>
				<b>{!! $a('army_active_battles') !!}</b>
				<br>
				<blockquote style="text-align: justify">
@if (count($battles) < 1)
							<hr size="1">
							There is no active battle for your country.
@endif
@foreach ($battles as $bat)
						<hr size="1">
						<a href="{{ $vars->getURL('battle', $bat['battleID']) }}">
							<img src="/images/media/att-s.jpg" border="0" align="absmiddle">
							{{ $bat['battle_type'] == 'battle' ? $bat['attName'] : 'Revolt force' }}
							attacked {{ $bat['regionName'] }}, {{ $bat['defName'] }}
						</a>
						<sup>started {!! $session->getDiff($bat['Start']) !!}</sup>
@endforeach
				</blockquote>
			</center>
@else
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
    <input type="submit" name="subwork" class="submit-blue-1" value="Train!" onclick="javascript:return checkTask();" />
</center>
</form>
<script type="text/javascript">
    var actOpt = ''; var wnRed = 0; var totFoods = 0;
    var wStats = [[0, 0, {{ $skills[1] }}, {{ $wChange[1] }}], [0, 0, {{ $skills[2] }}, {{ $wChange[2] }}], [0, 0, {{ $skills[3] }}, {{ $wChange[3] }}]];
    var maxF = new Array(6); var foods = new Array(6);
    function checkTask() {
        if (!actOpt) { alert('Choose a train type'); return false; }
        else if (totFoods > wnRed) { return confirm('You will waste your foods with this selection. Are you sure you want to do so?'); }
    }
@for ($i = 1; $i <= 5; $i++)
        maxF[{{ $i }}] = {{ $foods[$i] }};
@endfor
    function addStats(wType){
        var wNum = wType == 'Normal' ? 0 : (wType == 'Extra' ? 1 : 2);
        $("#stat-sp").html(wStats[wNum][2]); $("#stat-wn").html(wStats[wNum][3]);
        $("#maxWN").html((-wStats[wNum][3]));
        if (!wnRed) $("#supFood").slideDown(500);
        wnRed = -wStats[wNum][3];
    }
    function getChange(id) {
        totFoods = 0;
        for (i=1;i<6;i++) {
            flag = document.forms["trainform"].elements["am["+i+"]"];
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
            document.getElementById('t'+cID).checked = true;
            if (actOpt) $("div#"+actOpt).css("border", "3px solid");
            $("div#"+cID).css("border", "3px solid #CC3333");
            actOpt = cID; addStats(cID);
            for (i=1;i<6;i++) { flag = document.forms["trainform"].elements["am["+i+"]"]; if (!flag.disabled) flag.value = 0; foods[i] = 0; }
            totFoods = 0; $("#wn_count").html("0");
        });
    });
</script>
@endif
@endsection
