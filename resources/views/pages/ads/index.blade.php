@extends('layouts.game')
@section('content')
<a href="{{ $vars->getURL('ads', 'add') }}" id="buttons">Make a new ad</a>
<center><b>Your active advertisements</b></center>
<hr>
@if (count($rows) < 1)
			<center>You have no active advertisement</center>
@else
			<div id="myads">
				<div class="no">No</div><div class="title">Title</div><div class="price">Price</div><div class="time">Last edit</div><div class="view">View</div><div class="click">Click</div><div class="oper">Actions</div>
				<div class="clear"></div>
			</div>
@foreach ($rows as $i => $row)
			<div id="myads">
				<div class="no">{{ $i + 1 }}</div>
				<div class="title">{{ $row['title'] }}</div>
				<div class="price">{{ round($row['cost'], 4) }} TALA</div>
				<div class="time">{!! $session->getDiff($row['edit']) !!}</div>
				<div class="view">{{ $row['views'] }}</div>
				<div class="click">{{ $row['clicks'] }}</div>
				<div class="oper">
@if ($row['status'] == 1)
					<a href="{{ $vars->getURL('ads', 'stop', $row['adID']) }}" title="Stop this ad"><img src="/images/game/exit.gif" class="inlineIMGs" width="24" align="absmiddle"></a>
@else
					<a href="{{ $vars->getURL('ads', 'start', $row['adID']) }}" title="Start this ad"><img src="/images/game/start.gif" class="inlineIMGs" width="16" align="absmiddle"></a>
					<a href="{{ $vars->getURL('ads', 'edit', $row['adID']) }}" title="Edit this ad"><img src="/images/game/profile/icon-edit.gif" class="inlineIMGs" width="24" align="absmiddle"></a>
@endif
				</div>
				<div class="clear"></div>
			</div>
@endforeach
@endif
@endsection
