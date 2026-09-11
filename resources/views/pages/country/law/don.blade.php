<blockquote>
	<h3>Donate money from treasury</h3>
	<form action="" name="Issue" method="post">
		@csrf
		<div class="law-new-title">Amount to donate:</div>
		<div class="law-new-content"><input type="text" name="Amount" value="" size="5" maxlength="5"></div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Currency:</div>
		<div class="law-new-content">
			<select name="Cur">
@foreach ($form['accounts'] as $cAcc)
				<option value="{{ $cAcc['CurID'] }}">{{ $database->getCurrency($cAcc['CurID']) }}</option>
@endforeach
			</select>
		</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Target CA Profile ID:</div>
		<div class="law-new-content"><input type="text" name="Target" value="" size="8" maxlength="8"></div>
		@include('pages.country.law._debate', ['sub' => 'subdon'])
	</form>
</blockquote>
