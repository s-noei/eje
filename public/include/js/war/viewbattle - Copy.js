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
			maxFighters:      5,
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

	viewer.initBattle();
	batserver.getHeroes();
	batserver.getFighters(settings['server']['size']);
	
	$("#openfight").click(function(){
			if (battlevars.canFight == 1)
					$("#fightbox").fadeIn(500);
		});
	
	$("#fightbutton").click(function(){
			if (battlevars.canFight == 1)
					document.fight.submit();
		});
	
	$("#fightagain").click(function(){
			if (battlevars.canFight == 1)
					document.fight2.submit();
		});
	
	$(".attfightbutton").click(function(){
			if (battlevars.canFight == 1)
					document.fight_att.submit();
		});
	
	$(".deffightbutton").click(function(){
			if (battlevars.canFight == 1)
					document.fight_def.submit();
		});

});

var batserver = {

	getHeroes: function() {
		$.getJSON("heroes-"+battlevars['battleID']+".html", function(data) {
			if (data.attacker && data.attacker['name']) {
				if (!battlevars['attPrevHero'] || battlevars['attPrevHero']['name'] != data.attacker['name'] || battlevars['attPrevHero']['advance'] != data.attacker['advance']) {
					battlevars['attHero'] = data.attacker;
				}
			}

			if (data.defender && data.defender['name']) {
				if (!battlevars['defPrevHero'] || battlevars['defPrevHero']['name'] != data.defender['name'] || battlevars['defPrevHero']['advance'] != data.defender['advance']) {
					battlevars['defHero'] = data.defender;
				}
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
			}
	},


	showHeroes: function() {
		if (battlevars['attHero'] && battlevars['attHero']['name']) {
			with ($("div.battleView div.attacker div.hero")) {
				fadeOut();
				queue(function() {
					with ($(this)) {
						$("img#attHeroAvatar").attr("src", "uploads/avatars/citizen/"+battlevars['attHero']['Avatar']);
						$("a#attacker_battlehero_avatar_link").attr("href", "profile-"+battlevars['attHero']['CitizenID']+".html");
						$("a#attHeroName").html(battlevars['attHero']['name']);
						$("a#attHeroName").attr("href", "profile-"+battlevars['attHero']['CitizenID']+".html");
						children("div.force").html(" "+battlevars['attHero']['advance']+" ");
						fadeIn("fast");
						battlevars['attPrevHero'] = battlevars['attHero'];
						battlevars['attHero'] = NaN;
						dequeue();
					}
				});
			}
		}

		if (battlevars['defHero'] && battlevars['defHero']['name']) {
//			alert(battlevars['defHero']['advance']);
			with ($("div.battleView div.defender div.hero")) {
				fadeOut();
				queue(function() {
					with ($(this)) {
						$("img#defHeroAvatar").attr("src", "uploads/avatars/citizen/"+battlevars['defHero']['Avatar']);
						$("a#defender_battlehero_avatar_link").attr("href", battlevars['defHero']['url']);
						$("a#defHeroName").html(battlevars['defHero']['name']);
						$("a#defHeroName").attr("href", "profile-"+battlevars['defHero']['CitizenID']+".html");
						children("div.force").html(" "+battlevars['defHero']['advance']+" ");
						fadeIn("fast");
						battlevars['defPrevHero'] = battlevars['defHero'];
						battlevars['defHero'] = NaN;
						dequeue();
					}
				});
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
				children("div.fighter-att").children("div.fighter-force").html("hit "+attacker['advance']+"m with "+attacker['weapon']);

				show(200);
				queue(function() {
					setTimeout("viewer.showAtt();", 1000);
					with ($(this)) {
						children("*").fadeIn(250);
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
				children("div.fighter-def").children("div.fighter-force").html("hit "+defender['advance']+"m with "+defender['weapon']);
				
				slideDown("fast");
				queue(function() {
					setTimeout("viewer.showDef();", 1000);
					with ($(this)) {
						children("*").fadeIn(250);
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
		dPer *= 3.2;
		dPer += 105;
//		alert(dPer);
		if (battlevars.Status == 1) {
				dPer = 530;
				dForce = '';
			}else if (battlevars.Status == 2) {
				dPer = 0;
				dForce = '';
			}
		
		if (dForce <= 0) {
				ts = '<font color="red"><blink>';
				te = '</blink></font>';
			}else if (dForce >= battlevars['sPoint']) {
				ts = '<font color="lime"><blink>';
				te = '</blink></font>';
			}else{
				ts = '';
				te = '';
			}
		$("div.battle-field").children("div.battle-ind").html(ts+dForce+te);
		$("div.battle-field").children("div.battle-occupied").animate({
			width: dPer+"px"
			}, 500);

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

