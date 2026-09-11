@extends('layouts.game')
@section('content')
@foreach ($errors as $e)<h3 class="errHandle">{{ $e }}</h3>@endforeach
<div style="text-align: justify">
	<a href="{{ $vars->getURL('trans') }}" id="buttons">Back to translation panel</a>
	<br><br>
	<b>Define a new collaborator</b>
	<hr>
	<form action="" method="post">
		@csrf
		Profile ID: <input type="text" name="colid">
		<br>
		<input type="submit" id="submits" name="addcol" value="Add collaborator!">
	</form>
	<br><br>
	<b>Current collaborators for your language</b>
	<hr>
<blockquote>
@foreach ($rows as $i => $row)
		<form action="" method="post">
			@csrf
			{{ $i + 1 }}.
			<a href="{{ $vars->getURL('profile', $row['citID']) }}">{{ $row['name'] }}</a>
			<input type="hidden" name="remid" value="{{ $row['citID'] }}">
			<input type="hidden" name="token" value="{{ md5($row['citID'] . 'colab') }}">
			<input type="submit" name="remcol" value="Remove">
		</form>
@endforeach
</blockquote>
</div>
@endsection
