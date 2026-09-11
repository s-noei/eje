@extends('layouts.game')
@section('content')
<script>
	var TarURL;
</script>
<link rel="stylesheet" type="text/css" href="/include/css/ui.css" media="screen">
<link rel="stylesheet" type="text/css" href="/include/css/yuiskin.css" media="screen">
<link rel="stylesheet" type="text/css" href="/include/css/gzoom.css" media="screen">
<script type="text/javascript" src="/include/js/utilities.js"></script>
<script type="text/javascript" src="/include/js/container-min.js"></script>
<script type="text/javascript" src="/include/js/loadingpanel.js"></script>
<script type="text/javascript" src="/include/js/imageloader.js"></script>
<script type="text/javascript" src="/include/js/gzoom.js"></script>
<script type="text/javascript" src="/include/js/ui.core.js"></script>
<script type="text/javascript" src="/include/js/ui.slider.js"></script>
<script type="text/javascript" src="/include/js/mousewheel.js"></script>
<b>{{ $lang->getstr('map_bartitle', 'map') }} <sup style="color: red; font-size: 8pt; font-weight: bold">{{ $lang->getstr('beta') }}</sup></b>
<hr>
<a class="button-blue-1" href="/index.html">Back to eJahan</a>
<p style="text-align: center">
	<div style="border: 1px solid black; -moz-border-radius: 5px; text-align: center; padding: 5px; margin-bottom: 5px">
		<div style="position: absolute">
			<a href="javascript:void(0)" onclick="javascript:window.open(TarURL)" id="dlmap">Download active map</a>
		</div>
		<b>Select your desired map to show</b>
		<hr>
		<div style="display: inline-block">
			Map of today
			<br>
			<form name="currentmap">
				<select name="mapfilter" onchange="loadMap('/include/map/worldmap-'+this.value+'.gif')">
					<option value="0">Political map</option>
					<option value="1">Political map with region names</option>
					<option value="2">Political map with region IDs</option>
					<option value="3" selected="selected">Countries map</option>
					<option value="4">Original owners</option>
					<option value="5">Goddesses map</option>
					<option value="6">Population map</option>
					<option value="7">Clinics map</option>
					<option value="8">Municipalities map</option>
				</select>
			</form>
		</div>
		<div style="display: inline-block">
			Select date
			<br>
			<form name="oldmaps">
				<select name="maphistory" onchange="if (this.value) loadMap('/include/map/history/'+this.value+'.gif')">
					<option value="0">-- SELECT --</option>
@foreach ($days as $i)
					<option value="{{ $i }}">Day {{ $i }}</option>
@endforeach
					<option value="484">Day 484</option>
					<option value="478">Day 478</option>
				</select>
			</form>
		</div>
	</div>
    <a href="" onclick="return false;">
	<div id="mapcontainer" style="direction: ltr; border: 1px solid; border-radius: 5px; padding: 2px">
            <img id="mapview" ismap="ismap" name="mapview" src="" style="width: 880px; height: 440px; border: 0" usemap="#worldmap">
	</div>
    </a>
	<center>{{ sprintf($lang->getstr('map_version', 'map'), '0.5') }}<br></center>
</p>
<script type="text/javascript">
    $(function() {
		if ($.fn.gzoom) $("#mapcontainer").gzoom({ sW: 880, sH: 440, lW: 8000, lH: 4000, lightbox: true, debug : false, zoomIcon: '/images/zoom-in.png' });
    });
function putImage(image, url){ document.mapview.src = url; }
function loadMap(target){
	if (window.yuiLoadingPanel && window.ImageLoader) {
		var loadingPanel = new yuiLoadingPanel();
		$("#dlmap").hide();
		var loader = new ImageLoader(target);
		loadingPanel.show('Loading map...');
		loader.loadEvent = function(url, image){ putImage(image, url); loadingPanel.hide(); TarURL = url; $("#dlmap").show(); };
		loader.load();
	} else {
		putImage(null, target); TarURL = target;
	}
}
$(document).ready(function(){ loadMap('/include/map/worldmap-3.gif'); });
</script>
@endsection
