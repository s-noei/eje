@extends('lens.layout')
@section('content')
<center>
	<form action="" method="post">
		@csrf
		Enter an IP to track: <input type="text" name="trackid" value="{{ $id }}"> <input type="submit" value="Track" id="submits">
	</form>
</center>
<hr>
<center>
	<table id="table" bordercolor="black" border="1">
		<tr><th class="center">No.</th><th class="center">Citizen</th><th class="center">Times</th></tr>
@foreach ($rows as $i => $log)
		<tr>
			<td class="center">{{ $start + $i + 1 }}</td>
			<td class="center"><a href="{{ $lensUrl('citizen', 'tracker', $log['citID']) }}">{{ $log['name'] }}</a></td>
			<td class="center">{{ $log['Times'] }}</td>
		</tr>
@endforeach
	</table>
@if ($page > 1)<a href="{{ $lensUrl('ip', $type, $id, $page - 1) }}">Back</a> | @endif
	Page {{ $page }}
@if ($total > $start + $count) | <a href="{{ $lensUrl('ip', $type, $id, $page + 1) }}">Next</a>@endif
</center>
@endsection
