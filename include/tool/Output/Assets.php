<?php

namespace gp\tool\Output;

class Assets{


	/**
	 * Add CSS files to the array
	 * Convert .scss & .less files to .css
	 *
	 */
	public function MergeScripts($scripts, $to_add){

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


}
