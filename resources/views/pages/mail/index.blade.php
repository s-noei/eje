@extends('layouts.game')
@section('content')
<script>
	function checkUncheck(theElement) {
		var theForm = theElement.form, z = 0;
		for(z=0; z<theForm.length;z++){
			if(theForm[z].type == 'checkbox' && theForm[z].name != 'checkall'){ theForm[z].checked = theElement.checked; }
		}
	}
</script>
<div class="column-double">
@if ($target === 'compose')
@include('pages.mail.compose')
@elseif ($target === 'view')
<a href="{{ $vars->getURL('mail') }}" id=buttons>{{ $lang->getstr('pm_back', 'pm') }}</a><br>
@include('pages.mail.view')
@else
<div id=mailbar>
	 | <a href="{{ $vars->getURL('mail', 'compose') }}">{{ $lang->getstr('pm_compose', 'pm') }}</a>
	 | <a href="{{ $vars->getURL('mail') }}">{{ $lang->getstr('pm_inbox', 'pm') }}</a>
	 | <a href="{{ $vars->getURL('mail', 'sent') }}">{{ $lang->getstr('pm_sent', 'pm') }}</a>
	 | <a href="{{ $vars->getURL('mail', 'notes') }}">{{ $lang->getstr('pm_notes', 'pm') }}</a>
	 | <a href="{{ $vars->getURL('mail', 'requests') }}">{{ $lang->getstr('pm_reqs', 'pm') }}</a>
	 |
</div>
@include('pages.mail.' . $target)
{!! $accPaid ?: '' !!}
@endif
</div>
@endsection
