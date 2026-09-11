$(document).ready(function() {
	settings = {
		server: {
			getHSpeed:       30000,
			getFSpeed:     10000,
			getFDelay: 10000,
			getFDelayMax: 30000,
			size:      30
		},

		viewer: {
			initBattleDelay:       800,
			initDForceDelay:       800,
			initShowHDelay:    800,
			initShowAttDelay: 2000,
			initShowDefDelay: 2000,
			changeDFSpeed:        1200,
			maxFighters:      4,
			minFShowDelay:   5000
		}
	};

	battlevars = {
		battleID:   battleID,
		dForce:  defForce,
		sPoint:  secPoint,
		dueTime: dueTime,
		Status: Status,
		Phase: Phase,
		canFight: canFight,

		initial_fortifications_in_px: 0,
		extra_fortifications_in_px:   0,
		bad_fortifications_in_px:     0,

		attHero: [],
		defHero: [],
		attPrevHero: [],
		defPrevHero: [],
		attList: [],
		defList: [],
		already_queued:  [],
		attackers_queue: [],
		defenders_queue: []
	};

    minO = 0.05;
	viewer.initBattle();
	batserver.getHeroes();
	batserver.getFighters(settings['server']['size']);
	setInterval("viewer.showWall();", 30000);
    
    function getRadioCheckedValue(radio_name)
    {
       var oRadio = document.forms['fight'].elements[radio_name];
       for(var i = 0; i < oRadio.length; i++)
       {
          if(oRadio[i].checked)
          {
             return oRadio[i].value;
          }
       }
       return '';
    }
	
	$("#drjuicebf").mouseenter(function(){
			$("#jTT").fadeIn(100);
		});

	$("#drjuicebf").mouseleave(function(){
			$("#jTT").fadeOut(100);
		});

	$("#useclinic").mouseenter(function(){
			$("#cTT").fadeIn(100);
		});

	$("#useclinic").mouseleave(function(){
			$("#cTT").fadeOut(100);
		});

	$("#buyWP").mouseenter(function(){
			$("#wpTT").fadeIn(100);
		});

	$("#buyWP").mouseleave(function(){
			$("#wpTT").fadeOut(100);
		});

	$("#openfight").click(function(){
			if (battlevars.canFight == 1)
					$("#fightbox").fadeIn(500);
		});
	
	$("#fightbutton").click(function(){
			if (battlevars.canFight == 1) {
                    oForm = document.forms['fight'];
                    weap = oForm.elements['weapon'].value;
                    token = oForm.elements['token'].value;
                    forr = oForm.elements['for'].value;
					$("#fightbutton").removeClass("fightbut").addClass("fightbut-dis");
					$("#fightload").fadeIn(200);
        			$.getJSON("ajaxfight-"+battleID+"-"+weap+"-"+forr+"-"+token+".html", function(data) {
							if (data.result == 'done') {
									$(".my-inf #self-inf").html(data.myforce);
									$("#fight-report #force").html(data.force);
									$("#fight-report #pref").html(data.pref);
									$("#fight-report #mrank").html(data.mrank);
									$("#fight-report #skillinfo").html(data.skill);
									$("#fight-report #wellinfo").html(data.wellinf);
									$("#fight-report #rankinfo").attr({src: "images/game/war/mrank/"+data.rank+".gif", title: data.mrank, alt: data.mrank});
									$("#fight-report #epchange").html(data.ep);
									$("#fight-report #forcechange").html(data.totforce);
									if (data.clinic){
											canclickC = 1;
											$("#useclinic").css("display", "inline-block");
										}else{
											canclickC = 0;
										}
									CanDrJuice = 1;
									$("#juiceD").attr("src", "images/theme/drink.png");
									canclick = 1;
									changeElement('wellness', data.wellness);
									$(".background").fadeIn(250);
									$("#fight-report").fadeIn(250, function(){
											document.forms['fight'].elements['weapon'].value = data.maxq;
											$("#weapQ").attr("src", "images/game/"+data.maxq+"_star.gif");
										});
								}else
									$("#fightload").fadeOut(200);
							battlevars.canFight = data.fight;
							if (!data.fight) {
									$("#fightbutton").removeClass("fightbut").addClass("fightbut-dis");
									if (data.result == 'done') $("#welmeter").css("background-color", "red");
								}else
									$("#fightbutton").removeClass("fightbut-dis").addClass("fightbut");
                        });
                }
		});
    
	$("#changeweap").click(function(){
            $(".weapon-select").fadeTo(200, 0.5);
            $("#changeweap").fadeOut(200);
            $.get('getweap-'+document.forms['fight'].elements['weapon'].value+'.html', function(data) {
                    document.forms['fight'].elements['weapon'].value = data;
                    $("#weapQ").attr("src", "images/game/"+data+"_star.gif");
                    $(".weapon-select").fadeTo(200, 1);
                    $("#changeweap").fadeIn(200);
                });
        });
	$("#batStats").click(function(){
			$(".background").fadeIn(250);
			$.getJSON("ajaxstats-"+battleID+".html", function(data) {
					$(".bat-stats .handlers").text("");
					$(".bat-stats").fadeIn(250);
					$.each(data['att20'], function(idx, att20) {
            				var newstats = $("div#stat-att-temp").clone().appendTo("div#att20-handler");
                            newstats.attr("id", "att20-"+att20.id);
                            newstats.children("div.no").text((idx + 1));
                            newstats.children("div.fighter-name").children("a.fighter-link").attr("href", "profile-"+att20.id+".html");
                            newstats.children("div.fighter-name").children("a.fighter-link").text(att20.name);
                            newstats.children("div.fighter-fights").text(att20.fights);
                            newstats.children("div.fighter-force").text(-att20.totadv+"m");
                            newstats.children("div.fighter-average").text(-att20.avgadv+" m/hit");
                            newstats.fadeIn(100);
                        });
					$.each(data['attN'], function(idx, attN) {
            				var newstats = $("div#stat-att-temp").clone().appendTo("div#attN-handler");
                            newstats.attr("id", "attN-"+attN.id);
                            newstats.children("div.no").text((idx + 1));
                            newstats.children("div.fighter-name").children("a.fighter-link").attr("href", "profile-"+attN.id+".html");
                            newstats.children("div.fighter-name").children("a.fighter-link").text(attN.name);
                            newstats.children("div.fighter-fights").text(attN.fights);
                            newstats.children("div.fighter-force").text(-attN.totadv+"m");
                            newstats.children("div.fighter-average").text(-attN.avgadv+" m/hit");
                            newstats.fadeIn(100);
                        });

					$.each(data['def20'], function(idx, def20) {
            				var newstats = $("div#stat-def-temp").clone().appendTo("div#def20-handler");
                            newstats.attr("id", "def20-"+def20.id);
                            newstats.children("div.no").text((idx + 1));
                            newstats.children("div.fighter-name").children("a.fighter-link").attr("href", "profile-"+def20.id+".html");
                            newstats.children("div.fighter-name").children("a.fighter-link").text(def20.name);
                            newstats.children("div.fighter-fights").text(def20.fights);
                            newstats.children("div.fighter-force").text(def20.totadv+"m");
                            newstats.children("div.fighter-average").text(def20.avgadv+" m/hit");
                            newstats.fadeIn(100);
                        });
					$.each(data['defN'], function(idx, defN) {
            				var newstats = $("div#stat-def-temp").clone().appendTo("div#defN-handler");
                            newstats.attr("id", "defN-"+defN.id);
                            newstats.children("div.no").text((idx + 1));
                            newstats.children("div.fighter-name").children("a.fighter-link").attr("href", "profile-"+defN.id+".html");
                            newstats.children("div.fighter-name").children("a.fighter-link").text(defN.name);
                            newstats.children("div.fighter-fights").text(defN.fights);
                            newstats.children("div.fighter-force").text(defN.totadv+"m");
                            newstats.children("div.fighter-average").text(defN.avgadv+" m/hit");
                            newstats.fadeIn(100);
                        });
				});
		});
    
	$("#batStats2").click(function(){
			$.getJSON("ajaxstats-"+battleID+"-"+weap+"-"+forr+"-"+token+".html", function(data) {
					$.each(data['defList'], function(idx, defender) {
                            $("#fight-report #force").html(data.force);
                            $("#fight-report #pref").html(data.pref);
                            $("#fight-report #mrank").html(data.mrank);
                            $("#fight-report #skillinfo").html(data.skill);
                            $("#fight-report #wellinfo").html(data.wellinf);
                            $("#fight-report #rankinfo").attr({src: "images/game/war/mrank/"+data.rank+".gif", title: data.mrank, alt: data.mrank});
                            $("#fight-report #weapinfo").attr({src: "images/game/"+data.weapon+"_star.gif"});
                            $("#fight-report #wellchange").html(data.wellness);
                            $("#fight-report #epchange").html(data.ep);
                            $("#fight-report #forcechange").html(data.totforce);
                            changeElement('wellness', data.wellness);
                            $("#fight-report").fadeIn(250);
                            battlevars.canFight = data.fight;
                        });
                });
		});
    
	$("#attfightbutton").click(function(){
			if (battlevars.canFight == 1) {
					$("#fightbox").fadeIn(500);
					document.getElementById('fightAtt').checked = true;
				}
		});
	
	$("#deffightbutton").click(function(){
			if (battlevars.canFight == 1) {
					$("#fightbox").fadeIn(500);
					document.getElementById('fightDef').checked = true;
				}
		});
    
    $(".battle-field").mouseenter(function(){
            $(".battle-ind").fadeTo(500, 0.9);
        });
    
    $(".battle-field").mouseleave(function(){
            $(".battle-ind").fadeTo(500, minO);
        });

    $(".battle-time").mouseenter(function(){
            $(".battle-phase").slideDown(500);
            $(".timer-ind").fadeTo(500, 0.1);
        });
    
    $(".battle-time").mouseleave(function(){
            $(".battle-phase").slideUp(500);
            $(".timer-ind").fadeTo(500, 1);
        });

});

var batserver = {

	getHeroes: function() {
		$.getJSON("heroes-"+battlevars['battleID']+".html", function(data) {
			if (data.attacker) {
				co = 0;
				$.each(data['attacker'], function(idx, atthero) {
					co++;
					battlevars['attHero'][co] = atthero;
				});
			}

			if (data.defender) {
				co = 0;
				$.each(data['defender'], function(idx, defhero) {
					co++;
					battlevars['defHero'][co] = defhero;
				});
			}

			viewer.showHeroes();
			setTimeout("batserver.getHeroes();", window["settings"]["server"]["getHSpeed"]);
		});
	},


	getFighters: function(len) {
		if(battlevars['attList'].length < 10 && battlevars['defList'].length < 10) {
			var fListLink = "battle-"+battlevars['battleID']+"-log"+(len ? "-"+len:"")+".html";

			$.getJSON(fListLink, function(data) {
				if ((data["dForce"] != battlevars["dForce"] && data["dForce"]) || (battlevars.dueTime != data["End"]) || (battlevars.Status != data["Status"])) {
					battlevars["dForce"] = data["dForce"];
					battlevars.dueTime = data["End"];
					battlevars.Status = data["Status"];
					viewer.changeForce();
				}

				if (!data["attList"] && !data["defList"]) {
					batserver.expandDelay();
				} else {
					batserver.reset_delay_getting_fighters();
					$.each(data['attList'], function(idx, attacker) {
						if (!tools.inArray(attacker["fightID"], battlevars['already_queued'])) {
							battlevars['attList'].push(attacker);
						}
					});
					$.each(data['defList'], function(idx, defender) {
						if (!tools.inArray(defender["fightID"], battlevars['already_queued'])) {
							battlevars['defList'].push(defender);
						}
					});
				}
			});
		}
	},


	expandDelay: function() {
		settings['server']['getFSpeed'] += settings['server']['getFDelay'];
		if (settings['server']['getFSpeed'] > settings['server']['getFDelayMax']) {
			settings['server']['getFSpeed'] = settings['server']['getFDelayMax'];
		}
		clearInterval(battlevars['getFIntervar']);
		battlevars['getFInterval'] = setInterval("batserver.getFighters(settings['server']['size'])", settings['server']['getFSpeed']);
	},


	reset_delay_getting_fighters: function() {
		clearInterval(battlevars['getFInterval']);
		battlevars['getFInterval'] = setInterval("batserver.getFighters(settings['server']['size'])", settings['server']['getFSpeed']);
	}


}


var viewer = {

	initBattle: function() {
		$("div.battleinfo_loader").fadeOut();
		setTimeout('$("div.battleView").fadeIn();', window["settings"]["viewer"]["initBattleDelay"]);
		setTimeout("viewer.changeForce();",            window["settings"]["viewer"]["initDForceDelay"]);
		setTimeout("viewer.showHeroes();",                window["settings"]["viewer"]["initShowHDelay"]);
		if (canView) {
				setTimeout("viewer.showAtt();",            window["settings"]["viewer"]["initShowAttDelay"]);
				setTimeout("viewer.showDef();",            window["settings"]["viewer"]["initShowDefDelay"]);
				viewer.showWall();
			}
	},


	showHeroes: function() {
		for (i=1;i<=3;i++) {
			if (battlevars['attHero'][i]) {
				atthero = battlevars['attHero'][i];
				with ($("div.fight-arena div.heroes-att div.hero-"+i)) {
					fadeOut();
					co = 0;
					queue(function() {
						with ($(this)) {
							co++;
							atthero = battlevars['attHero'][co];
							$("img#attHeroAvatar-"+co).attr("src", "uploads/avatars/citizen/"+atthero['Avatar']);
							$("a#attacker_battlehero_avatar_link").attr("href", "profile-"+atthero['CitizenID']+".html");
							$("a#attHeroName-"+co).html(atthero['name']);
							$("a#attHeroName-"+co).attr("href", "profile-"+atthero['CitizenID']+".html");
							children("div.force").html(" "+(-atthero['advance'])+" ");
							fadeIn("fast");
							battlevars['attPrevHero'][co] = atthero;
							battlevars['attHero'][co] = NaN;
							dequeue();
						}
					});
				}
			}

			if (battlevars['defHero'][i]) {
				defhero = battlevars['defHero'][i];
				with ($("div.fight-arena div.heroes-def div.hero-"+i)) {
					fadeOut();
					co2 = 0;
					queue(function() {
						with ($(this)) {
							co2++;
							defhero = battlevars['defHero'][co2];
							$("img#defHeroAvatar-"+co2).attr("src", "uploads/avatars/citizen/"+defhero['Avatar']);
							$("a#defender_battlehero_avatar_link").attr("href", "profile-"+defhero['CitizenID']+".html");
							$("a#defHeroName-"+co2).html(defhero['name']);
							$("a#defHeroName-"+co2).attr("href", "profile-"+defhero['CitizenID']+".html");
							children("div.force").html(" "+defhero['advance']+" ");
							fadeIn("fast");
							battlevars['defPrevHero'][co2] = defhero;
							battlevars['defHero'][co2] = NaN;
							dequeue();
						}
					});
				}
			}
		}

	},


	showAtt: function() {
		var safe_to_proceed = true;
		if (battlevars['attList'].length && battlevars["attackers_queue"].length == settings["viewer"]['maxFighters']) {
			var last_in_que = battlevars["attackers_queue"].pop();
			if (tools.dateDiff(new Date(), last_in_que['visible_since']) >= settings["viewer"]['minFShowDelay']) {
				last_in_que['ref'].hide();
			} else {
				battlevars["attackers_queue"].push(last_in_que);
				safe_to_proceed = false;
			}
		}

		if (battlevars['attList'].length && safe_to_proceed) {
			var attacker = battlevars['attList'].shift();
			battlevars['already_queued'].push(attacker["fightID"]);
			var latest_in_queue = ({
				ref: $("div#fighter-att-temp").clone().insertAfter("div#fighter-att-temp"),
				visible_since: new Date()
			});
			battlevars["attackers_queue"].unshift(latest_in_queue);
			with (latest_in_queue["ref"]) {
				attr({id: (new Date()).getTime()});
				children("div.fighter-att").children("div.fighter-img").children("img").attr({src:'uploads/avatars/citizen/'+attacker['Avatar']});
				children("div.fighter-att").children("div.fighter-name").html(" "+attacker['name']+" ")
				children("div.fighter-att").children("div.fighter-force").html(-attacker['advance']+"m");

				show(200);
				queue(function() {
					setTimeout("viewer.showAtt();", 1000);
					with ($(this)) {
						children("*").css({width: "0px", display: "block"});
						children("*").animate({width: "100px"}, 250);
						dequeue();
					}
				});
			}
		} else {
			setTimeout("viewer.showAtt();", 2000);
		}
	},

	showDef: function() {
		var safe_to_proceed = true;
		if (battlevars['defList'].length && battlevars["defenders_queue"].length == settings["viewer"]['maxFighters']) {
			var last_in_que = battlevars["defenders_queue"].pop();
			if (tools.dateDiff(new Date(), last_in_que['visible_since']) >= settings["viewer"]['minFShowDelay']) {
				last_in_que['ref'].hide();
			} else {
				battlevars["defenders_queue"].push(last_in_que);
				safe_to_proceed = false;
			}
		}

		if (battlevars['defList'].length && safe_to_proceed) {
			var defender = battlevars['defList'].shift();
			battlevars['already_queued'].push(defender["fightID"]);
			$("div#fighter-def-temp").siblings("div.fighter-def").children("div.fighter-def").attr("class", "fighter grey");
			var latest_in_queue = ({
				ref: $("div#fighter-def-temp").clone().insertAfter("div#fighter-def-temp"),
				visible_since: new Date()
			});
			battlevars["defenders_queue"].unshift(latest_in_queue);
			with (latest_in_queue["ref"]) {
				attr({id: (new Date()).getTime()});
				children("div.fighter-def").children("div.fighter-img").children("img").attr({src:'uploads/avatars/citizen/'+defender['Avatar']});
				children("div.fighter-def").children("div.fighter-name").html(" "+defender['name']+" ")
				children("div.fighter-def").children("div.fighter-force").html(defender['advance']+"m");
				
				slideDown("fast");
				queue(function() {
					setTimeout("viewer.showDef();", 1000);
					with ($(this)) {
						children("*").css({width: "0px", display: "block"});
						children("*").animate({width: "100px"}, 250);
						dequeue();
					}
				});
			}
		} else {
			setTimeout("viewer.showDef();", 2000);
		}
	},


	changeForce: function() {
		var dForce = battlevars['dForce'];
		var dPer = 100 - (battlevars['dForce'] / battlevars['sPoint']) * 100;
/*
		if (dPer > 95) {
				if (Math.abs(dForce) >= 100)
						dPer = 95;
					else if (Math.abs(dForce) >= 10)
						dPer = 96;
					else
						dPer = 97;
			}
*/
		if (dPer > 100) dPer = 100;
		if (dPer < 0) dPer = 0;
		dPer *= 6.55;
		dPer += 75;
//		alert(dPer);
		if (battlevars.Status == 1) {
				dPer = 845;
				dForce = '';
                $(".battle-ind").fadeTo(500, 0);
			}else if (battlevars.Status == 2) {
				dPer = 0;
				dForce = '';
                $(".battle-ind").fadeTo(500, 0);
			}
		
		if (dForce <= 0) {
				ts = '<font color="red"><blink id="wallremain-blink">';
				te = '</blink></font>';
                minO = 0.75;
                $(".battle-ind").fadeTo(500, minO);
			}else if (dForce >= battlevars['sPoint']) {
				ts = '<font color="lime"><blink id="wallremain-blink">';
				te = '</blink></font>';
                minO = 0.75;
                $(".battle-ind").fadeTo(500, minO);
			}else{
				ts = '';
				te = '';
                minO = 0.05;
                $(".battle-ind").fadeTo(500, minO);
			}
		$("div.battle-field").children("div.battle-ind").children("span#wall-remain").html(ts+dForce+te);
		$("div.battle-field").children("div.battle-occupied").animate({
			width: dPer+"px"
			}, 500);

	},
	
	showWall: function() {
		$(".battle-ind").fadeTo(1000, 0.9);
		setTimeout("viewer.hideWall();", 5000);
	},

	hideWall: function() {
		$(".battle-ind").fadeTo(1000, minO);
	}

}


var tools = {

	dateDiff: function(date1, date2) {
		return (date1.getTime() - date2.getTime());
	},

	inArray: function(needle, haystack) {
		for(var i=0; i<haystack.length; i++) {
			if (haystack[i] == needle) {
				return true;
			}
		}
		return false;
	}

}

