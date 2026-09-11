<link rel="stylesheet" type="text/css" href="/include/css/donation.css">
@php $p = fn($k) => $lang->getstr($k, 'profile'); @endphp
@if ($remCons)
        <h3 class="infHandle">
			You have consumed Health Kit less than 20 minutes ago.<br>
			Time remaining until next consume Health Kit available: <span id="consumeRem">&nbsp;</span>
		</h3>
@else
<script type="text/javascript">
    var w = {{ (float) $citInfo['wellness'] }};
    function getChange(fQ) {
        wC = w + Math.round(20 * fQ + w);
        if (wC > 100) wC = 100;
        document.getElementById('newWellness').innerHTML = wC;
    }
</script>
<div id="donatehandle">
{!! $msg ?? '' !!}
    {!! $p('consume_phrase3') !!}
    <form action="" method="post" style="text-align: center;">
	@csrf
@if (!count($avail))
            <div class="buyfood">
                <a class="link" href="{{ $vars->getURL('special') }}">
                    <img src="/images/game/tasks/market.png" width="50px" /><br />
                    {!! $p('consume_buyhealthkit_title') !!}
                </a>
                {!! $p('consume_buyhealthkit_desc') !!}
            </div>
@else
@for ($i = 1; $i <= 5; $i++)
                <div style="display: inline-block; width: 100px; height: 120px; margin: 5px 0; border: 1px solid; border-radius: 0 0 5px 5px; text-align: center">
                    <img src="/images/icons/health-kit.png" /><br />
                    <img src="/images/game/{{ $i }}_star.gif" /><br />
                    <input type="radio" name="quality" onclick="getChange({{ $i }})" value="{{ $i }}"{{ isset($avail[$i]) ? '' : ' disabled' }} style="cursor: pointer;" />
                </div>
@endfor
                <br />
                {!! sprintf($p('consume_phrase2'), '<div id="newWellness" style="font-size: 14pt; color: green;">'.$citInfo['wellness'].'</div>') !!}
                <br />
                <input type="submit" name="subconsume" class="submit-blue-1" onclick="return confirm('Are you sure?')" value="{{ $p('consume_consume') }}" />
@endif
    </form>
</div>
@endif
<hr>
<center>
	Your Health Kit consumes on today and yesterday.
	<blockquote>
		<hr>
@if (count($log) < 1)
		You didn't consume Health Kit today in these 2 days.
@else
@foreach ($log as $cons)
		<b>{{ date("H:i:s", $cons['timestamp']) }} </b> Received {{ $cons['change'] }} wellness<br>
@endforeach
@endif
	</blockquote>
</center>
@include('pages.profile.consume-timer')
