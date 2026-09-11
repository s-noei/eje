@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
{!! $msg !!}
	<form action="" method="post" enctype="multipart/form-data">
		@csrf
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Specifications</th></tr>
			<tr><td>Title</td><td><input type="text" name="title" size="50" value=""></td></tr>
			<tr><td>Content</td><td><textarea name="content" cols="30" rows="5"></textarea></td></tr>
			<tr><td>Image</td><td><input type="file" name="pic" size="35" value=""></td></tr>
			<tr><td>Link</td><td><input type="text" name="link" size="50" value=""><br />Don't forget to put <strong>http://</strong></td></tr>
		</table>
        &nbsp;
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Placement</th></tr>
			<tr><td>Location</td><td><select name="location"><option value="top">Top</option><option value="left">Left</option></select></td></tr>
			<tr><td>Country</td><td><select name="country"><option value="0">World</option>@foreach ($countries as $c)<option value="{{ $c['CountryID'] }}">{{ $c['cName'] }}</option>@endforeach</select></td></tr>
			<tr><td>Language</td><td><select name="language"><option value="">Everyone</option>@foreach (['en' => 'English', 'fa' => 'Persian', 'hu' => 'Hungarian', 'ro' => 'Romanian', 'pl' => 'Polish', 'rs' => 'Serbian', 'hr' => 'Croatian', 'si' => 'Slovenian', 'es' => 'Spanish', 'fr' => 'French', 'it' => 'Italian', 'tr' => 'Turkish', 'pt' => 'Portuguese', 'ru' => 'Russian'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></td></tr>
        </table>
        &nbsp;
		<table id="table" bordercolor="black" border="1">
			<tr><th colspan="2">Pricing</th></tr>
			<tr><td>Credit\Expire</td><td><input type="text" name="credit" size="6" value=""></td></tr>
			<tr><td>Pricing type</td><td><select name="costtype"><option value="0">View</option><option value="1">Click</option><option value="2">Both</option><option value="3">Unlimited daily</option></select></td></tr>
		</table><br />
		<input type="submit" name="subadd" value="Submit">
	</form>
</center>
@endsection
