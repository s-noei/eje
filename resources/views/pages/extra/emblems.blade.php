@php $ref = url('/referrer-' . ($citInfo['CitizenID'] ?? 0) . '.html'); $base = url('/'); @endphp
<h3>Banners</h3>
<blockquote>
	Userbar 1 (linked to your personal referral link):
	<blockquote>
		<img src="/images/banners/ub1.gif">
		<textarea cols="40" rows="5">&lt;a href="{{ $ref }}"&gt;&lt;img src="{{ $base }}/images/banners/ub1.gif" title="eJahan"&gt;&lt;/a&gt;</textarea>
	</blockquote>
	Userbar 2 (linked to your personal referral link):
	<blockquote>
		<img src="/images/banners/ub2.gif">
		<textarea cols="40" rows="5">&lt;a href="{{ $ref }}"&gt;&lt;img src="{{ $base }}/images/banners/ub2.gif" title="eJahan"&gt;&lt;/a&gt;</textarea>
	</blockquote>
</blockquote>
<hr>
<h3>Your emblem</h3>
<blockquote>
	<img src="/emblem-{{ $citInfo['CitizenID'] ?? 0 }}.gif" alt="eJahan">
	<textarea cols="40" rows="5">&lt;a href="{{ $ref }}"&gt;&lt;img src="{{ $base }}/emblem-{{ $citInfo['CitizenID'] ?? 0 }}.gif" title="eJahan"&gt;&lt;/a&gt;</textarea>
</blockquote>
