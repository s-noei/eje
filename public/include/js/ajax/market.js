String.prototype.format = function() {
  var args = arguments;
  return this.replace(/{(\d+)}/g, function(match, number) { 
    return typeof args[number] != 'undefined'
      ? args[number]
      : match
    ;
  });
};

function viewOffers(i, q, c)
{
	vars.iID = (i ? i : vars.iID);
	vars.qID = (q ? q : vars.qID);
	vars.cID = (c ? c : vars.cID);
//	alert(iID+"+"+qID+"+"+cID);
	data.getMarket();
	return false
}

$(document).ready(function(){
	vars = {

		cangetoffers : 1,
		iID : iID,
		qID : qID,
		cID : cID,
		last: 0
		
	};
	
//	if (vars.iID && vars.cID) data.getMarket();
	
	$("div#industries .box-element a").click(function(){
		viewOffers($(this).attr("id"), 0, 0);
	});

	$("div#stars .box-element a").click(function(){
		viewOffers(0, $(this).attr("id"), 0);
	});
	
	
});
	function buyProduct(OfferID)
	{
		Industry = $("#offer_"+OfferID+" input[name=Industry]").val();
		Stars = $("#offer_"+OfferID+" input[name=Stars]").val();
		actOffer = OfferID;
		token = $("#offer_"+OfferID+" input[name=token]").val();
		amount = $("#offer_"+OfferID+" .txtAmount").val();
//		$.post("ajax-market-buy.html", { Industry: Industry, Stars: Stars, actOffer: actOffer, token: 'a'+token, amount: amount }, function(data){
//		$(".ajax-informer").fadeOut(500, function(){
		$.post("ajax-market-buy.html", $("#offer_"+OfferID).serialize(), function(data){
				if (data['result']) {
					switch (data['result'])
					{
						case 'cheat':
							info = 'Cheating detected...';
							stat = 1;
							break;
						case 'invalid':
							info = 'Invalid amount was entered.';
							stat = 1;
							break;
						case 'notenough':
							info = 'The company doesn\'t have the amount you entered.';
							stat = 1;
							break;
						case 'full':
							info = 'Your inventory is full.';
							stat = 1;
							break;
						case 'lowmoney':
							info = 'You don\'t have enough money in your account.';
							stat = 1;
							break;
						case 'done':
							info = "You have successfully bought {0} products for {1} {2}.".format(data['data'][0], data['data'][1], data['data'][2]);
							if (data['data'][4] > 0)
								$("#stock_"+data['data'][3]).text(data['data'][4]);
							else
								$("#offer_"+data['data'][3]).slideUp(500);
							
							$("#money-ind").text(data['data'][5]);
							changeElement('local', data['data'][5]);
							$("#free-ind").text(data['data'][6]);
							stat = 2;
					}
					$(".ajax-informer").text(info);
					$(".ajax-informer").fadeIn(500, function(){
						if (stat == 1)
							$(".ajax-informer").css({"border": "2px solid red", "color": "red"})
						else
							$(".ajax-informer").css({"border": "2px solid green", "color": "green"})
					});
				}
			}, "json");
	}

var data = {

	getMarket: function() {
			if (vars.cangetoffers == '1') {
//					alert("ajax-market-view-"+vars.iID+"-"+vars.qID+"-"+vars.cID+".html");
					lastOff = 0;
					lastID = '';
					vars.cangetoffers = 0;
					$.getJSON("ajax-market-view-"+vars.iID+"-"+vars.qID+"-"+vars.cID+".html", function(data) {
							if (data['result']) {
									$.each(data['data'], function(id, offer) {
											if (!lastOff)
													lastID = "div#market-offer-sample";
												else
													lastID = "div#"+lastOff;
//											alert(lastID);
											var newOff = $("div#market-offer-sample").clone().insertAfter(lastID);
											lastOff = offer['OfferID'];
											newOff.attr({id: lastOff});
											lastID = offer['OfferID'];
											newOff.children(".market-form").attr({id: "offer_"+lastOff})
											newOff.children(".market-form").children("div.provider").children("a.link").text(offer['Name']);
											newOff.children(".market-form").children("div.provider").children("a.link").attr({
												href:'company-'+offer['CompanyID']+'.html'
											});
											newOff.children(".market-form").children("div.stars").children("img.inlineIMGs").attr({
												src:'images/game/'+offer['Quality']+'_star.gif',
												title:offer['Quality']
											});
											newOff.children(".market-form").children("div.stock").text(offer['Stock']);
											newOff.children(".market-form").children("div.price").text(offer['tPrice']);
											newOff.fadeIn(500);
										});
								}else{
									$("#market-status").text(no_offers);
								}
							last = 0;
							vars.cangetoffers = 0;
						});
				}
		},
	
};