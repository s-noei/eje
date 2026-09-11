@if ($msg)<h3 class="errHandle">{{ $msg }}</h3>@endif
<form name="edit" action="" method="POST">
@csrf
<table align="left" border="0" cellspacing="0" cellpadding="3">
<tr><td class="tdstyle">Current Password:</td><td class="tdstyle" colspan="2"><input type="password" name="curpass" maxlength="30" value=""></td></tr>
<tr><td class="tdstyle">New Password:</td><td class="tdstyle" colspan="2"><input type="password" name="newpass1" maxlength="30" value=""></td></tr>
<tr><td class="tdstyle">Retype Password:</td><td class="tdstyle" colspan="2"><input type="password" name="newpass2" maxlength="30" value=""></td></tr>
<tr><td class="tdstyle">Email:</td><td class="tdstyle" colspan="2"><input type="text" name="email" maxlength="50" value="{{ $citInfo['email'] }}"></td></tr>
<tr><td colspan="3" align="right">
<input type="hidden" name="subpass" value="1">
<input type="submit" value="Done" id="buttons"></td></tr>
</table>
</form>
