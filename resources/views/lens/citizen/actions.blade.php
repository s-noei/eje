@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<form action="" method="post">
		@csrf
		Enter a citizen ID to perform actions: <input type="text" name="targetid" value="{{ $id }}"> <input type="submit" value="Show" id="submits">
	</form>
</center>
<hr>
@foreach ($errors as $e){{ $e }}<br>@endforeach
@if ($cit)
@php $medals = ['lm' => 'Lucky Miner', 'wf' => 'World Fame', 'ap' => 'Article Power', 'mp' => 'Media Power', 'pp' => 'Party Presidency', 'cg' => 'Congress', 'cp' => 'Country Presidency']; @endphp
<center>
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Change citizen info</th></tr>
			<tr><td>Email</td><td><input type="text" name="email" size="40" value="{{ $cit['email'] }}"></td></tr>
			<tr><th colspan="2"><input type="submit" name="subinfo" value="Submit"></th></tr>
		</table>
	</form>
	&nbsp;
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Change password</th></tr>
			<tr><td>Password</td><td><input type="password" name="pass1" size="40" value=""></td></tr>
			<tr><td>Confirm</td><td><input type="password" name="pass2" size="40" value=""></td></tr>
			<tr><th colspan="2"><input type="submit" name="subpass" value="Submit"></th></tr>
		</table>
	</form>
	&nbsp;
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Change citizen name</th></tr>
			<tr><td>New name</td><td><input type="text" name="name" size="30" value="{{ $cit['name'] }}"></td></tr>
			<tr><th colspan="2"><input type="submit" name="subname" value="Submit"></th></tr>
		</table>
	</form>
	&nbsp;
@foreach ([['Add a medal', 'subaddmed', 'Add'], ['Remove a medal', 'subdelmed', 'Remove']] as [$h, $n, $v])
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">{{ $h }}</th></tr>
			<tr><td>Medal type</td><td><select name="mtype">@foreach ($medals as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></td></tr>
			<tr><th colspan="2"><input type="submit" name="{{ $n }}" value="{{ $v }}"></th></tr>
		</table>
	</form>
	&nbsp;
@endforeach
	<form action="" method="post">@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Other actions</th></tr>
			<tr><th colspan="2">
@if (!$cit['active'])
				Activation ID: {{ $cit['actLink'] }} <input type="submit" name="subactivate" value="Activate account">
@endif
				<input type="submit" name="subremavatar" value="Remove avatar">
				<input type="submit" name="subremabout" value="Remove aboutme">
			</th></tr>
		</table>
	</form>
	&nbsp;
</center>
@endif
@endsection
