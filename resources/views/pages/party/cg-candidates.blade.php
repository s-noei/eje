	<table>
		<tr>
			<td>
				<table id="pMembers">
					<tr>
						<td colspan="3" id="pMembers">
							<form action="" method="post">
								Select region:
								<select name="regID" onchange="document.location.href='/party-{{ $row['pID'] }}-cgCandidates-'+this.value+'.html'">
									<option value="0" @if (!$regID) selected @endif>---- SELECT REGION ----</option>
@foreach ($regions as $reg)
									<option value="{{ $reg['RegionID'] }}" @if ($reg['RegionID'] == $regID) selected @endif>{{ $reg['rName'] }}</option>
@endforeach
								</select>
							</form>
							<hr>
						</td>
					</tr>
					<tr><td colspan="3">
					<blockquote>
						Citizens can propose themselves as candidates between 23rd and 26th.
						The party president will sort the candidates on 27th every month.
@if ($regID)
						Each party can propose maximum {{ $cgCount }} candidates for this region.
@endif
					</blockquote>
					</td></tr>
@if (count($cgCands) < 1)
						<tr><td colspan="3" id="pMembers" class=td>There are no candidates in this region</td></tr>
@else
						<tr><td colspan="3" id="pMembers"><center>Qualified candidates<hr></center></td></tr>
@foreach ($cgCands as $i => $member)
@php $co1 = $i + 1; @endphp
@if ($co1 == $cgCount + 1)
						<tr><td colspan="3" id="pMembers"><center>Not qualified<hr></center></td></tr>
@endif
					<tr>
						<td class="td" style="width: 10px">{{ $co1 > $cgCount ? $co1 - $cgCount : $co1 }}</td>
						<td class="td">
							<a href="{{ $vars->getURL('profile', $member['CitizenID']) }}">
								<img src="{{ $database->getCitizenAvatar($member['CitizenID'], $vars->getImgLoc('CitizenAvatar')) }}" class="Avatar-s" align="absmiddle">
								{{ $member['name'] }}
							</a>
						</td>
@if ($isPP && $day == \App\Game\Support\Constants::CG_SORT_DAY)
						<td class="td">
							<form action="" method="post">
								@csrf
								<input type="text" name="newOrder" size="2" value="">
								<input type="hidden" name="uID" value="{{ $member['CitizenID'] }}">
								<input type="hidden" name="token" value="{{ md5($member['CitizenID'] . 'k3y4 chanjing 0rd3r') }}">
								<input type="submit" name="subChange" value="Change!" id="submits">
							</form>
						</td>
@endif
					</tr>
					<tr><td colspan="3"><hr></td></tr>
@endforeach
@endif
				</table>
			</td>
		</tr>
	</table>
