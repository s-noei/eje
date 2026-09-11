<link rel="stylesheet" type="text/css" href="/include/css/donation.css">
@php $p = fn($k) => $lang->getstr($k, 'profile'); @endphp
@if ($remCons)
        <h3 class="infHandle">
			You have consumed food less than {{ $timeGP }} minutes ago.<br>
			Time remaining until next consume food available: <span id="consumeRem">&nbsp;</span>
		</h3>
@else
<script type="text/javascript">
    var hQ = {{ $qH }};
    var w = {{ (float) $citInfo['wellness'] }};
    function getChange(fQ) {
        wC = w + Math.round((7.5 - (w / 100)) * (fQ + hQ) * ({{ $gdMul }}));
        if (wC > 100) wC = 100;
        document.getElementById('newWellness').innerHTML = wC;
    }
</script>
<div id="donatehandle">
{!! $msg ?? '' !!}
    {!! $p('consume_phrase1') !!}
    <form action="" method="post" style="text-align: center;">
	@csrf
@if (!count($avail))
            <div class="buyfood">
                <a class="link" href="{{ $vars->getURL('market', 1) }}">
                    <img src="/images/game/tasks/market.png" width="50px" /><br />
                    {!! $p('consume_buyfood_title') !!}
                </a>
                {!! $p('consume_buyfood_desc') !!}
            </div>
@else
            <div style="display: inline-block; width: 524px; height: 50px; margin: 5px 0; margin-bottom: -6px; border: 1px solid; border-radius: 5px 5px 0 0; text-align: center">
                <img src="/images/icons/house.png" align="absmiddle" height="50px" />
                <img src="/images/game/{{ $qH }}_star.gif" align="absmiddle" />
            </div>
@for ($i = 1; $i <= 5; $i++)
                <div style="display: inline-block; width: 100px; height: 120px; margin: 5px 0; border: 1px solid; border-radius: 0 0 5px 5px; text-align: center">
                    <img src="/images/icons/food.png" /><br />
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
	Your food consumes on today and yesterday
	<blockquote>
		<hr>
@if (count($log) < 1)
		You didn't consume food today in these 2 days.
@else
@foreach ($log as $cons)
		<b>{{ date("H:i:s", $cons['timestamp']) }}</b> Received {{ $cons['change'] }} wellness<br>
@endforeach
@endif
	</blockquote>
</center>
@include('pages.profile.consume-timer')
