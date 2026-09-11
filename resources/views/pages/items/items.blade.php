@extends('layouts.game')
@section('content')
<br>
{!! $msg ?? '' !!}
<link rel="stylesheet" type="text/css" href="/include/css/special_items.css">
<div class="vs200">
<div class="vs201"></div>
<div class="vs204"><h2>Items</h2></div>
@foreach ($items as $item)
@php $known = $item['Type'] == 13; @endphp
<table width="680" height="50" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr class="vs229">
<td width="80" height="55"><center><img src="/images/icons/{{ $item['Icon'] }}.png" /><br><div class="quality"><img src="/images/game/{{ $item['Stars'] }}_star.gif"></div></center></td>
<td width="130" height="55" class="vs228">{{ $known ? 'Gold Pack (30 Days)' : $item['iName'] }}</td>
<td width="50" height="55"><center><strong>{{ $item['Amount'] }}</strong></center></td>
<td width="320" height="55" class="vs228">{{ $known ? 'This pack offers you complete ease of use, enables all game functions for 30 days and contains a number of bonuses.' : '' }}</td>
<td width="100" height="55">
	<form action="" method="post" name="Use">
	@csrf
	<input type="hidden" name="Item" value="{{ $known ? 1 : 0 }}">
	<center><input class="vs227" type="submit" name="Use" value="Use"></center>
	</form>
</td>
</tr>
</table>
@endforeach
<div class="clear"></div>
<div class="vs202"></div>
</div>
{!! $goldpack !!}
@endsection
