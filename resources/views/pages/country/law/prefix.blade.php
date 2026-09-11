<blockquote>
	<h3>Change prefix</h3>
	<div class="law-reminder">The prefix of every country will be shown in country's info page. It costs 5 Tala.</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Current name:</div>
		<div class="law-new-content"><label title="Old Prefix">{{ $row['Prefix'] ? $row['Prefix'] . ' of ' . $row['cName'] : $row['cName'] }}</label></div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">New name:</div>
		<div class="law-new-content"><input type="text" name="newPref" value="" size="10" maxlength="20"> (of) {{ $row['cName'] }}</div>
		<input type="hidden" name="oPref" value="{{ $row['Prefix'] }}">
		@include('pages.country.law._debate', ['sub' => 'subpref'])
	</form>
</blockquote>
