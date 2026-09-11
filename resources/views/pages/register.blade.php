@extends('layouts.game')
@section('content')
@push('styles')<link rel="stylesheet" type="text/css" href="/include/css/register.css">@endpush
@php $r = fn($k) => $lang->getstr($k, 'register'); @endphp
<script language="javascript">
	function addOption(selectbox,text,value ) {
		var optn = document.createElement("OPTION");
		optn.text = text; optn.value = value;
		selectbox.options.add(optn);
	}
	$(document).ready(function(){
			$("#country").change(function(){
					cID = $("#country").val();
					$.getJSON("/regions-"+cID+"-1.html", function(data) {
							$("img#map").attr({src: "/map-"+cID+".gif"});
							$("img#flag").attr({src: data['CountryFlag']});
							document.register.region.length = 0;
							addOption(document.register.region, '-- {!! addslashes($r('register_select')) !!} --', '0');
							$.each(data['region'], function(id, reg) {
									title = reg.RegionName;
									if (cID != reg.CountryID && cID == reg.OwnerID) title += {!! sprintf('\'(' . addslashes($r('register_annexed')) . ')\'', '\'+reg.CountryName+\'') !!};
									addOption(document.register.region, title, reg.RegionID)
								});
						});
				});
		});
</script>
@if ($view_form == -1)
	<h3 class="errHandle">
		Your invite link is invalid. You can make a direct registration by clicking
		<a href="{{ $vars->getURL('register') }}">here</a>.
	</h3>
@else
@if ($form->num_errors > 0)
<font size="2" color="#ff0000">{{ $form->num_errors }} error(s) found</font>
@endif
<center><h2>{!! $r('register_title') !!}</h2></center>
{!! $r('register_desc') !!}
<hr size="1">
<form name="register" action="/register-process.html" method="post" enctype="multipart/form-data">
	@csrf
	<div class="register-holder" id="r1" style="display: inline-block">
		<div class="loading" id="l1"><img src="/images/loading.gif" width="300px"></div>
		<div class="ok" id="o1"><img src="/images/pages/register/ok.gif" height="129px"></div>
		<div class="bottom">
			<a href="javascript:void(0)" class="button-blue-0" id="next_1">Next ></a>
		</div>
		<center>
			<h2>Step 1</h2>
			<h3>Your Information</h3>
			<hr size="1">
		</center>
		<div class="td-title">{!! $r('register_citname') !!}:</div>
		<div class="td-content">
			<input type="text" name="user" id="txtName" class="txtregister" maxlength="30" value="{{ $form->value('user') }}">
			{!! $form->error('user') !!}
		</div>
		<div style="clear: both"></div>
		<div class="td-title">{!! $r('register_pass') !!}:</div>
		<div class="td-content">
			<input type="password" name="pass" id="txtPass1" class="txtregister" maxlength="30">
			{!! $form->error('pass') !!}
		</div>
		<div style="clear: both"></div>
		<div class="td-title">{!! $r('register_passc') !!}:</div>
		<div class="td-content">
			<input type="password" name="pass2" id="txtPass2" class="txtregister" maxlength="30">
			{!! $form->error('pass2') !!}
		</div>
		<div style="clear: both"></div>
		<div class="td-title">{!! $r('register_invby') !!}:</div>
		<div class="td-content">{!! $refer !!}</div>
		<div style="clear: both"></div>
		<div class="td-title">{!! $r('register_mail') !!}:</div>
		<div class="td-content">
			<input type="text" name="email" id="txtMail" class="txtregister" maxlength="50" value="{{ $form->value('email') }}">
			{!! $form->error('email') !!}
		</div>
		<div style="clear: both"></div>
		<div class="td-title">{!! $r('register_gender') !!}:</div>
		<div class="td-content">
			<input type="radio" value="1" {{ $form->value('Sex') == '1' ? 'checked' : '' }} name="Sex">{!! $r('register_male') !!}
			<input type="radio" value="2" {{ $form->value('Sex') == '2' ? 'checked' : '' }} name="Sex">{!! $r('register_female') !!}
			{!! $form->error('Sex') !!}
		</div>
	</div>

	<div class="register-holder" id="r2">
		<div class="loading" id="l2"><img src="/images/loading.gif" width="300px"></div>
		<div class="ok" id="o2"><img src="/images/pages/register/ok.gif" height="129px"></div>
		<div class="bottom">
			<a href="javascript:void(0)" class="button-blue-0" id="back_2">< Back</a>
			<a href="javascript:void(0)" class="button-blue-0" id="next_2">Next ></a>
		</div>
		<center>
			<h2>Step 2</h2>
			<h3>Your Location</h3>
			<hr size="1">
			<div class="map-holder">
				<div class="flag-holder"><img id="flag" src="" width="32px" align="absmiddle"></div>
				<img id="map" src="" width="300px" align="absmiddle">
			</div>
		</center>
		<div class="td-title">{!! $r('register_country') !!}:</div>
		<div class="td-content">
			<select size="1" class="style" id="country" name="CountryID">
			<option value='0'>- {!! $r('register_select') !!} -</option>
@foreach ($countries as $row)
				<option value="{{ $row['CountryID'] }}" {{ $form->value('CountryID') == $row['CountryID'] ? 'selected' : '' }}>{{ $row['cName'] }}</option>
@endforeach
			</select>
			{!! $form->error('CountryID') !!}
		</div>
		<div style="clear: both"></div>
		<div class="td-title">{!! $r('register_region') !!}:</div>
		<div class="td-content">
			<select size="1" class="style" id="region" name="RegionID">
				<option value='0'>-- {!! $r('register_select') !!} --</option>
			</select>
			{!! $form->error('RegionID') !!}
		</div>
	</div>

	<div class="register-holder" id="r3">
		<div class="loading" id="l3"><img src="/images/loading.gif" width="300px"></div>
		<div class="ok" id="o3"><img src="/images/pages/register/ok.gif" height="129px"></div>
		<div class="bottom">
			<a href="javascript:void(0)" class="button-blue-0" id="back_3">< Back</a>
			<a href="javascript:void(0)" class="button-blue-0" id="next_3">Next ></a>
		</div>
		<center>
			<h2>Step 3</h2>
			<h3>Your Verification</h3>
			<hr size="1">
				<input type="hidden" name="captcha_id" value="0">
				<input type="hidden" name="captcha" value="0">
			<b>{!! $r('register_avatar_title') !!}</b>
			<br>
			{!! $r('register_avatar_desc') !!}:
			<br>
			<div style="font-size: 8pt">{!! sprintf($r('register_avatar_limit'), 50, "JPG") !!}</div>
			<input type="file" name="avatar">
		</center>
	</div>

	<div class="register-holder2" id="r4" style="text-align: center">
		<input type="checkbox" name="Terms" class="txtregister" value="ON" {{ $form->value('Terms') == 'ON' ? 'checked' : '' }}>
		{!! sprintf($r('register_agree1'), '<a href="'.$vars->getURL('laws').'">'.$r('register_agree2').'</a>') !!}
		{!! $form->error('Terms') !!}
		<br>
		<input type="hidden" name="subjoin" value="1">
		<input type="hidden" name="red" value="{{ $referer }}">
		<input type="hidden" name="token" value="{{ md5($referer . $referer . config('ejahan.salts.register')) }}">
		<a href="javascript:void(0)" class="txtregister" id="buttons" onclick="document.register.submit()">
			{!! $r('register_join') !!}
		</a>
	</div>
</form>
<script type="text/javascript" src="/include/js/register.js"></script>
@endif
@endsection
