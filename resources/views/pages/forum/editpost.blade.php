@extends('layouts.game')
@section('content')
<link rel="stylesheet" type="text/css" href="/include/css/forum.css">
@foreach ($errors as $e)<h3 class=errHandle>{{ $e }}</h3>@endforeach
	<div class="forum">
@if ($state === 'invalid')
		<div class="cat">Invalid post specified</div>
@elseif ($state === 'notowner')
		<div class="cat">You cannot edit a post owned by somebody else</div>
@else
		<div class="cat">
			<a href="{{ $vars->getURL('forum') }}">Forum index</a> &#8594;
			<a href="{{ $vars->getURL('forum', 'board', $post['board_id']) }}">{{ $post['board_name'] }}</a> &#8594;
			<a href="{{ $vars->getURL('forum', 'topic', $post['topic_id']) }}">{{ $post['topic_name'] }}</a> &#8594; Edit post
		</div>
		<div class="post-body">
			<form action="" method="post" name="editpost"><table>
				@csrf
				<tr><td valign="top">Title:</td><td valign="top"><input type="text" name="post_title" value="{{ $post['post_title'] }}" size="30" maxlength="40"></td></tr>
				<tr><td valign="top">Body:</td><td valign="top"><textarea name="post_body" cols="30" rows="6">{{ $post['post_body'] }}</textarea></td></tr>
				<tr>
					<td colspan="2" align="right" valign="top">
						<input type="hidden" name="target_post" value="{{ $id }}">
						<input type="hidden" name="token" value="{{ $token }}">
						<a href="javascript:void(0)" id="buttons" onclick="document.editpost.submit()">Edit the post</a>
					</td>
				</tr>
			</table></form>
		</div>
@include('pages.forum._tinymce', ['w' => 550])
@endif
	</div>
@endsection
