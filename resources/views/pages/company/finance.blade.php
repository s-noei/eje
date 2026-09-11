{!! $msg ?? '' !!}
	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
	<hr size="2">
			<div class="finance">
				<div class="finance-cur">&nbsp;</div>
				<div class="finance-your">Your account</div>
				<div class="finance-comp">Company's account</div>
				<div class="finance-amount">&nbsp;</div>
				<div class="finance-oper">&nbsp;</div>
				<div style="clear: both">&nbsp;</div>
				<hr size="2">
@if (count($accounts) < 1)
					There are no accounts in this company
@else
@foreach ($accounts as $row2)
				<form action="" method="post">
				@csrf
				<div class="finance-cur">
					<img src="{{ $database->getCurrencyIco($row2['CurID'], $vars->getImgLoc('CountryFlag')) }}" class="Flag-s" align="absmiddle">
					<br>
					{{ $row2['curName'] }}
				</div>
				<div class="finance-your">{{ round($row2['mine'], 2) . ' ' . $row2['curName'] }}</div>
				<div class="finance-comp">{{ round($row2['Amount'], 2) . ' ' . $database->getCurrency($row2['CurID']) }}</div>
				<div class="finance-amount"><input type="text" name="Amount" size="4" class="txtAmount-4" maxlength="4"></div>
				<div class="finance-oper">
					<input type="hidden" name="actOffer" value="{{ $row2['CurID'] }}">
					<input type="submit" value="Invest" name="cmdInvest" class="cmdEdit" style="width: 75px"><br>
					<input type="submit" value="Collect" name="cmdCollect" class="cmdEdit" style="width: 75px">
				</div>
				<div style="clear: both">&nbsp;</div>
				</form>
				<hr>
@endforeach
@endif
</div>
