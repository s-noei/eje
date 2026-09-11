@extends('layouts.game')
@section('content')
<div class="column-double">
@if ($ok)
<h3 class="infHandle">
	Thanks for your purchase!<br>
	We have received your order and have started processing it. We will complete your purchase as soon as it is being confirmed by Paypal.
</h3>
<br>
@else
<h3 class="errHandle">
	We're sorry!<br>
	Your payment has not been processed due to error on your paypal. You can try again in a couple of minutes.
</h3>
@endif
<a href="/index.html" id="buttons">Back to main page</a>
</div>
@endsection
