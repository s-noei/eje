@extends('lens.layout')
@section('content')
@include('lens._navbar')
	<center><table id="table" bordercolor="black" border="1" width="1100px">
		<tr><th>No.</th><th>Name</th><th>Citizen name</th><th>Access</th><th>Country</th><th>Invited by</th><th>Expert language</th><th>Birth date</th><th>Proof</th><th>Operations/Status</th></tr>
@foreach ($rows as $i => $row)
			<tr>
				<td>{{ $i + 1 }}</td>
				<td>{{ $row['fName'] }} {{ $row['lName'] }}</td>
				<td>{{ $row['name'] }}</td>
				<td>{{ $accs[(int) $row['access']] ?? '' }}</td>
				<td>{{ $row['cName'] }}</td>
				<td>{{ $row['invBy'] }}</td>
				<td>{{ $row['language'] ?: 'Just English' }}</td>
				<td>{{ $row['birth_year'] }}/{{ $row['birth_month'] }}/{{ $row['birth_day'] }}</td>
				<td>@if ($row['proof'])<a href="/uploads/proofs/lensreg/{{ $row['proof'] }}.jpg" target="_blank"><img src="/uploads/proofs/lensreg/{{ $row['proof'] }}.jpg" alt="Proof" style="width: 50px; height: 50px"></a>@endif</td>
				<td>
					<form action="" method="post">
						@csrf
						<input type="hidden" name="citID" value="{{ $row['citID'] }}">
@if ($row['status'] == 1)
						<input type="submit" name="subapprove" value="Approve">
						<input type="submit" name="subreject" value="Reject">
						<input type="submit" name="subremove" value="Remove" onclick="return confirm('Are you sure?')">
@elseif ($row['status'] == 2)
						APPROVED
@elseif ($row['status'] == 3)
						REJECTED <input type="submit" name="subremove" value="Remove" onclick="return confirm('Are you sure?')">
@elseif ($row['status'] == 0)
						INVITED
@else
						REMOVED
@endif
					</form>
				</td>
			</tr>
@endforeach
	</table></center>
@endsection
