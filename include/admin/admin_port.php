<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/FileSystem.php');

/**
 * There are a number of challenges with granular importing that may force us to just have a 'revert' feature that completely replaces the data folder?
 * 		- Page information ( 'type' in $gp_titles, 'file_number' in each page file, 'keywords/description/browser_title' in $gp_titles)
 * 			- galleries would not be incorporated correctly
 *		- Configuration settings
 * 		- Pages using content_types defined by an addon
 * 		- Layouts using gadgets defined by an addon
 * 		- Pages using a layout
 *
 */


class admin_port{

	var $export_fields = array();
	var $export_arch;
	var $export_dir;
	var $exported = array();
	var $export_ini_file;
	var $temp_dir;
	var $avail_compress = array();
	var $all_extenstions = array('tgz','tar','gz','bz');

	var $archive_path;
	var $archive_name;

	var $min_import_bits = 0;


	//importing/reverting
	var $import_object;
	var $import_list;
	var $import_info;

	var $replace_dirs = array();
	var $extra_dirs = array();

	var $iframe = '';


	function admin_port(){
		global $langmessage,$dataDir,$page;

		$this->export_dir = $dataDir.'/data/_exports';
		$this->temp_dir = $dataDir.'/data/_temp';
		$this->export_ini_file = $dataDir.'/data/_temp/Export.ini';
		@set_time_limit(90);
		@ini_set('memory_limit','64M');

		$this->Init();
		$this->SetExported();

		$cmd = common::GetCommand();
		switch($cmd){

			case 'do_export':
				$this->DoExport();
				$this->SetExported();
			break;


			case 'delete':
				$this->DeleteConfirmed();
				$this->SetExported();
			break;


			case 'revert_confirmed':
			case 'revert':
				if( $this->Revert($cmd) ){
					return;
				}
			break;
			case 'revert_clean':
				$this->RevertClean();
			break;

		}


		//echo '<h2>'.$langmessage['Import/Export'].'</h2>';
		echo '<h2>'.$langmessage['Export'].'</h2>';
		echo $langmessage['Export About'];
		echo $this->iframe;

		$this->ExportForm();

		echo '<br/>';

		$this->Exported();

	}




	/*
	 * Export Functions
	 *
	 *
	 *
	 */
	function DoExport(){
		global $dataDir, $langmessage, $config;

		//get list of directories to include
		$add_dirs = array();
		if( isset($_POST['pca']) ){
			$add_dirs[] = $dataDir.'/data/_pages';
			$add_dirs[] = $dataDir.'/data/_extra';
			$add_dirs[] = $dataDir.'/data/_site';
			$add_dirs[] = $dataDir.'/data/_menus';
			$add_dirs[] = $dataDir.'/data/_addoncode';
			$add_dirs[] = $dataDir.'/data/_addondata';
			$add_dirs[] = $dataDir.'/addons';
		}


		if( isset($_POST['media']) ){
			$add_dirs[] = $dataDir.'/data/_uploaded';
			$add_dirs[] = $dataDir.'/data/_resized';
		}
		if( isset($_POST['themes']) ){
			$add_dirs[] = $dataDir.'/data/_themes';
			$add_dirs[] = $dataDir.'/themes';
		}
		if( isset($_POST['trash']) ){
			$add_dirs[] = $dataDir.'/data/_trash';
		}


		//create binary string to identify which areas were exported
		$which_exported = 0;
		foreach($this->export_fields as $export_field){
			$name = $export_field['name'];
			if( isset($_POST[$name]) ){
				$which_exported += $export_field['index'];
			}
		}
		if( $which_exported === 0 ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !$this->Export_Ini($which_exported) ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}


		//check to see if dirs in add_dirs even exist
		foreach($add_dirs as $i => $dir){
			if( !file_exists($dir) ){
				unset($add_dirs[$i]);
			}
		}


		$success = $this->Create_Tar($which_exported,$add_dirs);

		//clean up
		unlink($this->export_ini_file);


		//iframe for download
		if( $success ){
			$this->iframe = '<iframe src="'.common::GetDir('/data/_exports/'.$this->archive_name).'" height="0" width="0" style="visibility:hidden;height:0;width:0;"></iframe>';
		}
	}



	function Create_Tar($which_exported,$add_dirs){
		global $dataDir, $langmessage;


		if( !$this->NewFile($which_exported,'tar') ){
			return;
		}

		$this->Init_Tar();
		//$tar_object = new Archive_Tar($this->archive_path,'gz'); //didn't always work when compressin
		$tar_object = new Archive_Tar($this->archive_path);
		//if( gpdebug ){
		//	$tar_object->setErrorHandling(PEAR_ERROR_PRINT);
		//}

		if( !$tar_object->createModify($add_dirs, 'gpexport', $dataDir) ){
			message($langmessage['OOPS'].'(5)');
			unlink($this->archive_path);
			return false;
		}

		//add in array so addModify doesn't split the string into an array
		$temp = array();
		$temp[] = $this->export_ini_file;
		if( !$tar_object->addModify($temp, 'gpexport', $this->temp_dir) ){
			message($langmessage['OOPS'].'(6)');
			unlink($this->archive_path);
			return false;
		}


		//compression
		$compression =& $_POST['compression'];
		if( !isset($this->avail_compress[$compression]) ){
			return true;
		}

		$new_path = $this->archive_path.'.'.$compression;
		$new_name = $this->archive_name.'.'.$compression;

		switch( $compression ){
			case 'gz':
				//gz compress the tar
				$gz_handle = @gzopen($new_path, 'wb9');
				if( !$gz_handle ){
					return true;
				}
				if( !@gzwrite( $gz_handle, file_get_contents($this->archive_path)) ){
					return true;
				}
				@gzclose($gz_handle);
			break;
			case 'bz':
				//gz compress the tar
				$bz_handle = @bzopen($new_path, 'w');
				if( !$bz_handle ){
					return true;
				}
				if( !@bzwrite( $bz_handle, file_get_contents($this->archive_path)) ){
					return true;
				}
				@bzclose($bz_handle);
			break;
		}

		unlink($this->archive_path);

		$this->archive_path = $new_path;
		$this->archive_name = $new_name;

		return true;
	}

	//create the file that will be use for the archive
	function NewFile($which_exported,$extension){

		if( !gpFiles::CheckDir($this->export_dir) ){
			message($langmessage['OOPS'].'(Export Dir)');
			return false;
		}

		$init_contents = '';

		$filename = time().'.'.$which_exported.'.'.rand(1000,9000).'.'.$extension;
		$full_path = $this->export_dir.'/'.$filename;

		if( !gpFiles::Save($full_path,$init_contents) ){
			message($langmessage['OOPS'].'(4)');
			return false;
		}

		$this->archive_path = $full_path;
		$this->archive_name = $filename;
		return true;
	}

	function Export_Ini($which_exported){
		global $dirPrefix, $dataDir;

		$content = array();
		$content[] = 'Export_Version = '.gpversion;
		$content[] = 'Export_Which = '.$which_exported;
		$content[] = 'Export_Time = '.time();
		$content[] = 'Export_Prefix = '.$dirPrefix;
		$content[] = 'Export_Root = '.$dataDir;
		$content[] = $this->GetUsers($which_exported);

		$content = implode("\n",$content);

		if( !gpFiles::Save($this->export_ini_file,$content) ){
			return false;
		}

		return true;
	}


	/**
	 * Add a list of registered users to the export ini file if the export includes pages, config, addons
	 *
	 */
	function GetUsers($which_exported){
		global $dataDir;

		if( ($this->min_import_bits & $which_exported) != $this->min_import_bits ){
			return;
		}

		$users = array();
		include($dataDir.'/data/_site/users.php');
		$user_array = array_keys($users);

		return 'Export_Users = '.implode(',',$user_array);
	}


	/*
	 * Revert
	 *
	 *
	 *
	 */

	function Revert($cmd){
		global $langmessage, $gp_filesystem, $dataDir, $gpAdmin;

		if( !$this->RevertFilesystem() ){
			return false;
		}

		$archive =& $_REQUEST['archive'];
		includeFile('tool/parse_ini.php');

		$this->import_object = $this->ArchiveToObject($archive);
		if( !$this->import_object ){
			message($langmessage['OOPS'] .' (No Archive)');
			return false;
		}

		$this->import_list = $this->import_object->listContent();
		if( $this->import_list <= 1 ){
			message($langmessage['OOPS'] .' (Empty file list)');
			return false;
		}

		$this->import_info = $this->ExtractIni($this->import_object);
		if( $this->import_info === false ){
			message($langmessage['OOPS'].' (No info)');
			return false;
		}

		if( !$this->CanRevert($this->import_info['Export_Which']) ){
			message($langmessage['OOPS'].' (Not Compatible)');
			return false;
		}

		// check for current user in export data
		// cancel the process if the current user was not in the system when the export was made
		if( isset($this->import_info['Export_Users']) ){
			$users = explode(',',$this->import_info['Export_Users']);
			if( array_search($gpAdmin['username'],$users) === false ){
				message($langmessage['revert_notice_user']);
				return false;
			}
		}

		echo '<h2>'.$langmessage['Revert'].'</h2>';

		if( !$gp_filesystem->ConnectOrPrompt('Admin_Port') ){
			return true;
		}

		//confirmed
		if( ($cmd == 'revert_confirmed') && isset($_POST['cmd']) ){
			$successful = $this->RevertConfirmed();
			$this->RevertFinished($successful);
			return true;
		}


		echo '<p>';
		$info = $this->FileInfo($archive);
		$file_count = count($this->import_list)-1;
		echo sprintf($langmessage['archive_contains'],$info['time'],number_format($file_count));
		echo '</p>';

		echo '<p class="gp_notice">';
		echo $langmessage['revert_notice'];
		echo '</p>';


		if( version_compare(gpversion,$this->import_info['Export_Version'],'!=') ){
			echo '<p class="gp_notice">';
			echo sprintf($langmessage['revert_ver_notice'],gpversion,$this->import_info['Export_Version']);
			echo '</p>';
		}


		echo '<form action="'.common::GetUrl('Admin_Port').'" method="post">';
		echo '<input type="hidden" name="archive" value="'.htmlspecialchars($archive).'" />';
		echo '<input type="hidden" name="cmd" value="revert_confirmed" />';
		echo '<input type="submit" name="" value="'.htmlspecialchars($langmessage['Revert_to_Archive']).'" class="gpsubmit" />';
		echo '<input type="submit" name="cmd" value="'.htmlspecialchars($langmessage['cancel']).'" class="gpcancel" />';

		echo '</form>';

		return true;
	}


	/**
	 * Transfer files from the archive to the active directories
	 *
	 */
	function RevertConfirmed(){
		global $langmessage, $gp_filesystem, $dataDir;

		//organize list
		$data_list = array();
		$theme_list = array();
		$addon_list = array();
		foreach($this->import_list as $file_info){

			$filename = $file_info['filename'];

			$pos_data = strpos( $filename, 'gpexport/data/' );
			$pos_theme = strpos( $filename, 'gpexport/themes/' );
			$pos_addons = strpos( $filename, 'gpexport/addons/' );

			if( $pos_data !== false ){
				$file_info['relative_path'] = substr($filename,$pos_data+13);
				$data_list[] = $file_info;
			}elseif( $pos_theme !== false ){
				$file_info['relative_path'] = substr($filename,$pos_theme+15);
				$theme_list[] = $file_info;
			}elseif( $pos_addons !== false ){
				$file_info['relative_path'] = substr($filename,$pos_theme+15);
				$addon_list[] = $file_info;
			}
		}


		//start with themes
		//should be done with $gp_filesystem
		if( $this->import_info['Export_Which'] & $this->bit_themes ){
			$new_relative = $gp_filesystem->TempFile( '/themes' );
			$this->replace_dirs['/themes'] = $new_relative;
			if( !$this->PutFiles( $theme_list, $new_relative ) ){
				return false;
			}
		}


		//then addons, nearly identical process to themes
		if( $this->import_info['Export_Which'] & $this->bit_addons ){
			if( count($addon_list) > 0 ){
				$new_relative = $gp_filesystem->TempFile( '/addons' );
				$this->replace_dirs['/addons'] = $new_relative;
				if( !$this->PutFiles( $addon_list, $new_relative ) ){
					return false;
				}
			}
		}


		//replace data directory
		//use $gp_filesystem for the first directory only
		//then copy other folders from /data so we don't lose sessions, uploaded content etc
		$new_relative = $gp_filesystem->TempFile( '/data' );
		$this->replace_dirs['/data'] = $new_relative;
		if( !$this->PutFiles( $data_list, $new_relative, true ) ){
			return false;
		}
		$source = $dataDir.'/data/';
		$new_full = $dataDir.$new_relative;
		$this->CopyDir( $source, $new_full );

		$replaced = $gp_filesystem->ReplaceDirs( $this->replace_dirs, $this->extra_dirs );

		if( $replaced !== true ){
			message($langmessage['revert_failed'].$replaced);
			return false;
		}

		$this->TransferSession();

		return true;
	}

	/**
	 * Make sure the current user stays logged in after a revert is completed
	 *
	 */
	function TransferSession(){
		global $gpAdmin,$dataDir;

		$username = $gpAdmin['username'];

		// get user info
		include($dataDir.'/data/_site/users.php');
		$userinfo =& $users[$username];
		$session_id = gpsession::create($userinfo,$username);

		if( !$session_id ){
			return;
		}

		//set the cookie for the new data
		require($dataDir.'/data/_site/config.php');
		$session_cookie = 'gpEasy_'.substr(sha1($config['gpuniq']),12,12);
		gpsession::cookie($session_cookie,$session_id);


		//set the update gpuniq value for the post_nonce
		$GLOBALS['config']['gpuniq'] = $config['gpuniq'];
	}


	function RevertFinished($successful){
		global $langmessage,$dataDir;

		if( $successful ){
			echo '<p>';
			echo $langmessage['Revert_Finished'];
			echo '</p>';
		}

		echo '<p>';
		echo $langmessage['old_folders_created'];
		echo '</p>';

		echo '<form action="'.common::GetUrl('Admin_Port').'" method="post">';
		echo '<ul>';

		$dirs = array_merge( array_values($this->replace_dirs), array_values($this->extra_dirs));
		$dirs = array_unique( $dirs );

		foreach($dirs as $folder){
			$folder = trim($folder,'/');
			$full = $dataDir.'/'.$folder;
			if( !file_exists($full) ){
				continue;
			}
			echo '<div><label>';
			echo '<input type="checkbox" name="old_folder[]" value="'.htmlspecialchars($folder).'" checked="checked" />';
			echo htmlspecialchars($folder);
			echo '</label></div>';
		}
		echo '</ul>';

		echo '<input type="hidden" name="cmd" value="revert_clean" />';
		echo '<input type="submit" name="" value="'.htmlspecialchars($langmessage['continue']).'" class="gpsubmit" />';

		echo '</form>';

		return true;
	}


	function RevertClean(){
		global $langmessage, $gp_filesystem, $dataDir;

		if( !$this->RevertFilesystem() ){
			return false;
		}

		$gp_filesystem->connect();

		$folders =& $_REQUEST['old_folder'];
		if( count($folders) > 0 ){
			$fs_root = $gp_filesystem->get_base_dir();

			foreach($folders as $folder){
				if( strpos($folder,'/') !== false || strpos($folder,'\\') !== false ){
					continue;
				}
				$check = $dataDir.'/'.$folder;
				if( !file_exists($check) ){
					continue;
				}
				$full = $fs_root.'/'.$folder;
				$gp_filesystem->rmdir_all($full);
			}
		}
	}

	/**
	 * Prepare gp_filesystem for writing to $dataDir
	 * $dataDir writability is required so that we can create a temporary directory next to /data for replacement
	 *
	 */
	function RevertFilesystem(){
		global $gp_filesystem, $dataDir, $langmessage;

		$context = array($dataDir=>'dir');
		gp_filesystem_base::init($context,'list');
		if( !$gp_filesystem ){
			message($langmessage['OOPS'] .' (No filesystem)');
			return false;
		}
		return true;
	}

	function CopyDir( $source, $dest ){
		global $dataDir, $langmessage;

		$data_files = gpFiles::ReadDir($source,false);

		foreach($data_files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			$source_full = $source.'/'.$file;
			$dest_full = $dest.'/'.$file;

			if( file_exists($dest_full) ){
				continue;
			}

			if( is_dir($source_full) ){
				if( !$this->CopyDir( $source_full, $dest_full ) ){
					return false;
				}
				continue;
			}

			$contents = file_get_contents($source_full);
			if( !gpFiles::Save($dest_full,$contents) ){
				message($langmessage['OOPS'].' Session file not copied (0)');
				return false;
			}
		}
		return true;
	}



	/**
	 * Put the files in $file_list in the destination folder
	 * If the second destination folder is set, use gpFiles methods
	 *
	 * @param array $file_list List of files
	 * @param string $dest_rel The relative destination directory for $file_list
	 * @param bool $gpfiles False to use $gp_filesystem for file replacement, True for gpFiles methods
	 * @return bool
	 */
	function PutFiles( $file_list, $dest_rel, $gpfiles = false ){
		global $gp_filesystem, $langmessage, $dataDir;

		$dest_fs = $gp_filesystem->get_base_dir().$dest_rel;
		if( $gpfiles ){
			$dest = $dataDir.$dest_rel;
		}

		//create destination
		if( !$gp_filesystem->mkdir($dest_fs) ){
			message($langmessage['revert_failed'].' Directory not created (0)');
			return false;
		}


		foreach($file_list as $file_info){

			$path =& $file_info['filename'];
			$flag = (int)$file_info['typeflag'];
			$full_fs = $dest_fs.$file_info['relative_path'];
			$full = false;
			if( $gpfiles ){
				$full = $dest.$file_info['relative_path'];
			}


			//create directories
			if( $flag === 5 ){
				if( $full ){
					if( !gpFiles::CheckDir($full,false) ){
						message($langmessage['revert_failed'].' Directory not created (1)');
						return false;
					}
					continue;
				}
				if( !$gp_filesystem->mkdir( $full_fs ) ){
					message($langmessage['revert_failed'].' Directory not created (2)');
					return false;
				}
				continue;
			}

			//create files
			if( $flag === 0 ){
				$contents = $this->GetImportContents( $path );

				if( $full ){
					if( !gpFiles::Save($full,$contents) ){
						message($langmessage['revert_failed'].' File not created (1)');
						return false;
					}
				}

				if( !$gp_filesystem->put_contents( $full_fs, $contents ) ){
					message($langmessage['revert_failed'].' File not created (2)');
					return false;
				}
				continue;
			}

			//symbolic link
			if( $flag === 2 ){

				$target = $this->FixLink($file_info['link']);

				/*
				 * This is too restrictive
				 * Can't even check the new folders being created in case the target hasn't been prepared yet
				 *
				if( !file_exists($target) ){
					message($langmessage['revert_failed'].' Symlink target doesn\'t exist.');
					return false;
				}
				*/

				if( !symlink($target,$full) ){
					message($langmessage['revert_failed'].' Symlink not created (1)');
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * Fix the target of a symbolic link if it points to a location within the installation
	 *
	 * @param string $link The target path
	 * @return string The fixed path
	 *
	 */
	function FixLink($link){
		global $dataDir;

		//adjust the link target?
		if( !isset($this->import_info['Export_Root']) ){
			return $link;
		}

		$export_root = $this->import_info['Export_Root'];

		if( $export_root == $dataDir ){
			return $link;
		}

		if( strpos($link,$export_root) === 0 ){
			$len = strlen($export_root);
			return substr_replace($link,$dataDir,0,$len);
		}

		return $link;
	}


	function GetImportContents($string){
		return $this->import_object->extractInString($string);
	}

	function ArchiveToObject($archive){
		global $langmessage;

		if( empty($archive) || !isset($this->exported[$archive]) ){
			message($langmessage['OOPS'].' (Invalid Archive)');
			return false;
		}

		$full_path = $this->export_dir.'/'.$archive;
		if( !file_exists($full_path) ){
			message($langmessage['OOPS'].' (Archive non-existant)');
			return false;
		}

		$compression = null;
		$info = $this->FileInfo($archive);
		switch($info['ext']){
			case 'gz':
				$compression = 'gz';
			break;
			case 'bz':
				$compression = 'bz2';
			break;
		}

		$this->Init_Tar();

		return new Archive_Tar($full_path,$compression);
	}



	function ExtractIni($tar_object){
		//get Export.ini
		$ini_contents = $tar_object->extractInString('gpexport/Export.ini');
		if( empty($ini_contents) ){
			return false;
		}
		return gp_ini::ParseString($ini_contents);
	}


	/*
	 *
	 *
	 *
	 */


	function Init(){
		global $langmessage;

		$this->bit_pages = 1;
		$this->bit_config = 2;
		$this->bit_media = 4;
		$this->bit_themes = 8;
		$this->bit_addons = 16;
		$this->bit_trash = 32;


		//pages, configuration and addons
		$this->export_fields['pca']['name'] = 'pca';
		$this->export_fields['pca']['label'] = $langmessage['Pages'].', '.$langmessage['configuration'].', '.$langmessage['add-ons'];
		$this->export_fields['pca']['index'] = $this->bit_pages | $this->bit_config | $this->bit_addons;

		$this->export_fields['media']['name'] = 'media';
		$this->export_fields['media']['label'] = $langmessage['uploaded_files'];
		$this->export_fields['media']['index'] = $this->bit_media;

		$this->export_fields['themes']['name'] = 'themes';
		$this->export_fields['themes']['label'] = $langmessage['themes'];
		$this->export_fields['themes']['index'] = $this->bit_themes;

		$this->export_fields['trash']['name'] = 'trash';
		$this->export_fields['trash']['label'] = $langmessage['trash'];
		$this->export_fields['trash']['index'] = $this->bit_trash;

		$this->min_import_bits = $this->bit_pages | $this->bit_config | $this->bit_addons;


		//supported compression types
		$this->avail_compress = array();
		if( function_exists('gzopen') ){
			$this->avail_compress['gz'] = 'gzip';
		}
		if( function_exists('bzopen') ){
			$this->avail_compress['bz'] = 'bzip';
		}
	}

	function Init_Tar(){
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/ArchiveTar/Tar.php');
	}


	function SetExported(){
		$this->exported = gpFiles::ReadDir($this->export_dir,$this->all_extenstions);
		arsort($this->exported);
	}

	function DeleteConfirmed(){
		global $langmessage;

		$file =& $_POST['file'];
		if( !isset($this->exported[$file]) ){
			message($langmessage['OOPS']);
			return;
		}

		$full_path = $this->export_dir.'/'.$file;
		unlink($full_path);
		unset($this->exported[$file]);
	}


	function FileInfo($file){
		global $langmessage;


		$temp = explode('.',$file);
		if( count($temp) < 3 ){
			return '';
		}

		$info = array();
		$info['ext'] = array_pop($temp);
		$info['bits'] = '';

		if( count($temp) >= 3 && is_numeric($temp[0]) && is_numeric($temp[1]) ){
			list($time,$exported,$rand) = $temp;
			$info['time'] = common::date($langmessage['strftime_datetime'],$time);
			$info['bits'] = $exported;

			foreach($this->export_fields as $export_field){
				$index = $export_field['index'];
				if( ($exported & $index) == $index ){
					$info['exported'][] = $export_field['label'];
				}
			}
			if( empty($info['exported']) ){
				$info['exported'][] = 'Unknown';
			}
		}else{
			$info['time'] = $file;
			$info['exported'][] = 'Unknown';
		}

		return $info;
	}



	function Exported(){
		global $langmessage;

		if( count($this->exported) == 0 ){
			return;
		}

		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['Previous Exports'];
		echo '</th><th> &nbsp; </th><th>';
		echo $langmessage['File Size'];
		echo '</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';


		$total_size = 0;
		$total_count = 0;
		foreach($this->exported as $file){

			$info = $this->FileInfo($file);
			if( !$info ){
				continue;
			}

			$full_path = $this->export_dir.'/'.$file;

			if( $total_count%2 == 0 ){
				echo '<tr class="even">';
			}else{
				echo '<tr>';
			}
			echo '<td>';
			echo str_replace(' ','&nbsp;',$info['time']);
			echo '</td><td>';
			echo implode(', ',$info['exported']);
			echo '</td><td>';
			$size = filesize($full_path);
			echo admin_tools::FormatBytes($size);
			echo ' ';
			echo $info['ext'];
			echo '</td><td>';
			echo '<a href="'.common::GetDir('/data/_exports/'.$file).'">'.$langmessage['Download'].'</a>';

			echo '&nbsp;&nbsp;';
			if( $this->CanRevert($info['bits']) ){
				echo common::Link('Admin_Port',$langmessage['Revert'],'cmd=revert&archive='.rawurlencode($file),'',$file);
			}else{
				echo $langmessage['Revert'];
			}

			echo '&nbsp;&nbsp;';
			echo common::Link('Admin_Port',$langmessage['delete'],'cmd=delete&file='.rawurlencode($file),array('data-cmd'=>'postlink','title'=>$langmessage['delete_confirm'],'class'=>'gpconfirm'),$file);
			echo '</td></tr>';

			$total_count++;
			$total_size+= $size;
		}

		//totals
		echo '<tr><th>';
		echo $langmessage['Total'];
		echo ': ';
		echo $total_count;
		echo '</th><th>&nbsp;</th><th>';
		echo admin_tools::FormatBytes($total_size);
		echo '</th><th>&nbsp;</th></tr>';

		echo '</table>';

	}


	/**
	 * Determine if the package can be used in the rever process
	 * Either must have bit_pages, bit_config and bit_addons or none of the three
	 *
	 */
	function CanRevert($bits){

		if( ($this->min_import_bits & $bits) == $this->min_import_bits ){
			return true;
		}

		if( ($this->bit_pages & $bits) || ($this->bit_config & $bits) || ($this->bit_addons & $bits) ){
			return false;
		}

		return true;
	}


	function ExportForm(){
		global $langmessage;

		echo '<form action="'.common::GetUrl('Admin_Port').'" method="post">';

		echo '<p>';
		foreach($this->export_fields as $info){
			$this->Checkbox_Export($info['name'],$info['label']);
			echo ' &nbsp; ';
		}
		echo '</p>';

		echo '<p>';
		echo '<input type="hidden" name="cmd" value="do_export" />';
		echo $langmessage['Compression'].': ';
		echo ' <select name="compression" class="gpselect">';
		foreach($this->avail_compress as $ext => $disp){
			echo '<option value="'.$ext.'">'.$disp.'</option>';
		}
		echo '<option value="">'.$langmessage['None'].'</option>';
		echo '</select>';


		echo ' &nbsp; <input type="submit" name="" value="'.$langmessage['Export'].'" class="gpsubmit" />';
		echo '</p>';
		echo '</form>';


	}

	function Checkbox_Export($name,$label){
		$checked = false;
		$class = '';
		if( !isset($_POST['cmd']) || ($_POST['cmd'] !== 'do_export') ){
			$checked = true;
			$class = ' checked';
		}elseif( isset($_POST[$name]) ){
			$checked = true;
			$class = ' checked';
		}

		echo '<label class="gpcheckbox'.$class.'">';
		if( $checked ){
			echo '<input type="checkbox" name="'.htmlspecialchars($name).'" value="on" checked="checked" class="gpcheck">';
		}else{
			echo '<input type="checkbox" name="'.htmlspecialchars($name).'" value="on" class="gpcheck">';
		}
		echo ' &nbsp; ';
		echo htmlspecialchars($label);
		echo '</label>';
	}

}


