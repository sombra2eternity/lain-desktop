var _littleDrag = {
	vars:{captured:{},applyLimits:true},
	init: function(){},
	onMouseDown: function(e){
		if(e.which != 1){return;}
		if(e.preventDefault){e.preventDefault();}
		var elem = e.target;while(elem.parentNode && !elem.className.match(/dragable/)){elem = elem.parentNode;}
		if(!elem.parentNode){return;}
		//elem.style.zIndex = ++highestZ;

		elem.startX = e.clientX - elem.offsetLeft;
		elem.startY = e.clientY - elem.offsetTop;

		if(elem.className.match(/wodIcon/)){
			if(elem.firstClick && (new Date().getTime() - elem.firstClick) < _wodern.vars.mouse.click.delay && elem.launch){elem.firstClick=false;return elem.launch();}
			elem.firstClick = new Date().getTime();
			var innerElem = elem;var elem = innerElem.cloneNode(1);var elemPos = $getOffsetPosition(innerElem);
			$fix(elem,{'.display':'none','.opacity':0,'isInvisible':true,'innerElem':innerElem,'startX':(e.clientX-elemPos.left),'startY':(e.clientY-elemPos.top),'eventX':e.clientX,'eventY':e.clientY,'.left':elemPos.left+'px','.top':elemPos.top+'px'});
			$_('lainFlowIcon').appendChild(elem);
		}

		if(!('mouseMoveHandler' in elem)){
			elem.mouseMoveHandler = function(ev){return _littleDrag.onMouseMove(ev,elem);}
			elem.mouseUpHandler = function(ev){return _littleDrag.onMouseUp(ev,elem);}
		}

		document.addEventListener('mousemove',elem.mouseMoveHandler,true);
		document.addEventListener('mouseup',elem.mouseUpHandler,true);
	},
	onMouseMove: function(e,elem){
		var exLimit = (-1*elem.offsetWidth)/2;
		var eyLimit = (-1*elem.offsetHeight)/3;
if(elem.className.match(/wodTheme/)){eyLimit = -3;}

		var eL = e.clientX - elem.startX;if(eL < exLimit && _littleDrag.vars.applyLimits){eL = exLimit;}elem.style.left = eL + 'px';
		var eT = e.clientY - elem.startY;if(eT < eyLimit && _littleDrag.vars.applyLimits){eT = eyLimit;}elem.style.top = eT + 'px';
		/* If is an icon active the drag based on a range and make it visible */
//FIXME: esto ya no se hace así, se hace con css
		if(elem.isInvisible && ( (e.clientX < elem.eventX-10) || (e.clientX > elem.eventX+10) || (e.clientY < elem.eventY-10) || (e.clientY > elem.eventY+10) ) ){elem.$B({'isInvisible':false,'.display':'block'});eFadein(elem);}

		return false;
	},
	onMouseUp: function(e,elem){
		document.removeEventListener('mousemove',elem.mouseMoveHandler,true);
		document.removeEventListener('mouseup',elem.mouseUpHandler,true);

		if(elem.className.match(/wodIcon/)){do{
			var fake = elem;elem = elem.innerElem;
			fake.parentNode.removeChild(fake);
			if((new Date().getTime() - elem.firstClick) < _wodern.vars.mouse.click.delay){break;}
			var x = e.clientX;var y = e.clientY;
			var candidate = document.elementFromPoint(x,y);if(!candidate){break;}
//FIXME: en vez de elem deberíamos enviar la selección
			var event = new CustomEvent('file.drop',{'detail':elem,'bubbles':true,'cancelable':true});candidate.dispatchEvent(event);
		}while(false);}

		return false;
	}
};
