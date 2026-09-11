@extends('layouts.game')
@section('content')
<script type="text/javascript" src="/include/js/slider.js"></script>
@if ($error)
<h3 class=errHandle>{!! $error !!}</h3>
@endif
@if ($showReport && $report)
@include('pages.mines.report', ['rep' => $report])
@elseif (!$error)
@if ($citInfo['puberty'] == 0)
<h3 class=errHandle>You must pass Social Puberty Level 1 to unlock Explore mines</h3>
@elseif ($exploredToday)
<h3 class=infHandle>You have explored the mine today!<br>Please come back tomorrow</h3>
<hr>
@if ($report)
@include('pages.mines.report', ['rep' => $report])
@endif
@else
    <h3>{{ $lang->getstr('explore_head', 'explore') }}</h3><hr>
		<form action="" method="post" name="exploreform">
							@csrf
							<center>
                				{{ $lang->getstr('explore_choose', 'explore') }}<br>
                				<br>
@foreach ([1 => ['Normal', 'normalwrk', 'explore_normal', 'normal'], 2 => ['Hard', 'extrawrk', 'explore_hard', 'hard'], 3 => ['Max', 'hardwrk', 'explore_max', 'max']] as $lvl => [$id, $cls, $key, $img])
@if ($lvl == 1 || $citInfo['puberty'] > 1)
										<div class="taskbuts" id="{{ $id }}">
                                            <label class="{{ $cls }}" title="{{ sprintf($tit, $lang->getstr($key, 'explore'), $lang->getstr('wellness') . ': ' . $wChange[$lvl]) }}">
                                                <img src="/images/game/mines/{{ $img }}.png" />
                                                <br />
                                                {{ $lang->getstr($key, 'explore') }}
                                                <br />
                                                <input type="radio" id="w{{ $id }}" name="explore" value="{{ $lvl }}" style="visibility: hidden;" />
                                            </label>
                                        </div>
@endif
@endforeach
							</center>
				<br />
<div id="supFood" style="display: none;">
    <center><strong>Consume food during this task</strong></center>
    <blockquote style="text-align: center;">
        <hr size="1" />
        	Maximum wellness to recover: <span id="maxWN"></span> wellness<br />
        	You will recover <span id="wn_count">0</span> wellness.<br />
@for ($i = 1; $i <= 5; $i++)
                <div style="display: inline-block; width: 100px; height: 120px; margin: 5px 0; border: 1px solid; border-radius: 5px; text-align: center">
                    <img src="/images/icons/food.png" />
                    <br />
                    <img src="/images/game/{{ $i }}_star.gif" />
                    <br />
                    <input type="text" id="am_{{ $i }}" name="am[{{ $i }}]" size="2" maxlength="2" onkeyup="getChange({{ $i }})" value="{{ $foods[$i] ? '0' : '--' }}" @if (!$foods[$i]) disabled @endif style="text-align: center;{{ $foods[$i] ? '' : 'border: 1px solid; background: #ddd' }}" />
                </div>
@endfor
    </blockquote>
    <hr size="1">
</div>
            <center>
                <input type="submit" name="subexplore" value="{{ $lang->getstr('explore_start', 'explore') }}" id="submits" style="width: 200px" onclick="javascript:return checkTask();">
				<hr size="1">
            </center>
		</form>
@endif
@endif
<b>{{ $lang->getstr('explore_stat', 'explore') }}</b><hr>
<div style="padding-left: 40px">
	<div id="wnmeter" style="background: transparent url('/images/tala-big.png') no-repeat scroll top; width: 256px; height: 256px" title="{{ $citInfo['mines_advance'] }}/1600">
		<div id="holder-wellness" class="meter" style="height: {{ 97 - round($citInfo['mines_advance'] / 1600 * 85, 2) }}%; margin-left: -2px">
			<img src="/images/tala-big-bw.png" alt="{{ $citInfo['mines_advance'] }}/1600" title="{{ $citInfo['mines_advance'] }}/1600">
		</div>
	</div>
	{{ $lang->getstr('explore_triact', 'explore') }}: {{ $citInfo['mines_tried'] }}<br>
	{{ $lang->getstr('explore_done', 'explore') }}: {{ $citInfo['mines_done'] }}<br><br>
	{!! sprintf($lang->getstr('explore_prize2', 'explore'), round(20 / ($tri - 9), 2) * 10, round(100 / ($tri - 9), 2) * 10) !!}
</div>
<script type="text/javascript">
    var actOpt = '';
    var wnRed = 0;
    var totFoods = 0;
    var wStats = new Array(3);
    var maxF = new Array(6);
    var foods = new Array(6);
    function checkTask()
    {
        if (!actOpt) { alert('Choose a working type'); return false; }
        else if (totFoods > wnRed) { return confirm('You will waste your foods with this selection. Are you sure you want to do so?'); }
    }
    for (i=0; i <3; i++) wStats[i]=new Array(4);
    wStats[0][3] = {{ $wChange[1] }};
    wStats[1][3] = {{ $wChange[2] }};
    wStats[2][3] = {{ $wChange[3] }};
@for ($i = 1; $i <= 5; $i++)
        maxF[{{ $i }}] = {{ $foods[$i] }};
@endfor
    function addStats(wType){
        var wNum = 0;
        switch(wType) { case 'Normal': wNum = 0; break; case 'Hard': wNum = 1; break; case 'Max': wNum = 2; break; }
        $("#stat-wn").html(wStats[wNum][3]);
        $("#maxWN").html((-wStats[wNum][3]));
        if (!wnRed) $("#supFood").slideDown(500);
        wnRed = -wStats[wNum][3];
    }
    function getChange(id)
    {
        totFoods = 0;
        for (i=1;i<6;i++)
        {
            flag = document.forms["exploreform"].elements["am["+i+"]"];
            if (flag.disabled) continue;
            if (flag.value > maxF[i]){ flag.value = maxF[i]; flag.focus(); flag.select(); }
            if (flag.value != parseInt(flag.value)){ flag.value = 0; flag.focus(); flag.select(); }
            foods[i] = flag.value;
            totFoods += foods[i] * i;
        }
        if (totFoods > wnRed) $("#wn_count").html("<font color='red'>"+totFoods+"</font>"); else $("#wn_count").html(totFoods);
    }
    $(document).ready(function(){
        $(".taskbuts").click(function(){
            var cID = $(this).attr("id");
            document.getElementById('w'+cID).checked = true;
            if (actOpt) $("div#"+actOpt).css("border", "3px solid");
            $("div#"+cID).css("border", "3px solid #CC3333");
            actOpt = cID;
            addStats(cID);
            for (i=1;i<6;i++)
            {
                flag = document.forms["exploreform"].elements["am["+i+"]"];
                if (flag.disabled) continue;
                flag.value = 0; foods[i] = 0;
            }
            totFoods = 0;
            $("#wn_count").html("0");
        });
    });
</script>
@endsection
