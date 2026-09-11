@extends('layouts.game')
@section('content')
@if ($step === 'error')
	<h3 class="errHandle">{{ $msg }}</h3>
@elseif ($step === 'question')
	<h2>Just another step</h2>
	<hr>
	<blockquote>
		Ok, you're almost complete. Now you must enter your security answer.<br><br>
		<b>I've forgotten my answer, what can I do?</b><br>
		In a single word, NOTHING! We're so sorry, there's no other way to identify yourself.<br><br>
		<form action="" method="post">
			@csrf
			<b>Your security question:</b><br>
				&nbsp;&nbsp;&nbsp;{{ $citZ['secu_question'] }}
			<br><br>
			<b>The answer:</b><br>
			<input type="text" name="secans" size="40">
			<input type="hidden" name="user" value="{{ $user }}">
			<input type="hidden" name="email" value="{{ $email }}">
			<input type="submit" name="subforgot2" value="Submit">
		</form>
	</blockquote>
@elseif ($step === 'done')
	<h3 class="infHandle">Your new password is {{ $newpass }}. We also sent the new pass to your email.</h3>
@else
<h1>Forgot Password</h1>
<blockquote>
A new password will be generated for you and sent to the email address<br>
associated with your account, all you have to do is enter your citizen name.<br><br>
{!! $form->error("user") !!}
<form action="" method="POST">
	@csrf
	<table>
		<tr><th>Citizen name:</th><td><input type="text" name="user" maxlength="30" value="{{ $form->value('user') }}"></td></tr>
		<tr><th>Email:</th><td><input type="text" name="email" maxlength="30" value="{{ $form->value('email') }}"></td></tr>
		<tr><td colspan="2">
			<input type="hidden" name="subforgot" value="1">
			<input type="submit" id="submits" value="Goto next step">
		</td></tr>
	</table>
</form>
</blockquote>
@endif
@endsection
