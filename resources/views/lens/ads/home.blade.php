@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>@include('lens._kv', ['head' => 'Ads status', 'rows' => $stats, 'width' => '400px'])</center>
@endsection
