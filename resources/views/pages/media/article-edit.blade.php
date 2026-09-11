@extends('layouts.game')
@section('content')
@include('pages.media._np-head')
	<div class="column-double">
@include('pages.media._tinymce')
			<a href="{{ $vars->getURL('article', $art['aID']) }}" class="button-blue-1">Back to article</a>
			<form action="" method=post onsubmit="return confirm('Are you sure you have completed editing?')">
			@csrf
			<input type=hidden name=eArtDone value="{{ $art['aID'] }}">
			<table border="0" width="80%" id="table1">
				<tr><td width="104" class=tdstyle>Title:</td><td class=tdstyle><input type="text" class="style" name="aTitle" value="{{ $art['aTitle'] }}" size="50"></td></tr>
				<tr>
					<td width="104" class=tdstyle valign=top>Body:</td>
					<td class=tdstyle>
						<textarea rows="11" class=textarea name="aContent" cols="70">{{ stripslashes($art['aContent']) }}</textarea>
@if ($art['isDraft'])
								<input type="checkbox" name="Draft" value="1" checked> Save as draft
@endif
					</td>
				</tr>
				<tr>
					<td colspan="2" class=tdstyle>
						<input type="hidden" name="token" value="{{ $editToken }}">
						<input type="submit" class="submit-blue-1" value="Finished editing!" name="B1">
						<p>Note that you must write about the game in your articles, we will remove all spams.
					</td>
				</tr>
			</table>
			</form>
	</div>
@endsection
