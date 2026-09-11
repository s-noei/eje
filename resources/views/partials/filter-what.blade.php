{{-- One "what" filter block: $id, $head, $img, $alt --}}
<div id="{{ $id }}" class="what">
	<div class="what-head">{!! $head !!}</div>
	<a href="javascript:void(0)">
		<div class="what-body">
			<img src="{{ $img }}" class="whatIMG" alt="{{ $alt ?? '' }}"><br>
		</div>
		<div class="what-foot">
			<img src="/images/arrow-down.jpg" class="selectIMG">
		</div>
	</a>
</div>
