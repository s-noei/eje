@extends('layouts.game')
@section('content')
@include('pages.media._np-head')
	<div class="column-double">
@if ($do === 'edit')
<form action="" method=post id=forms enctype="multipart/form-data">
	@csrf
	<h3>Edit newspaper details</h3>
	<table>
		<tr><td>Name:</td><td><input type=text size=20 name=npName class=text value="{{ $row['npName'] }}"></td></tr>
		<tr>
			<td>Country:</td>
			<td>
				<select name="npCountry">
@foreach ($allCountries as $coun)
						<option value="{{ $coun['CountryID'] }}" @if ($coun['CountryID'] == $row['CountryID']) selected @endif>{{ $coun['cName'] }}</option>
@endforeach
				</select>
			</td>
		</tr>
		<tr><td rowspan="2">Avatar</td><td rowspan="1"><img src="{{ $vars->getImgLoc('npAvatar') . '/' . $row['Avatar'] }}" class="Avatar-s"> <input type="file" name="npAvatar" id="npAvatar" /></td></tr>
		<tr><td class="style"><b>Restrictions:<br>.jpg &amp; .jpeg<br>Below 50 KBs</b></td></tr>
		<tr><td><input type=submit name=editOk value="Save changes" id=buttons></td></tr>
	</table>
</form>
@else
		<div class="column-headcol">
				<img src="{{ $vars->getImgLoc('CountryFlag') . $row['Flag'] . '.gif' }}" class="Avatars" />
		</div>
		<div class="column-details">
						{{ sprintf($lang->getstr('np_located_in', 'np'), '') }}
						<a href="{{ $vars->getURL('country', $row['CountryID']) }}">{{ $row['cName'] }}</a>
						<br><br>
						<b>{{ $lang->getstr('np_articles', 'np') }}</b>
						<hr>
@if (count($articles) < 1)
						No articles yet
@endif
@foreach ($articles as $np_art)
@if (!$np_art['Deleted'] || $session->isAdmin())
								<div id="articles">
									<div class="votes">{{ $np_art['aVotes'] }}</div>
									<div style="float: left; padding-left: 5px; width: 350px; overflow-X: hidden">
										<a href="{{ $vars->getURL('article', $np_art['aID']) }}">{{ strip_tags($np_art['aTitle']) }}</a>
										<br>
										<sup>{!! $session->getDiff($np_art['timestamp']) !!}</sup>
@if ($np_art['Deleted'])
											<font style="color:red"><b><sup>DELETED</sup></b></font>
@elseif ($np_art['isDraft'])
											<font style="color:red"><b><sup>DRAFT</sup></b></font>
@endif
										<br>
										{!! nl2br(e($vars->lenTrim(stripslashes(strip_tags($vars->utfcorrect($np_art['aContent']))), '200', '', '0'))) !!}
									</div>
								</div>
								<hr>
@endif
@endforeach
		</div>
		<div style="clear: both">&nbsp;</div>
		<hr>
		<center>
@if ($page > 1)
		<a href="{{ $vars->getURL('newspaper', $row['npID'], $page - 1) }}" id="buttons">&lt; Back</a>
@endif
		<span id="buttons">{{ $page }}</span>
@if ($npNum > $start + 5)
		<a href="{{ $vars->getURL('newspaper', $row['npID'], $page + 1) }}" id="buttons">Next &gt;</a>
@endif
	</center>
@endif
	</div>
@endsection
