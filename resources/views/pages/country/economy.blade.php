<style>
    .taxes td { padding: 0 10px; text-align: center; }
</style>
			<b>Economy</b>
			<hr width=90%>
			<blockquote>
@if ($logged)
				<b>Donate</b><hr width=90%>
				<form action="" method="post" name="donate">
					@csrf
					<blockquote>
						<input type=text maxlength=7 size=7 name=Amount>
						<select name=Type>
@foreach ($myCurrencies as $cid => $cname)
							<option value="{{ $cid }}">{{ $cname }}</option>
@endforeach
						</select>
						<input type=submit value="Donate" class="submit-blue-0">
						<input type=hidden name=subdonate value=1>
						<input type=hidden name=To value="{{ $row['CountryID'] }}">
					</blockquote>
				</form>
@endif
				<b>Treasury</b><hr width=90%>
@foreach ($accounts as $cAcc)
				<div style="float: left">
					<img src="{{ $database->getCurrencyIco($cAcc['CurID'], $vars->getImgLoc('CurrencyIcon')) }}" class=inlineIMGs align=absmiddle>
					{{ round($cAcc['Amount'], 2) . ' ' . $database->getCurrency($cAcc['CurID']) }}&nbsp;&nbsp;&nbsp;
				</div>
@endforeach
				<div style="clear: both">&nbsp;</div>
				<b>Trading Embargoes</b><hr width=90%>
				<blockquote>
@forelse ($embargoes as $emb)
				<img src="{{ $vars->getImgLoc('CountryFlag') . $emb['Flag'] }}.gif" class="inlineIMGs" align="absmiddle">&nbsp;
				<a href="{{ $vars->getURL('country', $emb['Country2']) }}">{{ $emb['cName'] }}</a>
				 (Expires in {{ $emb['Expire'] - $database->today }} day(s))<br>
@empty
                            This country has no trading embargoes
@endforelse
				</blockquote>
				<b>Citizen and nationality fee</b><hr width=90%>
				<blockquote>
				<img src="/images/tala.gif" class=inlineIMGs align=absmiddle> {{ $row['nFee'] }} Tala
				<img src="{{ $vars->getImgLoc('CurrencyIcon') . $row['Flag'] }}.gif" class=inlineIMGs align=absmiddle> {{ $row['cFee'] . ' ' . $row['curName'] }}
				</blockquote>
				<b>Taxes</b><hr width=90%>
                <table class="taxes">
                    <tr><td>&nbsp;</td><td>Income tax</td><td>Import tax</td><td>VAT Tax</td></tr>
@forelse ($taxes as $tax)
                        <tr>
                            <td><img src="{{ $vars->getImgLoc('Icon') . $tax['Icon'] }}.png" class="inlineIMGs" align="absmiddle" alt="{{ $database->getIndustry($tax['IndustryID']) }}"></td>
                            <td>{{ $tax['Income'] }}%</td>
                            <td>{{ $tax['Import'] }}%</td>
                            <td>{{ $tax['VAT'] }}%</td>
                        </tr>
@empty
					<tr><td colspan="4"><h3 class=errHandle>Nothing yet...</h3></td></tr>
@endforelse
                </table>
				<b>Important industries</b><hr width=90%>
				<blockquote style="text-align: center;">
@forelse ($impInds as $ind)
						<img src="{{ $vars->getImgLoc('Icon') . $ind['Icon'] }}.png" class="inlineIMGs" align="absmiddle" alt="{{ $ind['iName'] }}">
@empty
                            No important industries set for this country yet
@endforelse
				</blockquote>
				<b>Economical variables</b><hr width=90%>
				<blockquote>
					<b>Average Production Cost</b>: {{ $row['apc'] }} {{ $row['curName'] }}<br>
					<b>Recommended Exchange Rate</b>: {{ $row['apc'] * 50 }} {{ $row['curName'] }}<br>
					<b>Inflation</b>: {{ $row['inflation'] != '-10000' ? $row['inflation'] . '%' : 'N/A' }}
				</blockquote>
			</blockquote>
