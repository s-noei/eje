<blockquote>
	<h3>Propose a war Co-Account</h3>
	<div class="law-reminder">Every country can propose a war co-account only once in game.</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Assign profile ID:</div>
		<div class="law-new-content"><input type="text" size="8" maxlength="8" name="profile"></div>
		@include('pages.country.law._debate', ['sub' => 'subministry'])
	</form>
</blockquote>
