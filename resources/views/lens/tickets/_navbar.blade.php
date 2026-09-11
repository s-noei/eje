<style>
	.tickets th, .tickets td { padding: 2px 15px 2px 15px; text-align: center }
</style>
@if ($istopmod)
<center>
	<form action="" method="post" name="tickettype">
		@csrf
		Select your ticket types to view:
		<select name="ttype" onchange="document.forms.item('tickettype').submit()">
			<option value="">--- SELECT ---</option>
@if (in_array($tick, ['smod', 'guard', 'admin']))
			<option value="SM" disabled="disabled" style="color: white; background-color: maroon;">As a Super moderator</option>
			<option value="bugreport">Reported bugs</option>
			<option value="abuse">Reported obvious contents</option>
			<option value="multi">Reported multiple accounts</option>
@endif
@if (in_array($tick, ['guard', 'admin']))
			<option value="WG" disabled="disabled" style="color: white; background-color: maroon;">As a Website guard</option>
			<option value="appeal">Appeals</option>
			<option value="supgame">Game support</option>
@endif
@if ($tick == 'admin')
			<option value="WA" disabled="disabled" style="color: white; background-color: maroon;">As an administrator</option>
			<option value="modtickets">Mod tickets</option>
			<option value="modreport">Reported moderators</option>
			<option value="payment">Payment issues</option>
			<option value="support">Supporting eJahan</option>
			<option value="feedback">Feedbacks</option>
@endif
		</select>
	</form>
</center>
<hr>
@endif
