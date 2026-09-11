@extends('lens.layout')
@section('content')
<center>
	<form action="{{ $lensUrl('login') }}" method="post">
		@csrf
		<table>
			<tr><td colspan="2" style="text-align: center"><img src="/lens/images/header-bg.jpg"><br><br><b>Login to lens</b><hr></td></tr>
			<tr><td>Mod ID:</td><td><input type="text" size="20" maxlength="8" name="modid"></td></tr>
			<tr><td>Password:</td><td><input type="password" size="20" maxlength="72" name="pass"></td></tr>
			<tr><td colspan="2" style="text-align: center"><input type="submit" name="sublogin" value="Login"></td></tr>
		</table>
	</form>
	<hr>
	<b>Register</b>
	<br>
	To register in eJahan lens, you need an invite from administrators.
</center>
@endsection
