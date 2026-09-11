@extends('layouts.game')
@section('content')
@if (count($rows) > 0)
<h3 class="infHandle">{{ count($rows) }} result(s) found for <i>'{{ $sq }}'</i> &nbsp;in <i>'{{ $field }}'</i></h3>
@foreach ($rows as $row)
				<div id="sResults">
@if ($field === 'company')
						<a href="{{ $vars->getURL('company', $row['CompanyID']) }}">
							<img src="{{ $vars->getImgLoc('CompanyAvatar') . $row['Avatar'] }}" class="Avatar-s" align="absmiddle">
							{{ $row['Name'] }}
						</a>
						<a href="{{ $vars->getURL('country', $row['CountryID']) }}"><img src="/images/flags/l/{{ $row['Flag'] }}.gif" class="Flag-s" align="absmiddle" alt="Country"></a>
@else
						<a href="{{ $vars->getURL('profile', $row['CitizenID']) }}">
							{!! $vars->getAvatar($row, 'Avatar-s') !!}
							{{ $row['name'] }}
						</a>
						<a href="{{ $vars->getURL('country', $row['CountryID']) }}">
							<img src="/images/flags/l/{{ $row['Flag'] }}.gif" class="Flag-s" align="absmiddle" alt="Country">
							{{ $row['accType'] == 'citizen' ? " - EP: {$row['ep']}" : '' }}
						</a>
@endif
				</div>
				<hr>
@endforeach
@else
<h3 class="errHandle">Found nothing for <i>'{{ $sq }}'</i> &nbsp;in <i>'{{ $field }}'</i></h3>
@endif
@endsection
