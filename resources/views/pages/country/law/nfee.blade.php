<blockquote>
	<h3>Change nationality fee</h3>
	<form action="" method="post">
		@csrf
	<div class="law-reminder">%25 of nationality fee will transfer to treasury and %75 of it will be removed from game.</div>
		<div class="law-new-title">Current fee:</div>
		<div class="law-new-content"><label title="Old Fee">{{ $form['oFee'] }}</label> TALA</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">New fee:</div>
		<div class="law-new-content"><input type="text" name="nFee" value="" size="3" maxlength="3"> TALA</div>
		<input type="hidden" name="oFee" value="{{ $form['oFee'] }}">
		@include('pages.country.law._debate', ['sub' => 'subfee'])
	</form>
</blockquote>
