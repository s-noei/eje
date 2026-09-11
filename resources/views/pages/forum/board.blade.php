@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/forum.css">
	<div class="forum">
					<div class="cat">
						<a href="{{ $vars->getURL('forum') }}">Forum index</a> &#8594; {{ $board['board_name'] }}
					</div>
@if ($cant)
					<div class="empty-notify">Invalid board or you have no permission to view this board</div>
@else
@if ($ticketing)
							<center style="color: red; padding: 2px; border: 1px solid red; margin-top: 2px">You'll see your started topics in this board</center>
@elseif ($board['board_special_view'] == 'cg')
							<center style="color: red; padding: 2px; border: 1px solid red; margin-top: 2px">You'll see topics started by your country's congress members in this board</center>
@endif
					<div class="board-head-title">Title</div>
					<div class="board-head-count">Views</div>
					<div class="board-head-count">Replies</div>
					<div class="board-head-last">Last post</div>
					<div style="clear: both"></div>
@if (count($topics) < 1)
						<div class="empty-notify">There are no topics in this board.</div>
@endif
@foreach ($topics as $topic)
						<div class="board-img">
							<img src="/images/forum/board{{ $topic['topic_sticky'] ? '-sticky' : '' }}{{ $topic['topic_locked'] ? '-locked' : '' }}.gif" align="absmiddle">&nbsp;
						</div>
						<div class="board-title">
							<a href="{{ $vars->getURL('forum', 'topic', $topic['topic_id']) }}" class="forum">
								@if ($topic['topic_sticky'])<b>Important: </b>@endif{{ $topic['topic_name'] }}
							</a>
							@if ($topic['isNew'])<sup style="color: red; font-weight: bold">NEW</sup>@endif
							<br>
							Posted by <a href="{{ $vars->getURL('profile', $topic['topic_starter']) }}">{{ $topic['topic_starter_name'] }}</a>
						</div>
						<div class="board-count">{{ $topic['topic_views'] }}</div>
						<div class="board-count">{{ $topic['topic_count_post'] - 1 }}</div>
						<div class="board-last">
@if ($topic['last'])
									Last post posted {!! $session->getDiff($topic['last']['post_time']) !!}
									by <a href="{{ $vars->getURL('profile', $topic['last']['post_poster']) }}">{{ $topic['last']['post_poster_name'] }}</a>
@endif
@if ($canMod)
@php
	$lockname = $topic['topic_locked'] ? 'unlock' : 'lock';
	$stname = $topic['topic_sticky'] ? 'unsticky' : 'sticky';
	$rmname = $topic['topic_removed'] ? 'undelete' : 'delete';
@endphp
@foreach ([$lockname, $stname, $rmname] as $act)
							<div style="float: left">
								<form action="" name="{{ $act . $topic['topic_id'] }}" method="post">
									@csrf
									<input type="hidden" name="sub{{ $act }}" value="{{ $topic['topic_id'] }}">
									<a href="javascript:void(0)" onclick="document.{{ $act . $topic['topic_id'] }}.submit()">
										<img src="/images/forum/mod-{{ $act }}.gif" title="{{ $act }}" alt="{{ $act }}" class="modbut">
									</a>
								</form>
							</div>
@endforeach
@endif
						</div>
						<div style="clear: both"></div>
@endforeach
@include('pages.forum._pager', ['go' => 'board', 'pid' => $board['board_id']])
@endif
	</div>
@if (!$cant && $canPost)
			<center><a href="{{ $vars->getURL('forum', 'newtopic', $board['board_id']) }}" class="Button">New topic</a></center>
@endif
@endsection
