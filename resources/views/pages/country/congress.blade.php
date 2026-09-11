<script>
	var setshow2 = 0;
	$(document).ready(function(){
			$("a.proplaw").click(function(){
					if (setshow2) $("div #laws_to_prop").fadeOut('500'); else $("div #laws_to_prop").fadeIn('500');
					setshow2 = 1 - setshow2;
				});
		});
</script>
@php $id = $row['CountryID']; $anyRole = $isCG || $isCP || $isMoFA || $isMoE || $isMoW; @endphp
<b>Congress</b>
<hr>
<center>
@if ($isCP)
			<font size="3" face="Arial">Hello, country president!<br>
@endif
@if ($isCG)
			<form action="" method="post" name="resign">
				@csrf
@if (!$isCP && !$isMoFA)
						<font size="3" face="Arial">Hello, congress member!
@endif
				<input type="hidden" name="subresign" value="1">
				<a href="javascript:void(0)" class="cmdRemove" onclick="if (confirm('Are you sure you want to resign from congress?')) document.resign.submit()">Resign from congress</a>
			</form>
@endif
@if ($isMoFA)
			<font size="3" face="Arial">Hello, minister of foreign affairs!<br>
@endif
@if ($isMoE)
			<font size="3" face="Arial">Hello, minister of economy!<br>
@endif
@if ($isMoW)
			<font size="3" face="Arial">Hello, minister of war!<br>
@endif
@if ($logged && $anyRole)
@if (!$isCP && !$isMoFA && !$isMoE && !$isMoW)
			Your proposals:
			<span style="border: 1px gray solid; background: {{ $uStats[$uPStats[0]] }}"><label title="{{ $statsNoStyle[$uPStats[0]] }}">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></span>
			<span style="border: 1px gray solid; background: {{ $uStats[$uPStats[1]] }}"><label title="{{ $statsNoStyle[$uPStats[1]] }}">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></span>
			</font>
			<br><br>
@endif
@if (($isCG && $numProposes < 2) || $isCP || $isMoFA || $isMoE || $isMoW)
			<a href="javascript:void(0)" class="proplaw" id="buttons">Propose a new law</a>
			<div id="laws_to_prop" style="display:none">
@php
	$links = [];
	if ($isCG) { $links += ['fee' => 'Fee', 'tax' => 'Tax', 'don' => 'Donate', 'iss' => 'Issue']; }
	if (!$isCP && $isCG) { $links['imp'] = 'Impeach'; }
	if ($isCP) { $links += ['ministry' => 'Ministry', 'buycli' => 'BuyClinic', 'buymun' => 'BuyMunic', 'welmsg' => 'WelMsg', 'prefix' => 'Prefix', 'decwar' => 'DeclareWar', 'ppeace' => 'ProposePeace', 'ally' => 'Alliance', 'notrd' => 'Notrade', 'notra' => 'Notravel']; }
	if ($isMoFA) { $links['nfee'] = 'nFee'; }
	if ($isMoE) { $links['inds'] = 'Inds'; }
	if ($isMoW) { $links['warca'] = 'Warca'; }
@endphp
@foreach ($links as $k => $n)
				<div id="buttons-fix">
					<a href="{{ $vars->getURL('law', 'new', $k) }}" id="buttons-fix">{{ $names[$n] }}</a><br>
				</div>
@if ($k === 'nfee')
			 	<div id="buttons-fix">
					<a href="{{ $vars->getURL('country', $id, 'requests') }}" id="buttons-fix">Approve nationality requests</a><br>
				</div>
@endif
@endforeach
			</div>
@endif
@else
			<font size="3" face="Arial" class="errHandle">You must be a congress member/president/minister of this country in order to propose laws.</font>
@endif
</center>
<br>
Current laws:<hr size="3">
<div id="laws-table">
	<div class="laws-type">Law type</div>
	<div class="laws-by">Proposed by</div>
	<div class="laws-time">Proposed time</div>
	<div class="laws-stat">Status</div>
	<div class="newline"></div>
	<hr>
@if (count($laws) < 1)
			<div>No laws are in this country</div>
@endif
@foreach ($laws as $law)
				<div class="laws-type"><a href="{{ $vars->getURL('law', $law['lawID']) }}">{{ $names[$law['Type']] ?? $law['Type'] }}</a></div>
				<div class="laws-by"><a href="{{ $vars->getURL('profile', $law['byID']) }}">{{ $law['byName'] }}</a></div>
				<div class="laws-time">{!! $session->getDiff($law['pTime']) !!}</div>
				<div class="laws-stat">{!! $stats[$law['Status']] ?? '' !!}</div>
				<div style="clear: both"></div>
				<hr>
@endforeach
		<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('congress', $id, $page - 1) }}" id="buttons">&lt; Back</a>
@endif
		<span id="buttons">{{ $page }}</span>
@if ($lawCount > $start + $count)
		<a href="{{ $vars->getURL('congress', $id, $page + 1) }}" id="buttons">Next &gt;</a>
@endif
	</center>
</div>
