<?php

if( !defined('is_running' ) ){
	$start_time = microtime();
	define('is_running',true);
	//define('gpdebug',true);
	define('gp_cookie_cmd',false);
	define('gp_dev_combine',false); //prevents cache and 304 header when set to true

	require_once('common.php');
	common::EntryPoint(1,'combine.php',false);
	new gp_combine();
}

/*
global $debug_str;
$debug_str = '';
function combine_debug($string=''){
	global $debug_str;
	$debug_str .= "\n".$string;
}
*/


class gp_combine{

	var $last_modified = false;
	var $content_length = 0;

	var $css_data_file;
	var $css_data = array();
	var $css_data_changed = false;
	var $css_data_mod_time = false;

	function gp_combine(){
		global $debug_str;


		if( isset($_GET['css']) ){
			$this->Combine_CSS();
		}elseif( isset($_GET['js']) || isset($_GET['scripts']) ){
			$this->Combine_JS();
		}else{
			header('Not Implemented',true,503);
			return;
		}

		$this->CheckLastModified();

		if( gpdebug && !empty($debug_str) ){
			echo '/* ';
			echo $debug_str;
			echo '*/';
		}
	}



	/**
	 *
	 * @static
	 */
	function GenerateEtag($array){

		$modified = 0;
		$content_length = 0;

		foreach($array as $file_key => $file){
			$full_path = gp_combine::CheckFile($file,false);

			if( $full_path === false ){
				continue;
			}
			gp_combine::FileStat_Static($full_path,$modified,$content_length);
		}

		return common::GenEtag( $modified, $content_length );
	}

	// !cannot use php sessions, session_start() will remove $_SERVER['HTTP_IF_NONE_MATCH']
	function CheckLastModified(){

		//echo "\n\n/*\n";
		//print_r($_SERVER);
		//echo "\n*/";

		if( gp_dev_combine ){
			return;
		}

		if( $this->last_modified == 0 ){
			return;
		}


		// use extended max-age value
		header('Cache-Control: public, max-age=5184000');//60 days

		//attempt to send an 304 response
		$etag = common::GenEtag( $this->last_modified, $this->content_length );
		common::Send304($etag);
	}


	// not minimizing javascript so we don't cache anything
	// could potentially minimize using https://github.com/rgrove/jsmin-php/
	function Combine_JS(){
		global $dataDir;

		header('Content-type: application/x-javascript');

		if( count($_GET['js']) ){
			common::jsStart();
			echo "\n";
		}

		if( !empty($_GET['scripts']) ){
			$scripts = gp_combine::ScriptDependencies( $_GET['scripts'] );
			foreach($scripts as $script){
				if( !$script ){
					continue;
				}
				$full_path = realpath($dataDir.'/include/thirdparty/jquery_ui/'.$script);
				if( $full_path === false ){
					continue;
				}

				$this->FileStat($full_path);

				readfile($full_path);
				echo ";\n";
			}
		}


		//echo "/* combined js */\n";
		foreach($_GET['js'] as $file){
			$full_path = gp_combine::CheckFile($file);

			if( $full_path === false ){
				continue;
			}
			$this->FileStat($full_path);

			readfile($full_path);
			echo ";\n";
		}
		echo '/* done */';
	}


	/**
	 * Make sure the fiel is a css or js file and that it exists
	 * @static
	 */
	function CheckFile(&$file,$css_comments = true){
		global $dataDir, $dirPrefix, $dirPrefixEncoded;

		$file = gp_combine::TrimQuery($file);

		if( $css_comments ){
			$comment_start = '/*';
			$comment_end = '*/';
		}else{
			$comment_start = '<!--';
			$comment_end = '-->';
		}

		if( empty($file) ){
			return false;
		}

		//remove null charachters
		$file = gpFiles::NoNull($file);

		//require .js or .css
		$test = strtolower($file);
		if( substr($test, -3) != '.js' && substr($test, -4) != '.css' ){
			echo  "\n{$comment_start} File Not CSS or JS {$file} {$comment_end}\n";
			return false;
		}

		//paths that have been urlencoded
		if( strpos($file,'%') !== false ){
			$decoded_file = rawurldecode($file);
			if( $full_path = gp_combine::CheckFileSub($decoded_file) ){
				$file = $decoded_file;
				return $full_path;
			}
		}

		//paths that have not been encoded
		if( $full_path = gp_combine::CheckFileSub($file) ){
			return $full_path;
		}


		echo  "\n{$comment_start} File Not Found {$dataDir}{$file} {$comment_end}\n";
		return false;
	}

	function CheckFileSub(&$file){
		global $dataDir, $dirPrefix;

		//realpath returns false if file does not exist
		$full_path = realpath($dataDir.$file);
		if( $full_path ){
			return $full_path;
		}


		//check for paths that have already included $dirPrefix
		if( empty($dirPrefix) ){
			return false;
		}

		if( strpos($file,$dirPrefix) === 0 ){
			$fixed = substr($file,strlen($dirPrefix));
			$full_path = realpath($dataDir.$fixed);
			if( $full_path ){
				$file = $fixed;
				return $full_path;
			}
		}

		return false;
	}

	function Combine_CSS(){

		header('Content-type: text/css');
		$this->CSSDataGet();

		$this->GetFiles($_GET['css']);

		$this->CleanCache();
		$this->SaveCSSData();
	}



	//regularly purge an entry off the cache. If needed, it will be added again to the end of the data file
	function CleanCache(){
		global $dataDir;

		if( $this->css_data_mod_time === false ){
			return;
		}

		//check frequency: once a day
		$diff = time() - $this->css_data_mod_time;
		if( $diff < 86400 ){
			return;
		}


		reset($this->css_data);
		$file = key($this->css_data);
		$info = $this->css_data[$file];

		unset($this->css_data[$file]);
		$cache_file = $dataDir.'/data/_cache/'.$info['min'];
		if( file_exists($cache_file) ){
			unlink($cache_file);
		}

		$this->css_data_changed = true;
	}

	//clean the cache before saving
	function SaveCSSData(){

		if( !$this->css_data_changed ){
			return;
		}

		gpFiles::SaveArray($this->css_data_file,'css_data',$this->css_data);

	}

	function GetFiles($array){
		global $dataDir;
		static $files_used = array();

		foreach($array as $file){

			$cache_info = $this->CacheInfo($file);
			if( $cache_info === false ){
				continue;
			}

			//prevent circular @import references
			if( isset($files_used[$file]) ){
				continue;
			}
			$files_used[$file] = true;

			//get imported files first
			if( isset($cache_info['import']) && is_array($cache_info['import']) ){
				$this->GetFiles($cache_info['import']);
			}

			//the cache file should exist
			$cache_file = $dataDir.'/data/_cache/'.$cache_info['min'];
			if( file_exists($cache_file) ){
				$this->FileStat($cache_file);
				readfile($cache_file);
				echo "\n";
			}
		}

	}



	//	check minimized cache file against actual file
	//	return info about the cache file
	function CacheInfo($file){
		global $dataDir;

		$full_path = gp_combine::CheckFile($file);
		if( $full_path === false ){
			return false;
		}

		if( !isset($this->css_data[$file]) ){
			return $this->CacheCSS($file);
		}

		$cache_info = $this->css_data[$file];
		$cache_file = $dataDir.'/data/_cache/'.$cache_info['min'];

		if( !file_exists($cache_file) ){
			return $this->CacheCSS($file);
		}

		//check size and mod time
		$orig_mod = filemtime($full_path);
		$orig_len = filesize($full_path);
		if( ($cache_info['mod'] != $orig_mod) || ($cache_info['len'] != $orig_len) ){
			return $this->CacheCSS($file);
		}

		return $cache_info;
	}

	function CacheCSS($file){
		global $dataDir;

		$cache_info = array();

		$cache_info['min'] = md5($file).'.css';
		$cache_file = $dataDir.'/data/_cache/'.$cache_info['min'];
		//echo "\n cache file: ".$cache_file;


		//get all the cached files
		$temp = new gp_combine_css($file);
		//print_r($temp);
		gpFiles::Save($cache_file,$temp->content);

		$full_path = $dataDir.$file;
		$cache_info['mod'] = filemtime($full_path);
		$cache_info['len'] = filesize($full_path);
		if( is_array($temp->files) && count($temp->files) > 0 ){
			$cache_info['import'] = $temp->files;
		}

		$this->css_data[$file] = $cache_info;
		$this->css_data_changed = true;

		return $cache_info;
	}

	function CSSDataGet(){
		global $dataDir;

		$this->css_data_file = $dataDir.'/data/_cache/css_data.php';

		if( file_exists($this->css_data_file) ){
			include($this->css_data_file);
			$this->css_data = $css_data;
			$this->css_data_mod_time = $fileModTime;
		}

	}


	/*
	 *
	 * @static
	 */
	function TrimQuery($file){
		$pos = strpos($file,'?');
		if( $pos > 0 ){
			$file = substr($file,0,$pos);
		}
		return trim($file);
	}

	function FileStat($file_path){
		return gp_combine::FileStat_Static( $file_path, $this->last_modified, $this->content_length );
	}

	function FileStat_Static( $file_path, &$modified, &$content_length ){
		$content_length += @filesize($file_path);
		$modified = max( $modified, @filemtime($file_path) );
		return $modified;
	}


	function ScriptDependencies($components){

		if( is_string($components) ){
			$components = explode(',',strtolower($components));
		}
		$scripts['theme'] = false;

		//core
		$scripts['ui-core'] = 'ui.core.js';
		$scripts['mouse'] = 'ui.mouse.js';
		$scripts['position'] = 'ui.position.js';
		$scripts['widget'] = 'ui.widget.js';

		//interactions
		$scripts['draggable'] = 'ui.draggable.js';
		$scripts['droppable'] = 'ui.droppable.js';
		$scripts['resizable'] = 'ui.resizable.js';
		$scripts['selectable'] = 'ui.selectable.js';
		$scripts['sortable'] = 'ui.sortable.js';

		//widgets
		$scripts['accordion'] = 'ui.accordion.js';
		$scripts['autocomplete'] = 'ui.autocomplete.js';
		$scripts['button'] = 'ui.button.js';
		$scripts['datepicker'] = 'ui.datepicker.js';
		$scripts['dialog'] = 'ui.dialog.js';
		$scripts['progressbar'] = 'ui.progressbar.js';
		$scripts['slider'] = 'ui.slider.js';
		$scripts['tabs'] = 'ui.tabs.js';

		//effects
		$scripts['effects-core'] = 'effects.core.js';
		$scripts['blind'] = 'effects.blind.js';
		$scripts['bounce'] = 'effects.bounce.js';
		$scripts['clip'] = 'effects.clip.js';
		$scripts['drop'] = 'effects.drop.js';
		$scripts['explode'] = 'effects.explode.js';
		$scripts['fade'] = 'effects.fade.js';
		$scripts['fold'] = 'effects.fold.js';
		$scripts['highlight'] = 'effects.highlight.js';
		$scripts['pulsate'] = 'effects.pulsate.js';
		$scripts['scale'] = 'effects.scale.js';
		$scripts['shake'] = 'effects.shake.js';
		$scripts['slide'] = 'effects.slide.js';
		$scripts['transfer'] = 'effects.transfer.js';


		//core
		$dependencies['mouse'] = array('ui-core','widget');
		$dependencies['position'] = array();
		$dependencies['widget'] = array();

		//interactions
		$dependencies['draggable'] = array('ui-core', 'widget', 'mouse');
		$dependencies['droppable'] = array('ui-core', 'widget', 'mouse', 'draggable');
		$dependencies['resizable'] = array('ui-core', 'widget', 'mouse', 'theme');
		$dependencies['selectable'] = array('ui-core', 'widget', 'mouse', 'theme');
		$dependencies['sortable'] = array('ui-core', 'widget', 'mouse');

		//widgets
		$dependencies['accordion'] = array('ui-core', 'widget', 'theme');
		$dependencies['autocomplete'] = array('ui-core', 'widget', 'position', 'theme');
		$dependencies['button'] = array('ui-core', 'widget', 'theme');
		$dependencies['datepicker'] = array('ui-core', 'theme');
		$dependencies['dialog'] = array('ui-core', 'widget', 'position', 'theme');
		$dependencies['progressbar'] = array('ui-core', 'widget', 'theme');
		$dependencies['slider'] = array('ui-core', 'widget', 'mouse', 'theme');
		$dependencies['tabs'] = array('ui-core', 'widget', 'theme');


		//effects
		$dependencies['blind'] = array('effects-core');
		$dependencies['bounce'] = array('effects-core');
		$dependencies['clip'] = array('effects-core');
		$dependencies['drop'] = array('effects-core');
		$dependencies['explode'] = array('effects-core');
		$dependencies['fade'] = array('effects-core');
		$dependencies['fold'] = array('effects-core');
		$dependencies['highlight'] = array('effects-core');
		$dependencies['pulsate'] = array('effects-core');
		$dependencies['scale'] = array('effects-core');
		$dependencies['shake'] = array('effects-core');
		$dependencies['slide'] = array('effects-core');
		$dependencies['transfer'] = array('effects-core');


		$all_scripts = array();
		foreach($components as $component){
			if( !isset($scripts[$component]) ){
				continue;
			}
			if( isset($dependencies[$component]) ){
				$all_scripts += gp_combine::ScriptDependencies($dependencies[$component]);
			}
			$all_scripts[$component] = $scripts[$component];
		}
		return $all_scripts;
	}
}


/*
 * Get the contents of $file and fix paths:
 * 	- url(..)
 *	- @import
 * 	- @import url(..)
 */
class gp_combine_css{
	var $content;
	var $files = array();

	function gp_combine_css($file,$import=false){
		global $dataDir;

		includeFile('thirdparty/cssmin-v3.0.1.php');

		$full_path = $dataDir.$file;
		if( $import ){
			$this->files[] = $file; //only going to track @import files
		}

		//combine_debug('gp_combine_css: '.$file.' len: '.filesize($full_path));


		$this->content = file_get_contents($full_path);
		$this->content = CssMin::minify($this->content);

		$this->CSS_Import(0,$file);
		$this->CSS_FixUrls(0,$file);

	}


	//@import "../styles.css";
	//@import url("../styles.css");
	function CSS_Import($offset=0,$file){

		$pos = strpos($this->content,'@import ',$offset);
		if( !is_numeric($pos) ){
			return;
		}
		$replace_start = $pos;
		$pos += 8;

		$pos2 = strpos($this->content,';',$pos);
		if( !is_numeric($pos2) ){
			return;
		}

		//combine_debug('new import');
		$import = substr($this->content,$pos,$pos2-$pos);
		$import = trim($import);


		//trim url(..)
		if( substr($import,0,4) == 'url(' ){
			$import = substr($import,4);
			$import = substr($import,0,-1);
			$import = trim($import);
			//combine_debug('remove url(..)');
		}

		$import = trim($import,'"\'');
		//combine_debug('import: '.$import);


		//how to handle @import when the file is on a remote server?
		if( strpos($import,'://') > 0 ){
			$this->CSS_Import($pos2,$file);
			return;
		}

		if( $import{0} != '/' ){
			$new_import = dirname($file).'/'.$import;
		}else{
			$new_import = $import;
		}


		$replacement = '';
		$this->files[] = $this->ReduceUrl($new_import);
		$this->content = substr_replace($this->content,$replacement,$replace_start,$pos2-$replace_start+1);
		$this->CSS_Import(0,$file);
	}

	//http://www.weirdlover.com/2010/05/28/css-url/
	function CSS_FixUrls($offset=0,$file){
		$pos = strpos($this->content,'url(',$offset);
		if( !is_numeric($pos) ){
			return;
		}
		$pos += 4;

		$pos2 = strpos($this->content,')',$pos);
		if( !is_numeric($pos2) ){
			return;
		}
		$url = substr($this->content,$pos,$pos2-$pos);

		//combine_debug('file '.$file);
		$this->CSS_FixUrl($url,$file,$pos,$pos2);

		return $this->CSS_FixUrls($pos2,$file);
	}

	function CSS_FixUrl($url,$file,$pos,$pos2){

		$url = trim($url);
		$url = trim($url,'"\'');

		//relative url
		if( $url{0} == '/' ){
			return;
		}elseif( strpos($url,'://') > 0 ){
			return;
		}elseif( preg_match('/^data:/i', $url) ){
			return;
		}


		//use a relative path so sub.domain.com and domain.com/sub both work
		$replacement = '..'.dirname($file).'/'.$url;
		//$replacement = common::GetDir(dirname($file).'/'.$url);

		$replacement = $this->ReduceUrl($replacement);

		$replacement = '"'.$replacement.'"';
		$this->content = substr_replace($this->content,$replacement,$pos,$pos2-$pos);
		//combine_debug('url replacement '.$replacement);
	}

	/**
	 * Canonicalize a path by resolving references to '/./', '/../'
	 * Does not remove leading "../"
	 * @param string path or url
	 * @return string Canonicalized path
	 *
	 */
	function ReduceUrl($url){

		$temp = explode('/',$url);
		$result = array();
		foreach($temp as $i => $path){
			if( $path == '.' ){
				continue;
			}
			if( $path == '..' ){
				for($j=$i-1;$j>0;$j--){
					if( isset($result[$j]) ){
						unset($result[$j]);
						continue 2;
					}
				}
			}
			$result[$i] = $path;
		}

		return implode('/',$result);
	}

}