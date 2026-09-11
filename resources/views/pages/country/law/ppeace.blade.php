<blockquote>
	<h3>Propose peace</h3>
	<div class="law-reminder">You can offer an amount of Tala to return your regulations with a country to peace</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Propose peace with:</div>
		<div class="law-new-content">
			<select name="Country">
@foreach ($form['countries'] as $coun)
				<option value="{{ $coun['CountryID'] }}">{{ $coun['cName'] }}</option>
@endforeach
			</select>
		</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Offer amount:</div>
		<div class="law-new-content"><input type="text" name="price" size="4" maxlength="4"> Tala</div>
		@include('pages.country.law._debate', ['sub' => 'subpce'])
	</form>
</blockquote>
