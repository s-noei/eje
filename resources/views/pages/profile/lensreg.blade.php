@php $accs = ['', 'Normal user', 'Forum moderator', 'Forum Administrator', 'Technical moderator', 'Local moderator', 'Police', 'Super moderator', 'Guard', 'Administrator']; @endphp
@if ($err)<h3 class="errHandle">{!! $err !!}</h3>@endif
@if ($citreg['status'] == 1 && !$err)
	<h3 class="infHandle">Your registration info is submitted. Please wait until a super moderator checks your info.<br>The result will be sent via a private message.</h3>
@else
You have been invited to register in eJahan Lens, eJahan's moderation panel. Fill the form below to complete your registration.
<form action="" method="post" enctype="multipart/form-data">
	@csrf
	<table>
		<tr><td>Invited by:</td><td>{{ $citreg['invBy'] }}</td></tr>
		<tr><td>Access:</td><td>{{ $accs[$citreg['access']] ?? '' }}</td></tr>
		<tr><td>First name:</td><td><input type="text" name="fName" value="{{ old('fName', $citreg['fName']) }}"></td></tr>
		<tr><td>Last name:</td><td><input type="text" name="lName" value="{{ old('lName', $citreg['lName']) }}"></td></tr>
		<tr><td>Language (except English):</td><td>
			<select name="lang">
@foreach (['' => 'Nothing/Other', 'hr' => 'Croatian', 'fr' => 'French', 'de' => 'German', 'hu' => 'Hungarian', 'fa' => 'Persian', 'pl' => 'Polish', 'ro' => 'Romanian', 'ru' => 'Russian', 'rs' => 'Serbian', 'es' => 'Spanish', 'tr' => 'Turkish'] as $k => $v)
				<option value="{{ $k }}" {{ old('lang', $citreg['language']) === $k ? 'selected' : '' }}>{{ $v }}</option>
@endforeach
			</select>
		</td></tr>
		<tr><td>Birth date:</td><td>
			<select name="day"><option value="">Day</option>@for ($i = 1; $i <= 31; $i++)<option value="{{ $i }}" {{ old('day', $citreg['birth_day']) == $i ? 'selected' : '' }}>{{ $i }}</option>@endfor</select>
			<select name="month"><option value="">Month</option>@for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}" {{ old('month', $citreg['birth_month']) == $i ? 'selected' : '' }}>{{ $i }}</option>@endfor</select>
			<select name="year"><option value="">Year</option>@for ($i = 1996; $i >= 1940; $i--)<option value="{{ $i }}" {{ old('year', $citreg['birth_year']) == $i ? 'selected' : '' }}>{{ $i }}</option>@endfor</select>
		</td></tr>
		<tr><td>Proof:<br><span style="font-size: smaller; font-style: italic">Scanned picture of your ID Card or passport<br>to prove your information</span></td>
		<td><img src="/uploads/proofs/lensreg/{{ $citreg['proof'] }}?{{ time() }}" class="Avatar-s" align="absmiddle"><input type="file" name="proof" id="file"></td></tr>
		<tr><td>&nbsp;</td><td>
			<input type="submit" name="sublensreg" value="Register!">
			<input type="reset" name="reset" value="Reset form">
			<br>
			By clicking "Register" button you claim that you approve
			<a href="javascript:void(0)" onclick="javascript:window.open('/lens/manifesto.htm','map_ejahan','status=yes,scrollbars=yes,toolbar=no,menubar=no,location=no ,width=560px,height=550px')">eJahan Moderators Manifesto</a>.
			We recommend you to read this manifesto before you continue.
		</td></tr>
	</table>
</form>
@endif
