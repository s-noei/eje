@extends('lens.layout')
@section('content')
<center>
	<form action="" method="post">
		@csrf
		Enter an ID and type to track: <input type="text" name="trackid" value="{{ $id ?: '' }}">
		<select name="tracktype">
@foreach (['citizen' => 'Citizen', 'company' => 'Company', 'country' => 'Country', 'party' => 'Party'] as $k => $v)
			<option value="{{ $k }}" @if ($type == $k) selected @endif>{{ $v }}</option>
@endforeach
		</select>
		<select name="trackcur">
			<option value="0" @if (!$id2) selected @endif>All</option>
@foreach ($currencies as $cur)
			<option value="{{ $cur['CountryID'] }}" @if ($id2 == $cur['CountryID']) selected @endif>{{ $cur['curName'] }}</option>
@endforeach
		</select>
		<input type="submit" value="Track" id="submits">
	</form>
</center>
<hr>
@if (!$id)
<center>Please type an ID to track transactions.</center>
@else
@php $tid = $id2 ? "$id-$id2" : $id; @endphp
	<center>
		<a href="{{ $link }}">Owner's page</a>
		<br>
		<table id="table" bordercolor="black" border="1">
			<tr>
				<th class="center">No.</th><th class="center">Transaction ID</th><th class="center">From</th>
@if ($level >= 2)<th class="center">Value change</th>@endif
				<th class="center">To</th>
@if ($level >= 2)<th class="center">Value change</th>@endif
				<th class="center">{{ $level >= 2 ? 'Amount' : 'Currency' }}</th>
				<th class="center">Page/Description</th><th class="center">Time</th>
@if ($level >= 3)<th class="center">Refund</th>@endif
			</tr>
@foreach ($rows as $i => $t)
			<tr>
				<td class="center">{{ $start + $i + 1 }}</td>
				<td class="center">{{ $t['tID'] }}</td>
				<td class="center">{!! $t['fromCell'] !!}</td>
@if ($level >= 2)<td class="center">{{ $t['fromBef'] }} ~> {{ $t['fromAft'] }}</td>@endif
				<td class="center">{!! $t['toCell'] !!}</td>
@if ($level >= 2)<td class="center">{{ $t['toBef'] }} ~> {{ $t['toAft'] }}</td>@endif
				<td class="center">{{ $level >= 2 ? $t['Amount'] : '' }} {{ $t['curName'] }}</td>
				<td class="center">{{ $t['Page'] }}</td>
				<td class="center">{!! $session->getDiff($t['timestamp']) !!}</td>
@if ($level >= 3)
				<td class="center">
@if (!$t['refundBy'])
					<form action="" method="post" onsubmit="return confirm('Are you sure you want to refund this transaction?')">
						@csrf
						<input type="hidden" name="refID" value="{{ $t['tID'] }}">
						<input type="submit" name="subrefund" value="Do it!">
					</form>
@else
					Refunded by {{ $t['refundBy'] }}
@endif
				</td>
@endif
			</tr>
@endforeach
		</table>
@if ($page > 1)<a href="{{ $lensUrl('trans', $type, $tid, $page - 1) }}">Back</a> | @endif
		Page {{ $page }}
@if ($hasNext) | <a href="{{ $lensUrl('trans', $type, $tid, $page + 1) }}">Next</a>@endif
	</center>
@endif
@endsection
