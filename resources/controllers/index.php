<?php
	function index_main(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.desktop.php');
		include_once('api.drives.php');
		include_once('api.fs.php');

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'transfer.fragment':
				include_once('inc.uploadchain.php');
				$_params = array('file_name','file_size','file_parts','fragment_num','fragment_src','fragment_sum','fragment_len');
				foreach($_params as $param){if(!isset($_POST[$param]) || $_POST[$param] === ''){print_r(array('errorDescription'=>'INVALID_PARAMS:'.$param,'file'=>__FILE__,'line'=>__LINE__));exit;}}
				$r = uploadchain_fragment($_POST);if(isset($r['errorDescription'])){echo json_encode($r);exit;}
				if(isset($r['filePath'])){
					$oldmask = umask(0);
					$m = rename($r['filePath'],$GLOBALS['api']['fs']['root'].$r['fileName']);
					umask($oldmask);
//FIXME: emitir actualización de fichero
					echo json_encode(array('errorCode'=>'0'));exit;
				}
				echo json_encode($r);exit;
		}}

		//$r = fs_rename(array('fileName'=>'lololo.txt','fileRoute'=>'native:drive:/'),'test.txt');print_r($r);exit;
		//$r = fs_rename(array('fileName'=>'','fileMime'=>'folder','fileRoute'=>'native:drive:/'),'nueva');print_r($r);exit;
		//$r = fs_move(array('fileName'=>'as','fileMime'=>'folder','fileRoute'=>'native:drive:/'),'native:drive:/a/');print_r($r);exit;
		//$r = fs_move(json_decode('[{"fileName":"08_10_07_darker_wood_1920x1200.jpg","fileRoute":"ziparc:drive:/52cdd120a91ca.zip/","fileMime":"image/jpeg","fileSize":901598,"fileDateM":1318198554}]',1),json_decode('{"fileName":"asd","fileRoute":"native:drive:/","fileMime":"folder","fileDateM":1392418100}',1));print_r($r);exit;
		//$r = fs_move(json_decode('[{"fileName":"Music","fileRoute":"ziparc:drive:/52cdd120a91ca.zip/","fileMime":"folder"}]',1),json_decode('{"fileName":"asd","fileRoute":"native:drive:/","fileMime":"folder","fileDateM":1392418100}',1));print_r($r);exit;
		//$r = fs_folder_list('native:drive:/');

		$apps = desktop_app_getWhere('(appStatus = 1)');
		//FIXME:
		$places = array(
			array('placeName'=>'Desktop','placeType'=>'desktop','placeRoute'=>'native:drive:/'),
			array('placeName'=>'Trash','placeType'=>'trash','placeRoute'=>'native:trash:/')
		);
		if($drives = drives_status()){
			foreach($drives as $drive){
				$drive['driveStatus'] = str_replace(array('unknown'),array('disabled'),$drive['driveStatus']);
				$places[] = array('placeName'=>$drive['pool'],'placeType'=>'drive','placeRoute'=>$drive['pool'].':/','placeStatus'=>$drive['driveStatus']);
			}
		}

		//FIXME: hack
		if(!$apps){$apps = array(
			array('appCode'=>'synaptic','appName'=>'Synaptic'),
			array('appCode'=>'users','appName'=>'Users and groups'),
			array('appCode'=>'disks','appName'=>'Volume Manager'),
			array('appCode'=>'fileRoller','appName'=>'Compress Manager')
		);}
		$TEMPLATE['JSON.apps'] = json_encode($apps);
		$TEMPLATE['JSON.places'] = json_encode($places);
		$userValidFields = array_flip(array('id','userMail','userName'));
		$user = $GLOBALS['user'];foreach($user as $k=>$v){if(!isset($userValidFields[$k])){unset($user[$k]);}}
		$TEMPLATE['JSON.user'] = json_encode($user);
		/* INI-user-config */
		$config = users_data_load('(name LIKE \'public.%\')');
		$TEMPLATE['JSON.config'] = json_encode($config);
		/* END-user-config */

		$HTML_apps = '';foreach($apps as $app){$HTML_apps .= J.'<li onclick="launchApp(\''.$app['appCode'].'\');">'.$app['appName'].'</li>'.N;}
		$TEMPLATE['HTML_apps'] = $HTML_apps;

		//$desktopIcons = fs_folder_list('native:drive:/52cdd120a91ca.zip/Music/');print_r($desktopIcons);exit;
		//$desktopIcons = fs_folder_list('native:drive:/52cdd120a91ca.zip/');print_r($desktopIcons);exit;
		//$desktopIcons = fs_folder_list('native:drive:/lalala.zip/');print_r($desktopIcons);exit;
		//$desktopIcons = fs_folder_list('native:drive:/');print_r($desktopIcons);exit;
		//$r = fs_rename(array('fileName'=>'.','fileRoute'=>'ziparc:drive:/52cdd120a91ca.zip/'),'asd');print_r($r);exit;
		//$r = fs_rename(array('fileName'=>'lololo.txt','fileRoute'=>'ziparc:drive:/lalala.zip/'),'2lololo.txt');print_r($r);exit;
		common_renderTemplate('desktop');
	}

	function index_av($id = '',$s = 32){
		$id = preg_replace('/[^0-9]*/','',$id);
		$s = preg_replace('/[^0-9]*/','',$s);if(empty($s)){$s = 32;}
		$imagePath = $GLOBALS['api']['users']['dir.users'].$id.'/avatar/'.$s.'.jpeg';
		if(!file_exists($imagePath)){$imagePath = '../images/avatars/default/av'.$s.'.jpeg';}
		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}

	function index_login(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		if(users_isLogged()){header('Location: '.$GLOBALS['baseURL']);exit;}

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'ajax.user.login':$r = users_login($_POST['userMail'],$_POST['userPass']);
				unset($r['userPass'],$r['userCode'],$r['userStatus']);echo json_encode($r);exit;
			case 'user.register':
				$users = users_getSingle(1);if($users){break;}
				if(!isset($_POST['userPass']) || !isset($_POST['userPassR']) || $_POST['userPass'] != $_POST['userPassR']){echo 'passwords mismatch';exit;}
				$r = users_create($_POST);if(isset($r['errorDescription'])){print_r($r);exit;}
				$user = $r;
				/* Activate the new user so he can log into the system */
				$r = users_update($user['userMail'],array('userStatus'=>1,'userModes'=>',regular,admin,','userCode'=>''));
				header('Location: '.$GLOBALS['baseURL']);exit;
		}}

		/* INI-print all the allowed users */
		$users = users_getWhere(1,array('indexBy'=>'userMail'));
		if(!$users){return common_renderTemplate('u/register');}
		$usersGrid = '';
		foreach($users as $user){
			$user['loginName'] = $user['userName'];
			$usersGrid .= common_loadSnippet('snippets/login.user',$user);
		}
		$TEMPLATE['usersGrid'] = $usersGrid;
		/* INI-print all the allowed users */

		$TEMPLATE['BLOG_TITLE'] = 'Login';
		$TEMPLATE['HTML_DESCRIPTION'] = 'Login';
		$TEMPLATE['BLOG_SCRIPTS'][] = '{%baseURL%}r/js/login.js';
		common_renderTemplate('login');
	}

	function index_logout(){
		users_logout();
		header('Location: '.$GLOBALS['baseURL']);exit;
	}
?>
