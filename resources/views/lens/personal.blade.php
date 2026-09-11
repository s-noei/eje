@extends('lens.layout')
@section('content')
@php $posts = [4 => 'Technical Moderator', 5 => 'Local Moderator for ' . ($mod['cName'] ?? ''), 6 => 'Police', 7 => 'Super Moderator', 8 => 'Website Guard', 9 => 'Administrator']; @endphp
<center>
	<table id="table" bordercolor="black" border="1" width="400px">
		<tr><th colspan="2">Moderator details</th></tr>
		<tr><td>Moderator ID</td><td>{{ $mod['ModID'] }}</td></tr>
		<tr><td>Citizen name</td><td>{{ $mod['name'] }}</td></tr>
		<tr><td>Post</td><td>{{ $posts[(int) $mod['Access']] ?? '' }}</td></tr>
@if ((int) $mod['Access'] === 5)
		<tr><td>Access for</td><td>{{ $mod['AccessD'] }}</td></tr>
@endif
		<tr><td>ModVio Points</td><td>{{ $mod['ModVio'] }}</td></tr>
		<tr><td>Total tickets replied</td><td>{{ $replied }}</td></tr>
	</table>
	&nbsp;
	{{ $msg }}
	<form action="" method="post">
		@csrf
		<table id="table" bordercolor="black" border="1" width="400px">
			<tr><th colspan="2">Edit my password</th></tr>
			<tr><td>Old password</td><td><input type="password" name="oPass"></td></tr>
			<tr><td>New password</td><td><input type="password" name="nPass1"></td></tr>
			<tr><td>Retype new password</td><td><input type="password" name="nPass2"></td></tr>
			<tr><td colspan="2"><input type="submit" name="subedit" value="Change pass!"> <input type="reset" name="subreset" value="Reset data"></td></tr>
		</table>
	</form>
</center>
@endsection
