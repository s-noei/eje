<script language="javascript">
	function updatePrice(max)
	{
		var amount = document.Issue.Amount.value;
		var price = (Math.round(amount * 2) / 1000) + 15;
		if (isNaN(price)) document.Issue.Amount.value = '0';
		var start = '', end = '';
		if (price > max) { start = '<font color="red">'; end = '</font>'; }
		document.getElementById('iPrice').innerHTML = start + price + end;
	}
</script>
<blockquote>
	<h3>Issue money</h3>
	<div class="law-reminder"><b>Note:</b> eJahan will automatically take an amount of <b>0.002 Tala</b> for issue 1 local currency + 15 TALA as issue fee to prevent issue unlimited amounts!</div>
	<form action="" name="Issue" method="post">
		@csrf
		<div class="law-new-title">TALA in treasury:</div>
		<div class="law-new-content"><label title="Country Tala">{{ $form['oTala'] }}</label> TALA</div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Want to issue:</div>
		<div class="law-new-content">
			<input type="text" name="Amount" value="" size="5" maxlength="5" onkeyup="updatePrice('{{ $form['oTala'] }}')"> {{ $form['cur'] }}
			<b>(<span id="iPrice">0</span> Tala)</b>
		</div>
		<input type="hidden" name="cTala" value="{{ $form['oTala'] }}">
		@include('pages.country.law._debate', ['sub' => 'subiss'])
	</form>
</blockquote>
