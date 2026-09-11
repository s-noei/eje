@extends('layouts.game')
@section('content')
<center><b>Create a new ticket</b></center>
<hr>
@foreach ($errors as $e)<h3 class="errHandle">{{ $e }}</h3>@endforeach
<form action="" method="post" style="text-align: justify" enctype="multipart/form-data">
	@csrf
	<div class="form-title">Department:</div>
	<div class="form-content">{{ $sections[$section] ?? $section }}</div>
	<div class="form-title">Subject:</div>
	<div class="form-content">{{ $subject }}</div>
	<div class="form-title">Enter description:</div>
	<div class="form-content">
		<textarea name="desc" rows="10" cols="60">{{ request('desc') }}</textarea>
		<br>
		<i>To improve the readability of your ticket, please write your description as brief and useful as you can.</i>
	</div>
@if ($section != 'feedback' && $section != 'support')
			<div class="form-title">Proof:</div>
			<div class="form-content"><input type="file" name="proof" size="40"><br />JPEG Images<br />Max. size: 1024KB</div>
			<div style="clear: both"></div>
@endif
	<div class="form-title">Priority:</div>
	<div class="form-content">
		<select name="priority">
@foreach (['Low', 'Medium', 'High', 'Critical'] as $i => $p)
			<option value="{{ $i }}" @if (request('priority') == $i) selected @endif>{{ $p }}</option>
@endforeach
		</select>
	</div>
	<div class="form-title">&nbsp;</div>
	<div class="form-content">
		<input type="hidden" name="subject" value="{{ $subject }}">
		<input type="hidden" name="section" value="{{ $section }}">
		<input type="hidden" name="token" value="{{ md5($section . $subject . $citInfo['CitizenID'] . 'Key4 TiCkEt') }}">
		<input type="submit" name="subnewticket" class="submit-blue-1" value="Submit ticket">
	</div>
	<div style="clear: both"></div>
</form>
<hr>
<b>What should I do?</b><br>
You should select the department and the subject. Then, enter the required details and click on "Submit". That's it! You've sent your ticket to us.
Our moderators will see your ticket and process it.
<br><br>
<b>How can I write a custom subject?</b><br>
Just fill the text box near "Other" caption!
<br><br>
<b>When can I receive a reply?</b><br>
As soon as a moderator reads it!
<br><br>
<b>I want to send an appeal, what shall I do?</b><br>
You can send an appeal for your violations from your profile page!
<br><br>
@endsection
