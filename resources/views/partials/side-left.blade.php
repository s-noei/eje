<center>
@php
    $happyTime = ($sets['ht_start'] ?? 0) <= time() && ($sets['ht_due'] ?? 0) > time();
    $happyTime2 = ($sets['ht2_start'] ?? 0) <= time() && ($sets['ht2_due'] ?? 0) > time();
@endphp
@if ($happyTime)
        	<a href="{{ $vars->getURL('ejstore') }}">
        		<img src="/images/ads/happyTime.jpg" border="0" alt="Up to 50% discount on Tala packs in eJahan Store" width="120px">
        	</a>
@endif
@if ($happyTime2)
        	<a href="{{ $vars->getURL('chancebox', 'buy') }}">
        		<img src="/images/ads/happyTime2.jpg" border="0" alt="50% more points in buying chanceboxes" width="120px">
        	</a>
@endif
</center>
@if (!$isCA && $logged)
<script type="text/javascript" src="/include/js/tasks.js"></script>
<div id="hummytasks" style="display: none">
    YOUR TASKS
	<div id="task-sample" class="baloon">
		<div class="cover">
			<span class="due"></span>
		</div>
		<div class="pic">
			<img src="">
		</div>
		<div class="title">
			<a href="{{ $vars->getURL('company') }}">
				&nbsp;
			</a>
		</div>
	</div>
	<div class="hummy">
		&nbsp;
	</div>
</div>
@elseif (!$logged)
@php $cName = app(\App\Game\Services\Ip2Country::class)->get_country_name(); @endphp
<div id="hummytasks-out">
	<img src="/images/flags/l/{{ $cName ?: 'eJahan' }}.png" style="width: 70px; height: 60px">
    <br />
	<a href="{{ $vars->getURL('home') }}">
		<b>{{ $cName ?: 'Your country' }}</b><br>needs you!
	</a>
    <hr size="1" />
	I invite you to register in eJahan and play this game <b>FOR FREE!</b>
	Click here and receive more info about eJahan in main page.
    <br />
	<div class="hummy">
		&nbsp;
	</div>
</div>
@else
<img src="/images/ads/ad{{ rand(1, 3) }}.gif" width="100px">
@endif
@if ($logged)
		<hr>
		<center style="width: 95px; padding: 0px 10px">
			Your Inventory
			<hr size="1" color="black">
@foreach (($citInfo['inventory'] ?? []) as $i => $v)
			<div style="display: inline-block; text-align: center; width: 40px; border: 1px solid; border-radius: 3px; margin: 1px">
				<img src="/images/icons/{{ $v['Icon'] }}.png" width="40px">
				<img src="/images/game/{{ $v['Stars'] }}_star.gif" width="40px" style="border-bottom: 1px solid; border-top: 1px solid">
				{{ $v['Amount'] }}
			</div>
@endforeach
		</center>
@endif
<br>
