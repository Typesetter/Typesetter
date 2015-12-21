<?php
defined('is_running') or die('Not an entry point...');


class admin_status{
	var $check_dir_len = 0;
	var $failed_count = 0;
	var $passed_count = 0;
	var $show_failed_max = 50;

	function __construct(){
		global $dataDir,$langmessage;

		includeFile('tool/install.php');

		echo '<h2>'.$langmessage['Site Status'].'</h2>';

		$check_dir = $dataDir.'/data';
		$this->check_dir_len = strlen($check_dir);
		$this->euid = '?';
		if( function_exists('posix_geteuid') ){
			$this->euid = posix_geteuid();
		}


		ob_start();
		$this->CheckDir($check_dir);
		$failed_output = ob_get_clean();

		$checked = $this->passed_count + $this->failed_count;

		if( $this->failed_count == 0 ){
			echo '<p class="gp_passed">';
			echo sprintf($langmessage['data_check_passed'],$checked,$checked);
			echo '</p>';

			//$this->CheckPageFiles();
			return;
		}

		echo '<p class="gp_notice">';
		echo sprintf($langmessage['data_check_failed'],$this->failed_count,$checked);
		echo '</p>';

		if( $this->failed_count > $this->show_failed_max ){
			echo '<p class="gp_notice">';
			echo sprintf($langmessage['showing_max_failed'],$this->show_failed_max);
			echo '</p>';
		}


		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th colspan="2">';
		echo $langmessage['permissions'];
		echo '</th><th colspan="2">';
		echo $langmessage['File Owner'];
		echo '</th></tr>';

		echo '<tr><td>&nbsp;</td><td>';
		echo $langmessage['Current_Value'];
		echo '</td><td>';
		echo $langmessage['Expected_Value'];
		echo '</td><td>';
		echo $langmessage['Current_Value'];
		echo '</td><td>';
		echo $langmessage['Expected_Value'];
		echo '</td></tr>';
		echo $failed_output;
		echo '</table>';

		$this->CheckPageFiles();
	}

	/**
	 * Check page files for orphaned data files
	 *
	 */
	function CheckPageFiles(){
		global $dataDir,$gp_index;

		$pages_dir = $dataDir.'/data/_pages';
		$all_files = gpFiles::ReadDir($pages_dir,'php');
		foreach($all_files as $key => $file){
			$all_files[$key] = $pages_dir.'/'.$file.'.php';
		}

		$page_files = array();
		foreach($gp_index as $slug => $index){
			$page_files[] = gpFiles::PageFile($slug);
		}

		$diff = array_diff($all_files,$page_files);

		if( !count($diff) ){
			return;
		}

		echo '<h2>Orphaned Data Files</h2>';
		echo '<p>The following data files appear to be orphaned and are most likely no longer needed. Before completely removing these files, we recommend backing them up first.</p>';
		echo '<table class="bordered"><tr><th>File</th></tr>';
		foreach($diff as $file){
			echo '<tr><td>'
				. $file
				. '</td></tr>';
		}
		echo '</table>';
	}


	function CheckDir($dir){
		$this->CheckFile($dir);

		$dh = @opendir($dir);
		if( !$dh ){
			echo '<tr><td colspan="3">';
			echo '<p class="gp_notice">';
			echo 'Could not open data directory: '.$check_dir;
			echo '</p>';
			echo '</td></tr>';
			return;
		}

		while( ($file = readdir($dh)) !== false){
			if( $file == '.' || $file == '..' ){
				continue;
			}

			$full_path = $dir.'/'.$file;
			if( is_link($full_path) ){
				continue;
			}

			if( is_dir($full_path) ){
				$this->CheckDir($full_path,'dir');
			}else{
				$this->CheckFile($full_path,'file');
			}
		}
	}

	function CheckFile($path,$type='dir'){

		$current = '?';
		$expected = '777';
		$euid = '?';
		if( FileSystem::HasFunctions() ){
			$current = @substr(decoct( @fileperms($path)), -3);

			if( $type == 'file' ){
				$expected = FileSystem::getExpectedPerms_file($path);
			}else{
				$expected = FileSystem::getExpectedPerms($path);
			}

			if( FileSystem::perm_compare($expected,$current) ){
				$this->passed_count++;
				return;
			}

			$euid = FileSystem::file_uid($path);

		}elseif( gp_is_writable($path) ){
			$this->passed_count++;
			return;
		}

		$this->failed_count++;

		if( $this->failed_count > $this->show_failed_max ){
			return;
		}

		echo '<tr><td>';
		echo substr($path,$this->check_dir_len);
		echo '</td><td>';

		echo $current;
		echo '</td><td>';
		echo $expected;
		echo '</td><td>';
		echo $euid;
		echo '</td><td>';
		echo $this->euid;
		echo '</td></tr>';

	}


}

