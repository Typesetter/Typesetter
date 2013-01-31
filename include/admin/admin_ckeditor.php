<?php
defined('is_running') or die('Not an entry point...');

class admin_ckeditor{

	var $config_file;
	var $cke_config = array();

	function admin_ckeditor(){
		$this->Init();

		$cmd = common::GetCommand();
		switch($cmd){

			case 'save_custom_config':
				$this->SaveCustomConfig();
			break;

			case 'upload_plugin':
				$this->UploadPlugin();
			break;

			case 'rmplugin':
				$this->RemovePlugin();
			break;
		}

		echo '<style>';
		echo 'body #gp_admin_html pre.json{font-family:monospace;line-height:180%;font-size:12px}';
		echo '.custom_config{width:600px;height:200px;}';
		echo '</style>';

		echo '<h2>CKEditor Customization</h2>';
		echo '<p>';
		echo '<a href="http://ckeditor.com">CKEditor</a> is a powerful editor with <a href="http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html">many configuration options</a> and a growing <a href="http://ckeditor.com/addons/plugins">list of plugins</a>. ';
		echo '</p>';

		$this->CustomConfigForm();

		$this->PluginForm();

		$this->DisplayDefaults();
	}


	/**
	 * Display a form for uploading CKEditor plugins
	 *
	 */
	function PluginForm(){
		global $langmessage;

		echo '<h3>Plugins</h3>';
		echo '<p>Add a CKEditor plugin</p>';
		if( count($this->cke_config['plugins']) ){
			echo '<table class="bordered"><tr><th>'.$langmessage['name'].'</th><th>'.$langmessage['Modified'].'</th><th>'.$langmessage['options'].'</th></tr>';
			foreach($this->cke_config['plugins'] as $plugin_name => $plugin_info){
				echo '<tr><td>';
				echo $plugin_name;
				echo '</td><td>';
				echo common::date($langmessage['strftime_datetime'],$plugin_info['updated']);
				echo '</td><td>';

				$attr = array('data-cmd'=>'postlink', 'class'=>'gpconfirm','title'=>sprintf($langmessage['generic_delete_confirm'],$plugin_name));
				echo common::Link('Admin_CKEditor',$langmessage['delete'],'cmd=rmplugin&plugin='.rawurlencode($plugin_name), $attr );
				echo '</td></tr>';

			}
		}else{
			echo '<table>';
		}

		echo '<tr><td colspan="3">';
		echo '<form method="post" action="'.common::GetUrl('Admin_CKEditor').'" enctype="multipart/form-data">';
		echo '<input type="hidden" name="cmd" value="upload_plugin" />';
		echo '<input type="file" name="plugin" />';
		echo ' <input type="submit" value="Install Plugin" />';
		echo '</form>';
		echo '</td></tr>';

		echo '</table>';
	}


	/**
	 * Add an uploaded plugin
	 *
	 */
	function UploadPlugin(){
		global $langmessage, $dataDir;

		if( empty($_FILES['plugin']) ){
			message($langmessage['OOPS'].' (No File)');
			return;
		}

		$plugin_file = $_FILES['plugin'];

		if( strpos($plugin_file['name'],'.zip') === false ){
			message($langmessage['OOPS'].' (Not a zip file)');
			return;
		}

		// Unzip uses a lot of memory, but not this much hopefully
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/pclzip-2-8-2/pclzip.lib.php');
		$archive = new PclZip( $plugin_file['tmp_name'] );


		// get plugin name
		$plugin_name = false;
		$list = $archive->listContent();
		foreach($list as $file){
			if( !$file['folder'] ){
				continue;
			}
			$plugin_name = strrev(basename(strrev($file['filename'])));
			break;
		}
		if( !$plugin_name ){
			message($langmessage['OOPS'].' (Unknown plugin name)');
			return;
		}


		// check destination directory
		$destination = $dataDir.'/data/_ckplugins/'.$plugin_name;
		$temp_dir = false;
		if( file_exists($destination) ){
			$temp_dir = $destination.'_'.time();
			if( !rename($destination,$temp_dir) ){
				message($langmessage['OOPS'].' (Couldn\'t remove old plugin)');
				return;
			}
		}elseif( !gpFiles::CheckDir($destination) ){
			message($langmessage['OOPS'].' (Couldn\'t create plugin folder)');
			return;
		}


		// extract
		$return = $archive->extract( PCLZIP_OPT_PATH, $destination, PCLZIP_OPT_REMOVE_PATH, $plugin_name );
		if( !is_array($return) ){

			if( $temp_dir ){
				rename( $temp_dir, $destination );
			}

			message($langmessage['OOPS'].' (Extract Failed)');
			return;
		}


		// save configuration
		if( !array_key_exists( $plugin_name, $this->cke_config['plugins'] ) ){
			$this->cke_config['plugins'][$plugin_name] = array('installed'=>time());
		}
		$this->cke_config['plugins'][$plugin_name]['updated'] = time();
		$this->SaveConfig();

		message($langmessage['SAVED']);

		// remove temporary
		if( $temp_dir ){
			gpFiles::RmAll( $temp_dir );
		}

	}


	/**
	 * Remove a plugin
	 *
	 */
	function RemovePlugin(){
		global $langmessage, $dataDir;

		$plugin =& $_REQUEST['plugin'];
		if( !is_array($this->cke_config['plugins']) || !array_key_exists( $plugin, $this->cke_config['plugins'] ) ){
			message($langmessage['OOPS'].' ( )');
			return;
		}

		unset( $this->cke_config['plugins'][$plugin] );
		if( !$this->SaveConfig() ){
			message($langmessage['OOPS'].' (Not Saved)');
		}else{
			message($langmessage['SAVED']);
		}


		$path = $dataDir.'/data/_ckplugins/'.$plugin;
		gpFiles::RmAll( $path );
	}


	/**
	 * Display custom_config form
	 *
	 */
	function CustomConfigForm(){
		echo '<h3>Configuration</h3>';
		echo '<form method="post" action="'.common::GetUrl('Admin_CKEditor').'">';

		$placeholder = '{  "example_key":   "example_value"  }';
		echo '<textarea name="custom_config" class="custom_config" placeholder="'.htmlspecialchars($placeholder).'">';
		if( isset($_POST['custom_config']) ){
			echo htmlspecialchars($_POST['custom_config']);
		}elseif( !empty($this->cke_config['custom_config']) ){
			echo htmlspecialchars(self::ReadableJson($this->cke_config['custom_config']));
		}
		echo '</textarea>';

		echo '<div>';
		echo '<input type="hidden" name="cmd" value="save_custom_config" />';
		echo '<input type="submit" value="Save"/>';
		echo '</div>';

		echo '</form>';
	}

	/**
	 * Save custom_config value
	 *
	 */
	function SaveCustomConfig(){
		global $langmessage;

		$custom_config =& $_REQUEST['custom_config'];
		if( !empty($custom_config) ){
			$decoded = json_decode($custom_config,true);
			if( !is_array($decoded) ){
				message($langmessage['OOPS'].' (Invalid JSON String)');
				return false;
			}
		}

		$this->cke_config['custom_config'] = $custom_config;
		if( !$this->SaveConfig() ){
			message($langmessage['OOPS'].' (Not Saved)');
		}else{
			message($langmessage['SAVED']);
		}

	}


	/**
	 * Display gpEasy Defaults
	 *
	 */

	function DisplayDefaults(){
		echo '<h3>gpEasy Defaults</h3>';
		includeFile('tool/editing.php');
		$default_config = gp_edit::CKConfig(array(),'array');
		echo '<pre class="json">';
		echo self::ReadableJson($default_config);
		echo '</pre>';
	}


	/**
	 * Save the configuration file
	 *
	 */
	function SaveConfig(){
		return gpFiles::SaveArray($this->config_file,'cke_config',$this->cke_config);
	}


	/**
	 * Get current configuration settings
	 *
	 */
	function Init(){
		global $dataDir;

		$this->config_file = $dataDir.'/data/_site/ckeditor.php';
		if( file_exists($this->config_file) ){
			include($this->config_file);
			$this->cke_config = $cke_config;
		}

		//$this->cke_config += array('custom_config'=>array());
		$this->cke_config += array('plugins'=>array());

	}


	/**
	 * Output an array in a readable json format
	 *
	 */
	static function ReadableJson($mixed){
		static $level = 0;

		$type = gettype($mixed);
		if( gettype($mixed) != 'array' ){
			return json_encode($mixed);
		}


		$level++;

		//associative or indexed array
		$i = 0;
		$indexed = true;
		$separator = ' ';
		foreach($mixed as $key => $value){
			if( !is_integer($key) && !ctype_digit($key) ){
				$indexed = false;
			}elseif( (int)$key !== $i ){
				$indexed = false;
			}
			if( !$indexed || is_array($value) ){
				$separator = "\n".str_repeat('    ',$level);
			}
			$i++;
		}

		$comma = '';
		if( $indexed ){
			$output = '[';
			foreach($mixed as $key => $value){
				$output .= $comma.$separator.self::ReadableJson($value);
				$comma = ',';
			}
			$output .= $separator.']';

		}else{
			$output = '{';
			foreach($mixed as $key => $value){
				$output .= $comma.$separator.self::ReadableJson($key).' :'.self::ReadableJson($value);
				$comma = ',';
			}
			$output .= $separator.'}';
		}
		$level--;

		return $output;
	}

}