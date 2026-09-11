$(document).ready(function() {
	vars = {

		cangettask : 1
		
	};
	
	lastmsg = {
		id   : [],
		sent : '0'
	};
	
//	tasks.init();
	taskdata.getTask('current');	
	$("#nexttask").click(function(){
			taskdata.getTask('next');
		});
	
});

var chatList = [];
var last = 0;

var taskdata = {

	showTime: function(nowt, target, itemPic)
	{
		sTime = nowt;
		if (sTime > '0') {
		
				var itemsText = "Available on";
				if (itemPic == 'gold-pack' || itemPic == 'damge-booster') {
				itemsText = "Expires on";
				}
		
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
//				alert(target);
				$("#due-"+target).html(itemsText + "<br>" + dStart + dHour + ":" + dMin + ":" + dSec + dEnd);
				if (sTime >= '0') {
						nowt--;
						setTimeout("taskdata.showTime('" + nowt + "', '" + target + "', '" + itemPic + "')", 1000);
					}else{
						$("#cover-"+target).fadeOut(500);
					}
			}else{
				dStart = '<font color="red">';
				dEnd = '</font>';
				$("#cover-"+target).fadeOut(500);
			}
	},

	getTask: function(type) {
			if (vars.cangettask == '1' && (type == 'current' || type == 'next')) {
					vars.cangettask = 0;
                    hasTask = 0;
					if (type == 'next') $("#hummytasks").fadeOut(20);
					$.getJSON("tasks-current.html", function(data) {
							if (data['tasks'] != null) {
									$.each(data['tasks'], function(id, task) {
									        hasTask = 1;
                							if (last == 0)
                									lastID = "div#task-sample";
                								else
                									lastID = "div#task-"+last;
                							var newtask = $("div#task-sample").clone().insertAfter(lastID);
                							last++;
                							newtask.attr({id: "task-"+last});
                							if (task['time']) {
													newtask.children(".cover").children(".due").html("--");
													newtask.children(".cover").attr({id: "cover-"+last});
													newtask.children(".cover").children(".due").attr({id: "due-"+last});
													taskdata.showTime(task['time'], last, task['pic']);
												}
                							newtask.children(".pic").children("img").attr({
                								src:'images/game/tasks/'+task['pic']+'.png',
                								title:task['desc']
                							});
                							newtask.children(".title").children("a").html(task['title']);
                							newtask.children(".title").children("a").attr({
                								href:task['link'],
                								title:task['desc']
                							});
                							newtask.children(".desc").html(task['desc']);
                							if (task['skip'] == 0) $('#hummytasks #nexttask').hide();
                							newtask.css({display: "block"});
											if (!task['time']) {
													newtask.children(".cover").hide()
												}
										});
								}
							vars.cangettask = 1;
							if (hasTask) $("#hummytasks").fadeIn(500);
						});
				}
				
				clearInterval(vars.interv);
	
		}
}


var tasks = {

	showPMs: function() {
		$("div#chatters").show('500');
		$('#chatter-loader').fadeTo(300, 0.05);
	}
}

function getDiff(timestamp)
{
	now = parseInt(new Date().getTime().toString().substring(0, 10));
	diff = now - timestamp;
	if (diff == 0)
			return 'now';
	if (diff <= 60)
			return diff+' second(s) ago';
	if (diff <= 60*60)
			return Math.round(diff / 60)+' minute(s) ago';
	if (diff <= 24*60*60)
			return Math.round(diff / (60*60))+' hour(s) ago';
}