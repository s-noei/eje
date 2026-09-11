<blockquote>
	<h3>Declare war</h3>
	<div class="law-reminder">You can only start war with a country that has at least a border with you. The war becomes active just after approve. Also you must know that the congressmen of the target country will announce about this law</div>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">War price:</div>
		<div class="law-new-content"><span id="pricehold">0</span> <img src="/images/flags/s/eJahan.gif" class="Flag-xs"></div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Start war with:</div>
		<div class="law-new-content">
			<select name="Country" onchange="loadAjax('/include/ajFunc.php?q=getWDPrice&p='+this.value+'&q2={{ $citInfo['CountryID'] }}', 'pricehold')">
@foreach ($form['countries'] as $coun)
				<option value="{{ $coun['CountryID'] }}">{{ $coun['cName'] }}</option>
@endforeach
			</select>
		</div>
		@include('pages.country.law._debate', ['sub' => 'substwar'])
	</form>
</blockquote>
