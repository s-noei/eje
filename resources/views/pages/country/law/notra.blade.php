<blockquote>
	<h3>Travel Embargo</h3>
	<div class="law-reminder">Travel embargoes after accept are active for 30 days</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Stop travel with:</div>
		<div class="law-new-content">
			<select name="Country">
@foreach ($form['countries'] as $coun)
				<option value="{{ $coun['CountryID'] }}">{{ $coun['cName'] }}</option>
@endforeach
			</select>
		</div>
		@include('pages.country.law._debate', ['sub' => 'subnotrd'])
	</form>
</blockquote>
