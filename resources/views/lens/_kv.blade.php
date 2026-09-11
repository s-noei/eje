{{-- $head, $rows: [[k, v(html)]] --}}
<table id="table" bordercolor="black" border="1" @isset($width) width="{{ $width }}" @endisset>
	<tr><th colspan="2" align="center">{{ $head }}</th></tr>
@foreach ($rows as [$k, $v])
	<tr><td>{!! $k !!}</td><td>{!! $v !!}</td></tr>
@endforeach
</table>
&nbsp;
