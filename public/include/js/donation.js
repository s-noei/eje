
window.onload = init;
var d=document;
function init() {
	so_checkCanCreate();
}

function so_checkCanCreate() {
	// make sure the browser has images turned on. If they are, so_createCustomCheckBoxes will
	// fire when this small test image loads. otherwise, the user will get the hard-coded checkboxes
	testImage = d.body.appendChild(d.createElement("img"));

	// MSIE will cache the test image, causing it to not fire the onload event the next time the
	// page is hit. The parameter on the end will prevent this.
	testImage.src = "images/game/profile/blank.gif?" + new Date().valueOf();
	testImage.id = "so_testImage";
	testImage.onload = so_createCustomCheckBoxes;
}

function so_createCustomCheckBoxes() {
	// bail out is this is an older browser
	if(!d.getElementById)return;
	// remove our test image from the DOM
	d.body.removeChild(d.getElementById("so_testImage"));
	// an array of applicable events that we'll need to carry over to our custom checkbox
	events = new Array("onfocus", "onblur", "onselect", "onchange", "onclick", "ondblclick", "onmousedown", "onmouseup", "onmouseover", "onmousemove", "onmouseout", "onkeypress", "onkeydown", "onkeyup");
	// a reference var to all the forms in the document

	frm = d.getElementsByTagName("form");
	// loop over the length of the forms in the document
	for(i=0;i<frm.length;i++) {
		// reference to the elements of the form
		c = frm[i].elements;
		// loop over the length of those elements
		for(j=0;j<c.length;j++) {
			// if this element is a checkbox, do our thing

			if(c[j].getAttribute("type") == "checkbox") {
				// hide the original checkbox
				c[j].style.position = "absolute";
				c[j].style.left = "-9000px";
				// create the replacement image
				n = d.createElement("img");
				n.setAttribute("class","chk");
				// check if the corresponding checkbox is checked or not. set the
				// status of the image accordingly
				if(c[j].checked == false) {
					n.setAttribute("src","images/game/profile/chk_off.gif");
					n.setAttribute("title","click here to select this product.");
					n.setAttribute("alt","click here to select this product.");
				} else {
					n.setAttribute("src","images/game/profile/chk_on.gif");
					n.setAttribute("title","click here to deselect this product.");
					n.setAttribute("alt","click here to deselect this product.");
				}
				// there are several pieces of data we'll need to know later.
				// assign them as attributes of the image we've created
				// first - the name of the corresponding checkbox
				n.xid = c[j].getAttribute("name");
				// next, the index of the FORM element so we'll know which form object to access later

				n.frmIndex = i;
				// assign the onclick event to the image
				n.onclick = function() { so_toggleCheckBox(this,0);return false; }
				// insert the image into the DOM
				c[j].parentNode.insertBefore(n,c[j].nextSibling)
				// this attribute is a bit of a hack - we need to know in the event of a label click (for browsers that support it)
				// which image we need turn on or off. So, we set the image as an attribute!
				c[j].objRef = n;
				// assign the checkbox objects event handlers to its replacement image
				for(e=0;e<events.length;e++) if(eval('c[j].' +events[e])) eval('n.' + events[e] + '= c[j].' + events[e]);
				// append our onchange event handler to any existing ones.
				fn = c[j].onchange;
				if(typeof(fn) == "function") {
					c[j].onchange = function() { fn(); so_toggleCheckBox(this.objRef,1); return false; }
				} else {
					c[j].onchange = function () { so_toggleCheckBox(this.objRef,1); return false; }
				}
			}
		}
	}
}

function so_toggleCheckBox(imgObj,caller) {
	// if caller is 1, this method has been called from the onchange event of the checkbox, which means
	// the user has clicked the label element. Dont change the checked status of the checkbox in this instance
	// or we'll set it to the opposite of what the user wants. caller is 0 if coming from the onclick event of the image
	
	// reference to the form object
	formObj = d.forms[imgObj.frmIndex];
	// the name of the checkbox we're changing
	objName = imgObj.xid;
	// change the checked status of the checkbox if coming from the onclick of the image
	if(!caller)formObj.elements[objName].checked = !formObj.elements[objName].checked?true:false;
	// finally, update the image to reflect the current state of the checkbox.
	if(imgObj.src.indexOf("chk_on.gif")>-1) {
		imgObj.setAttribute("src","images/game/profile/chk_off.gif");
		imgObj.setAttribute("title","click here to select this option.");
		imgObj.setAttribute("alt","click here to select this option.");
	} else {
		imgObj.setAttribute("src","images/game/profile/chk_on.gif");
		imgObj.setAttribute("title","click here to deselect this option.");
		imgObj.setAttribute("alt","click here to deselect this option.");
	}
}
