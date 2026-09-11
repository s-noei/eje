<blockquote>
	<h3>Change taxes</h3>
	<div class="law-reminder">Remember that you must enter decimal values between 0% ~ 99%</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Industry:</div>
		<div class="law-new-content">
			<select name="Industry">
@foreach ($form['industries'] as $ind)
					<option value="{{ $ind['IndustryID'] }}">{{ $ind['iName'] }}</option>
@endforeach
			</select>
		</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">New Income Tax:</div>
		<div class="law-new-content"><input type="text" name="Income" value="" size="2" maxlength="2"> %</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">New Import Tax:</div>
		<div class="law-new-content"><input type="text" name="Import" value="" size="2" maxlength="2"> %</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">New VAT Tax:</div>
		<div class="law-new-content"><input type="text" name="VAT" value="" size="2" maxlength="2"> %</div>
		@include('pages.country.law._debate', ['sub' => 'subtax'])
	</form>
</blockquote>
