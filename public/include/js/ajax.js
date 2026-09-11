// AJAX Script by http://www.ajaxskins.com
<!--

var xmlHttp;
var	searchSplitter;
function createRequest(){ 
if(window.ActiveXObject){ 
	xmlHttp = new ActiveXObject("Microsoft.XMLHTTP"); 
} else if(window.XMLHttpRequest){  
	xmlHttp = new XMLHttpRequest(); 
} 
} 

var jsSrc;
var target;
function doeval(js, src){
	if(js!=null){
	var sc = document.createElement('script');
	sc.type = 'text/javascript';
	document.getElementsByTagName('head')[0].appendChild(sc);
	if (src!=null) sc.src = src; 
	sc.text = js;
	}
}


function getscript(str, src){
let_out=str;
    var reg = new RegExp( '(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)', "img" );
    var i=1;  
    var s=1;  
    while ( s = reg.exec ( let_out ) ) {
	   doeval(s[1], src);
    }
}

function loadAjax(url, targetID, jsSource, sp){ 
if (jsSource!=null) jsSrc = jsSource;
if (sp!=null) searchSplitter = sp;
target = targetID;
createRequest();
xmlHttp.open("GET", url + '&ms=' + new Date().getTime(), true); 
xmlHttp.onreadystatechange = updatepage; 
xmlHttp.send(null); 
}

function updatepage(){
	document.getElementById(target).style.display = "block";
// 	document.getElementById(target).innerHTML = "<div style=\"font-family:tahoma;font-size:9pt;text-align:center\"><img src=http://amsonline2005.googlepages.com/load.gif> Loading...</div>"
	if(xmlHttp.readyState == 4){
		str = xmlHttp.responseText;
		if ((searchSplitter != "") && (searchSplitter != null))
		{
			start = str.indexOf(searchSplitter);
			start = str.indexOf(searchSplitter, start + 12);
			end = str.indexOf(searchSplitter, start + 12);
			str = str.substring(start, end);
		}
		document.getElementById(target).innerHTML = str;
		// alert(str);
		getscript(str, jsSrc);
	}
}

function putHtml(strSource, strTarget)
{
	document.getElementById(strTarget).innerHTML = strSource;
}

function addHtml(strSource, strTarget)
{
	document.getElementById(strTarget).innerHTML += strSource;
}
//-->
// AJAX Script by http://www.ajaxskins.com