{{-- $links: [[label, url]] --}}
<center>
@foreach ($links as $i => [$label, $href])
@if ($i > 0) | @endif
<a href="{{ $href }}">{{ $label }}</a>
@endforeach
</center>
<hr>
