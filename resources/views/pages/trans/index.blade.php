@extends('layouts.game')
@section('content')
	<strong>News</strong>
	<hr>
	<table border="1" bordercolor="black" width="100%">
		<tr><th>News</th><th>Time</th></tr>
@foreach ($news as $n)
		<tr><td>{!! nl2br(e($n['body'])) !!}</td><td>{!! $session->getDiff($n['timestamp']) !!}</td></tr>
@endforeach
	</table>
@if ($post == 9)
			Add a news:
			<form action="" method="post">
				@csrf
				<textarea name="body" cols="30" rows="4"></textarea><br>
				<input type="submit" name="addnews" value="Submit!" id="submits">
			</form>
@endif
	<hr>
	<a href="{{ $vars->getURL('trans', 'view') }}" id="buttons">View strings</a>
	<a href="{{ $vars->getURL('trans', 'team') }}" id="buttons">Define collaborators</a>
	<hr>
	<b>Statistics:</b>
	<hr>
<div style="text-align: justify">
@foreach ($stats as $k => $v)
	<b>{{ $k }}:</b> {{ $v }}<br>
@endforeach
</div>
@endsection
