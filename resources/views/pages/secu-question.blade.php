@extends('layouts.game')
@section('content')
<div id="security-head">
	<div class="title">
		Wait a moment, secure your account!
	</div>
	To prevent other users to use your email and citizen name and change your
	password, you must enter a security question and answer if you want to
	continue playing eJahan.<br>
	<b>NOTE:</b> You must enter a simple, hard to guess answer. <b>You cannot
	change the question and answer anymore</b>!
	If you forgot your password, entering the answer is the only way to recover
	your password, so if you forgot it, say goodbye to your account!
	<hr>
@if ($done)
			<h3 class="infHandle">
				Ok, your security question and answer has been set successfully!<br>
				Question: {{ strip_tags($q) }}<br>
				Answer: {{ strip_tags($a) }}<br>
				Now click the button below to reload your page<br>
				<a href="" id="buttons">Reload this page</a>
			</h3>
@else
	<form action="" method="post" onsubmit="return (confirm('Are you sure you\'ll remember your security answer in the future?') &&
			confirm('This is the last chance! If you may forget the security answer, hit cancel and change it!'))">
		@csrf
		Question:
		<input type="text" name="secques" size="40">
		<br>
		Answer: &nbsp;
		<input type="text" name="secans" size="40">
		<br>
		<input type="submit" id="submits" name="subsecurity" value="Submit my question and answer">
	</form>
@endif
</div>
@endsection
