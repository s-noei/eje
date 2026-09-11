var canclick = 1;
var JOstat = 0;
var JCstat = 0;
function drinkJuice(suffix)
{
	if (canclick == 1) {
			if (!suffix)
                    $("#drjuice").fadeOut(100);
                else
                    $("#juice-img").attr("src", "images/game/war/juice-g.png");
			canclick = 0;
			$.getJSON("juice-"+OwnCit+"-"+OwnToken+".html", function(data) {
					switch (data.result)
					{
						case 'nojuice':
                            if (confirm('You have no juice in your inventory. Do you want to go to marketplace to buy some juice?'))
							     location.href = data.url;
							canclick = 1;
							break;
						case 'finish':
							alert("You received 200 wellness today");
							$("#juiceD").attr("src", "images/theme/juice-end.png");
							break;
						case 'wfull':
							alert("You currently have 100 wellness");
							$("#juiceD").attr("src", "images/theme/juice-end.png");
							break;
						case 'done':
							changeElement('wellness', data.wellness);
							if (typeof(battlevars) != "undefined") battlevars.canFight = data.fight;
							if (data.wellness < 20) {
									$("#fightbutton").removeClass("fightbut").addClass("fightbut-dis");
									$("#welmeter").css("background-color", "red");
								}else{
									$("#fightbutton").removeClass("fightbut-dis").addClass("fightbut");
									$("#welmeter").css("background-color", "green");
								}
							$("#arem").html(data.remain);
							if (!data.remain || data.wellness == 100)
									$("#juiceD").attr("src", "images/theme/juice-end.png");
								else if (!data.jremain){
									$("#drjuice").attr("href", juiceLink);
									$("#juiceD").attr("src", "images/theme/juice-buy.png");
								}else
									canclick = 1;
							$("#jTT .recleft").text(data.remain);
							break;
					}
        			if (!suffix)
                            $("#drjuice").fadeIn(100);
                        else
                            $("#juice-img").attr("src", "images/game/war/juice.png");
				});
		}
}

$(document).ready(function(){
		$("#drjuice").mouseenter(function(){
				if (!JOstat) {
						$("#juiceTog").fadeIn(500, function(){JOstat = 0});
						JOstat = 1;
					}
			});
		
		$("#drjuice").mouseleave(function(){
				if (!JCstat) {
						$("#juiceTog").fadeOut(200, function(){JCstat = 0});
						JCstat = 1;
					}
			});
		
		$("#drjuicedis").mouseenter(function(){
				if (!JOstat) {
						$("#juiceTog").fadeIn(500, function(){JOstat = 0});
						JOstat = 1;
					}
			});
		
		$("#drjuicedis").mouseleave(function(){
				if (!JCstat) {
						$("#juiceTog").fadeOut(200, function(){JCstat = 0});
						JCstat = 1;
					}
			});
		
		$("#drjuice").click(function(){
                if (CanDrJuice) drinkJuice("");
            });
        
		$("#drjuicebf").click(function(){
                if (CanDrJuice) drinkJuice("bf");
            });
        
		$(".ofjuice").click(function(){
				if (canclick == 1) {
						$(".ofjuice").hide();
						canclick = 0;
						$.getJSON("juice-"+targetCit+"-"+token+".html", function(data) {
								switch (data.result)
								{
									case 'nojuice':
										toggleMsg("<h3 class=\"errHandle\">You have no juice in your inventory.</h3>");
										break;
									case 'afinish':
										toggleMsg("<h3 class=\"errHandle\">The citizen received 20 juices today</h3>");
										break;
									case 'finish':
										toggleMsg("<h3 class=\"errHandle\">The citizen has received all available wellness today</h3>");
										break;
									case 'wfull':
										toggleMsg("<h3 class=\"errHandle\">The citizen currently has 100 wellness</h3>");
										break;
									case 'done':
										toggleMsg("<h3 class=\"infHandle\">Successfully offered a "+data.quality+"-star juice! New wellness is "+data.wellness+"<br>"
											+"This citizen can receive "+data.aremain+" juices/"+data.wremain+" wellness today</h3>");
										break;
								}
								$(".ofjuice").show();
							});
					}
			});
	});

var timeot;

function toggleMsg(msg) {
	if (msg == '') {
			$("div#juicemsg").slideUp(250);
			clearInterval(timeot);
		}else{
			$("div#juicemsg").html(msg);
			$("div#juicemsg").slideDown(250);
			clearInterval(timeot);
			timeot = setInterval("toggleMsg('');", 5000);
			canclick = 1;
		}
}
