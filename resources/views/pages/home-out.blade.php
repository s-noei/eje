@extends('layouts.game')
@section('content')
@push('styles')
<link rel="stylesheet" type="text/css" href="/include/css/out.css">
<link rel="stylesheet" type="text/css" href="/include/css/pikachoose.css">
@endpush
@php $o = fn($k) => $lang->getstr($k, 'main_out'); @endphp
<style type="text/css">
div.out-box { border-radius: 5px; padding: 5px; text-align: justify }
div.out-box div.title { font-weight: bolder; font-size: 12pt; padding-bottom: 3px; background: url('/images/main-out/bg-title.png') no-repeat bottom left; }
div.out-width-100 { width: 940px; display: inline-block; vertical-align: top }
div.out-width-50 { width: 400px; display: inline-block; vertical-align: top }
div.out-width-25 { width: 255px; display: inline-block; vertical-align: top }
div.out-features { margin-top: 5px; background: -moz-linear-gradient(#EAF5FF, #8FC9FE) repeat scroll 0 0 transparent; background: -webkit-gradient(linear, 0 0, 0 bottom, from(#EAF5FF), to(#8FC9FE)); padding: 10px 5px; height: 150px }
div.out-register { float: right; width: 300px; height: 140px; background: -moz-linear-gradient(#FEFD8F, #FFFFEA) repeat scroll 0 0 transparent; background: -webkit-gradient(linear, 0 0, 0 bottom, from(#FEFD8F), to(#FFFFEA)); }
a.out-register-button { display: inline-block; width: 130px; height: 25px; color: white; padding: 25px 10px; background: url('/images/main-out/regbutton.png'); background-position: 0px 0px; }
a.out-register-button:hover { background-position: 0px -75px; }
a.out-register-button:active { position: relative; top: 1px; left: 1px; background-position: 0px -150px; }
div.features-icon { width: 32px; height: 32px; position: absolute; margin-top: 100px; opacity: 0.3 }
div.features-icon img { width: 32px; height: 32px }
div.features-title { margin-left: 60px; margin-top: 5px; font-size: 18pt; color: maroon; }
div.features-title div { display: none }
div.features-desc { margin-left: 100px; color: black; font-size: 10pt; line-height: 15pt }
div.features-desc div { display: none }
div.out-topcouns { float: left; width: 255px; height: 31px; padding-top: 2px; padding-bottom: 2px; border: 0px solid; text-align: justify; }
div.out-topcouns div { display: inline-block; vertical-align: middle; }
div.out-topcouns div.no { width: 10px }
div.out-topcouns div.name { width: 160px }
div.out-topcouns div.ep, div.out-topcouns div.pop { width: 50px; text-align: right; }
div.out-topcouns div.icon { width: 15px }
</style>
<div class="out-box out-width-100 out-features">
    <div class="out-box out-register">
        <div class="title">{!! $o('out_not_registered') !!}</div>
        {!! $o('out_registration_desc') !!}
        <br style="font-size: 18pt" />
        <center>
            <a class="out-register-button" href="{{ $vars->getURL('register') }}">{!! $o('out_register') !!}</a>
        </center>
    </div>
    <div class="title">{!! $o('out_youcan') !!}</div>
    <div class="features-icon" id="feature-1" style="margin-left: 45px;"><img src="/images/main-out/economy.png" alt="Economy"></div>
    <div class="features-icon" id="feature-2" style="margin-left: 80px;"><img src="/images/main-out/politics.png" alt="Politics"></div>
    <div class="features-icon" id="feature-3" style="margin-left: 115px;"><img src="/images/main-out/military.png" alt="Military"></div>
    <div class="features-icon" id="feature-4" style="margin-left: 150px;"><img src="/images/main-out/media.png" alt="Media"></div>
    <div class="features-title">
        <div id="title-1">{!! $o('out_desc_economy') !!}</div>
        <div id="title-2">{!! $o('out_desc_politics') !!}</div>
        <div id="title-3">{!! $o('out_desc_military') !!}</div>
        <div id="title-4">{!! $o('out_desc_media') !!}</div>
    </div>
    <div class="features-desc">
@foreach (['economy', 'politics', 'military', 'media'] as $i => $f)
        <div id="desc-{{ $i + 1 }}">
            {!! $o("out_desc_{$f}_1") !!}<br />
            {!! $o("out_desc_{$f}_2") !!}<br />
            <strong>{!! $o("out_desc_{$f}_3") !!}</strong>
        </div>
@endforeach
    </div>
</div>
	<div class="out-box out-width-50">
        <div class="title">{!! $o('out_news') !!}</div>
@foreach ($news as $art)
                <div style="display: inline-block; vertical-align: top">
                    <img src="/images/logo.gif" alt="Around eJahan" width="50px" align="absmiddle" />
                </div>
                <div style="display: inline-block; vertical-align: top; width: 345px">
    				<a href="{{ $vars->getURL('article', $art['aID']) }}">{!! $art['aTitle'] !!}</a>
    				<div style="padding: 5px">
    					{!! $vars->lenTrim(strip_tags($art['aContent'], "<br>"), 200, 'char', 0) !!}
    				</div>
                </div>
@endforeach
	</div>
@foreach ([['topEP', 'out_top_pts', 'EP', 'ep', 'ep-icon'], ['topPop', 'out_top_pop', 'POP', 'pop', 'citizen-icon']] as [$var, $title, $col, $cls, $icon])
	<div class="out-box out-width-25">
        <div class="title">{!! $o($title) !!}</div>
@foreach ($$var as $co => $rCoun)
					<div class="out-topcouns">
						<div class="no">{{ $co + 1 }}</div>
						<div class="name">
							<a href="{{ $vars->getURL('country', $rCoun['CountryID']) }}">
								<img src="/images/flags/l/{{ $rCoun['flag'] }}.gif" class="Flag-xs" alt="{{ $rCoun['cName'] }}" align="absmiddle"> {{ $rCoun['cName'] }}
							</a>
						</div>
						<div class="{{ $cls }}">{{ number_format((float) $rCoun[$col]) }}</div>
						<div class="icon"><img src="/images/main-out/{{ $icon }}.png" align="absmiddle"></div>
						<div style="clear: both"></div>
					</div>
@if (!(($co + 1) % 2))<div style="clear: both"></div>@endif
@endforeach
	</div>
@endforeach
<script type="text/javascript" src="/include/js/out.js"></script>
@endsection
