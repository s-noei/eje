<div id="fight-report">
	<div class="advance" title="">
		<span id="pref">Good</span> fight, <span id="mrank">Baivarapatish</span>!
		<br>
		You advanced <span id="force">0</span>m
	</div>
	<hr size="1">
	<div class="hummy">&nbsp;</div>
	<div class="fightinfo">
		<div class="title">Skill</div>
		<div class="detail" id="skillinfo">{{ $citInfo['mSkill'] ?? '' }}</div>
		<div style="clear: both"></div>
		<div class="title">Rank</div>
		<div class="detail"><img id="rankinfo" src="/images/game/war/mrank/{{ $citInfo['mRank'] ?? 0 }}.gif" width="80"></div>
		<div style="clear: both"></div>
		<div class="title">Wellness</div>
		<div class="detail" id="wellinfo">{{ $citInfo['wellness'] ?? '' }}</div>
		<div style="clear: both"></div>
		<div class="title">EP</div>
		<div class="detail"><span id="epchange">{{ $citInfo['ep'] ?? '' }}</span> (<font color="green">+{{ ($citInfo['puberty'] ?? 0) >= 7 ? 1 : 2 }}</font>)</div>
		<div style="clear: both"></div>
		<div class="title">Force</div>
		<div class="detail"><span id="forcechange">{{ $citInfo['total_damage'] ?? '' }}</span></div>
	</div>
	<div class="opers">
        <a href="javascript:void(0)" class="button-blue-1" onclick="$('#fight-report').fadeOut(250); $('.background').fadeOut(250); $('#fightload').fadeOut(200)">Close</a>
	</div>
	<div style="clear: both"></div>
</div>
