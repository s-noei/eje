@extends('lens.layout')
@section('content')
<center>
	Started elections
	<table id="table" bordercolor="black" border="1" width="400px">
@foreach ($groups as $g)
		<tr><th colspan="3" align="center">{{ date('M', $g['ts']) }} {{ date('Y', $g['ts']) }}</th></tr>
		<tr>
@foreach ($g['items'] as $ele)
			<td align="center"><a href="{{ $lensUrl('election', 'track', $ele['eID']) }}">{{ $ele['day'] }} {{ date('M', $ele['timestamp']) }} {{ date('Y', $ele['timestamp']) }}:<br>{{ $ele['eType'] }} elections</a></td>
@endforeach
		</tr>
@endforeach
	</table>
</center>
@endsection
