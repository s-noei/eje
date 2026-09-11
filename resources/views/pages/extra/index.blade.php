@extends('layouts.game')
@section('content')
<center><b>eJahan Extra</b></center>
<hr size="1">
<center>
	<a href="{{ $vars->getURL('extra', 'credits') }}" class="button-blue-1">Credits</a>
	<a href="{{ $vars->getURL('extra', 'history') }}" class="button-blue-1">Version history</a>
	<a href="{{ $vars->getURL('extra', 'emblems') }}" class="button-blue-1">Emblems and banners</a>
	<a href="{{ $vars->getURL('extra', 'devnews') }}" class="button-blue-1">Developement news</a>
</center>
<hr size="1">
@include('pages.extra.' . $go)
@endsection
