@extends('layouts.game')
@section('content')
<center><b>Create a new ticket</b></center>
<hr>
<style>
	.ticket-new-dep, .ticket-new-sub { display: inline-block; border: 1px solid gray; vertical-align: top; border-radius: 5px; width: 340px; height: 185px; padding: 2px }
	.ticket-new-dep { margin-bottom: 5px }
	.subjects { display: none }
</style>
<script type="text/javascript">
	var actTar;
	function showSubs(target)
	{
		if (actTar) { $("#sub-"+actTar).hide(); }
		actTar = target;
		$("#subject").hide();
		$("#subject").html($("#sub-"+target).html());
		$("#subject").fadeIn(500);
	}
</script>
<form action="" method="post" style="text-align: justify">
	@csrf
	<div class="ticket-new-dep">
		<center><b>Please select your department</b></center>
		<hr>
@foreach ($sections as $k => $v)
		<label><input type="radio" name="section" value="{{ $k }}" onclick="javascript:showSubs('{{ $k }}')"> {{ $v }}</label><br>
@endforeach
	</div>
	<div class="ticket-new-sub" id="sub-main">
		<center><b>Now select a subject or write a custom subject</b></center>
		<hr>
		<div id="subject"></div>
	</div>
	<center><input type="submit" class="submit-blue-1" name="subnew" value="Next"></center>
</form>
@php $subs = \App\Http\Controllers\ContactController::SUBJECTS; @endphp
@foreach ($sections as $k => $v)
		<div id="sub-{{ $k }}" class="subjects">
@foreach ($subs[$k] ?? [] as $sk => $sv)
			<label><input type="radio" name="subject" value="{{ $sk }}"> {{ $sv }}</label><br>
@endforeach
			Other: <input type="text" name="sub-custom" size="40">
		</div>
@endforeach
<hr>
@endsection
