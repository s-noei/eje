@extends('layouts.game')
@section('content')
@include('pages.media._np-head')
	<div class="column-double">
@if ($pollForm)
			<script type="text/javascript">
				function viewDiv(target) { $("#q_"+target).fadeIn('fast') }
			</script>
			<form action="" method="post">
				@csrf
				You can post up to 5 questions in a single article.
@for ($i = 1; $i <= 5; $i++)
					<div id="q_{{ $i }}" style="border: 1px solid; -moz-border-radius: 5px; padding: 2px; margin-bottom: 2px{{ $i > 1 ? '; display: none' : '' }}">
						Question {{ $i }}:
						<input type="text" name="q_{{ $i }}" size="60">
						<blockquote>
@for ($j = 1; $j <= 10; $j++)
								<div id="q_{{ $i }}_{{ $j }}" style="padding: 2px 0{{ $j > 1 ? '; display: none' : '' }}">
									Option {{ $j }}:
									<input type="text" name="q_{{ $i }}_{{ $j }}" size="60">
@if ($j != 10)
											<a href="javascript:void(0)" onclick="javascript:viewDiv('{{ $i }}_{{ $j + 1 }}')">Add another option</a>
@endif
								</div>
@endfor
						</blockquote>
@if ($i != 5)
								<a href="javascript:void(0)" onclick="javascript:viewDiv('{{ $i + 1 }}')">Add another question</a>
@endif
					</div>
@endfor
				<input type="hidden" name="aTitle" value="{{ request('aTitle') }}">
				<input type="hidden" name="aContent" value="{{ request('aContent') }}">
				<input type="hidden" name="aPic" value="{{ request('aPic') }}">
				<input type="hidden" name="aSound" value="{{ request('aSound') }}">
				<input type="hidden" name="rtlArt" value="{{ request('rtlArt') }}">
				<input type="submit" name="Poll" class="submit-blue-1" value="Finished!">
				<input type="submit" name="Create" class="submit-blue-1" value="Publish without poll">
			</form>
@else
@include('pages.media._tinymce')
		<form action="" method=post>
			@csrf
			<table border="0" width="80%" id="table1">
				<tr><td width="104" class=tdstyle>Title:</td><td class=tdstyle><input type="text" name="aTitle" value="{{ request('aTitle') }}" size="50"></td></tr>
				<tr><td width="104" class=tdstyle valign=top>Body:</td><td class=tdstyle><textarea rows="11" name="aContent" cols="70">{{ request('aContent') }}</textarea></td></tr>
				<tr><td colspan="2" class=tdstyle><input type="checkbox" name="rtlArt"> The direction of this article is RTL (Persian or Arabic language)</td></tr>
				<tr><td colspan="2" class=tdstyle><input type="checkbox" name="postPoll"> Also create a poll for this article</td></tr>
				<tr><td width="104" class=tdstyle>Picture Location:</td><td class=tdstyle><input type="text" name="aPic" value="{{ request('aPic') }}" size="50"></td></tr>
				<tr><td width="104" class=tdstyle>Sound File Location:</td><td class=tdstyle><input type="text" name="aSound" value="{{ request('aSound') }}" size="50"></td></tr>
				<tr>
					<td colspan="2" class=tdstyle>
						<input type="submit" class="submit-blue-1" value="Create article!" name="Create">
						<input type="submit" class="submit-blue-1" value="Save as a draft" name="Save">
						<p>Note that you must create articles were are about the game, any abuse articles called &quot;spam&quot; and will be deleted.<br>
						<b>Also, the correct picture file must be .jpg or .gif and the correct sound file must be .mp3 or .wav or .mid</b>
					</td>
				</tr>
			</table>
		</form>
@endif
	</div>
@endsection
