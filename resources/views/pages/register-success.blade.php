@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/register.css">@endpush
<div id="reg-success">
	<div class="avatar">
		<img src="/uploads/avatars/citizen/{{ $newCit['Avatar'] }}" class="Avatars">
	</div>
	<div class="title">eJahan Identity</div>
	<div class="name">{{ $newCit['name'] }}</div>
	<div class="location">
		<center style="margin-bottom: 5px">
			Residence
			<hr size="1" color="maroon">
		</center>
		<div class="lochandle">
			<img src="/images/flags/animated/{{ $newCit['Flag'] }}.gif" width="80px" align="absmiddle">
			{{ $newCit['RegionName'] }}
		</div>
	</div>
	<div class="award">
		Award
		<br>
		<div class="award-amount">
			5 <img src="/images/flags/s/{{ $newCit['Flag'] }}.gif" align="absmiddle">
			<br>
			Free PRO Account for 10 days
		</div>
	</div>
	<div class="stamp">
		<img src="/images/pages/register/stamp.png" width="70px">
	</div>
</div>
<div style="display: inline-block; width: 620px">
	<h3 class="infHandle">Congratulations! Your account has been successfully created. A confirmation email has been sent to your email address
	in order to complete your registration.
	Have a good time in eJahan!
	</h3>
</div>
<center><a href="/index.html" id="buttons">Back to main page</a></center>
<script type="text/javascript">
	$(document).ready(function(){ setTimeout('$(".stamp").fadeIn(1000)', 2000); });
</script>
@endsection
