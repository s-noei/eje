@extends('layouts.game')
@section('content')
<script type="text/javascript">
	var activeDiv;
	function openSugs(divid)
	{
		if (activeDiv) $("div #"+activeDiv).slideUp(200);
		if (activeDiv != divid) { $("div #"+divid).slideDown(200); activeDiv = divid } else activeDiv = 0
	}
</script>
	<a href="{{ $vars->getURL('trans') }}" id="buttons">Back to translation panel</a>
	<form action="" method="post">
		@csrf
@if ($post == 9)
				Language:
				<select name="lang">
@foreach (['en' => 'English', 'hu' => 'Hungarian', 'fa' => 'Persian', 'pl' => 'Polish', 'ro' => 'Romanian', 'rs' => 'Serbian', 'hr' => 'Croatian', 'de' => 'German', 'fr' => 'French', 'it' => 'Italian', 'es' => 'Spanish', 'tr' => 'Turkish', 'ru' => 'Russian', 'pt' => 'Portuguese', 'si' => 'Slovenian'] as $k => $v)
					<option value="{{ $k }}" @if ($langID == $k) selected @endif>{{ $v }}</option>
@endforeach
				</select>
@endif
		<select name="location">
@foreach ($locations as $loc)
				<option value="{{ $loc['location'] }}" @if ($location == $loc['location']) selected @endif>{{ $loc['location'] }}</option>
@endforeach
		</select>
		<input type="hidden" name="go" value="view">
		<input type="submit" name="viewstrs" value="View" id="submits">
	</form>
<hr>
@if ($viewing)
			<table border="1" bordercolor="black" cellpadding="1px" width="100%">
				<tr><th>Phrase</th><th>English</th><th>Local</th><th>Suggestions</th></tr>
@foreach ($phrases as $phrase)
					<tr>
						<td><a name="phrase-{{ $phrase['strID'] }}"></a>{{ $phrase['phrase'] }}</td>
						<td>{{ $phrase['trans_en'] }}</td>
						<td>
@if ($post >= 8)
									<form action="" method="post">
										@csrf
										<textarea name="trans" cols="30" rows="4">{{ $phrase['local'] }}</textarea>
										<input type="hidden" name="phrase" value="{{ $phrase['phrase'] }}">
										<input type="hidden" name="lang" value="{{ $langID }}">
										<input type="hidden" name="location" value="{{ $location }}">
										<input type="hidden" name="viewstrs" value="1">
										<input type="hidden" name="token" value="{{ md5($phrase['phrase'] . $langID . 'LangUagE') }}">
										<br>
										<input type="submit" id="submits" name="addstr" value="Submit">
									</form>
@else
									{{ $phrase['local'] ?: 'N/A' }}
@endif
						</td>
						<td>
@if ($post < 8 && !$phrase['local'])
									<form action="" method="post">
										@csrf
										<textarea name="trans" cols="30" rows="4">{{ $phrase['mySug'] }}</textarea>
										<input type="hidden" name="phrase" value="{{ $phrase['phrase'] }}">
										<input type="hidden" name="lang" value="{{ $langID }}">
										<input type="hidden" name="location" value="{{ $location }}">
										<input type="hidden" name="viewstrs" value="1">
										<input type="hidden" name="token" value="{{ md5($phrase['phrase'] . $langID . 'LangUagE') }}">
										<br>
										<input type="submit" id="submits" name="addstr" value="Suggest">
									</form>
@elseif ($post < 8)
									CLOSED!
@elseif (count($phrase['sugs']) < 1)
									N/A
@else
											<a href="javascript:void(0)" onclick="javascript:openSugs('phrase_{{ $phrase['strID'] }}')">{{ count($phrase['sugs']) }} suggestion(s)</a>
											<div class="sugs" id="phrase_{{ $phrase['strID'] }}" style="border: 1px solid; display: none; position: absolute; padding: 3px; background: white; width: 300px; overflow-X: hidden">
@foreach ($phrase['sugs'] as $row)
													<form action="" method="post">
														@csrf
														<a href="{{ $vars->getURL('profile', $row['byID']) }}">{{ $row['name'] }}</a> suggests
														<blockquote>{{ $row['str'] }}</blockquote>
														<input type="hidden" name="token" value="{{ md5($row['ID'] . 'LangUagE') }}">
														<input type="hidden" name="sugid" value="{{ $row['ID'] }}">
														<input type="hidden" name="lang" value="{{ $langID }}">
														<input type="hidden" name="location" value="{{ $location }}">
														<input type="hidden" name="viewstrs" value="1">
														<center><input type="submit" name="selsug" value="Select this!"></center>
														<hr size="1">
													</form>
@endforeach
											</div>
@endif
						</td>
					</tr>
@endforeach
			</table>
@endif
@endsection
