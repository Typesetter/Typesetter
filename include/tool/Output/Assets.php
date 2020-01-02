<?php

namespace gp\tool\Output;

class Assets{


	/**
	 * Combine the files in $files into a combine.php request
	 * If $page->head_force_inline is true, resources will be
	 * included inline in the document
	 *
	 * @param array $files Array of files relative to $dataDir
	 * @param string $type The type of resource being combined
	 *
	 */
	public static function CombineFiles($files,$type,$combine){
		global $page;

		// allow arrays of scripts
		$files_flat = [];

		//only need file paths
		foreach($files as $key => $val){

			// single file path string
			if( !is_array($val) ){
				$files_flat[$key] = $val;

			// single script array
			}elseif( isset($val['file']) ){
				$files_flat[$key] = $val['file'];

			// multiple scripts
			}else{
				foreach( $val as $subkey => $file ){
					$files_flat[$key . '-' . $subkey] = is_array($file) ? $file['file'] : $file;
				}
			}
		}

		$files_flat = array_unique($files_flat);
		$files_flat = array_filter($files_flat);//remove empty elements

		// Force resources to be included inline
		// CheckFile will fix the $file path if needed
		if( $page->head_force_inline ){
			self::Inline($type, $files_flat );
			return;
		}


		//files not combined except for script components
		if( !$combine || (isset($_REQUEST['no_combine']) && \gp\tool::LoggedIn()) ){
			foreach($files_flat as $file_key => $file){

				\gp\tool\Output\Combine::CheckFile($file);
				if( \gp\tool::LoggedIn() ){
					$file .= '?v=' . rawurlencode(gpversion);
				}
				echo self::FormatAsset($type, \gp\tool::GetDir($file, true) );

			}
			return;
		}


		//create combine request
		$combined_file = \gp\tool\Output\Combine::GenerateFile($files_flat,$type);
		if( $combined_file === false ){
			return;
		}

		echo self::FormatAsset($type, \gp\tool::GetDir($combined_file, true) );
	}


	/**
	 * Add CSS files to the array
	 * Convert .scss & .less files to .css
	 *
	 */
	public static function MergeScripts($scripts, $to_add){
		global $dataDir;

		if( !is_array($to_add) ){
			return $scripts;
		}

		foreach($to_add as $key => $script){

			$files = [];

			// single file path string
			if( !is_array($script) ){
				$file = $script;
				$ext = \gp\tool::Ext($file);
				$files[$ext] = [$dataDir . $file];

			// single file array
			}elseif( isset($script['file']) ){
				$file = $script['file'];
				$ext = \gp\tool::Ext($file);
				$files[$ext] = [$dataDir . $file];

			// multiple scripts
			}else{
				foreach( $script as $file ){
					$file = is_array($file) ? $file['file'] : $file;
					$ext = \gp\tool::Ext($file);
					$files[$ext][] = $dataDir . $file;
				}
			}

			foreach( $files as $ext => $files_same_ext ){

				//less and scss
				if( $ext == 'less' || $ext == 'scss' ){
					$to_add[$key] = \gp\tool\Output\Css::Cache($files_same_ext, $ext);
				}
			}

		}

		return array_merge($scripts,$to_add);
	}

	/**
	 * Format <script> and <link> tags for asset urls
	 *
	 */
	public static function FormatAsset($type, $url){
		if( $type == 'css' ){
			return "\n" . '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
		}else{
			return "\n" .'<script type="text/javascript" src="' . $url . '"></script>';
		}
	}


	/**
	 * Output the javascript or css assets inline with the html
	 *
	 */
	public static function Inline($type, $files_flat){

		 if( $type == 'css' ){
			 echo '<style type="text/css">';
		 }else{
			 echo '<script type="text/javascript">';
		 }
		 foreach($files_flat as $file_key => $file){
			 $full_path = \gp\tool\Output\Combine::CheckFile($file);
			 if( $full_path === false ) continue;
			 readfile($full_path);
			 echo ";\n";
		 }
		 if( $type == 'css' ){
			 echo '</style>';
		 }else{
			 echo '</script>';
		 }
	 }


	/**
	 * Return a list of css files used by the current layout
	 *
	 */
	public static function LayoutStyleFiles(){
		global $page, $dataDir;


		$files			= array();
		$dir			= $page->theme_dir . '/' . $page->theme_color;
		$style_type		= \gp\tool\Output::StyleType($dir);


		//css file
		if( $style_type == 'css' ){

			$files[]		= rawurldecode($page->theme_path) . '/style.css';
			$files			= self::AddCustomStyleFiles($files, $style_type);
			return $files;
		}


		//less or scss file
		$var_file	= $dir .'/variables.' . $style_type;
		if( file_exists($var_file) ){
			$files[] = $var_file;
		}

		$files			= self::AddCustomStyleFiles($files, $style_type);

		if( $style_type == 'scss' ){

			$files[]		= $dir . '/style.scss';
			return array( \gp\tool\Output\Css::Cache($files) );
		}

		array_unshift($files, $dir . '/style.less');

		return array( \gp\tool\Output\Css::Cache($files, 'less') );
	}


	/**
	 * Add paths for custom css/scss/less files
	 *
	 */
	public static function AddCustomStyleFiles($files, $style_type){
		global $dataDir, $page;

		if( $page->gpLayout === false ){
			return $files;
		}

		$file_ext = $style_type == 'scss' ? 'scss' : 'css';

		$customizer_style_file 		= $dataDir . '/data/_layouts/' . $page->gpLayout . '/customizer.' . $file_ext;
		$layout_editor_style_file 	= $dataDir . '/data/_layouts/' . $page->gpLayout . '/custom.' . $file_ext;


		if( file_exists($customizer_style_file) ){
			$files[] = $customizer_style_file;
		}

		if( file_exists($layout_editor_style_file) ){
			$files[] = $layout_editor_style_file;
		}

		return $files;
	}

}
