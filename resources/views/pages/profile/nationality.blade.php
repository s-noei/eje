@if (!empty($blocked))
	<h3 class="errHandle">{{ $blocked }}</h3>
@else
<blockquote>
{!! $msg ?? '' !!}
@if ($same)
	<h3 class="errHandle">Your nationality is as same as your living country, so you don't need to change that.</h3>
@else
	<form name="nationality" action="" method="post">
	@csrf
	Change nationality to <img src="{{ $vars->getImgLoc('CountryFlag') . $cit['CountryFlag'] }}.gif" alt="{{ $cit['cName'] }}" class="Flag-xs" align="absmiddle"><br>
	Cost: {{ $nFee }} <img src="/images/flags/s/ejahan.gif" align="absmiddle"><br>
	You have: {{ $money }} <img src="/images/flags/s/ejahan.gif" align="absmiddle"><br>
@if ($money < $nFee)
	<h3 class="errHandle">You have not enough Tala to change your nationality.</h3>
@elseif ($embargo)
	<h3 class="errHandle">You cannot change from your current nationality because of a travel embargo.</h3>
@else
	Click on the button below to change your nationality<br>
	<input type="hidden" name="nationality" value="change">
	<a href="#" onclick="document.nationality.submit()" id="buttons">Change my nationality</a>
@endif
	</form>
@endif
</blockquote>
<b>Change nationality with a request</b><hr>
<blockquote>
@if ($hasRequest)
	<center><b>NOTE: You have a pending request in this country.</b></center>
@endif
	<form action="" method="post">
		@csrf
		Why do you want to change your nationality?<br>
		<textarea cols="50" rows="5" name="reason">{{ $myreason }}</textarea><br>
		<input type="submit" name="subrequestNC" value="Request!" class="button-blue-1">
	</form>
</blockquote>
@endif
