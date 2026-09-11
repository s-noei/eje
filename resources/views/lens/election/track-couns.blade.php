@extends('lens.layout')
@section('content')
<center>
	Select the country
	<table id="table" bordercolor="black" border="1">
@foreach (array_chunk($countries, 5) as $chunk)
		<tr>
@foreach ($chunk as $coun)
			<td style="text-align: center; width: 110px">
				<a href="{{ $lensUrl('election', 'track', $eid . '_' . $coun['CountryID']) }}">
					<img src="/images/flags/l/{{ $coun['Flag'] }}.gif" width="64px" border="0"><br>{{ $coun['cName'] }}
				</a>
			</td>
@endforeach
		</tr>
@endforeach
	</table>
</center>
@endsection
