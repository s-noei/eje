@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<table id="table" bordercolor="black" border="1">
		<tr><th>ID</th><th>Title</th><th>Location</th><th>Country</th><th>Lang</th><th>Credit</th><th>Views</th><th>Clicks</th><th>Active</th><th></th></tr>
@foreach ($rows as $r)
		<tr>
			<td>{{ $r['adID'] }}</td><td>{{ $r['title'] }}</td><td>{{ $r['location'] }}</td><td>{{ $r['country'] ?: 'World' }}</td><td>{{ $r['language'] ?: 'All' }}</td>
			<td>{{ $r['credit'] }}</td><td>{{ $r['views'] ?? 0 }}</td><td>{{ $r['clicks'] ?? 0 }}</td><td>{{ $r['active'] ? 'Yes' : 'No' }}</td>
			<td><form action="" method="post">@csrf<input type="hidden" name="toggle" value="{{ $r['adID'] }}"><input type="submit" value="{{ $r['active'] ? 'Disable' : 'Enable' }}"></form></td>
		</tr>
@endforeach
	</table>
</center>
@endsection
