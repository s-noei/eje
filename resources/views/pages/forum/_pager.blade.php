{{-- $go, $pid, $page, $numPages --}}
@if ($numPages > 1)
@php $u = fn($p) => $vars->getURL('forum', $go, $pid, $p); @endphp
<div style="clear: both"></div>
@if ($page == 1)
	<div class="page-sel">1</div>
@elseif ($page == 2)
	<div class="page-nav"><a href="{{ $u(1) }}">1</a></div><div class="page-sel">2</div>
@elseif ($page == 3)
	<div class="page-nav"><a href="{{ $u(1) }}">1</a></div><div class="page-nav"><a href="{{ $u(2) }}">2</a></div><div class="page-sel">3</div>
@else
	<div class="page-nav"><a href="{{ $u(1) }}">1</a></div><div class="page-etc">...</div><div class="page-nav"><a href="{{ $u($page - 1) }}">{{ $page - 1 }}</a></div><div class="page-sel">{{ $page }}</div>
@endif
@if ($page == $numPages - 1)
	<div class="page-nav"><a href="{{ $u($numPages) }}">{{ $numPages }}</a></div>
@elseif ($page == $numPages - 2)
	<div class="page-nav"><a href="{{ $u($numPages - 1) }}">{{ $numPages - 1 }}</a></div><div class="page-nav"><a href="{{ $u($numPages) }}">{{ $numPages }}</a></div>
@elseif ($page < $numPages)
	<div class="page-nav"><a href="{{ $u($page + 1) }}">{{ $page + 1 }}</a></div><div class="page-etc">...</div><div class="page-nav"><a href="{{ $u($numPages) }}">{{ $numPages }}</a></div>
@endif
<div style="clear: both"></div>
@endif
