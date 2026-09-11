function useClinic()
{
	if (canclickC == 1) {
			$("#useclinic").css("display", "none");
			canclickC = 0;
			$.getJSON("clinic-"+CliToken+".html", function(data) {
					switch (data.result)
					{
						case 'done':
							changeElement('wellness', data.wellness);
							battlevars.canFight = data.fight;
							if (data.wellness < 20) {
									$("#fightbutton").removeClass("fightbut").addClass("fightbut-dis");
									$("#welmeter").css("background-color", "red");
								}else{
									$("#fightbutton").removeClass("fightbut-dis").addClass("fightbut");
									$("#welmeter").css("background-color", "green");
								}
							$("#wrem").html(data.wremain);
							$("#arem").html(data.aremain);
							if (data.remain){
									canclickC = 1;
									$("#useclinic").css("display", "inline-block");
                                }
							$("#cTT .recleft").text(data.remain);
							break;
					}
				});
		}
}

$(document).ready(function(){
		$("#useclinic").click(function(){
                useClinic();
            });
	});