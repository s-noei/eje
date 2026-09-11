<link rel="stylesheet" type="text/css" href="/include/css/donation.css">
<a href="{{ $vars->getURL('profile', $row['CitizenID']) }}" class="button-blue-1">Back to profile page</a>
<div>
@php
	$tit = "cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]";
	$iURLs = ["hero"=>"bh", "revolt"=>"rs"];
	$iTitles = ["hero"=>"Became a battle hero", "revolt"=>"Revolt was successful", "is"=>"Imperishable Soldier", "cp"=>"Won country presidency elections",
		"cg"=>"Became a congress member", "pp"=>"Became a party president", "lm"=>"Finished an explore session<br>in less than 30 tries",
		"am"=>"Became an ambassador of eJahan", "gg"=>"Invited 15 active players", "mp"=>"Collected 1000 subscribers",
		"ap"=>"Article collected enough voters in 2 days", "wf"=>"Collected 1000 friends"];
@endphp
@foreach ($diaries as $co => $diar)
		<div style="background: url('/images/game/profile/trophy/{{ $iURLs[$diar['dType']] ?? $diar['dType'] }}.gif') 50% 0% no-repeat; width: 64px; height: 64px; border: 1px solid; float: left; -moz-border-radius: 10px"
			title="{{ sprintf($tit, $iTitles[$diar['dType']] ?? $diar['dType'], 'Received on day '.$diar['Day']) }}">
		</div>
@if (!(($co + 1) % 8))<div style="clear: both"></div>@endif
@endforeach
<div style="clear: both"></div>
</div>
