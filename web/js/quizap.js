document.onkeydown = function() {
	switch (event.keyCode) { 
		case 116 : //F5 button
			event.returnValue = false;
			event.keyCode = 0;
			return false; 
		case 91 : //command button
			event.returnValue = false;
			event.keyCode = 0;
			return false; 
		case 17 : //control button
			event.returnValue = false;
			event.keyCode = 0;
			return false; 
		case 82 : //R button
			if (event.ctrlKey) { 
				event.returnValue = false; 
				event.keyCode = 0;  
				return false; 
			} 
	}
}