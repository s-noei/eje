@extends('layouts.game')
@section('content')
<a href="{{ $vars->getURL('ads') }}" id="buttons">Back to ad center</a>
<hr>
@foreach ($errors as $e)<h3 class="errHandle">{{ $e }}</h3>@endforeach
<center>
	<b>{{ $go == 'edit' ? 'Edit advertisement' : 'Add a new advertisement' }}</b>
	<form action="" method="post" enctype="multipart/form-data">
		@csrf
		<div id="add-ad">
			<div class="title">Ad title:</div>
			<div class="content"><input type="text" name="title" size="35" value="{{ $form['title'] }}"></div>
			<div style="clear: both"></div>
			<div class="title">Ad content:</div>
			<div class="content"><textarea name="content" cols="27" rows="4">{{ $form['content'] }}</textarea></div>
			<div style="clear: both"></div>
			<div class="title">Ad picture:</div>
			<div class="content"><input type="file" name="adpic" id="file"></div>
			<div style="clear: both"></div>
			<div class="title">Target link:</div>
			<div class="content">{{ url('/') }}/<input type="text" name="link" size="35" value="{{ $form['link'] }}"></div>
			<div style="clear: both"></div>
			<div class="title">In country:</div>
			<div class="content">
				<select name="country">
					<option value="0">All around the world</option>
@foreach ($allCountries as $coun)
					<option value="{{ $coun['CountryID'] }}" @if ($coun['CountryID'] == $form['country']) selected @endif>{{ $coun['cName'] }}</option>
@endforeach
				</select>
			</div>
			<div style="clear: both"></div>
			<div class="title">Right to Left:</div>
			<div class="content"><input type="checkbox" name="rtl" @if ($form['rtl']) checked @endif> (Persian/Arabic ad)</div>
			<div style="clear: both">1 TALA = 5000 view</div>
			<div class="title">Budget ad:</div>
			<div class="content"><input type="text" name="price" size="5" value="{{ $form['price'] ?: '0' }}"> TALA</div>
			<div style="clear: both">1 TALA = 5000 view</div>
			<input type="hidden" name="token" value="{{ $token }}">
			<input type="hidden" name="edit" value="{{ $id }}">
			<input type="submit" name="submitad" value="{{ $go == 'edit' ? 'Edit ad' : 'Submit new ad' }}" id="submits">
		</div>
	</form>
</center>
@endsection
