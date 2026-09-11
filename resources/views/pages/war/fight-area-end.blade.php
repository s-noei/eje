@php
	if (in_array($rWar['Result'], ['secu', 'attreat'])) $flg = $rWar['defFlag']; else $flg = $rWar['attFlag'] ?: 'revolt';
	$flg = ($flg != 'revolt' ? "/images/flags/animated/$flg" : "/images/flags/$flg") . ".gif";
@endphp
	<img src="{{ $flg }}" width="100px" height="100px" style="border: 3px solid green; border-radius: 5px">
	<br>
@if ($rWar['Result'] == 'secu')
			<font size="4" color="green">{{ $rWar['defName'] }} secured this region against {{ $rWar['attName'] ?: 'the revolt force' }}</font>
@elseif ($rWar['Result'] == 'attreat')
			<font size="4" color="green">The CP of {{ $rWar['attName'] }} retreated from the battlefield<br>The region is secured by {{ $rWar['defName'] }}</font>
@elseif ($rWar['Result'] == 'defreat')
			<font size="4" color="red">The CP of {{ $rWar['defName'] }} retreated from the battlefield<br>The region is conquered by {{ $rWar['attName'] }}</font>
@elseif ($rWar['Result'] == 'conq')
			<font size="4" color="red">{{ $rWar['attName'] ?: 'The revolt force' }} conquered this region against {{ $rWar['defName'] }}</font>
@endif
