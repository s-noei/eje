@if ($sent)
	<h3 class="infHandle">Invite sent.</h3>
@else
To invite this citizen into eJahan Lens, please fill the form below. After citizen received the invite,
he/she will fill the registration form and the moderation team will approve/reject the registration.
<form action="" method="post">
	@csrf
	<table>
		<tr><td>Send invite as a</td><td>
			<select name="access">
				<option value="1">Technical moderator</option>
				<option value="2">Local moderator</option>
				<option value="3">Police</option>
			</select>
		</td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" name="subinvite" class="submit-blue-1" value="Send invite to this citizen!" /></td></tr>
	</table>
</form>
@endif
