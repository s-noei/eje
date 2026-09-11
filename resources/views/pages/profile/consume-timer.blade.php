@if ($remCons)
<script type="text/javascript">
	var dTime = {{ $remCons - time() }};
	function refreshTime() {
		tflag = dTime;
		hours = Math.floor(tflag / 3600); tflag -= hours * 3600;
		minutes = Math.floor(tflag / 60); tflag -= minutes * 60;
		seconds = tflag;
		t_hours = hours < 10 ? "0"+hours : hours;
		t_minutes = minutes < 10 ? "0"+minutes : minutes;
		t_seconds = seconds < 10 ? "0"+seconds : seconds;
		$("#consumeRem").text(t_hours+":"+t_minutes+":"+t_seconds);
		dTime--;
		if (dTime >= 0) setTimeout("refreshTime()", 1000);
	}
	refreshTime();
</script>
@endif
