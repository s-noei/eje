canBuyWP = 1;
$(document).ready(function(){
	$("#buyWP").click(function(){
			if (canBuyWP) {
					$("#buyWP").removeClass('button-blue-1').addClass('button-gray-1');
					canBuyWP = 0;
					$.getJSON("buywp.html", function(data) {
							switch (data.result)
							{
								case 'money':
									$("#fightmsg").html("<h3 class=\"errHandle\">Your money is not enough. You need at least 2 Tala</h3>");
									break;
								case 'wellness':
									$("#fightmsg").html("<h3 class=\"errHandle\">Your wellness is 100.</h3>");
									break;
								case 'finished':
									$("#fightmsg").html("<h3 class=\"errHandle\">You cannot buy more wellness packs.</h3>");
                                    if (!data.remain) $("#buyWP").fadeOut(200);
									break;
								case 'done':
									$("#fightmsg").html("<h3 class=\"infHandle\">You've bought a wellness pack! Your new wellness is "+data.wellness+"<br>"
										+"You can buy "+data.remain+" more wellness packs today.</h3>");
									
									changeElement('wellness', data.wellness);
									changeElement('tala', data.money);
									$("#wpTT .recleft").text(data.remain);
									$("#wpTT .wpPrice").text(data.cost);
									battlevars.canFight = data.fight;
                                    $("#buyWP img").attr("title", data.cost);
                                    if (data.wellness < 20) {
                                            $("#fightbutton").removeClass("fightbut").addClass("fightbut-dis");
                                            $("#welmeter").css("background-color", "red");
                                        }else{
                                            $("#fightbutton").removeClass("fightbut-dis").addClass("fightbut");
                                            $("#welmeter").css("background-color", "green");
                                        }
									break;
							}
							canBuyWP = 1;
							$("#buyWP").removeClass('button-gray-1').addClass('button-blue-1');
						});
				}
		});
	});