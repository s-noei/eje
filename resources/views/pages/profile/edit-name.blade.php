@if ($msg)<h3 class="errHandle">{{ $msg }}</h3>@endif
<form name="edit" action="" method="POST">
@csrf
<b>Note:</b> Editing name costs 10 Tala. If you change your name, your old name will be visible in your profile page.
Also, you cannot change your name for a month.<br>
<blink>When you've successfully changed your name, you'll be logged out of game, and you must login with your new name then.</blink>
<table align="left" border="0" cellspacing="0" cellpadding="3">
<tr><td class="tdstyle">Enter your password:</td><td class="tdstyle" colspan="2"><input type="password" name="curpass" maxlength="30" value=""></td></tr>
<tr><td class="tdstyle">New name:</td><td class="tdstyle" colspan="2"><input type="text" name="newname" maxlength="30" value=""></td></tr>
<tr><td colspan="3" align="right">
<input type="hidden" name="subname" value="1">
<input type="submit" value="Change name" id="buttons"></td></tr>
</table>
</form>
