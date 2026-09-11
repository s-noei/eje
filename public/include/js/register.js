String.prototype.format = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};

titl = "cssbody=[bodydiv] cssheader=[headdiv] header=[{0}] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;{1}&lt;/div&gt;]";

function checkIt(checkStr)
{
	var checkOK = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-*| ";
	var allValid = true;
	for (i = 0;  i < checkStr.length;  i++)
	{
		ch = checkStr.charAt(i);
		for (j = 0;  j < checkOK.length;  j++)
			if (ch == checkOK.charAt(j))
				break;
		if (j == checkOK.length)
		{
			allValid = false;
			break;
		}
	}
	return (allValid)
}

function checkMail(checkStr)
{
	var checkEmail = "@.";
	var EmailValid = false;
	var EmailAt = false;
	var EmailPeriod = false;
	for (i = 0;  i < checkStr.length;  i++)
	{
		ch = checkStr.charAt(i);
		for (j = 0;  j < checkEmail.length;  j++)
		{
			if (ch == checkEmail.charAt(j) && ch == "@")
				EmailAt = true;
			if (ch == checkEmail.charAt(j) && ch == ".")
				EmailPeriod = true;
			if (EmailAt && EmailPeriod)
				break;
			if (j == checkEmail.length)
				break;
		}
		// if both the @ and . were in the string
		if (EmailAt && EmailPeriod)
		{
			EmailValid = true
			break;
		}
	}
	return (EmailValid)
}

$(document).ready(function(){
	$("#next_1").click(function(){
		$("#l1").fadeIn(500, function(){
			// Checking the values
			err = errt = '';
			
			if (!$("#txtName").attr("value"))
				errt = 'Citizen name field cannot be empty\n';
			else if ($("#txtName").attr("value").length < 3)
				errt = 'Citizen name is too short\n';
			else if (!checkIt($("#txtName").attr("value")))
				errt = 'Citizen name contains invalid characters\n';
			
			if (errt) {
				err += errt;
				$("#txtName").css("border-color", "red");
				$("#txtName").attr("title", errt);
				errt = '';
			}else{
				$("#txtName").css("border-color", "gray");
				$("#txtName").attr("title", "");
			}
			
			if (!$("#txtPass1").attr("value"))
				errt = 'Password field cannot be empty\n';
			else if ($("#txtPass1").attr("value").length < 6)
				errt = 'Password is too short\n';
			else if ($("#txtPass1").attr("value") != $("#txtPass2").attr("value"))
				errt = "Password fields doesn't match\n";
			
			if (errt) {
				err += errt;
				$("#txtPass1").css("border-color", "red");
				$("#txtPass1").attr("title", errt);
				errt = '';
			}else{
				$("#txtPass1").css("border-color", "gray");
				$("#txtPass1").attr("title", "");
			}
			
			if (!$("#txtMail").attr("value"))
				errt = 'Email field cannot be empty\n';
			else if (!checkMail($("#txtMail").attr("value")))
				errt = 'Email field is invalid\n';
			
			if (errt) {
				err += errt;
				$("#txtMail").css("border-color", "red");
				$("#txtMail").attr("title", errt);
				errt = '';
			}else{
				$("#txtMail").css("border-color", "gray");
				$("#txtMail").attr("title", "");
			}
			
			var radioSelected = false;
			for (i = 0;  i < document.forms.register.Sex.length;  i++)
			{
			if (document.forms.register.Sex[i].checked)
			radioSelected = true;
			}
			if (!radioSelected)
				err += 'You must select a gender\n';

			if (!err) {
				$("#l1").fadeOut(500);
				$("#o1").fadeIn(500);
				$("#r2").css("display", "inline-block");
			}else{
				alert(err);
				$("#l1").fadeOut(500);
			}
		});
	});

	$("#next_2").click(function(){
		$("#l2").fadeIn(500, function(){
			// Checking the values
			err = '';
			
			if ($("#country").val() == 0)
				err += 'You must select your country\n';
			
			if ($("#region").val() == 0)
				err += 'You must select your region\n';

			if (!err) {
				$("#l2").fadeOut(500);
				$("#o2").fadeIn(500);
				$("#r3").css("display", "inline-block");
			}else{
				alert(err);
				$("#l2").fadeOut(500);
			}
		});
	});

	$("#back_2").click(function(){
		$("#r2").fadeOut(500);
		$("#o1").fadeOut(500);
	});

	$("#back_3").click(function(){
		$("#r3").fadeOut(500);
		$("#o2").fadeOut(500);
	});

	$("#next_3").click(function(){
		$("#l3").fadeIn(500, function(){
			// Checking the values
			err = '';

			if (!err) {
				$("#l3").fadeOut(500);
				$("#o3").fadeIn(500);
				$("#r4").css("display", "block");
			}else{
				alert(err);
				$("#l3").fadeOut(500);
			}
		});
	});
});