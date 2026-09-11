@extends('layouts.game')
@section('content')
<div class="column-double">
@if ($notNational)
<h3 class="errHandle"> Sorry, but you are not a national member of this country. Please change your nationality in order to do political actions.</h3>
@else
<h3>Your political statement<hr width=90%></h3>
<h4>If you want to grow in politics, be a candidate for congress or presidency of your country, you must join a party or create one.</h4>
<a href="{{ $vars->getURL('ranking', 'parties', '1', $citInfo['CountryID']) }}" class=button-blue-1>Join a party</a>&nbsp;&nbsp;&nbsp;
<a href="{{ $vars->getURL('create', 'party') }}" class=button-blue-1>Create a party</a>&nbsp;&nbsp;&nbsp;
@endif
</div>
@endsection
