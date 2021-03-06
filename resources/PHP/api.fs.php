<?php
if(!isset($GLOBALS['userPath'])){echo 'error userPath '.__FILE__;exit;}
	$GLOBALS['tables']['trash'] = array('_fileHash_'=>'TEXT NOT NULL','fileRoute'=>'TEXT NOT NULL','fileName'=>'TEXT NOT NULL',
	'fileMime'=>'TEXT NOT NULL','fileSize'=>'INTEGER NOT NULL','filePermissions'=>'TEXT NOT NULL','fileChilds'=>'INTEGER NOT NULL','fileOwner'=>'TEXT NOT NULL','fileGroup'=>'TEXT NOT NULL',
	'fileDate'=>'TEXT NOT NULL','fileTime'=>'TEXT NOT NULL');
	$GLOBALS['api']['fs'] = array('root'=>$GLOBALS['userPath'].'drive/','trash'=>$GLOBALS['userPath'].'trash/','tmp'=>$GLOBALS['userPath'].'tmp/','trashdb'=>$GLOBALS['userPath'].'trash.db',
		'file.mime'=>'../db/magic.mgc',
		'serverCage'=>true);

	function fs_protocol($fileRoute,$functionName = '',$functionArgs = array()){
		$funcName = substr($functionName,3);
		$protocol = substr($fileRoute,0,6);
		switch($protocol){
			case 'native':include_once('fs/inc.fs.native.php');return call_user_func_array('fs_native_'.$funcName,$functionArgs);
			case 'gdrive':include_once('fs/inc.fs.gdrive.php');return call_user_func_array('fs_gdrive_'.$funcName,$functionArgs);
			case 'ziparc':include_once('fs/inc.fs.zip.php');return call_user_func_array('fs_zip_'.$funcName,$functionArgs);
			default:return array('errorDescription'=>'NO_SUPPORT_YET','file'=>__FILE__,'line'=>__LINE__);
		}
	}

	function fs_rename($fileOB,$name){
		if(!isset($fileOB['fileRoute'])){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		return fs_protocol($fileOB['fileRoute'],__FUNCTION__,func_get_args());
	}
	function fs_move($fileOBs,$fileRoute){
		if(!is_string($fileRoute) && !isset($fileRoute['fileRoute'])){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(is_array($fileRoute)){$fileRoute = $fileRoute['fileRoute'].$fileRoute['fileName'];}
		if(isset($fileOBs['fileRoute'])){$fileOBs = array($fileOBs);}
		return fs_protocol($fileRoute,__FUNCTION__,array($fileOBs,$fileRoute));
	}

	function fs_helper_parsePath($path){
		if(strpos($path,'native:drive:') === 0){$path = substr($path,13);}
		$driveDir = $GLOBALS['api']['fs']['root'];
		if(!file_exists($driveDir)){$oldmask = umask(0);$r = @mkdir($driveDir,0777,1);umask($oldmask);}
		$q = realpath($driveDir.$path);$e = '';
		/* Soporte para usar ficheros zip como carpetas */
		if(!$q && $p = strpos($path,'.zip/')){$e = substr($path,$p+4);$path = substr($path,0,$p).'.zip';$q = realpath($driveDir.$path).$e;}
		if(!$q){return false;}
		$path = $q;
		if($GLOBALS['api']['fs']['serverCage']){$baseDir = realpath(getcwd().'/'.$driveDir);
		if(substr($path,0,strlen($baseDir)) != $baseDir){return false;}}
		return $path.(substr($path,-1) == '/' ? '' : '/');
	}

	function fs_helper_parsePath_trash($path){
		$driveDir = $GLOBALS['api']['fs']['trash'];
		if(!file_exists($driveDir)){$oldmask = umask(0);$r = @mkdir($driveDir,0777,1);umask($oldmask);}
		$path = realpath($driveDir.$path);
		if($GLOBALS['api']['fs']['serverCage']){$baseDir = realpath(getcwd().'/'.$driveDir);if(substr($path,0,strlen($baseDir)) != $baseDir){return false;}}
		return $path.'/';
	}

	function fs_file_getInfo($fileName,$filePath,$fileRoute,$finfo = false){
//FIXME: deprecated, usar fs_native_fileOB_get
		$fileData = stat($filePath.$fileName);
//FIXME: sacar fileRoute automaticamente con fs_native_route_get
		if(is_dir($filePath.$fileName)){return array('fileName'=>$fileName,'fileRoute'=>$fileRoute,'fileMime'=>'folder','fileDateM'=>$fileData['mtime']);}
		list($fileMimeType) = explode('; ',finfo_file($finfo,$filePath.$fileName));
		if($fileMimeType == 'application/zip'){$fileRoute = 'ziparc'.substr($fileRoute,6);}
		return array('fileName'=>$fileName,'fileRoute'=>$fileRoute,'fileMime'=>$fileMimeType,'fileSize'=>$fileData['size'],'fileDateM'=>$fileData['mtime']);
	}
	function fs_native_getInfo($file,$finfo = false){
		$fileData = stat($file);
		$fileRoute = ($GLOBALS['api']['fs']['serverCage']) ? 'native:drive:'.substr($file,strlen(realpath($GLOBALS['api']['fs']['root']))) : $file;
		$fileName = ($GLOBALS['api']['fs']['serverCage']) ? substr($file,strlen(realpath($GLOBALS['api']['fs']['root']))) : $file;
		$fileName = basename($fileName);
		if($fileName && substr($fileRoute,-1) == '/'){$fileRoute = substr($fileRoute,0,-1);}
		if($fileName){$fileRoute = substr($fileRoute,0,strlen($fileName)*-1);}
		if(is_dir($file)){return array('fileName'=>$fileName,'fileRoute'=>$fileRoute,'fileMime'=>'folder','fileDateM'=>$fileData['mtime']);}
		//FIXME: poner un mime por defecto
		$fileMimeType = '';if($finfo){list($fileMimeType) = explode('; ',finfo_file($finfo,$file));}
		if($fileMimeType == 'application/zip'){$fileRoute = 'ziparc'.substr($fileRoute,6);}
		return array('fileName'=>$fileName,'fileRoute'=>$fileRoute,'fileMime'=>$fileMimeType,'fileSize'=>$fileData['size'],'fileDateM'=>$fileData['mtime']);
	}

	function fs_folder_list($fileRoute = ''){
		include_once('fs/inc.fs.native.php');
		$protocol = substr($fileRoute,0,6);
		switch($protocol){
			case 'gdrive':include_once('fs/inc.fs.gdrive.php');return fs_gdrive_list($fileRoute);
		}
		if(strpos($fileRoute,'native:trash:') === 0){return fs_trash_list($fileRoute);}
		if(strpos($fileRoute,'native:drive:/') === 0){$fileRoute = substr($fileRoute,14);}
		if(strpos($fileRoute,'ziparc:drive:/') === 0){$fileRoute = substr($fileRoute,14);}
		$filePath = fs_helper_parsePath($fileRoute);if($filePath === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!is_dir($filePath)){
			switch(true){
				case (strpos($filePath,'.zip/')):include_once('fs/inc.fs.zip.php');return fs_zip_list($filePath);
			}
			return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);
		}
		$fileRoute = ($GLOBALS['api']['fs']['serverCage']) ? 'native:drive:'.substr($filePath,strlen(realpath($GLOBALS['api']['fs']['root']))) : $fileRoute;

		$files = array();
		if($handle = opendir($filePath)){
			$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);
			while(false !== ($fileName = readdir($handle))){if($fileName[0] == '.'){continue;}
				//echo $file.print_r(stat($driveDir.$fileName)).'\n';
				$files[] = fs_native_fileOB_get($filePath.$fileName,$finfo);
			}
			closedir($handle);
			finfo_close($finfo);
		}

		/* Al ser una carpeta no necesitamos mime */
		$folder = fs_native_getInfo($filePath);

//FIXME: esto no sirve para nada -> ksort
		sort($files);
		return array('folder'=>$folder,'files'=>$files);
	}

	function fs_file_copy($files,$target){
		/* $files could be json_encoded */
		if(is_string($files)){$files = json_decode($files,1);if($files === NULL){return array('errorDescription'=>'JSON_ERROR','file'=>__FILE__,'line'=>__LINE__);}}
		if($target[0] == '{'){$target = json_decode($target,1);if($target === NULL){return array('errorDescription'=>'JSON_ERROR','file'=>__FILE__,'line'=>__LINE__);}$target = $target['fileRoute'].$target['fileName'].'/';}
//FIXME: if(!count($files))
		$destRoute = $target;

		if(strpos($destRoute,'native:drive:') === 0){$destRoute = substr($destRoute,13);}
		$destPath = fs_helper_parsePath($destRoute);
		if($destPath === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!is_dir($destPath)){return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);}
		$destRoute = ($GLOBALS['api']['fs']['serverCage']) ? 'native:drive:'.substr($destPath,strlen(realpath($GLOBALS['api']['fs']['root']))) : $destPath;

		$fileRoutes = array();foreach($files as $file){$fileRoutes[$file['fileRoute']][] = $file;}
		$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);
		$return = array('add'=>array());
		foreach($fileRoutes as $fileRoute=>$files){
			if(strpos($fileRoute,'native:drive:') === 0){$fileRoute = substr($fileRoute,13);}
			$filePath = fs_helper_parsePath($fileRoute);
			if($filePath === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
			if(!is_dir($filePath)){return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);}
			foreach($files as $file){
				$sourceFile = $filePath.$file['fileName'];
				$targetFile = $destPath.$file['fileName'];
				/* If the source and target are the same we cant copy the file */
				if($sourceFile == $targetFile){continue;}
				if(file_exists($targetFile)){/* FIXME */continue;}
				//FIXME: hacer el copy con fread y fwrite y llevar el progreso
				$r = @copy($sourceFile,$targetFile);
				if(!$r){return array('errorDescription'=>'UNKNOWN_ERROR_WHILE_MOVING'.' '.$sourceFile.' to '.$targetFile,'file'=>__FILE__,'line'=>__LINE__);}
				$return['add'][$destRoute][] = fs_file_getInfo($file['fileName'],$destPath,$destRoute,$finfo);
			}
		}
		finfo_close($finfo);

		return $return;
	}

	function fs_file_compress($files,$target = false,$algorithm = 'zip'){
		$return = array('add'=>array());
		/* $files could be json_encoded */
		if(is_string($files)){$files = json_decode($files,1);if($files === NULL){return array('errorDescription'=>'JSON_ERROR','file'=>__FILE__,'line'=>__LINE__);}}
		if(!count($files)){return $return;}
		if($target && $target[0] == '{'){$target = json_decode($target,1);if($target === NULL){return array('errorDescription'=>'JSON_ERROR','file'=>__FILE__,'line'=>__LINE__);}$target = $target['fileRoute'].$target['fileName'].'/';}
		$targetName = false;if(!$target){$targetName = uniqid().'.'.$algorithm;}

		$fileRoutes = array();foreach($files as $file){$fileRoutes[$file['fileRoute']][] = $file;}
		$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);
		$zip = new ZipArchive;
		foreach($fileRoutes as $fileRoute=>$files){
			if(strpos($fileRoute,'native:drive:') === 0){$fileRoute = substr($fileRoute,13);}
			$filePath = fs_helper_parsePath($fileRoute);
			if($filePath === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
			if(!is_dir($filePath)){return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);}
			$destRoute = ($GLOBALS['api']['fs']['serverCage']) ? 'native:drive:'.substr($filePath,strlen(realpath($GLOBALS['api']['fs']['root']))) : $filePath;

			$oldmask = umask(0);
			if($zip->open($filePath.$targetName,ZIPARCHIVE::CREATE) !== true){return array('errorDescription'=>'COMPRESS_ERROR '.$filePath.$targetName,'file'=>__FILE__,'line'=>__LINE__);}
			foreach($files as $file){
				$sourceFile = $filePath.$file['fileName'];
				$zip->addFile($sourceFile,$file['fileName']);
			}
			$zip->close();
			umask($oldmask);
			$return['add'][$destRoute][] = fs_native_getInfo($filePath.$targetName,$finfo);
		}
		finfo_close($finfo);

		return $return;
	}

	function fs_trash($fileOBs = array()){
		$filesByProtocol = array();
		foreach($fileOBs as $fileOB){$protocol = substr($fileOB['fileRoute'],0,6);$filesByProtocol[$protocol][] = $fileOB;}
		foreach($filesByProtocol as $protocol=>$fileOBs){
			$r = fs_protocol($protocol,__FUNCTION__,array($fileOBs));
		}
	}

	function fs_file_trash($files,$db = false){
		/* $files could be json_encoded */
		if(is_string($files)){$files = json_decode($files,1);if($files === NULL){return array('errorDescription'=>'JSON_ERROR','file'=>__FILE__,'line'=>__LINE__);}}
		if(!file_exists($GLOBALS['api']['fs']['trash'])){$oldmask = umask(0);$r = @mkdir($GLOBALS['api']['fs']['trash'],0777,1);umask($oldmask);}

		$fileRoutes = array();
		foreach($files as $file){$fileRoutes[$file['fileRoute']][] = $file;}

		$return = array('remove'=>array());
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['fs']['trashdb']);sqlite3_exec('BEGIN',$db);$shouldClose = true;}
		$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);
		foreach($fileRoutes as $fileRoute=>$files){
			if(strpos($fileRoute,'native:drive:') === 0){$fileRoute = substr($fileRoute,13);}
			$filePath = fs_helper_parsePath($fileRoute);
			if($filePath === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
			if(!is_dir($filePath)){return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);}
			$fileRoute = ($GLOBALS['api']['fs']['serverCage']) ? 'native:drive:'.substr($filePath,strlen(realpath($GLOBALS['api']['fs']['root']))) : $filePath;

			$return['remove'][$fileRoute] = array();
			foreach($files as $file){
				$sourceFile = $filePath.$file['fileName'];
				if(!file_exists($sourceFile)){continue;}
				do{$fileHash = uniqid();$targetFile = $GLOBALS['api']['fs']['trash'].$fileHash;}while(false);
				$r = @rename($sourceFile,$targetFile);
				if(!$r){return array('errorDescription'=>'UNKNOWN_ERROR_WHILE_MOVING'.' '.$sourceFile.' to '.$targetFile,'file'=>__FILE__,'line'=>__LINE__);}

				if(is_dir($targetFile)){$fileMimeType = 'folder';}
				else{list($fileMimeType) = explode('; ',finfo_file($finfo,$targetFile));}

				//FIXME:
				$arr = array('fileHash'=>$fileHash,'fileRoute'=>$fileRoute,'fileMime'=>$fileMimeType,'fileSize'=>11,'filePermissions'=>'a','fileChilds'=>'aa','fileOwner'=>'aa','fileGroup'=>'aa',
				'fileDate'=>'11','fileTime'=>'11','fileName'=>$file['fileName']);
				$r = sqlite3_insertIntoTable('trash',$arr,$db);
				if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
				$return['remove'][$fileRoute][] = $file['fileName'];
			}
		}
		finfo_close($finfo);
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('OK'=>false,'errno'=>$GLOBALS['DB_LAST_ERRNO'],'error'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

//FIXME: falta añadir con la ruta de trash
		return $return;
	}

	function fs_file_stream($fileName,$fileRoute){
		if(strpos($fileRoute,'native:drive:') === 0){$fileRoute = substr($fileRoute,13);}
		$fileRoute = fs_helper_parsePath($fileRoute);
		if($fileRoute === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!is_dir($fileRoute)){return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);}
		$targetFile = $fileRoute.$fileName;
		if(!file_exists($targetFile)){return array('errorDescription'=>'FILE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}

		$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);list($fileMimeType) = explode('; ',finfo_file($finfo,$targetFile));finfo_close($finfo);
		header('Content-type: '.$fileMimeType);
		readfile($targetFile);exit;
	}

	function fs_file_stat($path){
return false;
		$s = stat($path);

		$realDrivePath = realpath($GLOBALS['drivePath']);
		$relativeDir = strpos($driveDir,$realDrivePath);
		$relativeDir = substr($driveDir,$relativeDir+strlen($realDrivePath));
		$fileHash = sha1_file($driveDir.$fileName);
		/* From 16384(4000) to 16895(41ff) */
		if($s['mode'] > 16383 && $s['mode'] < 16896){$fileIsDir = 1;$fileMime = 'folder';}
		else{
			$fileIsDir = 0;
			/* MimeType */
			$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);
			$fileMime = finfo_file($finfo,$driveDir.$fileName);
			finfo_close($finfo);
		}
		$fileLink = 'users/'.$GLOBALS['userAlias'].'/drive'.$relativeDir.$fileName.($fileIsDir == 1 ? '/' : '');

		$f = array('fileName'=>$fileName,'fileRoute'=>$relativeDir,'fileMime'=>$fileMime,'fileSize'=>$s['size'],'fileDate'=>date('Y-m-d',$s['ctime']),'fileTime'=>date('H:i:s',$s['ctime']),
			'fileDateM'=>date('Y-m-d',$s['mtime']),'fileTimeM'=>date('H:i:s',$s['mtime']),'fileIsDir'=>$fileIsDir,'fileHash'=>'sha1:'.$fileHash,'fileLink'=>$fileLink,'fileIface'=>'native:drive');

		$a = array('errorCode'=>0,'data'=>$f);
		return $a;
	}

	function fs_helper_removeDir($path,$avoidCheck=false){
		if(!$avoidCheck){$path = preg_replace('/\/$/','/',$path);if(!file_exists($path) || !is_dir($path)){return;}}
		if($handle = opendir($path)){while(false !== ($file = readdir($handle))){
			if(in_array($file,array('.','..'))){continue;}
			if(is_dir($path.$file)){fs_helper_removeDir($path.$file.'/',true);continue;}
			unlink($path.$file);
		}closedir($handle);}
		rmdir($path);
		return true;
	}

	function fs_trash_list($fileRoute = false){
		if(strpos($fileRoute,'native:trash:') !== 0){return fs_folder_list($fileRoute);}
		$fileRoute = substr($fileRoute,13);
		$p = strpos($fileRoute,'/',1);
		/* It can be a hashed folder or just a filename */
		$fileHash = ($p !== false) ? substr($fileRoute,0,$p) : $fileRoute;
		if(empty($fileHash) || $fileHash == '/'){
			$fileRows = fs_trash_getWhere(1);
			$files = array();
			foreach($fileRows as $fileRow){
				//FIXME: fileDateM
				$files[] = array('fileName'=>$fileRow['fileName'],'fileAlias'=>$fileRow['fileHash'],'fileRoute'=>'native:trash:/','fileMime'=>$fileRow['fileMime'],'fileSize'=>$fileRow['fileSize'],'fileDateM'=>'');
			}
			$folder = array('fileName'=>'trash','fileRoute'=>'native:trash:/','fileMime'=>'folder');
			return array('folder'=>$folder,'files'=>$files);
		}


		if($fileHash[0] == '/'){$fileHash = substr($fileHash,1);}
		$fileRow = fs_trash_getSingle('(fileHash = \''.$fileHash.'\')');
		if(!$fileRow){return array('errorDescription'=>'FILE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}
		$filePath = fs_helper_parsePath_trash($fileRoute);

		if($filePath === false){return array('errorDescription'=>'PATH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!is_dir($filePath)){return array('errorDescription'=>'PATH_IS_NOT_DIRECTORY','file'=>__FILE__,'line'=>__LINE__);}
		$fileRoute = ($GLOBALS['api']['fs']['serverCage']) ? 'native:trash:'.substr($filePath,strlen(realpath($GLOBALS['api']['fs']['root']))) : $filePath;

		$files = $folders = array();
		if($handle = opendir($filePath)){
			$finfo = finfo_open(FILEINFO_MIME,$GLOBALS['api']['fs']['file.mime']);
			while(false !== ($file = readdir($handle))){if($file[0] == '.'){continue;}
				$fileData = stat($filePath.$file);
				if(is_dir($filePath.$file)){$files[] = array('fileName'=>$file,'fileRoute'=>'native:trash:'.$fileRoute,'fileMime'=>'folder','fileDateM'=>$fileData['mtime']);continue;}
				list($fileMimeType) = explode('; ',finfo_file($finfo,$filePath.$file));
				$files[] = array('fileName'=>$file,'fileRoute'=>'native:trash:'.$fileRoute,'fileMime'=>$fileMimeType,'fileSize'=>$fileData['size'],'fileDateM'=>$fileData['mtime']);
			}
			finfo_close($finfo);
			closedir($handle);
		}

		$folder = array('fileName'=>'trash','fileRoute'=>'native:trash:/','fileMime'=>'folder');
		return array('folder'=>$folder,'files'=>$files);
	}

	function fs_trash_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['fs']['trashdb'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'fileHash';}
		$r = sqlite3_getSingle('trash',$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function fs_trash_getWhere($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['fs']['trashdb'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'fileHash';}
		$r = sqlite3_getWhere('trash',$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
?>
