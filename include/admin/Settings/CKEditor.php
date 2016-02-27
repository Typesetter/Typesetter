<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');

class CKEditor extends \gp\special\Base{

	var $config_file;
	var $cke_config			= array();
	var $build_config;

	var $subpages;
	var $current_subpage = '';

	function __construct($args){
		global $langmessage;

		parent::__construct($args);

		$this->page->css_admin[] = '/include/css/addons.css';
		//$this->page->head_js[] = '/include/js/admin_ckeditor.js';

		$this->Init();

		// subpage
		$this->subpages = array(
			''				=> $langmessage['Manage Plugins'],
			'Config'		=> $langmessage['configuration'],
			'Example'		=> 'Example',
			);


		$parts = explode('/',$this->page->requested);

		if( count($parts) > 2 && array_key_exists( $parts[2], $this->subpages ) ){
			$this->current_subpage = $parts[2];
		}


		// commands
		$cmd = \gp\tool::GetCommand();
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

		$this->Heading();


		switch($this->current_subpage){
			case 'Example':
				$this->Example();
			break;
			case 'Config':
				$this->CustomConfigForm();
				$this->DisplayCurrent();
			break;
			default:
				$this->PluginForm();
			break;
		}

		echo '<br/><p>';
		echo '<a href="http://ckeditor.com" target="_blank">CKEditor</a> is '.CMS_NAME.'\'s text editor of choice because it is a powerful tool with <a href="http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html" target="_blank">many configuration options</a> and a growing <a href="http://ckeditor.com/addons/plugins" target="_blank">list of plugins</a>. ';
		echo '</p>';
	}

	function Heading(){

		echo '<h2 class="hmargin">CKEditor &#187; ';

		$separator = '';
		foreach($this->subpages as $slug => $label){
			echo $separator;
			if( $slug == $this->current_subpage ){
				echo $label;
			}else{
				echo \gp\tool::Link( rtrim('Admin/CKEditor/'.$slug,'/'), $label );
			}
			$separator = ' <span>|</span> ';
		}
		echo '</h2>';

	}


	/**
	 * Display a form for uploading CKEditor plugins
	 *
	 */
	function PluginForm(){
		global $langmessage;

		echo '<form method="post" action="'.\gp\tool::GetUrl($this->page->requested).'" enctype="multipart/form-data">';
		echo '<table class="bordered"><tr><th>'.$langmessage['name'].'</th><th>'.$langmessage['Modified'].'</th><th>'.$langmessage['options'].'</th></tr>';
		if( count($this->cke_config['plugins']) ){
			foreach($this->cke_config['plugins'] as $plugin_name => $plugin_info){
				echo '<tr><td>';
				echo $plugin_name;
				echo '</td><td>';
				echo \gp\tool::date($langmessage['strftime_datetime'],$plugin_info['updated']);
				echo '</td><td>';

				$attr = array('data-cmd'=>'postlink', 'class'=>'gpconfirm','title'=>sprintf($langmessage['generic_delete_confirm'],$plugin_name));
				echo \gp\tool::Link($this->page->requested,$langmessage['delete'],'cmd=rmplugin&plugin='.rawurlencode($plugin_name), $attr );
				echo '</td></tr>';

			}
		}

		echo '<tr><td>';
		echo '<input type="hidden" name="cmd" value="upload_plugin" />';
		echo '<input type="file" name="plugin" />';
		echo '</td><td>&nbsp;';
		echo '</td><td>';
		echo ' <input type="submit" value="Install Plugin" />';
		echo '</td></tr>';

		echo '</table>';
		echo '</form>';


		//$this->build_config
		if( $this->build_config && isset($this->build_config['plugins']) ){

			$ordered = array();
			$count = 0;
			foreach($this->build_config['plugins'] as $plugin => $status){
				if( !$status ){
					continue;
				}

				$char				= strtoupper($plugin[0]);
				$ordered[$char][]	= '<a href="http://ckeditor.com/addon/'.$plugin.'" target="_blank">'.ucfirst($plugin).'</a>';
				$count++;
			}

			//echo '<h3>'.$langmessage['Installed'].'</h3>';
			echo '<p><br/></p>';
			echo '<table class="bordered">';
			echo '<tr><th colspan="2">'.$langmessage['Installed'].' ('.$count.')</th></tr>';
			foreach($ordered as $char => $plugins){
				echo '<tr><td>';
				echo '<b>'.$char.'</b>';
				echo '</td><td>';
				echo implode(', ',$plugins);
				echo '</td></tr>';
			}
			echo '</table>';
		}

	}


	/**
	 * Add an uploaded plugin
	 *
	 */
	function UploadPlugin(){
		global $langmessage, $dataDir;

		$archive = $this->UploadedArchive();
		if( !$archive ){
			return false;
		}



		// get plugin name and check file types
		$list			= $archive->ListFiles();
		$plugin_name	= '';
		$remove_path	= '';

		foreach($list as $file){

			//don't check extensions on folder
			if( $file['size'] == 0 ){
				continue;
			}

			//check extension
			if( !\gp\admin\Content\Uploaded::AllowedExtension($file['name'], false) ){
				msg($langmessage['OOPS'].' (File type not allowed:'.htmlspecialchars($file['name']).')');
				return false;
			}


			//plugin name
			if( strpos($file['name'],'plugin.js') !== false ){

				$new_plugin_name = $this->FindPluginName($archive, $file['name']);
				if( !$new_plugin_name ){
					continue;
				}

				//use the most relevant plugin name
				$new_path	= dirname($file['name']);
				if( !$plugin_name || strlen($new_path) < strlen($remove_path) ){
					$plugin_name	= $new_plugin_name;
					$remove_path	= $new_path;
				}
			}
		}


		if( !$this->CanUpload($plugin_name) ){
			return;
		}


		//extract to temporary location
		$extract_temp = $dataDir.\gp\tool\FileSystem::TempFile('/data/_temp/'.$plugin_name);
		if( !$archive->extractTo($extract_temp) ){
			\gp\tool\Files::RmAll($extract_temp);
			msg($langmessage['OOPS'].' (Couldn\'t extract to temp location)');
			return false;
		}


		//move to _ckeditor folder
		$destination = $dataDir.'/data/_ckeditor/'.$plugin_name;
		$rename_from = $extract_temp.'/'.ltrim($remove_path,'/');
		if( !\gp\tool\Files::Replace($rename_from, $destination) ){
			msg($langmessage['OOPS'].' (Not replaced)');
			return false;
		}


		// save configuration
		if( !array_key_exists( $plugin_name, $this->cke_config['plugins'] ) ){
			$this->cke_config['plugins'][$plugin_name] = array('installed'=>time());
		}

		$this->cke_config['plugins'][$plugin_name]['updated'] = time();
		$this->SaveConfig();

		msg($langmessage['SAVED']);
	}


	/**
	 * Get an archive object from the uploaded file
	 *
	 */
	function UploadedArchive(){
		global $langmessage;

		if( empty($_FILES['plugin']) ){
			msg($langmessage['OOPS'].' (No File)');
			return;
		}

		$plugin_file = $_FILES['plugin'];

		if( strpos($plugin_file['name'],'.zip') === false ){
			msg($langmessage['OOPS'].' (Not a zip file)');
			return;
		}

		//rename tmp file to have zip extenstion
		if( !\gp\tool\Files::Rename($plugin_file['tmp_name'], $plugin_file['tmp_name'].'.zip') ){
			msg($langmessage['OOPS'].' (Not renamed)');
			return;
		}

		$plugin_file['tmp_name'] .= '.zip';


		return new \gp\tool\Archive($plugin_file['tmp_name']);
	}


	/**
	 * Determine if we can upload the plugin
	 * @param string $plugin_name
	 * @return bool
	 */
	function CanUpload($plugin_name){
		global $langmessage;

		if( empty($plugin_name) ){
			msg($langmessage['OOPS'].' (Unknown plugin name)');
			return false;
		}


		//make sure plugin name isn't already in build_config
		if( $this->build_config
			&& isset($this->build_config['plugins'])
			&& isset($this->build_config['plugins'][$plugin_name])
			&& $this->build_config['plugins'][$plugin_name] > 0 ){
				msg($langmessage['addon_key_defined'], '<i>'.$plugin_name.'</i>');
				return false;
		}

		return true;
	}


	/**
	 * Get the plugin name from the plugin.js file
	 * use regular expression to look for "CKEDITOR.plugins.add('plugin-name'"
	 */
	function FindPluginName($archive, $name){

		$content = $archive->getFromName($name);

		if( !$content ){
			return false;
		}


		$pattern = '/CKEDITOR\s*\.\s*plugins\s*\.\s*add\s*\(\s*[\'"]([^\'"]+)[\'"]/';

		if( !preg_match($pattern,$content,$match) ){
			return false;
		}

		return $match[1];
	}


	/**
	 * Remove a plugin
	 *
	 */
	function RemovePlugin(){
		global $langmessage, $dataDir;

		$plugin =& $_REQUEST['plugin'];
		if( !is_array($this->cke_config['plugins']) || !array_key_exists( $plugin, $this->cke_config['plugins'] ) ){
			msg($langmessage['OOPS'].' ( )');
			return;
		}

		unset( $this->cke_config['plugins'][$plugin] );
		if( !$this->SaveConfig() ){
			msg($langmessage['OOPS'].' (Not Saved)');
		}else{
			msg($langmessage['SAVED']);
		}


		$path = $dataDir.'/data/_ckeditor/'.$plugin;
		\gp\tool\Files::RmAll( $path );
	}


	/**
	 * Display custom_config form
	 *
	 */
	function CustomConfigForm(){
		echo '<form method="post" action="'.\gp\tool::GetUrl($this->page->requested).'">';

		$placeholder = '{  "example_key":   "example_value"  }';
		echo '<p>';
		echo '<textarea name="custom_config" class="custom_config full_width" placeholder="'.htmlspecialchars($placeholder).'">';
		if( isset($_POST['custom_config']) ){
			echo htmlspecialchars($_POST['custom_config']);
		}elseif( !empty($this->cke_config['custom_config']) ){
			echo htmlspecialchars(self::ReadableJson($this->cke_config['custom_config']));
		}
		echo '</textarea>';
		echo '</p>';


		echo '<p>';
		echo '<input type="hidden" name="cmd" value="save_custom_config"/>';
		echo '<input type="submit" value="Save" data-cmd="gpajax"  class="gpsubmit" />';
		echo '</p>';

		echo '</form>';
	}

	/**
	 * Save custom_config value
	 *
	 */
	function SaveCustomConfig(){
		global $langmessage;

		$custom_config =& $_REQUEST['custom_config'];
		$decoded = array();
		if( !empty($custom_config) ){
			$decoded = json_decode($custom_config,true);
			if( !is_array($decoded) ){
				msg($langmessage['OOPS'].' (Invalid JSON String)');
				return false;
			}
		}

		$this->cke_config['custom_config'] = $decoded;

		if( !$this->SaveConfig() ){
			msg($langmessage['OOPS'].' (Not Saved)');
		}else{
			msg($langmessage['SAVED']);
		}

	}


	/**
	 * Show a CKEditor instance
	 *
	 */
	function Example(){

		$content = '<h3>Lorem Ipsum</h3> Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed tempor lectus id lectus laoreet scelerisque.</p><p>Vestibulum suscipit, lectus a feugiat facilisis, enim arcu fringilla nisi, et scelerisque nibh sapien in quam. Vivamus sit amet elementum nibh. Donec id ipsum nibh. Aliquam ligula nulla, condimentum sit amet consectetur eu, sagittis id ligula. In felis justo, feugiat et luctus sit amet, feugiat eget odio. Nullam suscipit mollis ipsum nec ultrices. Praesent ut lacus lorem. Fusce adipiscing arcu vitae dui ullamcorper a imperdiet felis dignissim. Maecenas eget tortor mi.</p>';
		\gp\tool\Editing::UseCK($content);
	}


	/**
	 * Display Current Configuration Settings
	 *
	 */
	function DisplayCurrent(){
		global $langmessage;

		echo '<h3>'.$langmessage['Current Configuration'].'</h3>';

		$default_config = \gp\tool\Editing::CKConfig(array(),'array');
		echo '<pre class="json">';
		echo self::ReadableJson($default_config);
		echo '</pre>';
	}


	/**
	 * Save the configuration file
	 *
	 */
	function SaveConfig(){
		return \gp\tool\Files::SaveData($this->config_file,'cke_config',$this->cke_config);
	}


	/**
	 * Get current configuration settings
	 *
	 */
	function Init(){

		$this->config_file		= '_ckeditor/config';
		$this->cke_config		= \gp\tool\Files::Get($this->config_file,'cke_config');

		//$this->cke_config 	+= array('custom_config'=>array());
		$this->cke_config		+= array('plugins'=>array());

		$this->BuildConfig();
	}


	/**
	 * Get the ckeditor build configuration
	 *
	 */
	function BuildConfig(){
		global $dataDir;



		//get data from build-config.js to determine which plugins are already included
		$build_file				= $dataDir.'/include/thirdparty/ckeditor_34/build-config.js';
		$build_config			= file_get_contents($build_file);
		if( !$build_config ){
			return;
		}


		// quotes
		$build_config			= str_replace('\'','"',$build_config);
		$build_config			= str_replace("\r\n", "\n", $build_config);

		// remove comments
		$build_config			= preg_replace("/\/\*[\d\D]*?\*\//",'',$build_config);

		// remove "var CKBUILDER_CONFIG = "
		$pos					= strpos($build_config,'{');
		$build_config			= substr($build_config,$pos);
		$build_config			= trim($build_config);
		$build_config			= trim($build_config,';');

		// fix variable names
		$build_config			= preg_replace("/([a-zA-Z0-9_]+?)\s*:/" , "\"$1\":", $build_config);

		$this->build_config		= json_decode($build_config,true);
	}




	/**
	 * Output an array in a readable json format
	 *
	 */
	static function ReadableJson($mixed){
		static $level = 0;

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