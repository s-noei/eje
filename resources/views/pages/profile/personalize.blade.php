<form name="edit" action="" method="POST" enctype="multipart/form-data">
@csrf
<table align="left" border="0" cellspacing="0" cellpadding="3">
	<tr>
		<td>Game language:</td>
		<td>
			<select name="lang">
@foreach (['en'=>'English','fa'=>'Persian','hu'=>'Hungarian','pl'=>'Polish','ro'=>'Romanian','rs'=>'Serbian','ru'=>'Russian','es'=>'Spanish','fr'=>'French','it'=>'Italian','tr'=>'Turkish','pt'=>'Portuguese','hr'=>'Croatian','si'=>'Slovenian','me'=>'Macedonian'] as $k => $v)
				<option value="{{ $k }}" {{ ($citInfo['setting_lang'] ?? 'en') === $k ? 'selected' : '' }}>{{ $v }}</option>
@endforeach
			</select>
		</td>
	</tr>
	<tr>
		<td>Background:</td>
		<td>
			<label><input type="radio" name="bg" value="default" checked> Default</label><br>
			<label><input type="radio" name="bg" value="nothing"> No background</label><br>
			<label><input type="radio" name="bg" value="url"> Custom from URL</label>: <input type="text" name="url" maxlength="100" size="40"><br>
			<label><input type="radio" name="bg" value="upload"> Custom from computer</label>: <input type="file" name="file">
		</td>
	</tr>
	<tr>
		<td>Main font:</td>
		<td>
			<select name="font">
				<option value="arial">Arial</option>
				<option value="mssans">Microsoft Sans Serif</option>
				<option value="tahoma">Tahoma</option>
			</select>
		</td>
	</tr>
<tr><td colspan="3" align="right">
<input type="hidden" name="subpers" value="1">
<input type="submit" value="Done" class="submit-blue-0"></td></tr>
</table>
</form>
