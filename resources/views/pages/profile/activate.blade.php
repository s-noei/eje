@if ($citInfo['active'])
	<h3 class="infHandle">Your account is already activated.</h3>
@elseif ($sentTo)
	<h3 class="infHandle">Sent an activation email to {{ $sentTo }}.</h3>
@else
	You must activate your account to be able to use these game features:
	<blockquote>
		1. Transfer Tala<br>2. Use international market to buy/sell items<br>3. Vote in elections<br>4. Vote articles<br>
		5. Subscribe newspapers<br>6. Add friends<br>7. Post more messages in chatbox<br>
		<b>+ Receive PRO account for 10 days for FREE!</b>
	</blockquote>
	Your account will be activated by one of these situations:
	<form action="" method="post">
		@csrf
		<blockquote>
			Receive 250 EP (Still {{ 250 - $citInfo['ep'] }} EPs left)<br>
			Verify your email address
		</blockquote>
		We can send email with activation link to email address that we have from you. You can change this email addres if you want.
		<br><br>
		Send activation link to this email addess:<br>
		<input type="text" name="email" size="50" value="{{ $citInfo['email'] }}">
		<input type="submit" class="submit-blue-1" name="subsend" value="Send activation mail">
	</form>
@endif
