if (!Array.prototype.inArray) {
   Array.prototype.inArray=function(val) {
      for (key in this) {
         if (this[key]==val) {
            return true;
         }
      }
      return false;
   }
}

function checkNumber(type, evt) {
	var charArray = new Array(46, 8, 190, 110, 44, 9, 37, 39);
	var charArray2 = new Array(8, 9, 37, 39);

	var noStart = 48;
	var noEnd = 59;
	var jokerKey = 0;

	if(typeof(evt.which) == "undefined") {
		var charCode = evt.keyCode;
    }else{
		var charCode = evt.which;
    }

    if ( type == "float" && ( charArray.inArray(charCode) || ( noStart <= charCode && charCode <= noEnd ) || jokerKey == charCode) ) {
	} else if ( type == "int" && ( charArray2.inArray(charCode) || ( noStart <= charCode && charCode < noEnd ) || jokerKey == charCode) ) {
		return true;
	}
	 else if ( type == "int" && ( charArray2.inArray(charCode) || ( noStart <= charCode && charCode < noEnd ) || jokerKey == charCode) ) {
		return true;
	}else {
		return false;
	}
}

function upkey(evt, obj) {
	$j(obj).val($j(obj).val().replace(',', '.'));
}

function checkFloat(object) {
	var price;
	price = parseFloat($j(object).val());
	price = (Math.ceil(Math.floor(price*100)))/100;
	if(!$j(object).val()){
		$j(object).val(0);
	} else {
		$j(object).val(price);
	}
}

