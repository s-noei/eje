@extends('layouts.game')
@section('content')
<div class="column-double">
@if (!$item)
			<h3 class="errHandle">Invalid item number, redirecting to main page...</h3>
			<script>setTimeout(function(){ location.href = '/index.html'; }, 3000);</script>
@else
			<div style="width: 680px; overflow: hidden;">
				<h3 class="infHandle">
					Item number: {{ $item['itemno'] }}<br>
					Item title: {{ $item['itemtitle'] }}<br>
					Price: {{ $item['itemprice'] }}$<br>
					Payment via Paypal<br>
				</h3>
			</div>
{!! $form !!}
@endif
</div>
@endsection
