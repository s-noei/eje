@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/forum.css">
	<div class="forum">
@if ($state === 'invalid')
		<div class="cat">Invalid post specified</div>
@elseif ($state === 'notowner')
		<div class="cat">You cannot view the history of a post owned by somebody else</div>
@else
		<div class="cat">
			<a href="{{ $vars->getURL('forum') }}">Forum index</a> &#8594;
			<a href="{{ $vars->getURL('forum', 'board', $post['board_id']) }}">{{ $post['board_name'] }}</a> &#8594;
			<a href="{{ $vars->getURL('forum', 'topic', $post['topic_id']) }}">{{ $post['topic_name'] }}</a> &#8594; Post history
		</div>
@foreach ($hists as $h)
							<div class="post-handle">
								<div class="post-poster">
									<a href="{{ $vars->getURL('profile', $h['post_poster']) }}">
										<b>{{ $h['post_poster_name'] }}</b><br>
										<img src="{{ $vars->getImgLoc('CitizenAvatar') . $h['post_poster_avatar'] }}" class="Avatars" align="absmiddle"><br>
									</a>
									Posts: {{ $h['post_poster_posts'] }}
								</div>
								<div style="height: 20px;">Edited {!! $session->getDiff($h['edit_time']) !!}, reason was {{ $h['edit_reason'] }}</div>
								<hr size="1">
								<div class="post-content-half">{!! $h['post_before'] !!}</div>
								<div class="post-content-half">{!! $h['post_after'] !!}</div>
								<div style="clear: both"></div>
							</div>
@endforeach
@endif
	</div>
@endsection
