var myAd = 0;
var activeAd = 0;

$("#Ad_1").fadeIn(200);
function changeAd()
{
	oldAd = activeAd;
	activeAd++;
	if (activeAd > 3) activeAd = 1;
	$("#Ad_"+oldAd).slideUp(100);
	$("#Ad_"+activeAd).slideDown(200);
	clearInterval(myAd);
	myAd = setInterval("changeAd();",10000);
}

changeAd();