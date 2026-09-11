<blockquote>
	<h3>Assign minister</h3>
	<div class="law-reminder">Every minister remains in cabinet even after president's change until he/she changes by president.</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">Assign profile ID:</div>
		<div class="law-new-content"><input type="text" size="8" maxlength="8" name="profile"></div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">As the minister of:</div>
		<div class="law-new-content">
			<select name="ministry">
				<option value="E">Economy</option>
				<option value="FA">Foreign Affairs</option>
				<option value="War">War</option>
			</select>
		</div>
		@include('pages.country.law._debate', ['sub' => 'subministry'])
	</form>
</blockquote>
