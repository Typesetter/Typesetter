<?php
defined('is_running') or die('Not an entry point...');

/*



bool symlink ( string $target , string $link )
symlink() creates a symbolic link to the existing target with the specified name link .


A Script Like this would most likely need to work with install.php to set up the data directories
	Using the Install_DataFiles_New() function
		- $_POST['username']
		- $_POST['password']


*/

$langmessage['not root install'] = 'Notice: This is not the root installation of gpEasy.';
$langmessage['site url'] = 'Site URL';
$langmessage['easily add installations'] = 'This addon will allow you to easily add installations of gpEasy to your server.';
$langmessage['multi_site_notes'] = ' This will not copy gpEasy code to new folders.';
$langmessage['multi_site_notes'] .= ' Rather, new installations will use the code running the current gpEasy installation.';
$langmessage['multi_site_notes'] .= ' This is more efficient and will enable you to update all of your gpEasy installations at once by updating the root installation.';
$langmessage['new_installation'] = 'Create a New Installation';



includeFile('tool/ftp.php');
includeFile('tool/RemoteGet.php');

class SetupSite{

	var $siteData = array();
	var $dataFile;
	var $checksum;
	var $site_uniq_id;

	function SetupSite(){
		global $dataDir, $page, $addonFolderName,$langmessage;

		if( defined('multi_site_unique') ){
			message($langmessage['not root install']);
			return;
		}

		$page->css_user[] = '/data/_addoncode/'.$addonFolderName.'/multi_site.css';
		//$page->head_js[] = '/data/_addoncode/'.$addonFolderName.'/multi_site.js';
		$page->head_js[] = '/include/js/admin_users.js';


		$page->admin_links[] = array('Admin_Site_Setup','Multi-Site Home');
		$page->admin_links[] = array('Admin_Site_Setup','New Installation','cmd=new');
		$page->admin_links[] = array('Admin_Site_Setup','Settings','cmd=settings');
		$page->admin_links[] = array('Admin_Site_Setup','About','cmd=about');

		$_REQUEST += array('install'=>array());


		//ftp setup
		$this->GetSiteData();


		$hide = false;
		$cmd = common::GetCommand();
		switch($cmd){

			case 'about':
				$this->About(true);
				$hide = true;
			break;

			case 'installed':
				$this->ShowSites();
				$hide = true;
			break;

			/* settings */
			case 'settings':
				$this->SettingsForm($this->siteData);
				$hide = true;
			break;
			case 'Save Settings':
				if( !$this->SaveSettings() ){
					$this->SettingsForm($_POST);
					$hide = true;
				}
			break;


			case 'save_options':
			case 'options':
				$this->Options($cmd);
				$hide = true;
			break;




			case 'uninstall':
				$this->UninstallSite();
			break;


			/*
			 * New Installation
			 */

			case 'new';
			case 'Install Now':
			case 'new_plugins':
			case 'new_install':
			case 'new_destination':
			case 'new_themes':
			case 'Continue':
				$this->InstallStatus($cmd);
				$hide = true;
			break;


			case 'subfolder':
				$this->SubFolder();
				$this->InstallStatus($cmd);
				$hide = true;
			break;

			case 'expandfolder':
				$this->ExpandFolder();
				$hide = true;
			return;
			case 'newfolder':
				$this->NewFolder();
				$hide = true;
			break;


			case 'Delete Folder':
				$hide = true;
				$this->RemoveDir();
			break;

			case 'rmdir':
				$hide = true;
				$this->RemoveDirPrompt();
			break;


		}

		if( !$hide ){
			$this->FrontPage();
		}
	}


	function Options($cmd = ''){
		global $langmessage;

		$site =& $_REQUEST['site'];
		if( !isset($this->siteData['sites'][$site]) ){
			message($langmessage['OOPS']);
			return false;
		}

		//message('Posted: '.showArray($_POST));
		//message('site data: '.showArray($this->siteData['sites'][$site]));

		switch($cmd){
			case 'save_options';
				$this->Options_Save($site);
			break;
		}

		$args = $_POST + $this->siteData['sites'][$site] + array('url'=>'http://');

		echo '<div id="install_wrap">';
		echo '<div id="install_step">';
		echo '<div id="install_step_inner">';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<table width="100%">';

		echo '<tr><th colspan="2">';
		echo $langmessage['options'];
		echo ': '.$site;
		echo '</th></tr>';

		echo '<tr><td class="label">';
		echo $langmessage['site url'];
		echo '</td><td>';
		echo '<input type="text" name="url" value="'.htmlspecialchars($args['url']).'" />';
		echo '</td>';
		echo '</tr>';

		echo '<tr><td class="label">';
		echo $langmessage['hide_index'];
		echo '</td><td>';

		if( gpRemoteGet::Test() ){
			if( isset($args['hide_index']) ){
				echo '<input type="checkbox" name="hide_index" value="hide_index" checked="checked"/>';
			}else{
				echo '<input type="checkbox" name="hide_index" value="hide_index"/>';
			}
		}else{
			echo 'Unavailable: Your php installation doesn\'t support the necessary functions to enable this option.';
		}

		echo '</td>';
		echo '</tr>';

		echo '</table>';

		echo '<div id="install_continue">';
		echo '<input type="hidden" name="site" value="'.htmlspecialchars($site).'" />';
		echo '<input type="hidden" name="cmd" value="save_options" />';
		echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'" class="continue"/>';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		echo '</div>';

		echo '<p>';
 		echo common::Link('Admin_Site_Setup',$langmessage['back']);
 		echo '</p>';

		echo '</form>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	function Options_Save($site){
		global $langmessage;
		$save = $this->Options_SiteUrl($site);
		$save = $save && $this->Options_htaccess($site);

		if( $save ){
			$this->SaveSiteData();
			message($langmessage['SAVED']);
		}
	}

	function Options_htaccess($site){
		global $langmessage;
		includeFile('admin/admin_permalinks.php');

		if( !gpRemoteGet::Test() ){
			return;
		}

		$site_info = $this->siteData['sites'][$site];
		$site_url = $site_info['url'];
		$site_uniq = false;
		if( isset($site_info['gpuniq']) ){
			$site_uniq = $site_info['gpuniq'];
		}
		$file_path = $site.'/.htaccess';
		if( file_exists($file_path) ){
			$original_contents = $contents = file_get_contents($file_path);
		}

		if( !isset($_POST['hide_index']) ){
			$to_hide_index = false;
			unset($this->siteData['sites'][$site]['hide_index']);
			$prefix = '';

		}else{

			if( empty($site_url) ){
				message('A valid site url is required to hide index.php');
				return false;
			}

			$array = @parse_url($site_url);
			$prefix =& $array['path'];
			$to_hide_index = $this->siteData['sites'][$site]['hide_index'] = true;
		}

		//add the gpeasy rules
		admin_permalinks::StripRules($contents);
		$contents .= admin_permalinks::Rewrite_Rules($to_hide_index,$prefix,$site_uniq);
		if( !gpFiles::Save($file_path,$contents) ){
			message($langmessage['OOPS'].' (Couldn\'t save .htaccess)');
			return false;
		}

		//check for valid response when hiding index.php
		if( $to_hide_index ){
			$check_url = $site_url.'/Special_Site_Map';
			$result = gpRemoteGet::Get_Successful($check_url);
			if( !$result ){
				message('Did not recieve valid response when fetching url without index.php: '.htmlspecialchars($check_url));
				gpFiles::Save($file_path,$original_contents);
				return false;
			}

		}



		return true;
	}

	function Options_SiteUrl($site){
		global $langmessage;

		if( empty($_POST['url']) ){
			unset($this->siteData['sites'][$site]['url']);
			return true;
		}
		$site_url = $_POST['url'];

		//remove index.php
		$pos = strpos($site_url,'/index.php');
		if( $pos ){
			$site_url = substr($site_url,0,$pos);
		}

		if( $site_url == 'http://' ){
			message($langmessage['OOPS'].' (Invalid URL)');
			return false;
		}

		$array = @parse_url($site_url);
		if( $array === false ){
			message($langmessage['OOPS'].' Invalid URL');
			return false;
		}

		if( empty($array['scheme']) ){
			$site_url = 'http://'.$site_url;
		}

		$this->siteData['sites'][$site]['url'] = rtrim($site_url,'/');

		return true;
	}

	function FrontPage(){
		global $langmessage;
		echo '<div id="install_wrap">';
		echo '<h3>gpEasy Multi-Site Plugin</h3>';

		echo '<div class="sm">';
		echo 'Easily add multiple installations of gpEasy to your server.';
		echo '</div>';

		echo '<div id="ms_links">';
		echo common::Link('Admin_Site_Setup',$langmessage['new_installation'],'cmd=new');
		echo ' &nbsp; &nbsp; ';
		echo common::Link('Admin_Site_Setup',$langmessage['Settings'],'cmd=settings');
		echo ' &nbsp; &nbsp; ';
		echo common::Link('Admin_Site_Setup',$langmessage['about'],'cmd=about');
		echo '</div>';

		$this->ShowSimple();
		echo '</div>';
	}


	function About($full){
		global $langmessage;

		echo '<div id="install_wrap">';
		echo '<h3>';
		echo $langmessage['about'];
		echo '</h3>';
		echo '<p>';
		echo $langmessage['easily add installations'];
		echo '</p>';

		echo ' <h3>';
		echo $langmessage['Notes'];
		echo '</h3> ';
		echo '<p>';
		echo $langmessage['multi_site_notes'];
		echo '</p>';


		echo '<h3>';
		echo common::Link('Admin_Site_Setup',$langmessage['Settings'],'cmd=settings');
		echo '</h3>';

		echo '<dl>';
		echo '<dt>Service Provider ID</dt>';
		echo '<dd>When your provider id is entered, <a href="http://www.gpeasy.com/Special_Services">gpEasy.com Services</a> can attribute each installation to your service.</dd>';
		echo '</dl>';

		echo '<dl>';
		echo '<dt>Service Provider Name</dt>';
		echo '<dd>Displayed on the site map of your hosted installations.</dd>';
		echo '</dl>';

		echo '<p>';
 		echo common::Link('Admin_Site_Setup',$langmessage['back']);
 		echo '</p>';

		echo '</div>';
	}



	function SaveSettings(){
		global $langmessage;

		$UpdateIndexFiles = false;

		//ftp information
		$ok_to_save = $this->SaveFTPInformation();


		//provider id
		if( !empty($_POST['service_provider_id']) ){
			if( is_numeric($_POST['service_provider_id']) ){

				//update index.php files
				if( !isset($this->siteData['service_provider_id']) || ($_POST['service_provider_id'] != $this->siteData['service_provider_id']) ){
					$UpdateIndexFiles = true;
				}

				$this->siteData['service_provider_id'] = $_POST['service_provider_id'];
			}else{
				message('The Service Provider ID must be a number.');
				$ok_to_save = false;
			}
		}

		//provider name
		if( !empty($_POST['service_provider_name']) ){

			//update index.php files
			if( !isset($this->siteData['service_provider_name']) || ($_POST['service_provider_name'] != $this->siteData['service_provider_name']) ){
				$UpdateIndexFiles = true;
			}

			$this->siteData['service_provider_name'] = $_POST['service_provider_name'];
		}

		if( $UpdateIndexFiles ){
			$this->UpdateProviderID();
		}

		if( !$ok_to_save ){
			return false;
		}

		if( $this->SaveSiteData() ){
			message($langmessage['SAVED']);
			return true;
		}
		message($langmessage['OOPS']);
		return false;

	}

	function UpdateProviderID(){
		foreach($this->siteData['sites'] as $path => $info){
			if( !isset($info['unique']) ){
				$info['unique'] = $this->NewId();
			}
			$this->CreateIndex($path,$info['unique']);
		}
		$this->SaveSiteData();
	}


	function SettingsForm($values=array()){
		global $langmessage,$config;

		$values += array('service_provider_id'=>'','service_provider_name'=>'');

		$ftp_vals = $_POST + $config + array('ftp_server'=>gpftp::GetFTPServer(),'ftp_user'=>'');


		echo '<div id="install_wrap">';
		echo '<h3>Settings</h3>';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<table class="bordered" width="100%">';

		echo '<tr>';
		echo '<th colspan="2">Service Provider Identification</th>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="2">';
		echo 'When your provider id is entered, <a href="http://www.gpeasy.com/Special_Services">gpEasy.com Services</a> can attribute each installation to your service.';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		echo 'Service Provider ID';
		echo '</td>';
		echo '<td>';
		echo '<input type="text" name="service_provider_id" value="'.htmlspecialchars($values['service_provider_id']).'" size="30" />';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		echo 'Service Provider Name';
		echo '</td>';
		echo '<td>';
		echo '<input type="text" name="service_provider_name" value="'.htmlspecialchars($values['service_provider_name']).'" size="30" />';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>&nbsp;</td>';
		echo '<td>&nbsp;</td>';
		echo '</tr>';


		echo '<tr>';
		echo '<th>FTP</th>';
		echo '<th>&nbsp;</th>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>FTP Server</td>';
		echo '<td>';
		echo '<input type="text" name="ftp_server" value="'.$ftp_vals['ftp_server'].'" size="30" />';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>FTP Username</td>';
		echo '<td>';
		echo '<input type="text" name="ftp_user" value="'.$ftp_vals['ftp_user'].'" size="30" />';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>FTP Password</td>';
		echo '<td>';
		echo '<input type="password" name="ftp_pass" value="" size="30" />';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

		echo '<div id="install_continue">';
		echo '<input type="submit" name="cmd" value="Save Settings" class="continue"/>';
		echo ' <input type="submit" name="" value="Cancel" />';
		echo '</div>';


		echo '</form>';
		echo '</div>';

	}

	function ShowSimple(){
		global $langmessage;

		if( !isset($this->siteData['sites']) || (count($this->siteData['sites']) == 0) ){
			return;
		}
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="get">';
		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
		echo '<th>';
		echo 'Recent Installations';
		echo '</th>';
		echo '<th>';
		echo 'URL';
		echo '</th>';
		echo '<th>';
		echo $langmessage['options'];
		echo '</th>';
		echo '</tr>';
		$reverse = 	array_reverse($this->siteData['sites']);
		$i = 0;


		foreach($reverse as $site => $data){
			$this->ShowRow($site,$data);
			$i++;
			if( $i == 5 ){
				break;
			}
		}
		$this->SearchRow();
		echo '</table>';

		if( count($this->siteData['sites']) > 5 ){
			echo '<p>';
			echo common::Link('Admin_Site_Setup','More Installations','cmd=installed');
			echo '</p>';
		}

	}

	function ShowRow(&$site,&$data){
		global $langmessage;

		echo '<tr>';
		echo '<td>';
		if( strlen($site) > 25 ){
			echo '...'.substr($site,-21);;
		}else{
			echo $site;
		}
		echo '</td>';
		echo '<td>';
		if( !empty($data['url']) ){
			echo '<a href="'.$data['url'].'" target="_blank">';
			if( strlen($data['url']) > 40 ){
				echo substr($data['url'],0,35).'...';
			}else{
				echo $data['url'];
			}
			echo '</a>';
		}

		echo '</td>';
		echo '<td>';
		echo common::Link('Admin_Site_Setup',$langmessage['options'],'cmd=options&site='.urlencode($site));
		echo ' &nbsp; ';
		//echo common::Link('Admin_Site_Setup',$langmessage['uninstall'],'cmd=uninstall&site='.urlencode($site),' name="gpajax"');

		$title = sprintf($langmessage['generic_delete_confirm'],' &quot;'.htmlspecialchars($site).'&quot; ');
		echo common::Link('Admin_Site_Setup',$langmessage['uninstall'],'cmd=uninstall&site='.urlencode($site),array('name'=>'postlink','class'=>'gpconfirm','title'=>$title));

		echo '</td>';
		echo '</tr>';
	}


	function ShowSites(){
		global $langmessage;

		$limit = 20; //20
		$offset = 0;
		if( isset($_GET['offset']) && is_numeric($_GET['offset']) ){
			$offset = $_GET['offset'];
		}

		if( !isset($this->siteData['sites']) || (count($this->siteData['sites']) == 0) ){
			return;
		}

		echo '<div id="install_wrap">';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="get">';
		echo '<table class="bordered">';
		echo '<tr>';
		echo '<th>';
		echo 'Recent Installations';
		echo '</th>';
		echo '<th>';
		echo 'URL';
		echo '</th>';
		echo '<th>';
		echo '&nbsp;';
		echo '</th>';
		echo '</tr>';

		$this->SearchRow();

		$reverse = 	array_reverse($this->siteData['sites']);
		if( !empty($_GET['q']) ){
			$reverse = $this->Search($reverse);
			if( count($reverse) == 0 ){
				echo '<tr>';
				echo '<td colspan="2">';
				echo 'Could not find any installations matching your search criteria.';
				echo '</td>';
				echo '</tr>';
			}
		}
		if( $offset > 0 ){
			$reverse = array_splice($reverse,$offset);
		}

		$i = 0;
		foreach($reverse as $site => $data){
			$this->ShowRow($site,$data);
			$i++;
			if( $i == $limit ){
				break;
			}
		}


		echo '</table>';
		echo '</form>';

		//navigation links
		if( $offset > 0 ){
			echo common::Link('Admin_Site_Setup','Prev','cmd=installed&q='.urlencode($_GET['q']).'&offset='.max(0,$offset-$limit));
		}else{
			echo 'Prev';
		}
		echo ' &nbsp; ';
		if( count($reverse) > $limit ){
			echo common::Link('Admin_Site_Setup','Next','cmd=installed&q='.urlencode($_GET['q']).'&offset='.($offset+$limit));
		}else{
			echo 'Next';
		}

		echo '<p>';
 		echo common::Link('Admin_Site_Setup',$langmessage['back']);
 		echo '</p>';

		echo '</div>';
	}

	function Search(&$array){
		$result = array();
		$key = $_GET['q'];
		foreach($array as $path => $info){

			if( strpos($path,$key) !== false ){
				$result[$path] = $info;
				continue;
			}
		}
		return $result;
	}

	function SearchRow(){
		$_GET += array('q'=>'');

		echo '<tr>';
			echo '<td colspan="2">';
			echo '<input type="text" name="q" value="'.htmlspecialchars($_GET['q']).'" />';
			echo '</td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="installed" />';
			echo '<input type="submit" name="" value="Search" />';
			echo '</td>';
			echo '</tr>';
	}


	/**
	 * Remove the files and folders of an installation as determined by the post request
	 *
	 */
	function UninstallSite(){
		global $langmessage, $config;

		$site =& $_POST['site'];
		if( empty($site) ){
			return false;
		}
		if( !isset($this->siteData['sites'][$site]) ){
			message($langmessage['OOPS'].' (Invalid Site)');
			return false;
		}

		if( !$this->RmSite($site) ){
			message($langmessage['OOPS'].'(Files not completely removed)');
			return false;
		}

		message($langmessage['SAVED']);

		unset($this->siteData['sites'][$site]);
		$this->SaveSiteData();
	}

	/**
	 * Remove the files and folders of an installation
	 *
	 */
	function RmSite($site){
		global $config;

		if( !$this->EmptyDir($site) ){
			return false;
		}

		return $this->RmDir($site);
	}

	/**
	 * Remove a folder that was created by the multi-site manager
	 *
	 */
	function RmDir($dir){
		global $config;

		if( @rmdir($dir) ){
			return true;
		}

		if( empty($config['ftp_server']) ){
			return false;
		}

		$conn_id = gpFiles::FTPConnect();
		if( !$conn_id ){
			return false;
		}

		$ftp_site = gpftp::GetFTPRoot($conn_id,$dir);
		if( !$ftp_site ){
			return false;
		}
		return ftp_rmdir($conn_id,$ftp_site);
	}

	/**
	 * Remove all the contents of a directory
	 *
	 */
	function EmptyDir($dir){

		if( !file_exists($dir) ){
			return true;
		}

		if( is_link($dir) ){
			return unlink($dir);
		}

		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}

		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}
		$success = true;

		$subDirs = array();
		while( ($file = readdir($dh)) !== false){
			if( $file == '.' || $file == '..' ){
				continue;
			}

			$fullPath = $dir.'/'.$file;

			if( is_link($fullPath) ){
				if( !unlink($fullPath) ){
					$success = false;
				}
				continue;
			}


			if( is_dir($fullPath) ){
				$subDirs[] = $fullPath;
				continue;
			}
			if( !unlink($fullPath) ){
				$success = false;
			}
		}
		closedir($dh);

		foreach($subDirs as $subDir){
			if( !$this->EmptyDir($subDir) ){
				$success = false;
			}
			if( !gpFiles::RmDir($subDir) ){
				$success = false;
			}

		}

		return $success;
	}




	function GetSiteData(){
		global $addonPathData;

		$this->dataFile = $addonPathData.'/data.php';
		if( file_exists($this->dataFile) ){
			require($this->dataFile);
			if( isset($siteData) ){
				$this->siteData = $siteData;
			}
			$this->checksum = $this->CheckSum($this->siteData);
		}


		$this->siteData += array('sites'=>array());
	}

	function SaveSiteData(){
		$check = $this->CheckSum($this->siteData);
		if( $check === $this->checksum ){
			return true;
		}

		unset($this->siteData['destination']); //no longer used
		unset($this->siteData['useftp']); //no longer used

		return gpFiles::SaveArray($this->dataFile,'siteData',$this->siteData);
	}

	function CheckSum($array){
		return crc32( serialize($array) );
	}


	function CreatePlugins($destination,$args = false){
		global $rootDir;

		if( $args === false ){
			$args = $_POST;
		}

		//may be valid even if plugins is not set
		$args += array('plugins'=>array());

		//selection of themes
		if( !gpFiles::CheckDir($destination.'/addons') ){
			message('Failed to create <em>'.$destination.'/addons'.'</em>');
			return false;
		}

		foreach($args['plugins'] as $plugin){
			$target = $rootDir.'/addons/'.$plugin;
			if( !file_exists($target) ){
				continue;
			}
			$name = $destination.'/addons/'.$plugin;
			$this->Create_Symlink($target,$name);
		}


		return true;
	}


	//Don't create symlink for /themes, users may want to add to their collection of themes
	function CopyThemes($destination,$args=false){
		global $rootDir;

		if( $args === false ){
			$args = $_POST;
		}

		//selection of themes
		if( !gpFiles::CheckDir($destination.'/themes') ){
			message('Failed to create <em>'.$destination.'/themes'.'</em>');
			return false;
		}

		$count = 0;
		foreach($args['themes'] as $theme){
			$target = $rootDir.'/themes/'.$theme;
			if( !file_exists($target) ){
				continue;
			}
			$name = $destination.'/themes/'.$theme;
			if( $this->Create_Symlink($target,$name) ){
				$count++;
			}
		}
		if( $count == 0 ){
			message('Failed to populate <em>'.$destination.'/themes'.'</em>');
			return false;
		}

		return true;
	}

	//create the index.php file
	function CreateIndex($destination,$unique){

		$path = $destination.'/index.php';


		$indexA = array();
		$indexA[] = '<'.'?'.'php';
		if( isset($this->siteData['service_provider_id']) ){
			$indexA[] = 'define(\'service_provider_id\',\''.(int)$this->siteData['service_provider_id'].'\');';
		}
		if( isset($this->siteData['service_provider_name']) ){
			$indexA[] = 'define(\'service_provider_name\',\''.addslashes($this->siteData['service_provider_name']).'\');';
		}
		$indexA[] = 'define(\'multi_site_unique\',\''.$unique.'\');';
		$indexA[] = 'require_once(\'include/main.php\');';
		$index = implode("\n",$indexA);
		if( !gpFiles::Save($path,$index) ){
			return false;
		}

		@chmod($path,0644); //to prevent 500 Internal Server Errors on some servers

		return true;
	}

	function NewId(){

		do{
			$unique = common::RandomString(20);
			foreach($this->siteData['sites'] as $array){
				if( isset($array['unique']) && ($array['unique'] == $unique) ){
					$unique = false;
					break;
				}
			}
		}while($unique==false);

		return $unique;
	}

	//create a symbolic link and test for $test_file
	function Create_Symlink($target,$path,$test_file = false ){

		echo '<li>Create Symlink: <em>'.$path.'</em></li>';
		if( !symlink($target,$path) ){
			message('Oops, Symlink creation failed (1)');
			return false;
		}

		if( $test_file && !file_exists($path.'/'.$test_file) ){
			message('Oops, Symlink creation failed (2)');
			return false;
		}

		return true;
	}



	/*
	 *
	 * File Handling Functions
	 *
	 */


	function SaveFTPInformation(){
		global $config, $langmessage;

		$_POST += array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'');

		//try to connect and login if ftp_server is not empty
		if( !empty($_POST['ftp_server']) ){

			$conn_id = @ftp_connect($_POST['ftp_server'],21,6);
			if( !$conn_id ){
				message('Oops, could not connect using ftp_connect() for server <i>'.htmlspecialchars($_POST['ftp_server']).'</i>');
				return false;
			}

			ob_start();
			$login_result = @ftp_login($conn_id,$_POST['ftp_user'],$_POST['ftp_pass'] );
			if( !$login_result ){
				message('Oops, could not login using ftp_login() for server <i>'.$_POST['ftp_server'].'</i> and user <i>'.$_POST['ftp_user'].'</i>');
				@ftp_close($conn_id);
				ob_end_clean();
				return false;
			}
			@ftp_close($conn_id);
			ob_end_clean();
		}


		$config['ftp_user'] = $_POST['ftp_user'];
		$config['ftp_server'] = $_POST['ftp_server'];
		$config['ftp_pass'] = $_POST['ftp_pass'];

		if( !admin_tools::SaveConfig() ){
			message('Oops, there was an error saving your ftp information.');
			return false;
		}

		return true;
	}




	/*
	 * New Installation Functions
	 *
	 *
	 *
	 */


	function InstallStatus($cmd){
		global $rootDir;

		//make sure default theme exists
		$path = $rootDir.'/themes/Light_Texture';
		if( !file_exists($path) ){
			message('The default theme for gpEasy "Light_Texture" does not exist. Please make sure it exists before continuing.');
			return;
		}



		if( empty($cmd) || $cmd == 'Continue' ){
			$cmd = false;
		}elseif( $cmd == 'Install Now' ){
			if( $this->NewCreate() ){
				return;
			}else{
				$cmd = false;
			}
		}

		$this->CheckValues();

		$ready = true;

		echo '<div id="install_wrap">';
		echo '<h3>Installation</h3>';

		echo '<table id="install_status" cellpadding="0" cellspacing="0">';


		$ready = $this->InstallStatus_Step($cmd,$ready,'Destination','new_destination','folder');
		$ready = $this->InstallStatus_Step($cmd,$ready,'Themes','new_themes','themes');
		$this->InstallStatus_Step($cmd,$ready,'Plugins','new_plugins','plugins','plugins_submitted');

		echo '<tr>';
			echo '<th>';
			echo 'Status';
			echo '</th>';
			if( $ready ){
				echo '<td id="install_state" class="ready">';
				$query_array = array('cmd'=>'new_install');
				echo $this->InstallLink('Ready To Install',$query_array);
				if( $cmd === false ){
					$cmd = 'new_install';
				}
			}else{
				echo '<td id="install_state" class="not_ready">';
				//$query_array = array('cmd'=>$cmd);
				//echo $this->InstallLink('Not Ready',$query_array);
				echo 'Not Ready to Install';

			}

			echo '</td>';
			echo '</tr>';


		echo '</table>';

		echo '<div id="install_step"><div id="install_step_inner">';
		switch($cmd){

			case 'subfolder':
			case 'new':
			case 'new_destination':
				$this->NewDestination();
			break;

			case 'new_themes':
				$this->NewThemes($_REQUEST['install']);
			break;

			case 'new_plugins':
				$this->NewPlugins($_REQUEST['install']);
			break;

			case 'new_install':
				$this->NewInstall();
			break;

		}
		echo '</div></div>';

		echo '</div>';
	}

	/*
	 * Check new values further before continuing with the installation process
	 *
	 */

	function CheckValues(){
		global $config, $langmessage;

		$langmessage['not_written_to'] = 'Sorry, the selected folder could not be written to. You may still be able to install in this folder by doing one of the following:';
		$langmessage['not_written_to'] .= '<ul>';
		$langmessage['not_written_to'] .= '<li>Make the folder writable by changing it\'s permissions.</li>';
		$langmessage['not_written_to'] .= '<li>Supply your server\'s <a href="%s">ftp information</a> to this plugin.</li>';
		$langmessage['not_written_to'] .= '</ul>';

		$langmessage['not_written_to'] = sprintf($langmessage['not_written_to'],common::GetUrl('Admin_Site_Setup','cmd=settings'));

		if( !isset($_REQUEST['new_val']) ){
			return;
		}

		$new_val_key = $_REQUEST['new_val'];

		if( $new_val_key == 'folder' ){
			$folder = $_REQUEST['install'][$new_val_key];

			if( is_writable($folder) ){
				return;
			}

			if( empty($config['ftp_server']) ){
				unset($_REQUEST['install'][$new_val_key]);
				message($langmessage['not_written_to']);
				return;
			}

			$conn_id = gpFiles::FTPConnect();
			$ftp_root = gpftp::GetFTPRoot($conn_id,$folder);

			if( !$ftp_root ){
				unset($_REQUEST['install'][$new_val_key]);
				message($langmessage['not_written_to']);
				return;
			}
		}

	}

	function InstallStatus_Step(&$cmd,$ready,$label,$step_cmd,$step_key,$step_key2=false){
		echo '<tr>';
			echo '<th>';
			echo $label;
			echo '</th>';
			echo '<td>';
			if( isset($_REQUEST['install'][$step_key]) ){
				$step_value = $_REQUEST['install'][$step_key];
				if( is_array($step_value) ){
					$link_label = implode(', ',$step_value);
					if( strlen($link_label) > 40 ){
						$link_label = substr($link_label,0,40).'...';
					}
				}else{
					$link_label = $step_value;
				}
			}elseif( $step_key2 && isset($_REQUEST['install'][$step_key2]) ){
				$link_label = 'Empty';
			}else{
				$ready = false;
				$query_array = array('cmd'=>'new_destination');
				$link_label = 'Not Set';

				if( !$cmd ){
					$cmd = $step_cmd;
				}
			}

			if( empty($link_label) ){
				$link_label = 'Empty';
			}
			$query_array = array('cmd'=>$step_cmd);
			echo $this->InstallLink($link_label,$query_array);
			echo '</td>';
			echo '</tr>';

		return $ready;
	}

	function NewInstall(){
		includeFile('tool/install.php');

		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<table style="width:100%">';
		Install_Tools::Form_UserDetails();
		echo '</table>';
		$this->InstallFields($_REQUEST['install'],'install');
		echo '<div id="install_continue">';
		echo '<input type="submit" name="cmd" value="Install Now" class="continue"/>';
		echo ' <input type="submit" name="" value="Cancel" />';
		echo '</div>';
		echo '</form>';
	}


	function NewDestination(){
		global $rootDir,$config;

		if( empty($this->siteData['last_folder']) ){
			$folder = $rootDir;
		}else{
			$folder = $this->siteData['last_folder'];
		}

		$this->InstallFolder($folder);
	}

	function NewThemes($values=array()){
		global $rootDir;

		if( !isset($values['themes']) ){
			$values += array('all_themes'=>'all');
		}
		$values += array('themes'=>array());


		//
		//	Themes
		//


		$all_themes = false;
		if( isset($values['all_themes']) && $values['all_themes'] == 'all' ){
			$all_themes = true;
		}

		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<table style="width:100%">';
		echo '<tr>';
			echo '<th>Select Themes</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td class="all_checkboxes">';
			echo '<div>';
			echo 'Select which themes will be available to the new installation. ';
			echo '</div>';
			echo '<br/>';

			echo '<table border="0" cellpadding="7">';
			echo '<tr>';
			echo '<td>';
			$checked = '';
			if( $all_themes ){
				$checked = ' checked="checked" ';
			}
			echo '<label class="select_all"><input type="checkbox" class="select_all" name="install[all_themes]" value="all" '.$checked.'/> All Themes</label> ';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';

			echo '<td style="border-top:1px solid #ccc;border-bottom:1px solid #ccc;vertical-align:middle;font-weight:bold;">';
			echo ' OR ';
			echo '</td>';

			echo '</tr>';
			echo '<tr>';

			echo '<td>';
			//light texture
			echo '<input type="hidden" name="install[themes][]" value="Light_Texture" />';
			echo '<label class="all_checkbox">';
			echo '<input type="checkbox" name="install[themes][]" value="Light_Texture" checked="checked" disabled="disabled" />';
			echo 'Light Texture';
			echo '</label>';
			echo ' And ... <br/>';

			echo '<p>';
			$dir = $rootDir.'/themes';
			$layouts = gpFiles::readDir($dir,1);
			asort($layouts);
			$i = 1;
			foreach($layouts as $name){
				if( $name == 'Light_Texture' ){
					continue;
				}

				$checked = '';
				if( $all_themes || (array_search($name,$values['themes']) > 0) ){
					$checked = ' checked="checked" ';
				}

				echo '<label class="all_checkbox">';
				echo '<input type="checkbox" name="install[themes]['.$i++.']" value="'.htmlspecialchars($name).'" '.$checked.'/>';
				echo str_replace('_',' ',$name);
				echo '</label>';
			}
			echo '</p>';
			echo '</td>';
			echo '</tr>';
			echo '</table>';


			echo '</td>';
			echo '</tr>';
		echo '</table>';

		$this->InstallFields($_REQUEST['install'],'install');
		echo '<div id="install_continue">';
		echo '<input type="submit" name="cmd" value="Continue" class="continue"/> ';
		echo ' <input type="submit" name="" value="Cancel" />';
		echo '</div>';
		echo '</form>';
	}

	function NewPlugins($values = array()){
		global $rootDir;

		$values += array('plugins'=>array());


		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<table style="width:100%">';
		echo '<tr>';
			echo '<th>Select Plugins</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td class="all_checkboxes">';
			echo '<div>';
			echo 'Select which plugins will be available to the new installation. Note, selected plugins will not be installed.';
			echo '</div>';
			echo '<br/>';

			$dir = $rootDir.'/addons';
			$addons = gpFiles::readDir($dir,1);
			$i = 1;
			foreach($addons as $addon){
				$checked = '';
				if( array_search($addon,$values['plugins']) > 0 ){
					$checked = ' checked="checked" ';
				}
				echo '<label class="all_checkbox">';
				echo '<input type="checkbox" name="install[plugins]['.$i++.']" value="'.htmlspecialchars($addon).'"'.$checked.'/>';
				echo str_replace('_',' ',$addon);
				echo '</label>';
			}

			echo '</td>';
			echo '</tr>';
		echo '</table>';

		$this->InstallFields($_REQUEST['install'],'install');
		echo '<div id="install_continue">';
		echo '<input type="submit" name="cmd" value="Continue" class="continue"/>';
		echo ' <input type="submit" name="" value="Cancel" />';
		echo '</div>';
		echo '<input type="hidden" name="install[plugins_submitted]" value="plugins_submitted" />';
		echo '</form>';
	}



	function InstallLink($label,$query_array=array(),$attr=''){
		return '<a href="'.$this->InstallUrl($query_array).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}

	function InstallUrl($query_array=array()){
		$query_array += array('install'=>array());
		$query_array['install'] = $query_array['install'] + $_REQUEST['install'];
		$query = http_build_query($query_array);

		return common::GetUrl('Admin_Site_Setup',$query);
	}

	function InstallFields($array,$key=''){
		foreach($array as $k => $v){

			if((!empty($key)) || ($key === 0)) $k = $key.'['.urlencode($k).']';

			if (is_array($v) || is_object($v)) {
				$this->InstallFields($v,$k);
			} else {
				echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'" />';
			}
		}
	}



	function InstallFolder($destination){

		echo '<table>';
		echo '<tr><th>';
		echo 'Select Destination Folder';
		echo '</th></tr></table>';


		$previous = $destination;
		$parent = $destination;
		$links = array();
		do{
			$previous = $parent;
			$query_array = array('cmd'=>'expandfolder','folder'=>$parent);
			$links[] = '<span>'.$this->InstallLink(basename($parent).'/',$query_array,' name="gpajax" ').'</span>';
			$parent = dirname($parent);
		}while( $previous != $parent );


		echo '<div id="parent_folders">';
		$links = array_reverse($links);
		echo implode('',$links);

		$query_array = array('cmd'=>'expandfolder','folder'=>$destination);
		echo '<form method="post" action="'.$this->InstallUrl($query_array).'">';
		echo '<input type="text" name="sub_dir" value="" />';
		echo '<input type="hidden" name="cmd" value="subfolder" />';
		echo '</form>';
		echo '</div>';

		//show subfolders
		echo '<div style="clear:both"></div>';

		echo '<div id="destination_select">';
		$this->InstallFolders($destination);
		echo '</div>';

		echo '<div id="install_continue">';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="get">';
		echo '<input type="submit" name="" value="Cancel" />';
		echo '</form>';
		echo '</div>';

	}


	function InstallFolders($dir){

		if( !is_readable($dir) ){
			echo '<p>';
			echo 'Sorry, <i>'.$dir.'</i> is not readable.';
			echo '</p>';
			return;
		}

		$subdirs = gpFiles::readDir($dir,1);

		echo '<ul>';
		$i = 0;
		$classes = array('even','odd');
		if( count($subdirs) > 0 ){
			natcasesort($subdirs);
			$temp = rtrim($dir,'/');
			foreach($subdirs as $subdir){
				echo '<li>';
				$this->FolderLink($subdir,$temp.'/'.$subdir,$classes[$i%2]);
				echo '</li>';
				$i++;
			}
		}


		echo '<li>';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post" class="'.$classes[$i%2].'">';
		echo '<input type="submit" name="" value="New Folder" class="gppost" /> ';
		echo '<input type="text" name="new_folder" value="" class="text"/> ';
		echo '<input type="hidden" name="folder" value="'.htmlspecialchars($dir).'" /> ';
		echo '<input type="hidden" name="cmd" value="newfolder" /> ';
		$this->InstallFields($_REQUEST['install'],'install');
		echo '</form>';
		echo '</li>';

		echo '</ul>';
	}

	function GetSubdirs($dir){
		global $config;

		if( is_readable($dir) ){
			return gpFiles::readDir($dir,1);
		}

		return false;
	}

	function FolderLink($base,$full,$class){
		echo '<span class="expand_child '.$class.'">';

		$this->InstallLinks($full);


		$query_array = array('cmd'=>'expandfolder','folder'=>$full);
		echo $this->InstallLink($base,$query_array,' name="gpajax" rel="'.htmlspecialchars($full).'" ');
		echo '</span>';
	}


	/*
	 * Check for /addons, /data, /include, /themes and /index.php
	 */
	function InstallLinks($dir){
		global $config;

		$check_short = array('addons','data','include','themes','index.php');
		$failed = array();

		//readable
		if( !is_readable($dir) ){
			return false;
		}

		//existing contents
		foreach($check_short as $short){
			$check_full = rtrim($dir,'/').'/'.$short;
			if( file_exists($check_full) ){
				$failed[] = $check_short;
			}
		}

		if( count($failed) > 0 ){
			return false;
		}

		$query_array = array('cmd'=>'Continue','new_val'=>'folder','install'=>array('folder'=>$dir));
		echo $this->InstallLink('Install Here',$query_array,' class="select" ');

		$query_array = array('cmd'=>'rmdir','dir'=>$dir);
		echo $this->InstallLink('Delete',$query_array,' class="rm" name="gpajax" ');
	}


	/**
	 * Show the contents of folder
	 *
	 */
	function ExpandFolder(){
		global $page, $langmessage,$config;

		$_REQUEST += array('install'=>array());
		$page->ajaxReplace = array();
		$page->ajaxReplace[] = 'messages';

		$folder =& $_REQUEST['folder'];
		if( empty($folder) || !file_exists($folder) || !is_dir($folder) ){
			message($langmessage['OOPS']);
			return;
		}

		$this->LoadFolder($folder);
	}

	/**
	 * Go to a user supplied sub directory in the browser
	 *
	 */
	function SubFolder(){

		$folder =& $_REQUEST['folder'];
		if( !empty($_REQUEST['sub_dir']) ){
			$folder .= '/'.$_REQUEST['sub_dir'];
		}
		if( empty($folder) || !file_exists($folder) || !is_dir($folder) ){
			message($langmessage['OOPS']);
			return;
		}

		$this->LoadFolder($folder);
	}


	function LoadFolder($folder){
		global $page;

		ob_start();
		echo $this->InstallFolder($folder);
		$content = ob_get_clean();

		$page->ajaxReplace[] = array('inner','#install_step_inner',$content);

		//save the folder location
		if( !isset($this->siteData['last_folder']) || $this->siteData['last_folder'] !== $folder ){
			$this->siteData['last_folder'] = $folder;
			unset($this->siteData['last_folder_ftp']);
			$this->SaveSiteData();
		}
	}


	/*
	 * Create a new folder
	 *
	 */
	function NewFolder(){
		global $page, $langmessage;

		$page->ajaxReplace = array();
		$page->ajaxReplace[] = 'messages';

		$folder =& $_POST['folder'];
		if( empty($folder) || !file_exists($folder) || !is_dir($folder) ){
			message($langmessage['OOPS']. ' (Parent Dir)');
			return false;
		}

		$new_name =& $_POST['new_folder'];
		if( empty($new_name) ){
			message($langmessage['OOPS']. ' (Empty Name)');
			return false;
		}

		$new_name = trim($new_name,'/\\');
		$folder = rtrim($folder,'/\\');

		$new_folder = $folder.'/'.$new_name;

		if( file_exists($new_folder) ){
			message($langmessage['OOPS']. ' (Already Exists)');
			return false;
		}

		if( !$this->MakeDir($folder,$new_name) ){
			return false;
		}

		$this->ExpandFolder();
	}

	function RemoveDirPrompt(){
		global $page, $langmessage;

		$page->ajaxReplace = array();
		$page->ajaxReplace[] = 'messages';


		$dir = $_REQUEST['dir'];
		if( !$this->RemoveDirCheck($dir) ){
			return;
		}

		ob_start();

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<input type="hidden" name="dir" value="'.htmlspecialchars($dir).'" />';
		echo '<input type="hidden" name="cmd" value="new_destination" />';
		$this->InstallFields($_REQUEST['install'],'install');

		echo sprintf($langmessage['generic_delete_confirm'],'<i>'.htmlspecialchars($dir).'</i>');

		echo '<p>';
		echo '<input type="submit" name="cmd" value="Delete Folder" class="gppost" />';
		echo ' <input type="submit" value="Cancel" class="admin_box_close" /> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';


		$content = ob_get_clean();

		$page->ajaxReplace[] = array('admin_box_data','',$content);
	}

	function RemoveDir(){
		global $page, $langmessage;

		$page->ajaxReplace = array();
		$page->ajaxReplace[] = 'messages';

		$dir = $_POST['dir'];
		if( !$this->RemoveDirCheck($dir) ){
			return;
		}

		$parent = dirname($dir);

		if( !$this->RmDir($dir) ){
			message($langmessage['OOPS']);
			return;
		}

		$this->LoadFolder($parent);
	}

	function RemoveDirCheck($dir){
		global $langmessage;

		if( empty($dir) || !file_exists($dir) || !is_dir($dir) ){
			message($langmessage['OOPS'].' (Invalid)');
			return false;
		}

		$dh = @opendir($dir);
		if( !$dh ){
			message($langmessage['OOPS'].' (Not Readable)');
			return false;
		}

		$count = 0;
		while( ($file = readdir($dh)) !== false){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			closedir($dh);
			message($langmessage['dir_not_empty']);
			return false;
		}

		closedir($dh);
		return true;
	}

	function MakeDir($parent,$new_name){
		global $config, $langmessage;


		$langmessage['not_created'] = 'Oops, the folder could not be created. ';
		$langmessage['not_created'] .= 'You may still be able to create it by doing one of the following: ';
		$langmessage['not_created'] .= '<ul>';
		$langmessage['not_created'] .= '<li>Make the parent folder writable by changing it\'s permissions.</li>';
		$langmessage['not_created'] .= '<li>Supply your server\'s <a href="%s">ftp information</a> to this plugin.</li>';
		$langmessage['not_created'] .= '</ul>';

		$langmessage['not_created'] = sprintf($langmessage['not_created'],common::GetUrl('Admin_Site_Setup','cmd=settings'));


		$new_folder = $parent.'/'.$new_name;

		if( mkdir($new_folder,0755) ){
			chmod($new_folder,0755); //some systems need more than just the 0755 in the mkdir() function
			return true;
		}


		if( empty($config['ftp_server']) ){
			message($langmessage['not_created']);
			return false;
		}

		$conn_id = gpFiles::FTPConnect();
		if( !$conn_id ){
			message($langmessage['not_created'].' (FTP Connection Failed)');
			return false;
		}


		$ftp_parent = gpftp::GetFTPRoot($conn_id,$parent);
		if( !$ftp_parent ){
			message('Oops, could not find the ftp location of <i>'.$parent.'</i> using the current ftp login.');
			return false;
		}

		$ftp_destination = $ftp_parent.'/'.$new_name;
		if( !ftp_mkdir($conn_id,$ftp_destination) ){
			message('Oops, could not create the folder using the current ftp login.');
			return false;
		}

		ftp_site($conn_id, 'CHMOD 0755 '. $ftp_destination );
		return true;
	}

	function NewCreate(){
		global $rootDir,$config,$checkFileIndex;
		global $dataDir; //for SaveTitle(), SaveConfig()

		includeFile('tool/install.php');

		$_POST += array('themes'=>array(),'plugins'=>array());
		$destination = $_REQUEST['install']['folder'];
		$this->site_uniq_id = $this->NewId();
		$checkFileIndex = false;


		//prevent reposting
		if( isset($this->siteData['sites'][$destination]) ){
			message('Oops, there\'s already an installation in '.htmlspecialchars($destination));
			return false;
		}

		echo '<ul>';
		echo '<li>Starting Installation</li>';


		//check user values first
		if( !Install_Tools::gpInstall_Check() ){
			$this->Install_Aborted($destination);
			return false;
		}


		//	Create index.php file
		echo '<li>Create index.php file</li>';
		if( !$this->CreateIndex($destination,$this->site_uniq_id) ){
			echo '<li>Failed to save the index.php file</li>';
			$this->Install_Aborted($destination);
			return false;
		}

		//	Create /include symlink
		$target = $rootDir.'/include';
		$name = $destination.'/include';
		if( !$this->Create_Symlink($target,$name,'main.php') ){
			$this->Install_Aborted($destination);
			return false;
		}


		//	Create /themes folder
		if( !$this->CopyThemes($destination,$_REQUEST['install']) ){
			$this->Install_Aborted($destination);
			return false;
		}

		//	Create /plugins folder
		if( !$this->CreatePlugins($destination,$_REQUEST['install']) ){
			$this->Install_Aborted($destination);
			return false;
		}


		//	variable juggling
		$oldDir = $dataDir;
		$dataDir = $destination;
		$old_unique = $config['gpuniq'];


		$new_config = array();
		$new_config['language'] = $config['language'];
		$config['gpuniq'] = $new_config['gpuniq'] = $this->NewId();

		if( !Install_Tools::Install_DataFiles_New( $destination, $new_config, false ) ){
			$this->Install_Aborted($destination);
			return false;
		}

		$dataDir = $oldDir;
		$config['gpuniq'] = $old_unique;


		$this->siteData['sites'][$destination] = array();
		$this->siteData['sites'][$destination]['unique'] = $this->site_uniq_id;
		$this->siteData['sites'][$destination]['gpuniq'] = $new_config['gpuniq'];

		$this->SaveSiteData();
		$this->Install_Success();
		return true;
	}

	function Install_Aborted($destination){

		echo '<li><b>Installation Aborted</b></li>';
		echo '</ul>';
		if( $destination ){
			$this->EmptyDir($destination);
		}
	}

	function Install_Success(){
		echo '</ul>';
		echo '<p></p>';
		echo '<b>Installation was completed successfully.</b> ';

		//show the options
		$_REQUEST['site'] = $_REQUEST['install']['folder'];
		$this->Options();

	}

}


if( !function_exists('http_build_query') ){
	function http_build_query($data, $prefix='', $sep='', $key='') {
		$ret = array();
		foreach((array)$data as $k => $v) {

			if (is_int($k) && $prefix != null) {
				$k = urlencode($prefix . $k);
			}
			if((!empty($key)) || ($key === 0)) $k = $key.'['.urlencode($k).']';

			if (is_array($v) || is_object($v)) {
				array_push($ret, http_build_query($v, '', $sep, $k));
			} else {
				array_push($ret, $k.'='.urlencode($v));
			}
		}
		if (empty($sep)) $sep = ini_get('arg_separator.output');
		return implode($sep, $ret);
	}
}
