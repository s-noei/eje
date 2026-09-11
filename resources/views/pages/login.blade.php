@extends('layouts.game')
@section('content')
<h2>Login</h2>
@if ($form->num_errors > 0)
<font size="2" color="#ff0000">{{ $form->num_errors }} error(s) found</font>
@endif
<form action="/loginprocess.html" method="POST">
	@csrf
	<table align="center" border="0" cellspacing="0" cellpadding="3">
	<tr><td>Citizen Name:</td><td><input type="text" name="user" maxlength="30" value="{{ $form->value('user') }}"></td><td>{!! $form->error('user') !!}</td></tr>
	<tr><td>Password:</td><td><input type="password" name="pass" maxlength="30"></td><td>{!! $form->error('pass') !!}</td></tr>
	<tr><td colspan="2" align="left"><input type="checkbox" name="remember"> <font size="2">Remember me next time</font>
	<input type="hidden" name="sublogin" value="1">
	<input type="hidden" name="redto" value="/index.html">
	<input type="submit" value="Login" class="submit-blue-0"> <a href="/forgotpass.html" class="button-blue-1">Forgot password?</a></td></tr>
	</table>
</form>
@endsection
