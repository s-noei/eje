@if ($err)<h3 class="errHandle">{{ $err }}</h3>@endif
@if (empty($blocked))
<script language="javascript">
	function addOption(selectbox,text,value ) {
		var optn = document.createElement("OPTION"); optn.text = text; optn.value = value; selectbox.options.add(optn);
	}
	$(document).ready(function(){
			$("#country").change(function(){
					cID = $("#country").val();
					$.getJSON("/regions-"+cID+"-1.html", function(data) {
							$("img#flag").attr({src: data['CountryFlag']});
							document.travel.region.length = 0;
							addOption(document.travel.region, '-- SELECT --', '0');
							$.each(data['region'], function(id, reg) {
									if (cID == reg.CountryID) addOption(document.travel.region, reg.RegionName, reg.RegionID)
								});
						});
				});
		});
</script>
<form action="" method="post" name="travel">
@csrf
@if (count($tickets) < 1)
	<h3 class="errHandle">You have not any tickets in your inventory.<br>Buy some from <a href="{{ $vars->getURL('market', '2', '0') }}">market</a>, please.</h3>
@else
	<table>
		<tr><td rowspan="7" class="style"><img src="/images/game/Travel.gif"></td></tr>
		<tr>
			<td class="style">Your current location:</td>
			<td class="style">
				<a href="{{ $vars->getURL('region', $citInfo['RegionID']) }}">{{ $citInfo['RegionName'] }}</a>,
				<a href="{{ $vars->getURL('country', $citInfo['CountryID']) }}">{{ $citInfo['cName'] }}</a>
			</td>
		</tr>
		<tr>
			<td class="style">Ticket type:</td>
			<td class="style">
				<select name="ticket" class="style">
@foreach ($tickets as $ticket)
					<option value="{{ $ticket['Stars'] }}" {{ $actTicket == $ticket['Stars'] ? 'selected="selected"' : '' }}>{{ $ticket['Stars'] }}-star ticket</option>
@endforeach
				</select>
			</td>
		</tr>
		<tr>
			<td class="style">Country:</td>
			<td class="style">
				<select size="1" id="country" class="style" name="CountryID">
				<option value='0'>- SELECT -</option>
@foreach ($countries as $rowC)
					<option value="{{ $rowC['CountryID'] }}" {{ $toCoun == $rowC['CountryID'] ? 'selected' : '' }}>{{ $rowC['cName'] }}</option>
@endforeach
				</select>
				<img id="flag" src="" class="Flag-s" align="absmiddle">
			</td>
		</tr>
		<tr>
			<td class="style">Region:</td>
			<td class="style">
				<select size="1" class="style" id="region" name="RegionID"><option value='0'>-- SELECT --</option></select>
			</td>
		</tr>
		<tr>
			<td class="style" colspan="2">
				<input type="hidden" name="subtravel" value="1">
				<a href="#" onclick="document.travel.submit(); return false" id="buttons">Move</a>
			</td>
		</tr>
		<tr>
			<td class="style" colspan="2">
				<b>Ticket effects on citizen's wellness:</b>
				<blockquote>
					<b>1-star:</b> Inside (<span style="color: red; font-weight: bold">-2</span>) - Outside (<span style="color: red; font-weight: bold">Not availabe</span>)<br>
					<b>2-star:</b> Inside (<span style="color: green; font-weight: bold">+2</span>) - Outside (<span style="color: red; font-weight: bold">-5</span>)<br>
					<b>3-star:</b> Inside (<span style="color: red; font-weight: bold">-2</span>) - Outside (<span style="color: red; font-weight: bold">-3</span>)<br>
					<b>4-star:</b> Inside (<span style="color: green; font-weight: bold">+2</span>) - Outside (<span style="color: green; font-weight: bold">+1</span>)<br>
					<b>5-star:</b> Inside (<span style="color: green; font-weight: bold">+5</span>) - Outside (<span style="color: green; font-weight: bold">+5</span>) + <span style="color: green; font-weight: bold">travel to forbidden countries</span><br>
				</blockquote>
			</td>
		</tr>
	</table>
@endif
</form>
@endif
