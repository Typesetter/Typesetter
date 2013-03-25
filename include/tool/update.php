<?php
defined('is_running') or die('Not an entry point...');


/*
See also
wordpress/wp-admin/update.php
wordpress/wp-admin/includes/class-wp-upgrader.php
wordpress/wp-admin/includes/class-wp-filesystem-ftpext.php
*/


class update_class{

	//page variables
	var $label = 'gpEasy Updater';
	var $head = '';
	var $admin_css = '';
	var $contentBuffer = '';
	var $head_script = '';
	var $gpLayout;
	var $title = '';
	var $admin_js = false;
	var $meta_keywords = array();
	var $head_js = array();



	//for unpacking and replacing
	var $replace_dirs = array();
	var $extra_dirs = array();


	//update vars
	var $update_data = array();
	var $data_timestamp = 0;
	var $steps = array();


	//content for template
	var $output_phpcheck = '';

	//force inline js and css in case for updates incase the files are deleted/changed during update processs
	var $head_force_inline = true;

	/* methods for $page usage */
	function GetContent(){
		global $langmessage;

		echo '<div id="gpx_content">';
		echo GetMessages();
		echo $this->contentBuffer;
		echo '</div>';

	}

	/* constructor */
	function update_class($process='page'){

		includeFile('tool/RemoteGet.php');
		includeFile('tool/FileSystem.php');
		$this->GetData();


		//called from admin_tools.php
		if( $process == 'embededcheck' ){
			$this->DoRemoteCheck();
			header('content-type: image/gif');
			echo base64_decode('R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='); // send 1x1 transparent gif
			die();
		}


		ob_start();
		$this->Run();
		$this->contentBuffer = ob_get_clean();

	}

	/*
	 * @static
	 *
	 */
	static function VersionsAndCheckTime(&$new_versions){
		global $config, $dataDir;

		includeFile('tool/RemoteGet.php');

		$new_versions = array();

		if( !gpRemoteGet::Test() ){
			return update_class::CheckIncompatible();
		}

		update_class::GetDataStatic($update_data,$data_timestamp);

		//check core version
		// only report new versions if it's a root install
		if( !defined('multi_site_unique') && isset($update_data['packages']['core']) ){
			$core_version = $update_data['packages']['core']['version'];

			if( $core_version && version_compare(gpversion,$core_version,'<') ){
				$new_versions['core'] = $core_version;
			}
		}


		//check addon versions
		if( isset($config['addons']) && is_array($config['addons']) ){
			update_class::CheckArray($new_versions,$config['addons'],$update_data);
		}

		//check theme versions
		if( isset($config['themes']) && is_array($config['themes']) ){
			update_class::CheckArray($new_versions,$config['themes'],$update_data);
		}

/*
		echo '<blockquote style="z-index:100;position:fixed;">';
		echo showArray($config['themes']);
		echo showArray($new_versions);
		echo '</blockquote>';
*/


		$diff = time() - $data_timestamp;

		//get new information
		//604800 one week
		if( $diff > 604800 ){
			return 'embedcheck';
		}

		return 'checklater';
	}

	static function CheckArray(&$new_versions,$array,$update_data){

		foreach($array as $addon => $addon_info){
			if( !isset($addon_info['id']) ){
				continue;
			}

			if( !isset($addon_info['version']) ){
				$installed_version = 0;
			}else{
				$installed_version = $addon_info['version'];
			}

			$addon_id = $addon_info['id'];

			if( !isset($update_data['packages'][$addon_id]) ){
				continue;
			}

			$new_addon_info = $update_data['packages'][$addon_id];
			$new_addon_version = $new_addon_info['version'];
			if( version_compare($installed_version,$new_addon_version,'>=') ){
				continue;
			}

			//new version found
			$new_versions[$addon_id] = $new_addon_info;
			$new_versions[$addon_id]['name'] = $addon_info['name'];
		}

	}



	static function CheckIncompatible(){

		update_class::GetDataStatic($update_data,$data_timestamp);
		$diff = time() - $data_timestamp;
		if( $diff < 604800 ){
			return 'checklater';
		}

		if( empty($data_timestamp) || ($data_timestamp < 1) ){
			return 'checklater';
		}

		update_class::SaveDataStatic($update_data);
		return 'checkincompat';
	}



	function Run(){

		if( !$this->CheckPHP() ){
			echo $this->output_phpcheck;
			return;
		}

		$cmd = common::GetCommand();

		$show = true;
		switch($cmd){

			case 'checkremote':
				$this->DoRemoteCheck(true);
			break;


			case 'update';
				if( $this->Update() ){
					$show = false;
				}
			break;
		}

		if( $show ){
			$this->ShowStatus();

			echo '<h2>Status</h2>';
			$this->CheckStatus();
			echo $this->output_phpcheck;
		}

	}


	//
	//	$update_data['packages'][id] = array()
	//
	//		--type--
	//		array['id'] = id of addon (unique across all types), "core" if type is "core"
	//		array['type'] = [core,plugin,theme]
	//		array['md5'] = expected md5 sum of zip file
	//		array['zip'] = name of file on remote server
	//		array['file'] = file on local system
	//		array['version'] = version of the package
	//
	//
	function GetData(){

		update_class::GetDataStatic($update_data,$timestamp);

		$this->update_data = $update_data;
		$this->data_timestamp = $timestamp;
	}

	static function GetDataStatic(&$update_data,&$data_timestamp){
		global $dataDir;

		$data_timestamp = 0;
		$update_data = array();
		$file = $dataDir.'/data/_updates/updates.php';
		if( file_exists($file) ){
			require($file);
			$data_timestamp = $fileModTime;
		}

		$update_data += array('packages'=>array());
	}

	static function SaveDataStatic(&$update_data){
		global $dataDir;
		$file = $dataDir.'/data/_updates/updates.php';
		gpFiles::SaveArray($file,'update_data',$update_data);
	}

	function SaveData(){
		update_class::SaveDataStatic($this->update_data);
	}

	function CheckPHP(){
		global $dataDir, $langmessage;

		ob_start();

		$passed = true;
		echo '<table class="styledtable">';

		echo '<tr><th>';
			echo $langmessage['Test'];
			echo '</th><th>';
			echo $langmessage['Value'];
			echo '</th><th>';
			echo $langmessage['Expected'];
			echo '</th></tr>';

		echo '<tr><td>';
			echo 'RemoteGet';
			echo '</td><td>';
			if( gpRemoteGet::Test() ){
				echo '<span class="passed">'.$langmessage['True'].'</span>';
			}else{
				$passed = false;
				echo '<span class="failed">'.$langmessage['False'].'</span>';
			}

			echo '</td><td>';
			echo $langmessage['True'];
			echo '</td></tr>';

		echo '<tr><td>';
			echo 'Root Installation';
			echo '</td><td>';
			if( !defined('multi_site_unique') ){
				echo '<span class="passed">'.$langmessage['True'].'</span>';
			}else{
				echo '<span class="failed">'.$langmessage['False'].'</span>';
				if( gpdebug ){
					message('This feature is not normally available in a multi-site installation.
							It is currently accessible because gpdebug is set to true.
							Continuing is not recommended.');
				}else{
					$passed = false;
				}
			}

			echo '</td><td>';
			echo $langmessage['True'];
			echo '</td></tr>';

		echo '</table>';

		if( !$passed ){
			echo '<div class="inline_message">';
			echo $langmessage['Server_isnt_supported'];
			echo '</div>';
		}
		$this->output_phpcheck = ob_get_clean();

		return $passed;
	}

	function CheckStatus(){
		global $langmessage;

		$diff = time() - $this->data_timestamp;

		if( $this->data_timestamp > 0 ){
			echo '<p>';
			echo sprintf($langmessage['Software_updates_checked'],common::date($langmessage['strftime_datetime'],$this->data_timestamp));
			echo '</p>';
		}

		//one hour old
		if( $diff > 3600 ){
			echo '<p>';
			echo '<a href="?cmd=checkremote">'.$langmessage['Check Now'].'</a>';
			echo '</p>';
		}

	}

	function DoRemoteCheck($ForceCheck = false){
		global $langmessage;

		$diff = time() - $this->data_timestamp;

		//604800 one week
		if( !$ForceCheck && ($diff < 604800) ){
			return;
		}

		if( !$this->DoRemoteCheck2() ){
			message($langmessage['check_remote_failed']);
		}else{
			message($langmessage['check_remote_success']);
			$this->data_timestamp = time();
		}

		$this->SaveData();
	}

	function DoRemoteCheck2(){
		global $config;

		$path = common::IdUrl();
		$result = gpRemoteGet::Get_Successful($path);

		if( !$result ){
			return false;
		}

		parse_str($result,$array);
		if( !is_array($array) || (count($array) < 1) ){
			return false;
		}

		$core_version = false;
		foreach($array as $info){
			$id =& $info['id'];
			if( !is_numeric($id) ){
				continue;
			}
			if( !isset($info['type']) ){
				continue;
			}
			if( $info['type'] == 'core' ){
				$id = 'core';
				$core_version = $info['version'];
			}
			$this->update_data['packages'][$id] = $info;
		}



		//save some info in $config
/*
		if( $core_version && version_compare(gpversion,$core_version,'<') ){
			$config['updates']['core'] = time();
		}
		$config['updates']['checked'] = time();
*/

		//admin_tools::SaveConfig();


		return true;
	}



	function ShowStatus(){
		global $langmessage;

		if( !isset($this->update_data['packages']['core']) ){
			return;
		}
		$core_package = $this->update_data['packages']['core'];

		echo '<div class="inline_message">';
		if( version_compare(gpversion,$core_package['version'],'<') ){
			echo '<span class="green">';
			echo $langmessage['New_version_available'];
			echo ' &nbsp; ';
			echo '</span>';
			echo '<a href="?cmd=update"> &#187; '.$langmessage['Update_Now'].' &#171; </a>';

			echo '<table>';
			echo '<tr>';
			echo '<td>'.$langmessage['Your_version'].'</td>';
			echo '<td>'.gpversion.'</td>';
			echo '</tr>';

			echo '<tr>';
			echo '<td>'.$langmessage['New_version'].'</td>';
			echo '<td>'.$core_package['version'].'</td>';
			echo '</tr>';
			echo '</table>';

		}else{
			echo $langmessage['UP_TO_DATE'];
			echo '<div>'.$langmessage['Your_version'];
			echo '  '.gpversion;
			echo '</div>';
			echo '<div>';
			echo common::link('',$langmessage['return_to_your_site']);
			echo '</div>';

			//preliminary versions of the update software didn't run cleanup properly
			$this->RemoveUpdateMessage();
		}
		echo '</div>';

	}


	function Update(){
		global $langmessage, $gp_filesystem;


		if( !isset($this->update_data['packages']['core']) ){
			echo $langmessage['OOPS'];
			return false;
		}

		$core_package =& $this->update_data['packages']['core'];

		if( !isset($_POST['step']) ){
			$curr_step = 1;
		}else{
			$curr_step = (int)$_POST['step'];
		}

		//already up to date?
		if( ($curr_step < 4) && version_compare(gpversion,$core_package['version'],'>=') ){
			message($langmessage['UP_TO_DATE']);
			return false;
		}

		//filesystem
		$filesystem_method = false;
		if( isset($_POST['filesystem_method']) && gp_filesystem_base::set_method($_POST['filesystem_method']) ){
			$filesystem_method = $_POST['filesystem_method'];
		}else{
			$curr_step = 1;
			$this->DetectFileSystem();
			if( !$gp_filesystem ){
				message('Update Aborted: Could not establish a file writing method compatible with your server.');
				return false;
			}
			$filesystem_method = $gp_filesystem->method;
		}



		$this->steps[1] = $langmessage['step:prepare'];
		$this->steps[2] = $langmessage['step:download'];
		$this->steps[3] = $langmessage['step:unpack'];
		$this->steps[4] = $langmessage['step:clean'];

		echo '<div>'.$langmessage['update_steps'].'</div>';
		echo '<ol class="steps">';
		$curr_step_label = '';
		foreach($this->steps as $temp_step => $message ){

			if( $curr_step == $temp_step ){
				echo '<li class="current">'.$message.'</li>';
				$curr_step_label = $message;
			}elseif( $temp_step < $curr_step ){
				echo '<li class="done">'.$message.'</li>';
			}else{
				echo '<li>'.$message.'</li>';
			}
		}
		echo '</ol>';

		echo '<h3>'.$curr_step_label.'</h3>';


		echo '<form method="post" action="?cmd=update">';
		if( $filesystem_method ){
			echo '<input type="hidden" name="filesystem_method" value="'.htmlspecialchars($filesystem_method).'" />';
		}

		$done = false;
		$passed = false;
		switch($curr_step){
			case 4:
				$done = $this->CleanUp($core_package);
			break;
			case 3:
				echo '<ul>';
				$passed = $this->UnpackAndReplace($core_package);
				$this->OldFolders();
				echo '</ul>';
			break;
			case 2:
				echo '<ul class="progress">';
				$passed = $this->DownloadSource($core_package);
				echo '</ul>';
			break;
			case 1:
				$passed = $this->GetServerInfo($core_package);
			break;

		}

		if( $gp_filesystem ){
			$gp_filesystem->destruct();
		}
		$this->SaveData(); //save any changes made by the steps

		if( !$done ){
			if( $passed ){
				echo '<input type="hidden" name="step" value="'.min(count($this->steps),$curr_step+1).'"/>';
				echo '<input type="submit" class="submit" name="" value="'.htmlspecialchars($langmessage['next_step']).'" />';
			}elseif( $curr_step < 3 ){
				echo '<input type="hidden" name="step" value="'.min(count($this->steps),$curr_step).'"/>';
				echo '<input type="submit" class="submit" name="" value="'.htmlspecialchars($langmessage['continue']).'" />';
			}else{
				echo '<input type="hidden" name="failed_install" value="failed_install"/>';
				echo '<input type="hidden" name="step" value="4"/>';
				echo '<input type="submit" class="submit" name="" value="'.htmlspecialchars($langmessage['step:clean']).'..." />';
			}
		}

		echo '</form>';


		//echo showArray($this->update_data);
		//echo showArray($core_package);

		return true;
	}

	function DetectFileSystem(){
		global $dataDir;

		//Need to be able to write to the dataDir
		$context[$dataDir] = 'file';

		//Need to be able to rename or delete the include directory
		$context[$dataDir . '/include'] = 'file';

		//these may have user content in them and should not be completely replaced
		$context[$dataDir . '/themes'] = 'dir';
		$context[$dataDir . '/addons'] = 'dir';
		gp_filesystem_base::init($context,'list');
	}


	function CleanUp(&$package){
		global $langmessage, $config, $gp_filesystem, $dataDir;

		echo '<ul>';

		if( $gp_filesystem->connect() !== true ){
			echo '<li>'.$langmessage['OOPS'].': (not connected)</li>';
			return false;
		}

		//delete old folders
		if( isset($_POST['old_folder']) && is_array($_POST['old_folder']) ){
			$filesystem_base = $gp_filesystem->get_base_dir();
			$not_deleted = array();
			foreach($_POST['old_folder'] as $old_folder){

				if( ( strpos($old_folder,'../') !== false )
					|| ( strpos($old_folder,'./') !== false )
					){
					continue;
				}

				$old_folder = '/'.ltrim($old_folder,'/');
				$old_folder_full = $filesystem_base.$old_folder;

				if( !$gp_filesystem->file_exists($old_folder_full) ){
					continue;
				}

				if( !$gp_filesystem->rmdir_all($old_folder_full) ){
					$not_deleted[] = htmlspecialchars($old_folder);
				}
			}

			if( count($not_deleted) > 0 ){
				echo '<li>';
				echo $langmessage['delete_incomplete'].': '.implode(', ',$not_deleted);
				echo '</li>';
			}
		}


		if( isset($_POST['failed_install']) ){
			echo '<li>'.$langmessage['settings_restored'].'</li>';
			echo '</ul>';

			echo '<h3>';
			echo common::link('',$langmessage['return_to_your_site']);
			echo ' &nbsp; &nbsp; ';
			echo '<a href="?cmd=update">'.$langmessage['try_again'].'</a>';

			echo '</h3>';


		}else{

			//delete zip file
			if( !empty($package['file']) && file_exists($package['file']) ){
				unlink($package['file']);
			}

			echo '<li>'.$langmessage['settings_restored'].'</li>';
			echo '<li>'.$langmessage['software_updated'].'</li>';

			//get new package information .. has to be after deleting the zip
			echo '</ul>';


			echo '<h3>';
			echo common::link('','&#187; '.$langmessage['return_to_your_site']);
			echo '</h3>';

		}

		return true;
	}

	//remove updating message
	function RemoveUpdateMessage(){
		global $config,$langmessage;

		if( !isset($config['updating_message']) ){
			return true;
		}

		unset($config['updating_message']);
		if( !admin_tools::SaveConfig() ){
			message($langmessage['error_updating_settings']);
			return false;
		}

		return true;
	}


	/**
	 * Replace the /include, /themes and /addons folders
	 * Start by creating the new folders with the new content
	 * Then replace the existing directories with the new directories
	 *
	 */
	function UnpackAndReplace(&$package){
		global $langmessage, $config, $gp_filesystem, $dataDir;

		if( $gp_filesystem->connect() !== true ){
			echo '<li>'.$langmessage['OOPS'].': (not connected)</li>';
			return false;
		}

		if( !$this->UnpackAndSort($package['file']) ){
			return false;
		}

		echo '<li>Files Sorted</li>';

		echo '<li>'.$langmessage['copied_new_files'].'</li>';

		$config['updating_message'] = $langmessage['sorry_currently_updating'];
		if( !admin_tools::SaveConfig() ){
			echo '<li>'.$langmessage['error_updating_settings'].'</li>';
			return false;
		}

		$replaced = $gp_filesystem->ReplaceDirs( $this->replace_dirs, $this->extra_dirs );

		if( $replaced !== true ){
			echo '<li>'.$langmessage['error_unpacking'].' '.$replaced.'</li>';
			$this->RemoveUpdateMessage();
			return false;
		}
		$this->RemoveUpdateMessage();

		return true;
	}

	function OldFolders(){
		global $langmessage, $dataDir, $gp_filesystem;

		$dirs = array_merge( array_values($this->replace_dirs), array_values($this->extra_dirs));
		$dirs = array_unique( $dirs );
		if( count($dirs) == 0 ){
			return;
		}

		$filesystem_base = $gp_filesystem->get_base_dir();

		echo '<li>';
		echo $langmessage['old_folders_created'];

		echo '<ul>';
		foreach($dirs as $folder){

			$folder = '/'.ltrim($folder,'/');
			$folder_full = $filesystem_base.$folder;


			if( !$gp_filesystem->file_exists($folder_full) ){
				continue;
			}

			echo '<div><label>';
			echo '<input type="checkbox" name="old_folder[]" value="'.htmlspecialchars($folder).'" checked="checked" />';
			echo htmlspecialchars($folder);
			echo '</label></div>';
		}
		echo '</ul>';

		echo '</li>';
	}




	/**
	 * Unpack the archive and save the files in temporary folders
	 * @return bool
	 */
	function UnpackAndSort($file){
		global $langmessage, $gp_filesystem;


		//create archive object of $file
		includeFile('thirdparty/pclzip-2-8-2/pclzip.lib.php');
		$archive = new PclZip($file);
		$archive_root = $this->ArchiveRoot( $archive );
		if( !$archive_root ){
			echo '<li>'.$langmessage['error_unpacking'].' (no root)</li>';
			return false;
		}
		$archive_root_len = strlen($archive_root);



		//requires a lot of memory, most likely not this much
		@ini_set('memory_limit', '256M');
		$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		if( $archive_files == false ){
			echo '<li>'.$langmessage['OOPS'].': '.$archive->errorInfo(true).'</li>';
			return false;
		}

		echo '<li>'.$langmessage['package_unpacked'].'</li>';

		//organize
		foreach($archive_files as $file){

			$filename =& $file['filename'];

			if( strpos($filename,$archive_root) === false ){
				continue;
			}

			$rel_filename = substr($filename,$archive_root_len);

			$name_parts = explode('/',trim($rel_filename,'/'));
			$dir = array_shift($name_parts);
			$replace_dir = false;
			switch($dir){

				case 'include':
					$replace_dir = 'include';
					$rel_filename = implode('/',$name_parts);
				break;

				case 'themes':
				case 'addons':
					if( count($name_parts) == 0 ){
						continue 2;
					}
					$replace_dir = $dir.'/'.array_shift($name_parts);
					$rel_filename = implode('/',$name_parts);
				break;
			}

			if( !$replace_dir ){
				continue;
			}

			$replace_dir = trim($replace_dir,'/');
			if( isset( $this->replace_dirs[$replace_dir] ) ){
				$new_relative = $this->replace_dirs[$replace_dir];

			}else{

				$new_relative = $gp_filesystem->TempFile( $replace_dir );
				$this->replace_dirs[$replace_dir] = $new_relative;
			}

			$file_rel = $new_relative.'/'.$rel_filename;
			if( !$this->PutFile( $file_rel, $file ) ){
				return false;
			}
		}

		return true;
	}


	function PutFile( $dest_rel, $file ){
		global $langmessage, $gp_filesystem;

		$full = $gp_filesystem->get_base_dir().'/'.trim($dest_rel,'/');

		if( $file['folder'] ){
			if( !$gp_filesystem->mkdir($full) ){
				echo '<li>'.$langmessage['error_unpacking'].' (1)</li>';
				trigger_error('Could not create directory: '.$full);
				return false;
			}
			return true;
		}

		if( !$gp_filesystem->put_contents($full,$file['content']) ){
			trigger_error('Could not create file: '.$full);
			echo '<li>'.$langmessage['error_unpacking'].' (2)</li>';
			return true;
		}

		return true;
	}



	/**
	 * Find $archive_root by finding Addon.ini
	 *
	 */
	function ArchiveRoot( $archive ){

		$archive_files = $archive->listContent();
		$archive_root = false;

		//find $archive_root by finding Addon.ini
		foreach( $archive_files as $file ){

			$filename =& $file['filename'];
			if( strpos($filename,'/Addon.ini') === false ){
				continue;
			}

			$root = common::DirName($filename);

			if( !$archive_root || ( strlen($root) < strlen($archive_root) ) ){
				$archive_root = $root;
			}

		}
		return $archive_root;
	}


	function DownloadSource(&$package){
		global $langmessage;

		/* for testing
		 * $download = 'http://test.gpeasy.com/gpEasy_test.zip';
		 * $download = 'http://gpeasy.loc/rocky/x_gpEasy_test.zip';
		 */
		$download = addon_browse_path.'/Special_gpEasy?cmd=download&version='.urlencode($package['version']).'&file='.urlencode($package['zip']);

		echo '<li>Downloading version '.$package['version'].' from gpEasy.com.</li>';


		$contents = gpRemoteGet::Get_Successful($download);
		if( !$contents || empty($contents) ){
			echo '<li>'.$langmessage['download_failed'].' (1)</li>';
			return false;
		}
		echo '<li>'.$langmessage['package_downloaded'].'</li>';

		$md5 = md5($contents);
		if( $md5 != $package['md5'] ){
			echo '<li>'.$langmessage['download_failed_md5'];
			echo '<br/>Downloaded Checksum ('.$md5.') != Expected Checksum ('.$package['md5'].')';
			echo '</li>';
			return false;
		}


		echo '<li>'.$langmessage['download_verified'].'</li>';


		//save contents
		$tempfile = $this->tempfile();
		if( !gpFiles::Save($tempfile,$contents) ){
			message($langmessage['download_failed'].' (2)');
			return false;
		}

		$package['file'] = $tempfile;
		return true;
	}



	function GetServerInfo(&$package){
		global $langmessage,$gp_filesystem;

		$connect_result = $gp_filesystem->connect();
		if( $connect_result === true ){
			echo '<ul class="progress">';
			echo '<li>';
			echo $langmessage['your_installation_is_ready_for_upgrade'];
			echo '</li>';
			echo '</ul>';
			return true;

		}elseif( isset($_POST['connect_values_submitted']) ){
			message($connect_result);
		}

		//not connected, show form
		echo '<table class="formtable">';
		echo '<tr><td>';

		$gp_filesystem->connectForm();

		echo '</table>';
		return false;

	}


	function tempfile(){
		global $dataDir;

		do{
			$tempfile = $dataDir.'/data/_temp/'.rand(1000,9000).'.zip';
		}while(file_exists($tempfile));

		return $tempfile;
	}


}

