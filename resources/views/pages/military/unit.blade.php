@extends('layouts.game')
@section('content')
<div class="column-double">
<h3> Military unit </h3>

<table width="100%" height="100" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="background:#F8F8F8;border-bottom:1px solid #F0F0F0;"><td style="color:#707070; text-align:left;padding-left:20px;" height="80" width="100%">
<span><strong> You are not a soldier of any military unit.</strong><br> You can join an existing military unit or you can create your own military unit if you cannot find the right one for you. Being a soldier of a military unit helps you and your fellow soldiers to be better organized, dealing more damage. </span>
<br><br><center><a href="{{ $vars->getURL('create', 'unit') }}" class=button-blue-1> Create a military unit </a></center>
</td></tr></table>

<br>

<span style="font-family: Trebuchet MS, Myriad Pro, Arial,sans-serif;font-size:14px;font-weight:bold;color:#707070;"><center><i>or</i></center></span>

<br>

<table width="100%" height="50" border="0" align="center" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr style="background:#F8F8F8;border-bottom:1px solid #F0F0F0;"><td style="font-family: Trebuchet MS, Myriad Pro, Arial,sans-serif;font-size:14px;font-weight:bold;color:#707070;text-align:center;" height="50" width="100%">
<center>Apply for membership in a military unit</center>
</td></tr></table>

<table style="width: auto;" align="center"><tbody><tr><td>
@foreach ($units as $unit)
		<div style="padding: 10px; float: left; text-align: center; overflow: hidden">
		<a href="{{ $vars->getURL('military-unit', $unit['mID']) }}">
		<div title="" style="display: inline-block;">
		<img src="{{ $vars->getImgLoc('MULogo') . $unit['mLogo'] }}" class="Avatar-s" style="border: 2px solid rgb(0, 100, 0); border-radius: 5px;" alt="{{ $unit['mName'] }}" align="absmiddle">
		</div><br> {{ $unit['mName'] }} </a>
		<br><span style="font-family: Trebuchet MS, Arial;font-size:10px;color rgb(153, 153, 153);"> {{ $unit['mMembers'] }} members </span>
		</div>
@endforeach
</td></tr></tbody></table>
</div>
@endsection
