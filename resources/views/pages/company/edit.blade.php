{!! $msg ?? '' !!}
<script type="text/javascript">
	function addOption(selectbox,text,value ) { var optn = document.createElement("OPTION"); optn.text = text; optn.value = value; selectbox.options.add(optn); }
	$(document).ready(function(){
			$("#countrylist").fadeIn(1000); $("#regionlist").fadeIn(1000);
			$("#countrylist").change(function(){
					cID = $("#countrylist").val();
					$("#regionlist").fadeOut(200);
					$.getJSON("/regions-"+cID+"-0.html", function(data) {
							document.editcomp.regionID.length = 0;
							$.each(data['region'], function(id, reg) { addOption(document.editcomp.regionID, reg.RegionName, reg.RegionID) });
							$("#regionlist").fadeIn(1000);
						});
				});
		});
	function putPrice(country) { if (country == {{ $row['CountryID'] }}) return {{ $movePriceHome }}; else return {{ $movePriceAway }}; }
</script>
	<a class="button-blue-1" href="{{ $vars->getURL('company', $row['CompanyID'], 'details') }}">{!! $lang->getstr('company_back_link', 'company') !!}</a>
	<form action="" name="editcomp" method="post" id="forms" enctype="multipart/form-data">
		@csrf
		<h3>Edit company details</h3>
		<table>
			<tr><td>Name:</td><td><input type="text" size="20" name="cName" class="text" value="{{ $row['Name'] }}"></td></tr>
			<tr>
				<td>Location:</td>
				<td>
					<select name="countryID" id="countrylist" onchange="javascript:document.getElementById('moveprice').innerHTML = putPrice(countryID.value)" style="display: none">
@foreach ($countries as $reg)
						<option value="{{ $reg['CountryID'] }}"{{ $reg['CountryID'] == $row['CountryID'] ? ' selected' : '' }}>{{ $reg['cName'] }}</option>
@endforeach
					</select>
					<select name="regionID" id="regionlist" style="display: none">
@foreach ($regions as $reg)
						<option value="{{ $reg['RegionID'] }}"{{ $reg['RegionID'] == $row['RegionID'] ? ' selected' : '' }}>{{ $reg['rName'] }}</option>
@endforeach
					</select>
				</td>
			</tr>
			<tr>
				<td>Price</td>
				<td>
					<span id="moveprice">N/A</span> <img src="/images/tala.gif" align="absmiddle">
					<script type="text/javascript">document.getElementById('moveprice').innerHTML = putPrice({{ $row['CountryID'] }})</script>
				</td>
			</tr>
			<tr>
				<td>Avatar</td>
				<td><img src="{{ $vars->getImgLoc('CompanyAvatar') . $row['Avatar'] }}" alt="avatar" width="75"> <input type="file" name="compAvatar" id="compAvatar" /></td>
			</tr>
			<tr><td class="tdstyle"><b>Restrictions:<br>.jpg &amp; .jpeg<br>Below 50 KBs</b></td></tr>
			<tr><td>Company Message</td><td><textarea rows="8" cols="40" name="compmsg">{{ $row['company_message'] }}</textarea></td></tr>
			<tr><td><input type="submit" name="editOk" value="Save changes" class="submit-blue-1"></td></tr>
		</table>
	</form>
