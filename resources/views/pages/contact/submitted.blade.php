@extends('layouts.game')
@section('content')
				<h3 class="infHandle">
					Your ticket is submitted with ID {{ $tID }}.
					Your eJahan moderation team will process your ticket.<br>
					You will be informed in notes if the moderation team answers your ticket. You can add posts in your ticket if you wanted.
				</h3>
				<a href="{{ $vars->getURL('contact') }}" class="button-blue-1">Back to contact</a>
@endsection
