<blockquote>
	<h3>Sign alliance</h3>
	<div class="law-reminder">Signing a 7-day and 30-day alliance is free for new countries.</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Sign alliance with:</div>
		<div class="law-new-content">
			<select name="Country">
@foreach ($form['countries'] as $coun)
				<option value="{{ $coun['CountryID'] }}">{{ $coun['cName'] }}</option>
@endforeach
			</select>
		</div>
		<div class="law-new-title">Alliance period:</div>
		<div class="law-new-content">
			<label><input type="radio" name="period" value="1"> 7 days ({{ $form['newCountry'] ? 'Free' : '10 Tala' }})</label><br>
			<label><input type="radio" name="period" value="2"> 30 days ({{ $form['newCountry'] ? 'Free' : '25 Tala' }})</label><br>
			<label><input type="radio" name="period" value="3"> 90 days (70 Tala)</label><br>
			<label><input type="radio" name="period" value="4"> 180 days (130 Tala)</label><br>
		</div>
		@include('pages.country.law._debate', ['sub' => 'subally'])
	</form>
</blockquote>
