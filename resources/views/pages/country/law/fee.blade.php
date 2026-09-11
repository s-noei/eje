<blockquote>
	<h3>Change citizen fee</h3>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Current citizen Fee:</div>
		<div class="law-new-content"><label title="Old Fee">{{ $form['oFee'] }}</label> {{ $form['cur'] }}</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">New citizen fee:</div>
		<div class="law-new-content"><input type="text" name="nFee" value="" size="3" maxlength="3"> {{ $form['cur'] }}</div>
		<input type="hidden" name="oFee" value="{{ $form['oFee'] }}">
		@include('pages.country.law._debate', ['sub' => 'subfee'])
	</form>
</blockquote>
