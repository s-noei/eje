			<b>Politics</b>
			<hr width=90%>
			<blockquote>
				<b>Bordered with</b><hr width=90%>
                <center>
@foreach ($neighbors as $nei)
						<div style="display: inline-block; text-align: center; padding: 5px">
							<a href="{{ $vars->getURL('country', $nei['CountryID'], 'politics') }}">
								<img src="{{ $vars->getImgLoc('CountryFlag') . $nei['Flag'] }}.gif" class="Flag-s" align="absmiddle">
                                <br />
								{{ $nei['cName'] }}
							</a>
						</div>
@endforeach
                </center>
				<b>Country President</b><hr width=90%>
				<blockquote>
@if ($president)
					<h3><a href='{{ $vars->getURL('profile', $president['CitizenID']) }}'>{!! $vars->getAvatar($president, 'Avatar-s') !!} {{ $president['name'] }} </a></h3>
@else
					<h3>No country president</h3>
@endif
						<a href="{{ $vars->getURL('congress', $row['CountryID']) }}" id="buttons">Goto congress</a>
				</blockquote>
				<b>Cabinet</b><hr width=90%>
				<blockquote>
@forelse ($cabinet as $m)
						<div style="display: inline-block; text-align: center; padding: 5px; border-radius: 5px; border: 1px solid">
							<a href="{{ $vars->getURL('profile', $m['cit']['CitizenID']) }}">
								{!! $vars->getAvatar($m['cit'], 'Avatar-s') !!}<br>
								{{ $m['cit']['name'] }}
							</a><br>
							{{ $m['title'] }}
						</div>
@empty
						This country doesn't have any members in the cabinet.
@endforelse
				</blockquote>
				<b>Congress seats<hr width=90%></b>
					<table>
@foreach ($cgSeats as $cong)
						<tr>
							<td style="font-face: Arial; font-size: 10.5pt; padding:3px; padding-right: 20px">
								<a href="{{ $vars->getURL('party', $cong['pID']) }}">
									<img src="{{ $vars->getImgLoc('PartyLogo') . $cong['pLogo'] }}" class="Avatar-s" align="absmiddle">
									{{ $cong['pName'] }}
								</a>
							</td>
							<td style="font-face: Arial; font-size: 10.5pt; padding:3px; padding-right: 20px">{{ $cong['CGCount'] }} seat(s)</td>
						</tr>
@endforeach
					</table>
					<a href="{{ $vars->getURL('congress', $row['CountryID']) }}" id="buttons">Goto congress</a>
			</blockquote>
