@if ($blocked)
        <h3 class="errHandle">This company has an active bid, so you cannot change the quality of this company.</h3>
    	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
@else
{!! $msg ?? '' !!}
	<script type="text/javascript">
		var actDiv = '#spend'; var canSubmit = false;
		function setCost(cost) {
			$("#notEnough").hide();
			$(actDiv).slideUp(100, function(){
					if (cost > 0) {
							if (cost > {{ $cMon }}) { canSubmit = false; $("#notEnough").show(); $("#start").removeClass('submit-blue-1').addClass('submit-gray-1'); }
							else { canSubmit = true; $("#start").removeClass('submit-gray-1').addClass('submit-blue-1'); }
							$("#spend").slideDown(250); document.getElementById('sCost').innerHTML = cost; actDiv = "#spend";
						} else if (cost < 0) {
							canSubmit = true; $("#start").removeClass('submit-gray-1').addClass('submit-blue-1');
							$("#receive").slideDown(250); document.getElementById('rCost').innerHTML = -cost; actDiv = "#receive";
						} else {
							canSubmit = false; $("#start").removeClass('submit-blue-1').addClass('submit-gray-1');
							$("#receive").slideDown(250); document.getElementById('rCost').innerHTML = -cost; actDiv = "#receive";
						}
				});
		}
	</script>
	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
	<form action="" method="post" onsubmit="return canSubmit">
		@csrf
		<h3>Change quality</h3>
		<center>
			<div style="border: 1px solid; -moz-border-radius: 5px; display: inline-block; padding: 2px; width: 650px; margin-bottom: 4px">Select the target quality</div>
			<div class="qualities">
@for ($i = 0; $i <= 5; $i++)
@php $cost = $getCost($i); @endphp
					<div class="{{ $i == $row['Stars'] ? 'current' : 'notsel' }}">
						<center>
							{{ $i ? "$i-star" : "Dissolve" }}
							<br>
							<img src="/images/game/{{ $i }}_star_b.gif" width="95px" align="absmiddle">
							<br>
							{{ $cost >= 0 ? "Cost: $cost" : "Receive: " . -$cost }}
							<img src="/images/Tala.gif" align="absmiddle">
							<br>
							<input type="radio" name="gValue" value="{{ $i }}"{{ $i == $row['Stars'] ? ' disabled' : '' }} onclick="javascript:setCost({{ $cost }})">
						</center>
					</div>
@endfor
			</div>
			<div style="border: 1px solid; -moz-border-radius: 5px; display: inline-block; padding: 2px; width: 650px; margin: 4px 0">
				<div style="text-align: justify">Tala in company account: {{ $cMon }} <img src="/images/flags/s/eJahan.gif" align="absmiddle"></div>
				<div class="prices" id="spend" style="text-align: justify">
					Tala needed: <span id="sCost">0</span> <img src="/images/flags/s/eJahan.gif" align="absmiddle">
					<div id="notEnough" style="display: none; text-align: center; background: maroon; border: 1px solid; -moz-border-radius: 5px; color: white; padding: 5px">You have not enough money in your company account...</div>
				</div>
				<div class="prices" id="receive" style="display: none; text-align: justify">You will receive: <span id="rCost">0</span> <img src="/images/flags/s/eJahan.gif" align="absmiddle"></div>
			</div>
			<div style="border: 1px solid; -moz-border-radius: 5px; display: inline-block; padding: 2px; width: 650px; margin: 4px 0">
				<font color="maroon">
					<b>NOTE:</b> If you change the quality of your company, your stocks and unfinished product points will be lost.
					If you want to keep your old stocks, put them on sale on the market before changing the quality of company, but keep in mind that you will have no access
					to your old stocks anymore, so put the price wisely.
				</font>
			</div>
			<br>
			<input type="submit" name="upgOk" id="start" value="Change quality" class="submit-gray-1">
		</center>
		<br>
	</form>
@endif
