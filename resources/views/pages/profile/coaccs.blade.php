{!! $msg ?? '' !!}
@if ($citInfo['puberty'] < 4)
	<h3 class="errHandle">{!! sprintf($lang->getstr('error_puberty', 'msgs'), $lang->getstr('puberty_4'), 100) !!}</h3>
@else
	<blockquote>{!! $lang->getstr('profile_ca_intro', 'profile') !!}<br><br></blockquote>
	{!! $lang->getstr('profile_ca_yours', 'profile') !!}<hr>
@if (count($coaccs) < 1)
	You have not any co-accounts yet
@else
	<table>
@foreach (array_chunk($coaccs, 5) as $chunk)
		<tr>
@foreach ($chunk as $coacc)
			<td style="padding: 15px;text-align: center">
				<a href="{{ $vars->getURL('profile', $coacc['CitizenID']) }}"><img src="{{ $vars->getImgLoc('CitizenAvatar') . $coacc['Avatar'] }}" alt="{{ $coacc['name'] }}" class="Avatar-s"><br>{{ $coacc['name'] }}</a>
			</td>
@endforeach
		</tr>
@endforeach
	</table>
@endif
	<br><br>
	Make a new Co-Account<hr>
	<blockquote>
		<form name="makeca" action="" method="post">
			@csrf
			<table>
				<tr><td>Name:</td><td><input type="text" name="user"></td></tr>
				<tr><td>Password:</td><td><input type="password" name="pass1"></td></tr>
				<tr><td>Retype Password:</td><td><input type="password" name="pass2"></td></tr>
				<tr><td colspan="2">
					<input type="hidden" name="subca" value="1">
					<a href="#" id="buttons" onclick="document.makeca.submit(); return false">Register CA</a>
				</td></tr>
			</table>
			Cost: 5 TALA
		</form>
	</blockquote>
@endif
