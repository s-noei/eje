<html>
	<head>
		<title>eJahan: We will be back soon</title>
		<link rel="shortcut icon" href="/favicon.ico">
		<link rel="stylesheet" type="text/css" href="/style.css">
		<script src="/include/js/clock.js" type="text/javascript"></script>
	</head>
<body>
<center>
	<br><br><br>
	<div style="background: url('/images/maintenance.gif'); width: 220px; height:180px; padding: 72px 17px 248px 263px; color: black">
		{{ $main_msg ?? ($database->setting['maintenance_reason'] ?? config('ejahan.maintenance.msg')) }}
	</div>
	<div style="position: absolute; top:0">
		<b>Current eJahan time</b><br>
		<span id="clock">{{ date("D m/d H:i:s T") }}</span><br>
		<b>Site will be back on</b><br>
		{{ !empty($database->setting['maintenance_due']) ? date("D m/d H:i:s T", (int) $database->setting['maintenance_due']) : config('ejahan.maintenance.due') }}
	</div>
	<hr width="60%">
@if (empty($main_msg))
	<b>Login for administrators of eJahan:</b><br>
	<form action="/loginprocess.html" method="POST">
	@csrf
	<table align="center" border="0" cellspacing="0" cellpadding="3" style="background: #e4f9dc">
	<tr><td><font size="2">Citizen Name:</font></td><td><input type="text" name="user" maxlength="30" value="{{ $form->value('user') }}"></td><td>{!! $form->error('user') !!}</td></tr>
	<tr><td><font size="2">Password:</font></td><td><input type="password" name="pass" maxlength="30"></td><td>{!! $form->error('pass') !!}</td></tr>
	<tr><td colspan="2" align="left"><input type="checkbox" name="remember">
	<font size="2">Remember me next time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="hidden" name="sublogin" value="1">
	<input type="hidden" name="redto" value="/index.html">
	<input type="submit" value="Login" id="submits"></td></tr>
	</table>
	</form>
@endif
</center>
<script>clock('clock', {{ (int) date("H") }}, {{ (int) date("i") - 1 }}, {{ (int) date("s") - 1 }});</script>
</body>
</html>
