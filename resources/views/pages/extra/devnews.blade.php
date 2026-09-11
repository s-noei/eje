@if (count($news) < 1)
		The administration team didn't add any developement news yet.
@endif
	<ul>
@foreach ($news as $dev)
		<li>
			<b>Revision {{ $dev['newsID'] }} {!! $dev['typeLabel'] !!} - </b>{{ $dev['title'] }}@if ($dev['link'])(<a href="{{ $dev['link'] }}" target="_blank">?</a>)@endif
		</li>
@endforeach
	</ul>
@if (($citInfo['CitizenID'] ?? 0) == 1)
		<hr>
		<form action="" method="post">
			@csrf
			Add a developement news:<br>
			Type:
			<select name="type">
				<option value="bugfix">Fixed bug</option>
				<option value="minupd">Minor update</option>
				<option value="majupd">Major update</option>
			</select>
			<br>
			Title: <input type="text" name="title" size="30" maxlength="100"><br>
			Reported by: <input type="text" name="repBy" size="8"><br>
			Link: <input type="text" name="link" size="30" maxlength="60"><br>
			Version: <input type="text" name="version" size="3" value="2.0"><br>
			<input type="submit" name="subadddev" value="Add news">
		</form>
@endif
