@extends('lens.layout')
@section('content')
<center>
	<a href="{{ $lensUrl('election', 'track', $eid . '_' . $counid) }}">Select another candidate</a> |
	<a href="{{ $lensUrl('election', 'track', $eid) }}">Select another country</a> |
	<a href="{{ $lensUrl('election') }}">Select another election</a>
	<br><br>
	<table id="table" bordercolor="black" border="1">
		<tr><th>No.</th><th>Citizens voted for this candidate</th><th>Operations</th></tr>
@foreach ($voters as $i => $cand)
		<tr>
			<td>{{ $i + 1 }}</td>
			<td><a href="{{ $vars->getURL('profile', $cand['CitizenID']) }}">{{ $cand['name'] }}</a></td>
			<td>@if ($mod['at_multrack'])<a href="{{ $lensUrl('multi', 'track', $cand['CitizenID']) }}">Multi track</a>@endif</td>
		</tr>
@endforeach
	</table>
</center>
@endsection
