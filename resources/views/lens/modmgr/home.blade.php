@extends('lens.layout')
@section('content')
@include('lens._navbar')
<center>
	<form action="" method="post">
		@csrf
		Enter citizen ID to view details: <input type="text" size="5" name="citID" value="{{ $id ?: '' }}" style="text-align: center"> <input type="submit" name="subview" value="Go!">
	</form>
	<hr>
{!! $msg !!}
@if ($id && !$cit)
	This citizen has no access to lens.
	<form action="" method="post">
		@csrf
		<input type="hidden" name="modID" value="{{ $id }}" />
		<select name="access">
@foreach ([4, 5, 6, 7, 8, 9] as $a)<option value="{{ $a }}">{{ $accs[$a] }}</option>@endforeach
		</select>
		<input type="submit" name="subinvite" value="Send invite to this citizen!" />
	</form>
@elseif ($cit)
@include('lens._kv', ['head' => 'Moderator', 'rows' => [['Citizen ID', $id], ['Citizen name', e($cit['name'])], ['Current access', $accs[(int) $cit['Access']] ?? ''], ['Moderation ID', e($cit['ModID'])]], 'width' => '400px'])
@include('lens._kv', ['head' => 'Access details', 'rows' => [
	['Citizens', $access1[(int) $cit['at_citizen']] ?? ''], ['Companies', $access1[(int) $cit['at_company']] ?? ''], ['Elections', $access2[(int) $cit['at_elections']] ?? ''],
	['Tickets', $cit['at_tickets'] ?: 'No access'], ['Multi Tracker', $access2[min(1, (int) $cit['at_multrack'])]], ['Payments', $access2[(int) $cit['at_payments']] ?? ''],
	['Ads', $access2[(int) $cit['at_ads']] ?? ''], ['Mod management', $access2[(int) $cit['at_mods']] ?? ''],
], 'width' => '400px'])
@include('lens._kv', ['head' => 'Activities in tickets', 'rows' => [
	['Tickets replied', $activity['replied']], ['Total replies', $activity['total']], ['Auto replies', $activity['auto']],
	['Rate points collected', ($activity['rate']['Tot'] ?? 0) . ' out of ' . ($activity['rate']['Nums'] ?? 0) . ' post(s)<br>Average: ' . ($activity['rate']['Avg'] ?? 0)],
], 'width' => '400px'])
@endif
</center>
@endsection
