function clock(id, hours, minutes, seconds)
{
	seconds += 1;
	if(seconds > 59) {
		seconds = 0;
		minutes += 1;
	}
	if(minutes > 59) {
		minutes = 0;
		hours += 1;
	}
	if(hours > 23) {
		hours = 0;
	}

	t_hours = hours;
	t_minutes = minutes;
	t_seconds = seconds;
	
	if(seconds < 10) {
		t_seconds = "0"+seconds;
	}
	if(minutes < 10) {
		t_minutes = "0"+minutes;
	}
	if(hours < 10) {
		t_hours = "0"+hours;
	}
	
	time = t_hours+":"+t_minutes+":"+t_seconds;
	timeID = document.getElementById(id);
	timeID.innerHTML = time;
	
	setTimeout("clock(\""+id+"\", "+hours+", "+minutes+", "+seconds+")", 1000);
}

function changeElement(where, what)
{
	switch(where)
	{
		case 'tala':
		case 'local':
			$("#holder-"+where).html(what);
			break;
		case 'pm':
		case 'note':
		case 'req':
			$("#holder-"+where).html(what);
			if (what == 0)
					$("#holder-img-"+where).attr("src", "images/theme/no_new_"+where+".png");
			break;
		case 'wellness':
			$("#wncaption").html(what);
			$("#wellness-cap").html(what);
			$("#holder-wellness").css("height", (100 - what)+"%");
			$("#welmeter").animate({"width": (what * 1.88)+"px"}, 800);
			$("#wellness-view").animate({"width": (what * 1.1) + "px"}, 400);
			break;
	}
}

/* Friendship requests */

function FRAct(fID, todo, token)
{
    $("#fri_"+fID+" div.fri-wait").fadeTo(100, 0.75);
	$.getJSON("friendship-"+todo+"-"+fID+"-"+token+".html", function(data) {
			if (data.result == 'done')
                textX = "Friend request "+todo+"ed";
            else if (data.result == 'dup')
                textX = "Error in processing";
            else if (data.result == 'err')
                textX = "Cheating?!";
            $("#fri_"+fID+" div.fri-wait").fadeTo(100, 0.9);
            $("#fri_"+fID+" div.fri-wait").text(textX);
            changeElement('req', $("#holder-req").text() - 1);
            setTimeout("removeDiv('fri_"+fID+"')", 4000);
		});
}

function removeDiv(ID)
{
    $("#"+ID).fadeOut(200)
}

function openWindow(url, x, y)
{
	$.get(url, function(data) {
		$(".background").fadeIn(500, function(){
			$("#window").fadeIn(100, function(){
				$("#window").css({border: "2px solid green", "border-radius": "5px"});
				$("#window").animate({width: x+"px", height: y+"px", padding: "5px"
									, top: (($(window).height() - y) / 2) + $(window).scrollTop() + "px"
									, left: (($(window).width() - x) / 2) + $(window).scrollLeft() + "px"
									}, 1000, function(){
					$("#window #X").fadeIn(250);
					$("#window #windowContent").html(data);
				});
			});
		});
	});
}

function closeWindow()
{
	$("#window #windowContent").html("");
	$("#window #X").fadeOut(100);
	$("#window").css({border: "0"});
	$("#window").animate({width: "0px", height: "0px", padding: "0px"
						, top: ($(window).height() / 2) + $(window).scrollTop() + "px"
						, left: ($(window).width() / 2) + $(window).scrollLeft() + "px"
						}, 1000, function(){
		$("#window").fadeOut(100, function(){
			$(".background").fadeOut(500);
		});
	});
}