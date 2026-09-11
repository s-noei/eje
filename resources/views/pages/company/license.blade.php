{!! $msg ?? '' !!}
	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
	<form action="" method="post" id="forms">
		@csrf
		<h3>Buy a license</h3>
		Buy an export license for:<br><br>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<select name="licTo">
			<option value='0'>--- SELECT A COUNTRY ---</option>
@foreach ($countries as $rowC)
				<option value="{{ $rowC['CountryID'] }}">{{ $rowC['cName'] }}</option>
@endforeach
		</select>
		<br><br>
		Tala needed: 10 <img src="/images/flags/s/eJahan.gif" align="absmiddle"><br>
		Tala in company account: {{ $cMon }} <img src="/images/flags/s/eJahan.gif" align="absmiddle"><br>
		<input type="submit" name="buyOk" value="Buy license" class="submit-blue-1">
	</form>
