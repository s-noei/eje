@extends('layouts.game')
@section('content')
<h3>Create party</h3>
<div width="90%">
{!! $msg ?? '' !!}
<form action="" method="post" enctype="multipart/form-data">
	@csrf
	<table>
		<tr><td>Party name:</td><td><input type="text" name="pName" size="20" class="text" value="{{ old('pName') }}"></td></tr>
		<tr><td>Economical orientation:</td><td>
			<select name="eOrient">
				<option value="0">Choose economical orientation</option>
@foreach (['Far-left', 'Center-left', 'Center', 'Center-right', 'Far-right'] as $o)
				<option value="{{ $o }}">{{ $o }}</option>
@endforeach
			</select>
		</td></tr>
		<tr><td>Social orientation:</td><td>
			<select name="sOrient">
				<option value="0">Choose social orientation</option>
@foreach (['Totalitarian', 'Authoritarian', 'Libertarian', 'Anarchist'] as $o)
				<option value="{{ $o }}">{{ $o }}</option>
@endforeach
			</select>
		</td></tr>
		<tr><td rowspan="2">Avatar</td><td rowspan="1"><input type="file" name="partyLogo" id="partyLogo" /></td></tr>
		<tr><td class="style"><b>Restrictions:<br>.jpg &amp; .jpeg<br>Below 50 KBs</b></td></tr>
		<tr><td>Cost:</td><td><strong>40</strong> <img src="/images/tala.gif" alt="Tala" align="absmiddle"></td></tr>
		<tr><td><input type="submit" name="Create" value="Create" id="buttons"></td></tr>
	</table>
</form>
</div>
@endsection
