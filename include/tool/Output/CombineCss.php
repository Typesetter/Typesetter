<?php

namespace gp\tool\Output;

defined('is_running') or die('Not an entry point...');

/**
 * Get the contents of $file and fix paths:
 * 	- url(..)
 *	- @import
 * 	- @import url(..)
 */
class CombineCSS{

	public $content;
	public $file;
	public $full_path;
	public $imported = array();
	public $imports = '';

	public function __construct($file){
		global $dataDir;

		includeFile('thirdparty/cssmin_v.1.0.php');

		$this->file = $file;
		$this->full_path = $dataDir.$file;


		$this->content = file_get_contents($this->full_path);
		$this->content = \cssmin::minify($this->content);

		$this->CSS_Import();
		$this->CSS_FixUrls();
	}


	/**
	 * Include the css from @imported css
	 *
	 * Will include the css from these
	 * @import "../styles.css";
	 * @import url("../styles.css");
	 * @import styles.css;
	 *
	 *
	 * Will preserve the @import rule for these
	 * @import "styles.css" screen,tv;
	 * @import url('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.css.css');
	 *
	 */
	public function CSS_Import($offset=0){
		global $dataDir;

		$pos = strpos($this->content,'@import ',$offset);
		if( !is_numeric($pos) ){
			return;
		}
		$replace_start = $pos;
		$pos += 8;

		$replace_end = strpos($this->content,';',$pos);
		if( !is_numeric($replace_end) ){
			return;
		}

		$import_orig = substr($this->content,$pos,$replace_end-$pos);
		$import_orig = trim($import_orig);
		$replace_len = $replace_end-$replace_start+1;

		//get url(..)
		$media = '';
		if( substr($import_orig,0,4) == 'url(' ){
			$end_url_pos = strpos($import_orig,')');
			$import = substr($import_orig,4, $end_url_pos-4);
			$import = trim($import);
			$import = trim($import,'"\'');
			$media = substr($import_orig,$end_url_pos+1);
		}elseif( $import_orig[0] == '"' || $import_orig[0] == "'" ){
			$end_url_pos = strpos($import_orig,$import_orig[0],1);
			$import = substr($import_orig,1, $end_url_pos-1);
			$import = trim($import);
			$media = substr($import_orig,$end_url_pos+1);
		}


		// keep @import when the file is on a remote server?
		if( strpos($import,'//') !== false ){
			$this->imports .= substr($this->content, $replace_start, $replace_len );
			$this->content = substr_replace( $this->content, '', $replace_start, $replace_len);
			$this->CSS_Import($offset);
			return;
		}


		//if a media type is set, keep the @import
		$media = trim($media);
		if( !empty($media) ){
			$import = \gp\tool::GetDir(dirname($this->file).'/'.$import);
			$import = $this->ReduceUrl($import);
			$this->imports .= '@import url("'.$import.'") '.$media.';';
			$this->content = substr_replace( $this->content, '', $replace_start, $replace_len);
			$this->CSS_Import($offset);
			return;
		}


		//include the css
		$full_path = false;
		if( $import[0] != '/' ){
			$import = dirname($this->file).'/'.$import;
			$import = $this->ReduceUrl($import);
		}
		$full_path = $dataDir.$import;

		if( file_exists($full_path) ){

			$temp = new \gp\tool\Output\CombineCss($import);
			$this->content = substr_replace($this->content,$temp->content,$replace_start,$replace_end-$replace_start+1);
			$this->imported[] = $full_path;
			$this->imported = array_merge($this->imported,$temp->imported);
			$this->imports .= $temp->imports;

			$this->CSS_Import($offset);
			return;
		}

		$this->CSS_Import($pos);
	}


	public function CSS_FixUrls($offset=0){
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

		$this->CSS_FixUrl($url,$pos,$pos2);

		return $this->CSS_FixUrls($pos2);
	}

	public function CSS_FixUrl($url,$pos,$pos2){
		global $dataDir;

		$url = trim($url);
		$url = trim($url,'"\'');

		if( empty($url) ){
			return;
		}

		//relative url
		if( $url{0} == '/' ){
			return;
		}elseif( strpos($url,'://') > 0 ){
			return;
		}elseif( preg_match('/^data:/i', $url) ){
			return;
		}


		//use a relative path so sub.domain.com and domain.com/sub both work
		$replacement = \gp\tool::GetDir(dirname($this->file).'/'.$url);
		$replacement = $this->ReduceUrl($replacement);


		$replacement = '"'.$replacement.'"';
		$this->content = substr_replace($this->content,$replacement,$pos,$pos2-$pos);
	}

	/**
	 * Canonicalize a path by resolving references to '/./', '/../'
	 * Does not remove leading "../"
	 * @param string path or url
	 * @return string Canonicalized path
	 *
	 */
	public function ReduceUrl($url){

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

