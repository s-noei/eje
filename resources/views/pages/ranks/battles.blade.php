<div id="rankings">
	<div class="rank-no" style="padding-top: 15px">No.</div>
	<div class="rank-name" style="padding-top: 15px; width: 340px;">Battle</div>
	<div class="rank-ep" style="padding-top: 15px">Fights</div>
	<div class="rank-avgep" style="padding-top: 15px">Force</div>
	<div style="clear: both"></div>
	<hr>
@if (count($rows) < 1)
			<div>There are no battles here</div>
@endif
@foreach ($rows as $i => $r)
	<div class="rank-no">{{ $start + $i + 1 }}</div>
	<div class="rank-name" style="width: 340px;">
		<a href="{{ $vars->getURL('battle', $r['battleID']) }}">
			<img src="/images/flags/{{ $r['AttFlag'] }}.gif" alt="{{ $r['AttName'] }}" title="{{ $r['AttName'] }}" class="Flag-s" align="absmiddle">
            vs
			<img src="/images/flags/{{ $r['DefFlag'] }}.gif" alt="{{ $r['DefName'] }}" title="{{ $r['DefName'] }}" class="Flag-s" align="absmiddle">
			{{ $r['RegionName'] }}
		</a>
	</div>
	<div class="rank-ep">{{ $r['TotFight'] ?: 0 }}</div>
	<div class="rank-avgep">{{ $r['TotDamage'] ?: 0 }}</div>
	<div style="clear: both"></div>
	<hr>
@endforeach
	<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('ranking', 'battles', $page - 1) }}" id="buttons">&lt; Back</a>
@endif
@if ($hasNext)
		<a href="{{ $vars->getURL('ranking', 'battles', $page + 1) }}" id="buttons">Next &gt;</a>
@endif
	</center>
</div>
