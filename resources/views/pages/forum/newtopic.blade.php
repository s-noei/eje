@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/forum.css">
@foreach ($errors as $e)<h3 class=errHandle>{{ $e }}</h3>@endforeach
	<div class="forum">
			<div class="cat">
				<a href="{{ $vars->getURL('forum') }}">Forum index</a>
				&#8594;
				<a href="{{ $vars->getURL('forum', 'board', $board['board_id'] ?? 0) }}">{{ $board['board_name'] ?? '' }}</a>
				 &#8594; Post a new topic
			</div>
@if ($cant)
					<div class="empty-notify">Invalid board or you have no permission to post a new topic in this board</div>
@else
					<div class="post-body">
							<form action="" method="post" name="newtopic"><table>
								@csrf
								<tr><td valign="top">Title:</td><td valign="top"><input type="text" name="post_title" size="30" maxlength="40"></td></tr>
								<tr><td valign="top">Body:</td><td valign="top"><textarea name="post_body" cols="30" rows="6"></textarea></td></tr>
								<tr>
									<td colspan="2" align="right" valign="top">
										<input type="hidden" name="target_board" value="{{ $id }}">
										<input type="hidden" name="token" value="{{ $token }}">
										<a href="javascript:void(0)" id="buttons" onclick="document.newtopic.submit()">Post new topic</a>
									</td>
								</tr>
							</table></form>
					</div>
@include('pages.forum._tinymce', ['w' => 600])
@endif
	</div>
@endsection
