<?php

namespace gp\admin\Tools;

defined('is_running') or die('Not an entry point...');

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


class Port{

	public $export_fields = array();
	public $export_arch;
	public $export_dir;
	public $exported = array();
	public $temp_dir;
	public $avail_compress = array();
	public $all_extenstions = array('tgz','tbz','tar','gz','bz','zip');

	public $archive_path;
	public $archive_name;

	public $min_import_bits = 0;


	//importing/reverting
	public $import_object;
	public $import_info;
	protected $FileSystem;

	public $replace_dirs = array();
	public $extra_dirs = array();

	public $iframe = '';


	private $bit_pages	= 1;
	private $bit_config	= 2;
	private $bit_media	= 4;
	private $bit_themes	= 8;
	private $bit_addons	= 16;
	private $bit_trash	= 32;


	public function __construct(){
		global $dataDir;

		$this->export_dir		= $dataDir.'/data/_exports';
		$this->temp_dir			= $dataDir.'/data/_temp';

		// @set_time_limit(90);
		// @ini_set('memory_limit','64M');

		$this->Init();
		$this->SetExported();
	}

	public function Init(){
		global $langmessage;

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
		$this->avail_compress = \gp\tool\Archive::Available();
	}


	public function RunScript(){
		global $langmessage;

		$cmd = \gp\tool::GetCommand();

		if( empty($this->avail_compress) ){
			$cmd = '';
		}

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


		echo '<h2>'.$langmessage['Export'].'</h2>';
		echo $langmessage['Export About'];

		if( empty($this->avail_compress) ){
			echo '<p class="bg-danger">';
			echo 'None of PHP\'s archive extensions are enabled. ';
			echo 'Please enable <a href="http://php.net/manual/en/class.phardata.php">PharData</a> or <a href="http://php.net/manual/en/class.ziparchive.php">ZipArchive</a> in your PHP installation';
			echo '</p>';

		}else{
			echo $this->iframe;

			$this->ExportForm();
		}

		echo '<br/>';

		$this->Exported();

	}


	/**
	 *
	 *
	 */
	protected function Warning(){


	}


	/**
	 * Create an archive of the selected folders
	 *
	 */
	public function DoExport(){
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


		$success = $this->Create_Tar($which_exported,$add_dirs);


		//iframe for download
		if( $success ){
			$this->iframe = '<iframe src="'.\gp\tool::GetDir('/data/_exports/'.$this->archive_name).'" height="0" width="0" style="visibility:hidden;height:0;width:0;"></iframe>';
		}

		return true;
	}


	/**
	 * Create a tar archive of the folders in $add_dirs
	 *
	 */
	public function Create_Tar($which_exported,$add_dirs){
		global $dataDir, $langmessage;


		$compression =& $_POST['compression'];
		if( !$this->NewFile($which_exported,$compression) ){
			return false;
		}

		try{

			$tar_object = new \gp\tool\Archive($this->archive_path);
			foreach($add_dirs as $dir){
				$localname = '/gpexport'.substr($dir, strlen($dataDir));
				$tar_object->Add($dir,$localname);
			}

			//add ini file
			$this->Export_Ini($tar_object,$which_exported);

			$tar_object->compress();

		}catch( \Exception $e){
			message($langmessage['OOPS'].' (Archive couldn\'t be created)');
			return false;
		}

		return true;
	}


	/**
	 * Generate the name and path of the archive
	 *
	 */
	public function NewFile($which_exported,$extension){
		global $langmessage;

		if( !\gp\tool\Files::CheckDir($this->export_dir) ){
			message($langmessage['OOPS'].'(Export Dir)');
			return false;
		}

		$this->archive_name = time().'_'.$which_exported.'_'.rand(1000,9000).'.'.$extension;
		$this->archive_path = $this->export_dir.'/'.$this->archive_name;

		return true;
	}

	public function Export_Ini($tar_object, $which_exported){
		global $dirPrefix, $dataDir;

		$content = array();
		$content[] = 'Export_Version = '.gpversion;
		$content[] = 'Export_Which = '.$which_exported;
		$content[] = 'Export_Time = '.time();
		$content[] = 'Export_Prefix = '.$dirPrefix;
		$content[] = 'Export_Root = '.$dataDir;
		$content[] = $this->GetUsers($which_exported);

		$content = implode("\n",$content);

		$tar_object->addFromString('/gpexport/Export.ini',$content);
	}


	/**
	 * Add a list of registered users to the export ini file if the export includes pages, config, addons
	 *
	 */
	public function GetUsers($which_exported){


		if( ($this->min_import_bits & $which_exported) != $this->min_import_bits ){
			return;
		}

		$users		= \gp\tool\Files::Get('_site/users');
		$user_array = array_keys($users);

		return 'Export_Users = '.implode(',',$user_array);
	}


	/*
	 * Revert
	 *
	 *
	 *
	 */

	public function Revert($cmd){
		global $langmessage, $dataDir, $gpAdmin;

		if( !$this->RevertFilesystem() ){
			return false;
		}

		$archive =& $_REQUEST['archive'];


		if( !$this->ArchiveToObject($archive) ){
			return false;
		}


		$this->import_info = $this->ExtractIni();
		if( $this->import_info === false ){
			message($langmessage['OOPS'].' (No import info)');
			return false;
		}

		if( !$this->CanRevert($this->import_info['Export_Which']) ){
			message($langmessage['OOPS'].' (Not Compatible)');
			return false;
		}


		echo '<h2>'.$langmessage['Revert'].'</h2>';

		if( !$this->FileSystem->ConnectOrPrompt('Admin/Port') ){
			return true;
		}

		//confirmed
		if( ($cmd == 'revert_confirmed') && isset($_POST['cmd']) ){
			$successful = $this->RevertConfirmed();
			$this->RevertFinished($successful);
			return true;
		}


		echo '<p>';
		$info			= $this->FileInfo($archive);
		$file_count		= $this->import_object->count();
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


		echo '<form action="'.\gp\tool::GetUrl('Admin/Port').'" method="post">';
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
	public function RevertConfirmed(){
		global $langmessage, $dataDir;


		//extract to temp location
		$temp_file		= $dataDir.\gp\tool\FileSystem::TempFile('/data/_temp/revert');
		$temp_name		= basename($temp_file);
		$replace_dirs	= array();


		try{

			$this->import_object->extractTo($temp_file);

		}catch( \Exception $e){
			message($langmessage['OOPS'].' (Archive couldn\'t be extracted)');
			return false;
		}



		if( $this->import_info['Export_Which'] & $this->bit_themes ){
			$replace_dirs['themes'] = false;
		}

		if( $this->import_info['Export_Which'] & $this->bit_addons ){
			$replace_dirs['addons'] = false;
		}

		if( ($this->import_info['Export_Which'] & $this->bit_pages) || ($this->import_info['Export_Which'] & $this->bit_media) ){
			$replace_dirs['data'] = true;
		}

		foreach($replace_dirs as $dir => $merge){
			if( !$this->AddReplaceDir($dir,$temp_name, $merge) ){
				return false;
			}
		}

		$replaced = $this->FileSystem->ReplaceDirs( $this->replace_dirs, $this->extra_dirs );

		if( $replaced !== true ){
			message($langmessage['revert_failed'].$replaced);
			return false;
		}

		$this->TransferSession();

		return true;
	}

	/**
	 * Add themes, addons or data directories to the replace_dirs list only if they're not empty
	 *
	 */
	public function AddReplaceDir($dir, $temp_name, $merge = false){
		global $dataDir, $langmessage;

		$rel_path	= '/data/_temp/'.$temp_name.'/gpexport/'.$dir;
		$full_path	= $dataDir.$rel_path;

		if( !file_exists($full_path) ){
			return true;
		}

		$files		= scandir($full_path);
		if( count($files) === 0 ){
			return true;
		}


		// move to location outside of existing /data directory
		// otherwise ReplaceDirs() will fail when we try to replace the data directory
		$new_relative	= \gp\tool\FileSystem::TempFile( '/themes' );
		if( !$this->FileSystem->RelRename($rel_path, $new_relative) ){
			message($langmessage['revert_failed'].' (AddReplaceDir Failed)');
			return false;
		}

		// copy other folders from /data so we don't lose sessions, uploaded content etc
		if( $merge ){
			$source		= $dataDir.'/data/';
			$new_full	= $dataDir.$new_relative;
			$this->CopyDir( $source, $new_full );
		}


		$this->replace_dirs[$dir]	= $new_relative;

		return true;
	}


	/**
	 * Make sure the current user stays logged in after a revert is completed
	 *
	 */
	public function TransferSession(){
		global $gpAdmin;

		//unlock
		if( isset($_COOKIE[gp_session_cookie]) ){
			$session_id = $_COOKIE[gp_session_cookie];
			\gp\tool\Session::Unlock($session_id);
		}

		$username = $gpAdmin['username'];

		// get user info
		$users		= \gp\tool\Files::Get('_site/users');
		$userinfo	=& $users[$username];
		$session_id = \gp\tool\Session::create($userinfo, $username, $sessions);

		if( !$session_id ){
			return;
		}

		//set the cookie for the new data
		$config = \gp\tool\Files::Get('_site/config');
		$session_cookie = 'gpEasy_'.substr(sha1($config['gpuniq']),12,12);
		\gp\tool\Session::cookie($session_cookie,$session_id);


		//set the update gpuniq value for the post_nonce
		$GLOBALS['config']['gpuniq'] = $config['gpuniq'];
	}


	public function RevertFinished($successful){
		global $langmessage,$dataDir;

		if( $successful ){
			echo '<p>';
			echo $langmessage['Revert_Finished'];
			echo '</p>';
		}

		echo '<p>';
		echo $langmessage['old_folders_created'];
		echo '</p>';

		echo '<form action="'.\gp\tool::GetUrl('Admin/Port').'" method="post">';
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


	public function RevertClean(){
		global $langmessage, $dataDir;

		if( !isset($_POST['old_folder']) || !is_array($_POST['old_folder']) ){
			return true;
		}

		if( !$this->RevertFilesystem() ){
			return false;
		}


		if( !$this->FileSystem->connect() ){
			return false;
		}

		$this->FileSystem->CleanUpFolders($_POST['old_folder'], $not_deleted);

		return true;
	}


	/**
	 * Prepare FileSystem for writing to $dataDir
	 * $dataDir writability is required so that we can create & rename subfolders: $dataDir/data, $dataDir/themes
	 *
	 */
	public function RevertFilesystem(){
		global $dataDir, $langmessage;

		$this->FileSystem	= \gp\tool\FileSystem::init($dataDir);

		if( is_null($this->FileSystem) ){
			message($langmessage['OOPS'] .' (No filesystem)');
			return false;
		}
		return true;
	}

	public function CopyDir( $source, $dest ){
		global $dataDir, $langmessage;

		$data_files = \gp\tool\Files::ReadDir($source,false);

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
			if( !\gp\tool\Files::Save($dest_full,$contents) ){
				message($langmessage['OOPS'].' Session file not copied (0)');
				return false;
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
	public function FixLink($link){
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


	public function GetImportContents($string){
		return $this->import_object->extractInString($string);
	}


	/**
	 * Get an archive object
	 *
	 */
	public function ArchiveToObject($archive){
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


		try{

			$this->import_object = new \gp\tool\Archive($full_path);

		}catch( \Exception $e){
			message($langmessage['OOPS'].' (Archive couldn\'t be opened)');
			return false;
		}

		return true;
	}


	/**
	 * Get the export.ini contents
	 *
	 */
	public function ExtractIni(){

		$ini_contents		= $this->import_object->getFromName('/gpexport/Export.ini');

		if( empty($ini_contents) ){
			return false;
		}
		return \gp\tool\Ini::ParseString($ini_contents);
	}

	public function SetExported(){
		$this->exported = \gp\tool\Files::ReadDir($this->export_dir,$this->all_extenstions);
		arsort($this->exported);
	}

	public function DeleteConfirmed(){
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


	public function FileInfo($file){
		global $langmessage;


		$file = str_replace('_','.',$file);

		$temp = explode('.',$file);
		if( count($temp) < 3 ){
			return '';
		}

		$info = array();
		$info['ext'] = array_pop($temp);
		$info['bits'] = '';

		if( count($temp) >= 3 && is_numeric($temp[0]) && is_numeric($temp[1]) ){
			list($time,$exported) = $temp;
			$info['time'] = \gp\tool::date($langmessage['strftime_datetime'],$time);
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



	public function Exported(){
		global $langmessage;

		if( count($this->exported) == 0 ){
			return;
		}

		echo '<table class="bordered full_width">';
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

			echo '<tr><td>';
			echo str_replace(' ','&nbsp;',$info['time']);
			echo '</td><td>';
			echo implode(', ',$info['exported']);
			echo '</td><td>';
			$size = filesize($full_path);
			echo \gp\admin\Tools::FormatBytes($size);
			echo ' ';
			echo $info['ext'];
			echo '</td><td>';
			echo '<a href="'.\gp\tool::GetDir('/data/_exports/'.$file).'">'.$langmessage['Download'].'</a>';

			echo '&nbsp;&nbsp;';
			if( $this->CanRevert($info['bits']) ){
				echo \gp\tool::Link('Admin/Port',$langmessage['Revert'],'cmd=revert&archive='.rawurlencode($file),'',$file);
			}else{
				echo $langmessage['Revert'];
			}

			echo '&nbsp;&nbsp;';
			echo \gp\tool::Link('Admin/Port',$langmessage['delete'],'cmd=delete&file='.rawurlencode($file),array('data-cmd'=>'postlink','title'=>$langmessage['delete_confirm'],'class'=>'gpconfirm'),$file);
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
		echo \gp\admin\Tools::FormatBytes($total_size);
		echo '</th><th>&nbsp;</th></tr>';

		echo '</table>';

	}


	/**
	 * Determine if the package can be used in the revert process
	 * Either must have bit_pages, bit_config and bit_addons or none of the three
	 *
	 */
	public function CanRevert($bits){

		if( ($this->min_import_bits & $bits) == $this->min_import_bits ){
			return true;
		}

		if( ($this->bit_pages & $bits) || ($this->bit_config & $bits) || ($this->bit_addons & $bits) ){
			return false;
		}

		return true;
	}


	public function ExportForm(){
		global $langmessage;

		echo '<form action="'.\gp\tool::GetUrl('Admin/Port').'" method="post">';

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
		echo '</select>';


		echo ' &nbsp; <input type="submit" name="" value="'.$langmessage['Export'].'" class="gpsubmit" />';
		echo '</p>';
		echo '</form>';


	}

	public function Checkbox_Export($name,$label){
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
