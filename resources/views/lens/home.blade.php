@extends('lens.layout')
@section('content')
Welcome to eJahan Lens!<br>
Current version of eJahan Lens: v1.0 BETA<br>
Please choose your target from the navigation bar on the top!
<hr>
<b>Your information:</b><br>
<b>Name:</b> {{ $mod['name'] }}<br>
<b>Post:</b>
@switch($ulevel)
@case(4) Technical Moderator @break
@case(5) Local Moderator for {{ $mod['cName'] }} @break
@case(6) Police @break
@case(7) Super Moderator @break
@case(8) Website Guard @break
@case(9) Administrator @break
@endswitch
<br>
<b>Mod vio points:</b> {{ $mod['ModVio'] }}
@endsection
