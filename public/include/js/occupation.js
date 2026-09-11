function showOcc(sTime)
{
	if (sTime > '0') {
			var lTime = sTime;
			var dSec = lTime % 60;
			if (dSec < '10') dSec = '0' + dSec;
			lTime -= dSec;
			lTime /= 60;
			var dMin = lTime % 60;
			if (dMin < '10') dMin = '0' + dMin;
			lTime -= dMin;
			lTime /= 60;
			var dHour = lTime;
			if (dHour < '10') dHour = '0' + dHour;
			dStart = '';
			dEnd = '';
			if (dHour == '00' && dMin < '10') {
					dStart = '<font color="red">';
					dEnd = '</font>';
				}
			document.getElementById("occDue").innerHTML = dStart + dHour + ":" + dMin + ":" + dSec + dEnd;
			if (sTime == '0') history.go(0);//document.getElementById('occDue').innerHTML = dStart + 'CLOSED' + dEnd;
			if (sTime >= '0') setTimeout("showOcc('" + (sTime - 1) + "')", 1000);
		}else{
			history.go(0);
		}
}

