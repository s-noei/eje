<blockquote>
	<h3>Select important industries</h3>
	<div class="law-reminder">Your country will make 10% more productivity in industries which you select. You can select max. 3 industries</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Select industries:</div>
		<div class="law-new-content">
			<select multiple name="inds[]" size="10">
@foreach ($form['industries'] as $ind)
					<option value="{{ $ind['IndustryID'] }}">{{ $ind['iName'] }}</option>
@endforeach
			</select>
		</div>
		@include('pages.country.law._debate', ['sub' => 'subinds'])
	</form>
</blockquote>
