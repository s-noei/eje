@extends('lens.layout')
@section('content')
<center>
	<form action="" method="post">
		@csrf
		Enter an ID and type to track the multies: <input type="text" name="trackid" value="{{ $id ?: '' }}" size="7" style="text-align: center">
		Show suspicious accounts with more than: <input type="text" name="lowcase" value="{{ $lowcase }}" size="3" style="text-align: center">%
		<input type="submit" value="Track" id="submits">
	</form>
</center>
<hr>
@if (!$id)
@if ($detail)
			<center>
				<b>Help</b>
				<blockquote>
					<table id="table" bordercolor="black" border="1">
						<tr><th class="center">Symbol</th><th class="center">Meaning</th><th class="center">Points</th></tr>
@foreach ([['olive','I','Has IP conflict',1],['orange','S','Has session conflict',2],['teal','R','Same region',1],['navy','P','Same password',2],['maroon','A','User agent conflict',5],['chocolate','L','Login time test',1],['DarkOliveGreen','W','Work time test',1],['DarkSlateBlue','T','Train time test',1],['teal','E','Explore time test',1],['hotpink','V','Vote test',2],['khaki','M','Transaction test',2]] as [$c, $s, $m, $p])
						<tr><td class="center"><font color="{{ $c }}">{{ $s }}</font></td><td class="center">{{ $m }}</td><td class="center">{{ $p }}</td></tr>
@endforeach
					</table>
				</blockquote>
			</center>
			<hr>
@else
			<center>Enter ID to view suspicious multiple accounts</center>
@endif
@else
Want to check ID number {{ $id }}
<hr>
<center>
	<table id="table" bordercolor="black" border="1">
		<tr><th class="center">ID</th>@if ($detail)<th class="center">Conflict string</th><th class="center">Points</th>@endif<th class="center">Suspicious percentage</th></tr>
		<tr>
			<td class="center"><a href="{{ $vars->getURL('profile', $id) }}" target="_blank">{{ $id }}</a></td>
@if ($detail)<td class="center"><font style="font-size: 13pt; font-weight: bold">OWNACCOUNT</font></td><td class="center">---</td>@endif
			<td class="center">Being checked</td>
		</tr>
@foreach ($hunter->multies as $mData)
@php $percent = round(($mData['points'] / $hunter->totalPoints) * 100, 2); @endphp
@if ($percent < $lowcase || $mData['points'] <= 2) @break @endif
		<tr>
			<td class="center"><a href="{{ $vars->getURL('profile', $mData['citID']) }}" target="_blank">{{ $mData['citID'] }}</a></td>
@if ($detail)<td class="center"><font style="font-size: 13pt; font-weight: bold">{!! $mData['string'] !!}</font></td><td class="center">{{ $mData['points'] }}</td>@endif
			<td class="center">{{ $percent }}%</td>
		</tr>
@endforeach
	</table>
</center>
@endif
@endsection
