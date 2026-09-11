@extends('layouts.game')
@section('content')
&nbsp;<p>
<font size="7" color="#800000" style="font-family: 'Hobo Std'">Pay attention, please!</font><br>
<p class="errHandle">Your citizen is hibernated...</p>
<p>You can revive yoiur citizen by clicking the button below. Your citizen will start playing again with the wellness of 10.</p>
<p><b>CAUTION:</b> You have a few days to do this action. After that, your citizen will become <b>DEAD</b> and you cannot login to your account anymore.</p>
<p><b>HINT:</b> Don't forget to buy food for your citizen.</p>
<p>Regards,<br>eJahan team</p>
<a href="{{ $vars->getURL('revive') }}" id="buttons">Revive my citizen</a>
@endsection
