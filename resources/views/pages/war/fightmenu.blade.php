<div id="fightbox" style="text-align: justify; padding: 2px; border: 0px solid; margin-top: 2px; -moz-border-radius: 5px">
{!! $rageMsg ?? '' !!}
@if (count($rages ?? []))
	<hr>
	<b>Hit a rage</b>
	<blockquote>
		<hr size="1">
		<form action="" method="post" onsubmit="return confirm('Are you sure? A rage produces VERY HARD, so be careful and don\'t waste it!')">
			@csrf
			<select name="rageID">
				<option value="0">--- SELECT ---</option>
@foreach ($rages as $rage)
				<option value="{{ $rage['pID'] }}">{{ $rage['Stars'] }}-star Rage</option>
@endforeach
			</select>
			-->
			<select name="regID">
				<option value="0">------ SELECT THE TARGET ------</option>
@foreach ($rageRegions as $reg)
				<option value="{{ $reg['RegionID'] }}">{{ $reg['rName'] }} (pop.: {{ $reg['stat_pop'] }} - Avg. wellness: {{ $reg['avgWell'] }})</option>
@endforeach
			</select>
			<br>
			<input type="submit" name="subhitrage" class="submit-red-1" value="Fire!">
		</form>
	</blockquote>
@endif
</div>
