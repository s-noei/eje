<div class="mini-login">
	<form action="/loginprocess.html" method="POST">
		@csrf
		<b>Login</b>
		<hr size="1">
		<table class="login-table">
			<tr>
				<td>Citizen Name:</td>
				<td><input type="text" name="user" maxlength="30"></td>
			</tr>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="pass" maxlength="30"></td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="remember">
					<font size="2">Remember me</font>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="hidden" name="sublogin" value="1">
					<input type="hidden" name="redto" value="{{ request()->getRequestUri() }}">
					<input type="submit" value="Login" class="submit-blue-0">
					<a href="/forgotpass.html" class="button-blue-1">Forgot password?</a>
				</td>
			</tr>
		</table>
	</form>
</div>
<div class="mini-welcome">
		<a href="{{ $vars->getURL('home') }}">
			<b>eJahan - the reality of your dreams</b>
		</a>
		<hr size="1">
		You are viewing the game as a guest. If you have a citizen account, you can login with form in left.<br>
		If you have no citizen account yet, we recommend you to register and become a citizen of this world.
</div>
<div class="mini-register">
	<b>Register</b>
	<hr size="1">
	Yeah, it's real! You are one step out to change the history of this world! Just click on button below, fill a form and join us!<br><br>
	<a href="{{ $vars->getURL('register') }}" class="button-blue-1">Become a citizen</a>
</div>
