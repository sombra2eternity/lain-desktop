var lainExplorer = new Class({
	init: function(holder,params){
		if(!VAR_apps['lainExplorer']){this.vars = {apiURL:'r/PHP/api.fs.php',wCounter:0,wHolder:holder,wList:$A([]),cList:$A([])};}
		if(params && params.tagName && params.tagName == 'LI'){var iProp = _desktop.icon_getProperties(params);this.createExplorer(iProp.fileRoute+iProp.fileName);return;}
		if(params && params.constructor == String){this.createExplorer(params);return;}
	},
	appKill: function(){this.vars.wList.each(function(w){window_destroy(w);}.bind(this));},
	wList_removeElem: function(el){this.vars.wList.each(function(w,n){if(w == el){this.vars.wList.splice(n,1);}}.bind(this));},
	createExplorer: function(path){
		var ths = this;
		holder = this.vars.wHolder;

		var wNum = this.vars.wCounter;
		var wPos = _desktop.window_loadRelativePosition('wod_lainExplorer'+wNum);
		var w = window_create('lainExplorer'+wNum,{wodTitle:'Lain File Explorer','.width':'400px','.left':wPos.left+'px','.top':wPos.top+'px',
			beforeRemove:function(){this.wList_removeElem(w);}.bind(this),
			onDropElement:function(elem){ths.onDropElement(elem,this);}
		},holder);
		this.vars.wList.push(w);
		h = w.windowContainer;


		/* INI-MENU */
		var wodMenuHolder = $C('UL',{className:'wodMenuHolder'},h);
		var ul = $C('UL',{},$C('LI',{innerHTML:'File',onclick:function(){_desktop.menu_show(this);}},wodMenuHolder));
$C('LI',{className:'icon_folder_add',innerHTML:'Create new folder',onclick:function(){this.operation_createFolder(iconCanvas);}.bind(this)},ul);
		var ul = $C('UL',{},$C('LI',{innerHTML:'Edit',onclick:function(){_desktop.menu_show(this);}},wodMenuHolder));
		$C('LI',{className:'icon_paste',innerHTML:'Paste',onclick:function(){this.operation_paste(iconCanvas);}.bind(this)},ul);
		var ul = $C('UL',{},$C('LI',{innerHTML:'View',onclick:function(){_desktop.menu_show(this);}},wodMenuHolder));
		$C('LI',{innerHTML:'Side Panel',onclick:function(){alert(1);}.bind(this)},ul);
		/* END-MENU */

		var buttonHolder = $C("DIV",{className:"wodButtonMenu"},h);
		var bt_uponelevel = $C("DIV",{innerHTML:"<img class='icon icon_token_uponelevel' src='r/images/t.gif'/>"},buttonHolder);

		var explorerBody = $C('DIV',{className:'lainExplorer_explorerBody'},h);
		var sidePanel = this.sidePanel_create();
		explorerBody.appendChild(sidePanel);

		var iconCanvasParent = $C('DIV',{className:'lainExplorer_iconHolder iconCanvasDecorator'},h);
		var iconCanvas = w.iconCanvas = _desktop.iconCanvas_create('lainExplorer'+wNum+'_iconCanvas',iconCanvasParent);
		this.vars.cList.push(iconCanvas);
		$C('I',{className:'floatSeparator'},iconCanvasParent);
		this.list(iconCanvas,path);



		bt_uponelevel.onclick = function(){this.list_upOneLevel(iconCanvas);}.bind(this);

		this.vars.wCounter++;
	},
	list: function(iconCanvas,path){
		//FIXME: parsepath
		var path = (path ? path : '/');
		var reveal = path.match(/^(reveal|ubuone)/);
		if(path != '/' && path[path.length-1] != '/'){path += '/';};
		//if(!reveal && path != '/' && path[0] != '/'){path = '/'+path;}

		var wNum = iconCanvas.id.match(/lainExplorer([0-9]+)_iconCanvas/)[1];
		var t = $_('wod_lainExplorer'+wNum+'_title',{innerHTML:'Lain File Explorer - '+path});
		ajaxPetition(this.vars.apiURL,'command=folder_list&path='+base64.encode(path),function(ajax){
			var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			iconCanvas.empty();
			iconCanvas.innerPath = path;
			$A(r.folders).each(function(elem){_desktop.icon_create(elem,iconCanvas);}.bind(this));
			$A(r.files).each(function(elem){_desktop.icon_create(elem,iconCanvas);}.bind(this));

			/* Calculate the height of an iconCanvas, if there is no icons */
			_desktop.iconCanvas_autoResize(iconCanvas);
		}.bind(this));
	},
	list_upOneLevel: function(iconCanvas){
		if(iconCanvas.innerPath == ''){return;}
		var baseName = iconCanvas.innerPath.replace(/\/[^\/]*\/$/,'/');
		if(baseName == '/'){baseName = '';}
		this.list(iconCanvas,baseName);
	},
	sidePanel_create: function(){
		var sidePanel = $C('UL',{className:'lainExplorer_sidePanel'});
//FIXME: terminar
$A($_('lainPlacesMenu_itemList').childNodes).each(function(el){
	if(el.nodeType != 1){return;};
	if(el.className.match(/dropLeyend/)){return;}
	var li = el.cloneNode(true);
	sidePanel.appendChild(li);
});
		return sidePanel;
	},
	operation_paste: function(iconCanvas){
		if(_desktop.vars.fileOperation != 'cut' && _desktop.vars.fileOperation != 'copy'){return;}
		var iconElem = _desktop.vars.fileOrig;

		var destPath = iconCanvas.innerPath;
		var iProp = _desktop.icon_getProperties(iconElem);
		var origPath = iProp.fileRoute+iProp.fileName;

		ajaxPetition(this.vars.apiURL,'command=moveFile&path='+origPath+'&dest='+destPath,function(ajax){
			var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			_desktop.icon_create(iProp,iconCanvas);
			if(_desktop.vars.fileOperation == 'cut'){iconElem.parentNode.removeChild(iconElem);}
			_desktop.vars.fileOperation = false;
		}.bind(this));
	},
	operation_createFolder: function(iconCanvas){
		var destPath = iconCanvas.innerPath;
		var h = info_create('createFolder',{},iconCanvas).infoContainer;
		$C('DIV',{innerHTML:'Name the new folder'},h);
		var i = $C('INPUT',{},$C('DIV',{className:'inputText'},h));

		var btHolder = $C('UL',{className:'buttonHolder'},h);
		gnomeButton_create('Cancel',function(){info_destroy(h);},btHolder);
		gnomeButton_create('OK',function(){
			_iface.reveal_folder_create(i.value,destPath,function(d){_desktop.icon_create(d,iconCanvas);});
		},btHolder);	
	},
	signal_folderAdd: function(src,fSel){
		//alert(print_r(fSel));
		$A(fSel).each(function(el){
			this.vars.cList.each(function(canvas){
				if(canvas.innerPath != src){return;}
				_desktop.icon_create(el,canvas);
				//alert(canvas.innerPath);
			}.bind(this));
		}.bind(this));
	},
	signal_iconAdd: function(iconObj){
		this.vars.cList.each(function(canvas){
			if(canvas.innerPath != iconObj.fileRoute){return;}
			_desktop.icon_create(iconObj,canvas);
		});
	},
	dragIconStart: function(e){
		var elem = e.target;while(elem.parentNode && !elem.className.match(/dragable/)){elem = elem.parentNode;}
		elem = $fix(elem,{'.opacity':.5,onmouseup:function(){this.$B({'.opacity':1});_littleDrag.vars.applyLimits = true;}});

		_littleDrag.vars.applyLimits = false;
		_littleDrag.onMouseDown(e);
	},
	onDropElement: function(iconElem,w){
		var iconCanvas = w.iconCanvas;
		if(!iconElem.$B){el = $fix(el);}
		/* Si el elemento pertenece a la misma ventana, no hacemos nada */
		if(iconElem.isChildNodeOf(iconCanvas)){return;}

		var destPath = iconCanvas.innerPath.replace(/[\/]*$/,'')+'/';
//FIXME:hack
if(destPath[0] == '/'){destPath = 'native:drive:'+destPath;}
		var origPath = iconElem.parentNode.innerPath.replace(/[\/]*$/,'')+'/';
		/* if the paths are the same, no op is needed */
		if(destPath == origPath){return;}

		var iProp = _desktop.icon_getProperties(iconElem);
		var elemName = iProp.fileName;

		//FIXME: quiza hacerlo por api del sistema
		//alert("from " + origPath + " to " + destPath);
		var files = [];
		files.push(iProp);
		//ajaxPetition(this.vars.apiURL,'command=moveFile&path='+origPath+elemName+'&dest='+destPath,function(ajax){
		ajaxPetition(this.vars.apiURL,'command=moveFile&files='+jsonEncode(files)+'&dest='+destPath,function(ajax){
			var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
//FIXME: cambiar el sistema
//alert(print_r(r));
			_desktop.icon_move(iconElem,iconCanvas);
		}.bind(this));
	}
});
