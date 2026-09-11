@extends('layouts.game')
@section('content')
<div class="column-double">
@if ($logged)
	<center><b>Contact eJahan Administration team</b></center>
	<hr>
	<blockquote>
		<img src="/images/contact/new.png" align="absmiddle" width="64px">
		<a href="{{ $vars->getURL('contact', 'new') }}">Create a new ticket</a><br>
		<font style="size: 8pt; padding-left: 10px">Create a new ticket for eJahan team. You can make a report, ask a question, request to support eJahan team or send your feedback.</font>
		<hr size="1">
		<img src="/images/contact/view.png" align="absmiddle" width="64px">
		<a href="{{ $vars->getURL('contact', 'track') }}">View my tickets</a><br>
		<font style="size: 8pt; padding-left: 10px">View your sent tickets and the answer of eJahan team</font>
	</blockquote>
	<hr>
@else
	<center><b>Contact eJahan Administration team</b></center>
	<hr>
	<blockquote>Please <a href="{{ $vars->getURL('login') }}">log in</a> to contact the eJahan team.</blockquote>
@endif
</div>
@endsection
