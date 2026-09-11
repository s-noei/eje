<div class="bat-stats">
    <div style="position: absolute; margin-left: 620px; border: 1px solid #cfcfcf; border-radius: 3px; padding: 0 3px">
        <a href="javascript:void(0)" id="stat_close" onclick="$('.bat-stats').fadeOut(200); $('.background').fadeOut(250)">X</a>
    </div>
    <b>Battle statistics</b><br>
	<ul class="nButtons">
		<li id="start" class="snButton"><a href="javascript:void(0)">Overview</a></li>
		<li id="att20"><a href="javascript:void(0)">Top 20 attackers</a></li>
		<li id="def20"><a href="javascript:void(0)">Top 20 defenders</a></li>
		<li id="attNat"><a href="javascript:void(0)">Attacker nationalities</a></li>
		<li id="defNat"><a href="javascript:void(0)">Defender nationalities</a></li>
    </ul>
@foreach (['att', 'def'] as $side)
	<div class="fightlog fightlog-{{ $side }}" id="stat-{{ $side }}-temp" style="display: none;">
		<div class="no">0</div>
		<div class="fighter-name"><a class="fighter-link" href="#">name</a></div>
		<div class="fighter-fights">0</div>
		<div class="fighter-force">0m</div>
		<div class="fighter-average">0</div>
		<div style="clear: both"></div>
	</div>
@endforeach
	<div id="overview" class="newsBox">
        <center><strong>Battle stats!</strong><hr size="1">To view statistics, click on one of the upper tabs.</center>
    </div>
@foreach ([['top20att', 'Top 20 attackers', 'att20', 'att-h'], ['top20def', 'Top 20 defenders', 'def20', 'def-h'], ['topNatAtt', 'Nationalities fought in attackers side', 'attN', 'att'], ['topNatDef', 'Nationalities fought in defenders side', 'defN', 'def']] as [$id, $title, $key, $cls])
	<div id="{{ $id }}" class="newsBox" style="display: none;">
        <strong>{{ $title }}</strong>
        <hr>
        <div class="fightlog fightlog-{{ $cls }}" id="div-{{ $key }}" style="border-bottom: 1px solid;">
            <div class="no" style="font-weight: bold;">No.</div>
			<div class="fighter-name" style="font-weight: bold;">Name</div>
			<div class="fighter-fights" style="font-weight: bold;">Total fights</div>
			<div class="fighter-force" style="font-weight: bold;">Total force</div>
			<div class="fighter-average" style="font-weight: bold;">Average force</div>
			<div style="clear: both"></div>
        </div>
		<div class="handlers" id="{{ $key }}-handler"></div>
    </div>
@endforeach
    <script>
    	var actTab = 'start';
    	$(document).ready(function(){
    		function tab(btn, show, id) { $("#"+btn).click(function(){ $("div #overview, div #top20att, div #top20def, div #topNatAtt, div #topNatDef").hide(); $("div #"+show).fadeIn('500'); $("#"+actTab).toggleClass("snButton"); actTab = id; $("#"+actTab).toggleClass("snButton"); }); }
    		tab('start', 'overview', 'overview'); tab('att20', 'top20att', 'top20att'); tab('def20', 'top20def', 'top20def'); tab('attNat', 'topNatAtt', 'topNatAtt'); tab('defNat', 'topNatDef', 'topNatDef');
    	});
    </script>
</div>
