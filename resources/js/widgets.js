var widgets = {};
function widget(widgetname,params){
	$findFunc = function(l,pool){if(!pool){pool = window;}var func = pool;var funcSplit = l.split('.');var e = true;for(i = 0;i < funcSplit.length;i++){if(!func[funcSplit[i]]){e = false;break;}func = func[funcSplit[i]];}return e ? func : false;};
	if(!(func = $findFunc(widgetname))){return false;}
	function w(){};
	w.prototype = func;
	var f = new w;
	return f.init(params);
}

var _widgets = {
	getScrollBarWidth: function(){
		var inner = $C('P',{'.width':'100%','.height':'200px'});
		var outer = $C('DIV',{'.position':'absolute','.top':'0px','.left':'0px','.visibility':'hidden','.width':'200px','.height':'150px','.overflow':'hidden'});
		outer.appendChild(inner);document.body.appendChild(outer);var w1 = inner.offsetWidth;outer.style.overflow = 'scroll';var w2 = inner.offsetWidth;if(w1 == w2){w2 = outer.clientWidth;}document.body.removeChild(outer);
		return (w1 - w2);
	}
}

/* INI-wodItem */
widgets.wodItem = {
	init: function(params){
		if(!params){params = {};}
		var wodItem = $C('DIV',{className:'wodItem',
			'vars': {'text':false},
			'signals': {},
			'set': {
				'text': function(){var args = Array.prototype.slice.call(arguments);args.unshift(wodItem);return widgets.wodItem.set.text.apply({},args);},
				'callback': function(){var args = Array.prototype.slice.call(arguments);args.unshift(wodItem);return widgets.wodItem.set.callback.apply({},args);}
			},
			'disable': function(){var args = Array.prototype.slice.call(arguments);args.unshift(wodItem);return widgets.wodItem.disable.apply({},args);}
		});
		if(params.text){wodItem.set.text(params.text);}
		if(params.title){wodItem.set.text(params.title);}
		if(params.class && !$is.empty(params.class)){wodItem.className += ' '+params.class;}
		if(params.callback){if(!params.params){params.params = [];}wodItem.set.callback(params.callback,params.params);}
		return wodItem;
	},
	set:{
		text: function(wodItem,item){
			if($is.string(item) && item == '-'){$E.class.add(wodItem,'separator');return wodItem;}
			if(!$is.string(item)){wodItem.appendChild(item);return wodItem;}
			wodItem.vars.text = item;wodItem.innerHTML = item;return wodItem;
		},
		callback: function(wodItem,callback,params){
			if(!params.params){params.params = [];}
			if(wodItem.signals['mouse.down.left']){wodItem.removeEventListener('mouse.down.left',wodItem.signals['mouse.down.left']);wodItem.signals['mouse.down.left'] = false;}
			wodItem.signals['mouse.down.left'] = function(e){
				if($is.string(callback)){var func = $F.find(callback);if(!func){return false;}return func(e,params);}
				//FIXME: if $is.function
				return callback(e,params);
			};
			wodItem.addEventListener('mouse.down.left',wodItem.signals['mouse.down.left']);
		}
	},
	disable: function(wodItem){$E.class.add(wodItem,'disabled');return wodItem;}
}
/* END-wodItem */


var _wodHContainer = {
	vars: {},
	init: function(params){
		var wodHContainer = $C('DIV',{className:'wodHContainer'

		});
		return wodHContainer;
	}
}

var _wodTable = {
	vars: {},
	init: function(params){
		/* params = {'tableMode':'wodTable'|'wodCheck'} */
		var wodTable = $C('DIV',{className:'wodTable',
			'thead':false,'tbody':false,
			'vars':{'tableMode':'wodTable','rowsSelected':[],'rowsChecked':[],'hasScrollbars':false,'scrollbarWidth':0},
			'reflow': function(params){return _wodTable.reflow(wodTable);},
			'columns':{
				'childs': [],
				'add': function(){return _wodTable.column_add.apply(wodTable,arguments);}
			},
			'rows':{
				'childs': [],
				//FIXME: comprobar data-oncontextmenu para custom menus por rows
				//FIXME: también añadir una function setCustomMenu
				'oncontextmenu': function(e,el){return false;},
				'add': function(){return _wodTable.row_add.apply(wodTable,arguments);},
				'getChecked': function(){return wodTable.vars.rowsChecked;}
			}
		});
		extend(wodTable,{
			'tbody':$C('DIV',{className:'wodTbody'},wodTable),
		});
		if(params && params.tableMode){wodTable.vars.tableMode = params.tableMode;}
		return wodTable;
	},
	reflow: function(wodTable){
		var width = wodTable.tbody.clientWidth;
		wodTable.vars.hasScrollbars = (wodTable.tbody.scrollHeight > wodTable.tbody.clientHeight);
		if(wodTable.vars.hasScrollbars){wodTable.vars.scrollbarWidth = wodTable.clientWidth-wodTable.tbody.clientWidth;}
		wodTable.thead.$B({'.paddingRight':wodTable.vars.scrollbarWidth+'px'});
		//FIXME: normalizar todos los heights de cada row al mayor de ellos

		var reservedWidth = 0;var columnsAuto = 0;
		/* Calculate the reserved space first */
		$each(wodTable.columns.childs,function(k,v){
			if(v.widthCalc == 'auto'){columnsAuto++;return;}
			if(v.widthCalc == 'fixed'){var w = parseInt(v.columnWidth);reservedWidth += w;wodTable.columns.childs[k].width = w+'px';return;}
		});

		width = width-reservedWidth;
		var columnsWidth = width/columnsAuto;
		//var columnsWidth = Math.floor(width/columnsAuto);
		var columnsPercentage = (columnsWidth/wodTable.tbody.clientWidth)*100;
		var columnsPercentage = parseInt((1/columnsAuto)*1000)/1000;
		$each(wodTable.columns.childs,function(k,v){
			if(v.widthCalc != 'auto'){return;}
			//FIXME: habría que convertirlo a porcentaje
			//wodTable.columns.childs[k].width = columnsWidth+'px';
			//wodTable.columns.childs[k].width = columnsPercentage+'%';
			wodTable.columns.childs[k].width = 'calc('+columnsPercentage+' * (100% - '+reservedWidth+'px))';
		});

		if(wodTable.thead){$each(wodTable.thead.firstChild.childNodes,function(k,v){if(!v.nodeType){return;}v.style.width = wodTable.columns.childs[k].width;});}
		$each(wodTable.rows.childs,function(j,row){$each(row.childNodes,function(k,v){if(!v.nodeType){return;}v.style.width = wodTable.columns.childs[k].width;});});
	},
	header_add: function(wodTable){var el = $C('DIV',{className:'wodThead'});$C('DIV',{className:'wodTr'},el);wodTable.insertBefore(el,wodTable.firstChild);wodTable.thead = el;},
	column_add: function(params){
		/* params = {'columnName':'TEXT','columnIndex':'INTEGER'},{...} */
		var wodTable = this;
		$each(arguments,function(k,v){
			/* If any of the arguments has a name, we need a header */
			if(v.columnName && !wodTable.thead){_wodTable.header_add(wodTable);}
			extend(v,{'widthCalc':'auto'});// auto | fixed
			if(v.columnIndex !== undefined){wodTable.columns.childs.splice(v.columnIndex,0,v);}
			else{wodTable.columns.childs.push(v);}
		});

		/* INI-wodCheck If tableMode is 'wodCheck' we must ensure the first element is the checker cell */
		if(wodTable.vars.tableMode == 'wodCheck'){
			$each(wodTable.columns.childs,function(k,v){if(v.columnType == 'wodCheck'){wodTable.columns.childs.splice(k,1);}});
			wodTable.columns.childs.splice(0,0,{'columnName':'<i class="icon-check-sign"></i>','columnType':'wodCheck','widthCalc':'fixed','columnWidth':'19px','columnResize':'noresize'});
		}
		/* END-wodCheck */

		if(wodTable.thead){wodTable.thead.firstChild.empty();$each(wodTable.columns.childs,function(k,v){
			var td = $C('DIV',{className:'wodTd',innerHTML:((v.columnName) ? v.columnName : '')});
			td.setAttribute('data-index',k);
			/* Some columns cant be resized */
			if(!v.columnResize || v.columnResize != 'noresize'){var mv = $C('DIV',{className:'resize',onmousedown:function(e){return _wodTable.col_signal_resize_mousedown(wodTable,e,td);}},td);}
			wodTable.thead.firstChild.appendChild(td);
		});}

		$each(wodTable.rows.childs,function(j,row){$C('DIV',{className:'wodTd'},row);});
		wodTable.reflow();
		return true;
	},
	row_add: function(params){
		/* params = 'value-1','value-n' */
		var wodTable = this;

		var args = arguments;
		var tr = $C('DIV',{className:'wodTr',
			'onclick': function(e){return _wodTable.row_signal_click(wodTable,e,tr);},
			'oncontextmenu': function(e){return _wodTable.row_signal_contextmenu(wodTable,e,tr);},
			'getValue': function(e){return _wodTable.row_getValue(wodTable,e,tr);}
		},wodTable.tbody);
		
		$each(wodTable.columns.childs,function(k,v){
			if(v.columnType == 'wodCheck'){$C('DIV',{className:'checkbox'},$C('DIV',{className:'wodTd wodCheck','.width':wodTable.columns.childs[k].width},tr));return;}
			var value = args[k] ? args[k] : '';$C('DIV',{className:'wodTd','.width':wodTable.columns.childs[k].width,innerHTML:value},tr);
		});

		//FIXME: no hacemos push si hay index
		wodTable.rows.childs.push(tr);

		var hasScrollbars = (wodTable.tbody.scrollHeight > wodTable.tbody.clientHeight);
		if(hasScrollbars != wodTable.vars.hasScrollbars){wodTable.reflow();}
	},
	row_getValue: function(wodTable,e,tr){
		var o = {};$each(tr.childNodes,function(k,v){
			if($E.classHas(v,'wodCheck')){o[k] = Boolean($E.classHas(v.firstChild,'checked'));return;}
			o[k] = v.innerHTML;
		});
		return o;
	},
	row_signal_click: function(wodTable,e,tr){
		var clickDelay = (window._desktop) ? _desktop.vars.clickDelay : 300;
		var controlKey = (window._desktop && _desktop.vars.input_presedKeys.indexOf(17) > -1);
		var trIsSelected = $E.classHas(tr,'selected');

		/* Control double-click*/
		if(tr.firstClick && (new Date().getTime() - tr.firstClick)<clickDelay){
			tr.firstClick = false;
			if(wodTable.vars.tableMode == 'wodCheck'){
				if($E.classHas(tr.firstChild.firstChild,'checked')){
					$E.classRemove(tr.firstChild.firstChild,'checked');
					var index = wodTable.vars.rowsChecked.indexOf(tr);
					wodTable.vars.rowsChecked.splice(index,1);
				}
				else{
					$E.classAdd(tr.firstChild.firstChild,'checked');
					wodTable.vars.rowsChecked.push(tr);
				}
			}
		}
		else{tr.firstClick = new Date().getTime();}

		/* If the row was previously selected and the control key is pressed we must switch it */
		if(trIsSelected && controlKey){var index = wodTable.vars.rowsSelected.indexOf(tr);wodTable.vars.rowsSelected.splice(index,1);$E.classRemove(tr,'selected');return true;}

		if(controlKey){wodTable.vars.rowsSelected.push(tr);}
		else{$each(wodTable.vars.rowsSelected,function(k,v){$E.classRemove(v,'selected');});wodTable.vars.rowsSelected = [tr];}
		$E.classAdd(tr,'selected');
	},
	row_signal_contextmenu: function(wodTable,e,tr){
		return wodTable.rows.oncontextmenu(e,tr);
	},
	col_signal_resize_mousedown: function(wodTable,e,td){
		e.stopPropagation();
		td.startX = e.clientX;
		td.startW = td.clientWidth;
		if(td.nextSibling){td.nextSibling.startW = td.nextSibling.clientWidth;}
		if(!td.mousemovehandler){td.mousemovehandler = function(ev){return _wodTable.col_signal_resize_mousemove(wodTable,ev,td);};}
		if(!td.mouseuphandler){td.mouseuphandler = function(ev){return _wodTable.col_signal_resize_mouseup(wodTable,ev,td);};}
		document.addEventListener('mousemove',td.mousemovehandler,true);
		document.addEventListener('mouseup',td.mouseuphandler,true);
	},
	col_signal_resize_mousemove: function(wodTable,e,td){
		var eL = e.clientX-td.startX;
		td.style.width = (td.startW+eL+1)+'px';
		if(td.nextSibling){td.nextSibling.style.width = (td.nextSibling.startW-eL+1)+'px';}
	},
	col_signal_resize_mouseup: function(wodTable,e,td){
		var eL = e.clientX-td.startX;
		document.removeEventListener('mousemove',td.mousemovehandler,true);
		document.removeEventListener('mouseup',td.mouseuphandler,true);
		var index = parseInt(td.getAttribute('data-index'));
		var newWidth = td.clientWidth+eL+1;
		extend(wodTable.columns.childs[index],{'widthCalc':'fixed','columnWidth':newWidth+'px'});
		if(td.nextSibling){
			var newWidth = td.nextSibling.clientWidth-eL+1;
			extend(wodTable.columns.childs[index+1],{'widthCalc':'fixed','columnWidth':newWidth+'px'});
		}
		wodTable.reflow();
	}
};
