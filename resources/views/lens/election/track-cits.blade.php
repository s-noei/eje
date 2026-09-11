@extends('lens.layout')
@section('content')
<center>
	<a href="{{ $lensUrl('election', 'track', $eid) }}">Select another country</a> | <a href="{{ $lensUrl('election') }}">Select another election</a>
	<br><br>
	<table id="table" bordercolor="black" border="1">
		<tr><th>Candidate</th><th>Party</th><th>Votes</th><th>Operations</th></tr>
@foreach ($cands as $cand)
		<tr>
			<td><a href="{{ $vars->getURL('profile', $cand['CandidateID']) }}">{{ $cand['name'] }}</a></td>
			<td>{{ $cand['pName'] }}</td>
			<td>{{ $cand['TotalVotes'] }}</td>
			<td><a href="{{ $lensUrl('election', 'track', $eid . '_' . $counid . '_' . $cand['CandidateID']) }}">Track voters</a></td>
		</tr>
@endforeach
	</table>
@if (!$cands)
	<br>Only CP elections are tracked here.
@endif
</center>
@endsection
