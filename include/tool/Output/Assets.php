<?php

namespace gp\tool\Output;

class Assets{


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

}
