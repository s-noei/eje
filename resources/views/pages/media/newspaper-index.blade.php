@extends('layouts.game')
@section('content')
<h1>Newspaper</h1>
<div width=90%>
		<h4>
			In eJahan, you can have a newspaper and publish your articles there. Your articles will be shown in "Latest news"
			list just after you create them. If your article collects enough votes from other citizens, it will be listed in "Top-Rated"
				list, and in a higher grade, in "International Top-rated" list!
		</h4>
		<a href="{{ $vars->getURL('create', 'newspaper') }}" class="button-blue-1">Create a newspaper</a>
</div>
@endsection
