<blockquote>
	<h3>Buy buildings</h3>
	<div class="law-reminder">The company must have offers in your country</div>
	<form action="" name="Issue" method="post">
		@csrf
		<div class="law-new-title">Clinic company ID:</div>
		<div class="law-new-content"><input type="text" name="CompanyID" value="{{ request('CompanyID') }}" size="7" maxlength="7"></div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Install into:</div>
		<div class="law-new-content">
			<select name="Region">
@foreach ($form['regions'] as $cReg)
				<option value="{{ $cReg['RegionID'] }}" @if ($cReg['RegionID'] == request('Region')) selected @endif>{{ $cReg['rName'] }}</option>
@endforeach
			</select>
		</div>
		@include('pages.country.law._debate', ['sub' => 'subbuy'])
	</form>
</blockquote>
