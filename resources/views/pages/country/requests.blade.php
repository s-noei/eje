<b>{{ $lang->getstr('country_requests', 'countryinfo') }}</b>
<hr>
<table width="100%">
@foreach ($requestsList as $req)
		<tr>
			<td style="width: 110px; text-align: center">
				<a href="{{ $vars->getURL('profile', $req['CitizenID']) }}">
					<img src="{{ $vars->getImgLoc('CitizenAvatar') . $req['avatar'] }}" class="Avatar-s"><br>
					{{ $req['name'] }}
				</a>
			</td>
			<td>
				<b>{{ $lang->getstr('country_nc_old', 'countryinfo') }}</b>:
				<img src="{{ $vars->getImgLoc('CountryFlag') . $req['OFlag'] }}.gif" class="Flag-xs" align="absmiddle"><br>
				<b>{{ $lang->getstr('country_nc_time', 'countryinfo') }}</b>:
				{!! $session->getDiff($req['timestamp']) !!}
				<hr size="1">
				<blockquote>{!! $req['reason'] !!}</blockquote>
				<hr size="1">
				<b>{{ $lang->getstr('country_nc_status', 'countryinfo') }}</b>:
@if (!$req['approved'])
						<form action="" method="post">
							@csrf
							{{ $lang->getstr('country_nc_pending', 'countryinfo') }}
@if ($isMoFA)
									<input type="hidden" name="cID" value="{{ $req['CitizenID'] }}">
									<input type="hidden" name="toID" value="{{ $req['nNation'] }}">
									<input type="hidden" name="token" value="{{ md5($req['CitizenID'] . $req['nNation'] . 'Chang3 Nati0n') }}">
									<input type="submit" name="subapprove" value="{{ $lang->getstr('country_nc_approve', 'countryinfo') }}" class="button-blue-1">
@endif
						</form>
@else
@php $approver = $req['approved'] == 1 ? $lang->getstr('country_nc_money', 'countryinfo') : '<a href="' . $vars->getURL('profile', $req['approved']) . '">' . e($req['approver']) . '</a>'; @endphp
						{!! sprintf($lang->getstr('country_nc_approved', 'countryinfo'), $approver) !!}
@endif
			</td>
		</tr>
		<tr><td colspan="2"><hr></td></tr>
@endforeach
</table>
