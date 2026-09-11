@extends('lens.layout')
@section('content')
@include('lens.tickets._navbar')
<blockquote style="border: 1px solid; padding: 20px; margin: 0 200px 0 200px">
	<div style="text-align: justify">
		Violation submitted by <a href="{{ $vars->getURL('profile', $tInfo['byID']) }}">{{ $tInfo['byName'] }}</a>
		against <a href="{{ $vars->getURL('profile', $tInfo['citID']) }}">{{ $tInfo['name'] }}</a><br>
		Title: {{ $tInfo['Title'] }}
		<blockquote>{!! $tInfo['Description'] !!}</blockquote>
	</div>
	<hr>
@if ($tInfo['appeal'])
	<div style="text-align: justify">
		<a href="{{ $vars->getURL('profile', $tInfo['citID']) }}">{{ $tInfo['name'] }}</a>'s appeal:
		<blockquote>{!! nl2br(e($tInfo['appeal'])) !!}</blockquote>
	</div>
	<hr>
@endif
@if ($tInfo['appeal_reply'])
	<div style="text-align: justify">Appeal reply:<blockquote>{{ $tInfo['appeal_reply'] }}</blockquote></div>
@endif
	<form action="" method="post">
		@csrf
		<b>Reply to this appeal</b><br>
@if ($tInfo['appeal'] && !$tInfo['appeal_reply'] && $tInfo['Active'])
		<textarea name="appreply" cols="40" rows="4"></textarea><br>
		<input type="submit" name="reply" value="Submit reply"><br>
@elseif (!$tInfo['appeal'])
		There's no appeal sent to this violation, so reply is not possible.<br>
@elseif ($tInfo['appeal_reply'])
		The the appeal was replied, so another reply is not possible.<br>
@elseif (!$tInfo['Active'])
		The violation is deactive, so reply is not possible.<br>
@endif
@if ($tInfo['Active'])
		<input type="submit" name="deactive" value="Deactive the forfeit">
@endif
@if ($banned)
		<input type="submit" name="unban" value="Unban this citizen">
@endif
	</form>
</blockquote>
<hr>
<a href="{{ $lensUrl('tickets', 'appeal') }}">Back to tickets</a>
@endsection
