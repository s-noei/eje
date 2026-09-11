$(document).ready(function() {
	chatvars = {

		chatList  : [],
		count     : '0',
		page      : '1',
		annInt    : '60000',
		msgInt    : '25000',
		interv    : '',
		anninterv : '',
		msgInterv : '',
		lastID    : 0,
		cangetmsg : 1
		
	};
	
	lastmsg = {
		id   : [],
		sent : '0'
	};
	
	chat.init();
	data.getChat();
	data.getAnnounce();
	
	$("#addmessage").click(function(){
			message = document.getElementById('yourmessage').value;
			$.post("addchat.html", { message: message }, function(data){
					if (data) chat.toggleMsg(data);
				});
			document.getElementById('yourmessage').value = '';
			data.getChat();
		});
	
});

var chatList = [];
var last = 0;

var data = {

	getChat: function() {
			$('#chatter-loader').fadeTo(300, 1);
			if (chatvars.cangetmsg == '1') {
					chatvars.cangetmsg = 0;
					$.getJSON("getchat-"+chatvars.lastID+".html", function(data) {
							if (data['chat'] != null) {
									$.each(data['chat'], function(id, chatter) {
											if (last == 0)
													lastID = "div#chatter-sample";
												else
													lastID = "div#"+last;
											var newchat = $("div#chatter-sample").clone().insertAfter(lastID);
											last = chatter['chatID'];
											newchat.attr({id: last});
											newchat.children("div.chat-sender").children("img.chat-avatar").attr({
												src:'uploads/avatars/citizen/'+chatter['Avatar'],
												title:chatter['name']
											});
											if (chatvars.lastID < chatter['chatID']) chatvars.lastID = chatter['chatID'];
											newchat.children("div.chat-message").html(
												'<a href="profile-'+chatter['citID']+'.html">'+chatter['name']+'</a>: '+chatter['message']//+'<br>'+getDiff(chatter['timestamp'])
											);
											newchat.fadeIn(500);
										});
								}
							last = 0;
							chatvars.cangetmsg = 1;
						});
				}
				
				clearInterval(chatvars.interv);
				chatvars.interv = setInterval("data.getChat();",chatvars.msgInt);
				chat.showPMs()
	
		},
	
	getAnnounce: function() {
			$.getJSON("getchat-"+chatvars.lastID+"-1.html", function(data) {
					if (data['chat'] != null) {
							$.each(data['chat'], function(id, chatter) {
									$("div#chatter-announce").children("div.chat-sender").children("img.chat-avatar").attr({
										src:'uploads/avatars/citizen/'+chatter['Avatar'],
										title:chatter['name']
									});
									$("div#chatter-announce").children("div.chat-message").html(
										chatter['name']+'(announce): '+chatter['message']//+'<br>'+getDiff(chatter['timestamp'])
									);
								});
						}
				clearInterval(chatvars.anninterv);
				chatvars.anninterv = setInterval("data.getAnnounce();",chatvars.annInt);
				chat.showAnn()
				});
		},
	
	addChat: function(message, form) {
			var field = document.getElementById('chatmsg');
			field.value = message;
			form.submit();
		}
}


var chat = {

	init: function() {
		$("div#chatters").fadeOut(function(){
				$("div#chatters").html("");
			});
		chatvars.interv = setInterval("data.getChat();",chatvars.msgInt);
		chatvars.anninterv = setInterval("data.getAnnounce();",chatvars.annInt);
	},

	showPMs: function() {
		$("div#chatters").show('500');
		$('#chatter-loader').fadeTo(300, 0.05);
	},
	
	toggleMsg: function(msg) {
		if (msg == '') {
				$("div#chat-msg").fadeOut(500);
				clearInterval(chatvars.msgInterv);
			}else{
				$("div#chat-msg").html(msg);
				$("div#chat-msg").fadeIn(500);
				clearInterval(chatvars.msgInterv);
				chatvars.msgInterv = setInterval("chat.toggleMsg('');", 5000);
			}
	},

	showAnn: function() {
//		$("div#chatter-announce").fadeIn('500');
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