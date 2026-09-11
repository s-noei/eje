@if ($msg)<h3 class="errHandle">{{ $msg }}</h3>@endif
<script language="javascript">
	function count() {
		var valu = document.edit.aboutme.value;
		if (valu.length > 100) return false;
		document.getElementById('count').innerHTML = valu.length;
		return true;
	}
</script>
<form name="edit" action="" method="POST" enctype="multipart/form-data">
@csrf
<table align="left" border="0" cellspacing="0" cellpadding="3">
<tr>
	<td class="tdstyle" colspan="2">
		<a href="{{ $vars->getURL('profile', '', 'edit', 'name') }}">Change name</a>
@if (!$isNCA)
		| <a href="{{ $vars->getURL('profile', '', 'edit', 'pass') }}">Change email and/or password</a>
@endif
		| <a href="{{ $vars->getURL('profile', '', 'personalize') }}">Personalize</a>
	</td>
	<td class="tdstyle"></td>
</tr>
<tr>
	<td class="tdstyle">About yourself:</td>
	<td class="tdstyle" colspan="2">
		<textarea rows="8" name="aboutme" cols="29" onkeydown="return count()" style="overflow: auto;">{{ old('aboutme', $citInfo['aboutme']) }}</textarea>
		<br>
		<span style="font-size: 8pt">100 Chars max<br>Currently: <span id="count" style="font-size: 8pt"><script>count();</script></span>&nbsp;characters</span>
	</td>
	<td></td>
</tr>
<tr><td class="tdstyle" colspan="3">&nbsp;</td><td>&nbsp;</td></tr>
<tr>
<td class="tdstyle" rowspan="2">Avatar</td>
<td class="tdstyle" rowspan="2"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $citInfo['Avatar'] }}" class="Avatar-s"></td>
<td class="tdstyle"><input type="file" name="file" id="file"></td>
<td rowspan="2"></td>
</tr>
<tr><td class="tdstyle"><b>Restrictions:<br>.jpg &amp; .jpeg<br>Below 50 KBs</b></td></tr>
<tr><td colspan="3" align="right">
<input type="hidden" name="subedit" value="1">
<input type="submit" value="Edit Account" class="submit-blue-1"></td></tr>
</table>
</form>
