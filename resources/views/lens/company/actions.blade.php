@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<form action="" method="post">
		@csrf
		Enter a company ID to perform actions: <input type="text" name="targetid" value="{{ $id }}"> <input type="submit" value="Show" id="submits">
	</form>
</center>
<hr>
@if ($comp)
<center>
@foreach ([["Change company's name", 'Name', 'name', 'subname'], ['Stock', 'New stock', 'stock', 'substock'], ['Manager', 'New manager ID', 'manager', 'submanager'], ['Change location', 'New location (Region ID)', 'location', 'subloc']] as [$h, $l, $n, $s])
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">{{ $h }}</th></tr>
			<tr><td>{{ $l }}</td><td><input type="text" name="{{ $n }}"></td></tr>
			<tr><th colspan="2"><input type="submit" name="{{ $s }}" value="Submit"></th></tr>
		</table>
	</form>
	&nbsp;
@endforeach
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Other actions</th></tr>
			<tr><th colspan="2"><input type="submit" name="subremavatar" value="Remove avatar"> <input type="submit" name="subremmsg" value="Remove message"></th></tr>
		</table>
	</form>
</center>
@endif
@endsection
