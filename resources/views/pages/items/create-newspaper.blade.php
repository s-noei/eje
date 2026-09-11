@extends('layouts.game')
@section('content')
<h3>Create newspaper</h3>
<div width="90%">
{!! $msg ?? '' !!}
<form action="" method="post" enctype="multipart/form-data">
	@csrf
	<table>
		<tr><td>Name:</td><td><input type="text" name="aTitle" size="20" class="text" value="{{ old('aTitle') }}"></td></tr>
		<tr><td rowspan="2">Avatar</td><td rowspan="1"><input type="file" name="npAvatar" id="npAvatar" /></td></tr>
		<tr><td class="style"><b>Restrictions:<br>.jpg &amp; .jpeg<br>Below 50 KBs</b></td></tr>
		<tr><td>Cost:</td><td><strong>2</strong> <img src="/images/tala.gif" alt="Tala" align="absmiddle"></td></tr>
		<tr><td><input type="submit" name="Create" value="Create" id="buttons"></td></tr>
	</table>
</form>
</div>
@endsection
