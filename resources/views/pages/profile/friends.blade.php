@if (count($friends) < 1)
	{!! $lang->getstr('profile_fri_no', 'profile') !!}
@else
@foreach (array_chunk($friends, 5) as $chunk)
<div>
@foreach ($chunk as $fris)
	<div style="padding: 10px; float: left; text-align: center; overflow: hidden">
		<a href="{{ $vars->getURL('profile', $fris['CitizenID']) }}">{!! $vars->getAvatar($fris, 'Avatar-s') !!}<br>{{ $fris['name'] }}</a>
		<br>
		<a href="{{ $vars->getURL('mail', 'compose', $fris['CitizenID']) }}"><img src="/images/game/icon_sendmsg.gif" class="inlineIMGs" align="absmiddle"></a>
@if ($view_own)
		<a href="{{ $vars->getURL('profile', $citInfo['CitizenID'], 'remove', $fris['CitizenID']) }}" onclick="return confirm('Are you sure?')"><img src="/images/game/icon_remove.gif" class="inlineIMGs" align="absmiddle"></a>
@endif
	</div>
@endforeach
</div>
@endforeach
@endif
