@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/forum.css">
	<div class="forum">
@if (count($cats) < 1)
					<div class="cat">There are no forums yet.</div>
@endif
@foreach ($cats as $cat)
						<div class="cat">{{ $cat['cat_name'] }}</div>
						<div class="board-head-title">Title</div>
						<div class="board-head-count">Topics</div>
						<div class="board-head-count">Posts</div>
						<div class="board-head-last">Last post</div>
						<div style="clear: both"></div>
@foreach ($cat['boards'] as $board)
						<div class="board-img"><img src="/images/forum/board.gif" align="absmiddle">&nbsp;</div>
						<div class="board-title">
							<a href="{{ $vars->getURL('forum', 'board', $board['board_id']) }}" class="forum">
								{{ $board['board_name'] }}
								@if ($board['isNew'])<sup style="color: red; font-weight: bold">NEW</sup>@endif
							</a>
							<br>
							{{ $board['board_desc'] }}
						</div>
						<div class="board-count">{{ $board['private'] ? '--' : $board['board_count_topic'] }}</div>
						<div class="board-count">{{ $board['private'] ? '--' : $board['board_count_post'] }}</div>
						<div class="board-last">
@if ($board['private'])
									<center style="padding-top: 20px">Private board</center>
@elseif ($board['last'])
											<a href="{{ $vars->getURL('forum', 'topic', $board['last']['topic_id']) }}">{{ $board['last']['topic_name'] }}</a>
											<br>
											by <a href="{{ $vars->getURL('profile', $board['last']['post_poster']) }}">{{ $board['last']['post_poster_name'] }}</a>
											<br>
											Posted {!! $session->getDiff($board['last']['post_time']) !!}
@else
									<center style="padding-top: 20px">No post</center>
@endif
						</div>
						<div style="clear: both"></div>
@endforeach
@endforeach
					<hr>
					<div class="cat">Forum statistics</div>
					<div style="float: left; width: 50%">
						<b>Top 10 posters</b><hr>
@foreach ($topPosters as $i => $cit)
								{{ $i + 1 }}. <a href="{{ $vars->getURL('profile', $cit['CitizenID']) }}">{{ $cit['name'] }}</a>: {{ $cit['forum_posts'] }} posts<br>
@endforeach
					</div>
					<div style="float: left; width: 50%">
						<b>Latest updated topics</b><hr>
@foreach ($latest as $i => $post)
								{{ $i + 1 }}. <a href="{{ $vars->getURL('forum', 'topic', $post['topic_id']) }}">{{ $post['post_title'] }}</a>
								by <a href="{{ $vars->getURL('profile', $post['post_poster']) }}">{{ $post['post_poster_name'] }}</a><br>
@endforeach
					</div>
			<div style="clear:both"><hr></div>
	</div>
@endsection
