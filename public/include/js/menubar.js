/*

Main Javascript for jQuery Realistic Hover Effect
Created by Adrian Pelletier
http://www.adrianpelletier.com

*/

/* =Realistic Navigation
============================================================================== */

	// Begin jQuery
	
var oldTarget = 'Home';
var menuOp = 0;
function changeDiv(target, dtitle) {
	$("#upmenubar").animate({"height": "155px"}, 500, 'linear', function(){
		if (oldTarget != target) {
				$("ul#"+oldTarget).fadeOut("fast", function(){
						$("ul#"+target).fadeIn(100);
						oldTarget = target;
					});
			}else{
		        $("ul#"+oldTarget).fadeOut("fast", function(){
                    	$("#upmenubar").animate({"height": "50px"}, 500, 'linear');
                    });
				oldTarget = 'Home';
			}
	});
}

	$(document).ready(function() {

	/* =Reflection Nav
	-------------------------------------------------------------------------- */	
		
		// Append span to each LI to add reflection
		
		$("#nav-reflection li").append("<span></span>");	
		
		// Animate buttons, move reflection and fade
		
		$("#nav-reflection a").hover(function() {
		    $(this).stop().animate({ marginTop: "-10px" }, 200);
		    $(this).parent().find("span").stop().animate({ marginTop: "18px", opacity: 0.25 }, 200);
		},function(){
		    $(this).stop().animate({ marginTop: "0px" }, 300);
		    $(this).parent().find("span").stop().animate({ marginTop: "1px", opacity: 1 }, 300);
		});
				
	/* =Shadow Nav
	-------------------------------------------------------------------------- */
	
		// Append shadow image to each LI
		
		$(".nav-shadow li").append('<img class="shadow" src="images/theme/shadow.png" width="81" height="27" alt="" style="opacity: 0.8" />');
	
		// Animate buttons, shrink and fade shadow
		
		$(".nav-shadow li").hover(function() {
			var e = this;
		    $(e).find("a").stop().animate({ marginTop: "-14px" }, 250, function() {
		    	$(e).find("a").animate({ marginTop: "-10px" }, 250);
		    });
		    $(e).find("img.shadow").stop().animate({ width: "60%", height: "20px", marginLeft: "8px", opacity: 0.25 }, 250);
		},function(){
			var e = this;
		    $(e).find("a").stop().animate({ marginTop: "4px" }, 250, function() {
		    	$(e).find("a").animate({ marginTop: "0px" }, 250);
		    });
		    $(e).find("img.shadow").stop().animate({ width: "80%", height: "27px", marginLeft: "0", opacity: 0.8 }, 250);
		});
						
	// End jQuery
	
		$("#menubar div a").click(function(){
//				$("#dock div").hide();
			});

		$("#menubar .home a").click(function(){
				location.href = 'index.html';
			});

		$("#menubar .myplaces a").click(function(){
				changeDiv("MyPlaces", "My Places");
			});

		$("#menubar .economy a").click(function(){
				changeDiv("Economy", "Economy");
			});

		$("#menubar .ranking a").click(function(){
				changeDiv("Rankings", "Rankings");
			});

		$("#menubar .info a").click(function(){
				changeDiv("Information", "Information");
			});

		$("#menubar .extra a").click(function(){
				changeDiv("Extra", "Extra");
			});
		
/*
		$("#upmenubar").mouseOver(function(){
				$("#upmenubar").animate({"height": "50px"}, 500)
			});
*/
	});
