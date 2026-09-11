@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/forum.css">
	<div class="forum">
					<div class="cat">
						<a href="{{ $vars->getURL('forum') }}">Forum index</a>
						&#8594;
						<a href="{{ $vars->getURL('forum', 'board', $topic['board_id']) }}">{{ $topic['board_name'] }}</a>
						 &#8594; {{ $topic['topic_name'] }}
					</div>
@if ($cant)
					<div class="empty-notify">Invalid topic or you have no permission to view this topic</div>
@else
@if (count($posts) < 1)
						<div class="empty-notify">There are no posts in this topic.</div>
@endif
@foreach ($posts as $post)
						<div class="post-handle">
							<a name="post{{ $post['post_id'] }}"></a>
							<div class="post-poster">
								<a href="{{ $vars->getURL('profile', $post['post_poster']) }}">
									<b>{{ $post['post_poster_name'] }}</b><br>
									<img src="{{ $vars->getImgLoc('CitizenAvatar') . $post['post_poster_avatar'] }}" class="Avatars" align="absmiddle"><br>
								</a>
@if ($post['post_poster_level'] == $levels['forummod'])<b>Moderator</b>@endif
@if ($post['post_poster_level'] == $levels['supermod'])<b>Super moderator</b>@endif
@if ($post['post_poster_level'] == $levels['admin'])<b>Administrator</b>@endif
								Posts: {{ $post['post_poster_posts'] }}
@if (($post['post_poster'] == $citInfo['CitizenID'] || $canMod) && !$post['post_removed'])
									<br>
									<a href="{{ $vars->getURL('forum', 'editpost', $post['post_id']) }}" class="cmdEdit">Edit</a>
									<br>
									<a href="{{ $vars->getURL('forum', 'removepost', $post['post_id']) }}" class="cmdRemove" onclick="return confirm('Remove this post?')">Remove</a>
@elseif ($canMod)
									<b style="color: red">DELETED!</b>
@endif
							</div>
							<div class="post-content">
								<div style="height: 20px;">
									<div style="float: right">Posted {!! $session->getDiff($post['post_time']) !!}</div>
									<a href="#post{{ $post['post_id'] }}" class="forum">{{ $post['post_title'] }}</a>
								</div>
								<hr size=1>
								{!! $post['post_body'] !!}
@if ($post['post_edit_time'])
								<hr>
								Last edited {!! $session->getDiff($post['post_edit_time']) !!}
								by <a href="{{ $vars->getURL('profile', $post['post_edit_by']) }}">{{ $post['eName'] }}</a>
@if ($post['post_poster'] == $citInfo['CitizenID'] || $canMod)
									(<a href="{{ $vars->getURL('forum', 'history', $post['post_id']) }}">History</a>)
@endif
@endif
							</div>
							<div style="clear: both"></div>
						</div>
@endforeach
@include('pages.forum._pager', ['go' => 'topic', 'pid' => $topic['topic_id']])
@if ($canReply)
		<div class="post-body">
			<center><b>Post reply @if ($topic['topic_locked'])(Locked topic)@endif</b><hr size="1"></center>
			<form action="" method="post" name="reply">
				@csrf
				<textarea name="post-body"></textarea>
                <br />
                <center><input type="submit" name="subpost" class="submit-blue-1" value="Post reply" /></center>
			</form>
		</div>
		<div style="clear: both"></div>
@include('pages.forum._tinymce')
@endif
@endif
	</div>
@endsection
