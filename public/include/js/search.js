$(document).ready(function(){
		$("#searchinput").focus(function(){
				if ($("#searchinput").val() == search_caption) $("#searchinput").val('');
			})
		$("#searchinput").blur(function(){
				if ($("#searchinput").val() == '') $("#searchinput").val(search_caption);
			})
	});