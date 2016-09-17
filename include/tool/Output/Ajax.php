<?php

namespace gp\tool\Output{

	defined('is_running') or die('Not an entry point...');


	class Ajax{

		static $script_objects	= array(
									'/include/js/inline_edit/inline_editing.js'		=> 'gp_editing',
									'/include/thirdparty/ckeditor_34/ckeditor.js'	=> 'CKEDITOR',
									'/include/js/ckeditor_config.js'				=> 'CKEDITOR',
									);

		static function ReplaceContent($id,$content){
			self::JavascriptCall('WBx.response','replace',$id,$content);
		}

		static function JavascriptCall(){
			$args = func_get_args();
			if( !isset($args[0]) ){
				return;
			}

			echo array_shift($args);
			echo '(';
			$comma = '';
			foreach($args as $arg){
				echo $comma;
				echo self::quote($arg);
				$comma = ',';
			}
			echo ');';
		}

		static function quote($content){
			return \gp\tool::JsonEncode($content);
		}

		static function JsonEval($content){
			echo '{DO:"eval"';
			echo ',CONTENT:';
			echo self::quote($content);
			echo '},';
		}

		static function JsonDo($do,$selector,&$content){
			static $comma = '';
			echo $comma;
			echo '{DO:';
			echo self::quote($do);
			echo ',SELECTOR:';
			echo self::quote($selector);
			echo ',CONTENT:';
			echo self::quote($content);
			echo '}';
			$comma = ',';
		}


		/**
		 * Handle HTTP responses made with $_REQUEST['req'] = json (when <a ... data-cmd="gpajax">)
		 * Sends JSON object to client
		 *
		 */
		static function Response(){
			global $page;

			if( !is_array($page->ajaxReplace) ){
				die();
			}

			//admin toolbar
			self::AdminToolbar();

			//gadgets may be using gpajax/json request/responses
			\gp\tool\Output::TemplateSettings();
			\gp\tool\Output::PrepGadgetContent();


			echo self::Callback();
			echo '([';

			//output content
			if( !empty($_REQUEST['gpx_content']) ){
				switch($_REQUEST['gpx_content']){
					case 'gpabox':
						self::JsonDo('admin_box_data','',$page->contentBuffer);
					break;
				}
			}elseif( in_array('#gpx_content',$page->ajaxReplace) ){
				$replace_id = '#gpx_content';

				if( isset($_GET['gpreqarea']) ){
					$replace_id = '#'.$_GET['gpreqarea'];
				}

				ob_start();
				$page->GetGpxContent(true);
				$content = ob_get_clean();
				self::JsonDo('replace',$replace_id,$content);
			}

			//other areas
			foreach($page->ajaxReplace as $arguments){
				if( is_array($arguments) ){
					$arguments += array(0=>'',1=>'',2=>'');
					self::JsonDo($arguments[0],$arguments[1],$arguments[2]);
				}
			}


			//always send messages
			self::Messages();
			echo ']);';
			die();
		}


		/**
		 * Add the admin toolbar content to the ajax response
		 *
		 */
		static function AdminToolbar(){
			global $page;

			if( !isset($_REQUEST['gpreq_toolbar']) ){
				return;
			}

			ob_start();
			\gp\admin\Tools::AdminToolbar();
			$toolbar = ob_get_clean();
			if( empty($toolbar) ){
				return;
			}

			$page->ajaxReplace[] = array('replace','#admincontent_panel',$toolbar);
		}


		/**
		 * Add the messages to the response
		 *
		 */
		static function Messages(){

			ob_start();
			echo GetMessages(false);
			$content = ob_get_clean();
			if( !empty($content) ){
				self::JsonDo('messages','',$content);
			}
		}



		/**
		 * Check the callback parameter, die with an alert if the test fails
		 *
		 */
		static function Callback(){

			if( !isset($_REQUEST['jsoncallback']) ){
				self::InvalidCallback();
			}

			if( !preg_match('#^[a-zA-Z0-9_]+$#',$_REQUEST['jsoncallback'], $match) ){
				self::InvalidCallback();
			}
			return $match[0];
		}

		static function InvalidCallback(){

			echo '$gp.Response([';
			self::Messages();
			echo ']);';
			die();

		}


		/**
		 * Send a header for the javascript request
		 * Attempt to find an appropriate type within the accept header
		 *
		 */
		static function Header(){

			$accept = self::RequestHeaders('accept');
			$mime = 'application/javascript'; //default mime

			if( $accept && preg_match_all('#([^,;\s]+)\s*;?\s*([^,;\s]+)?#',$accept,$matches,PREG_SET_ORDER) ){
				$mimes = array('application/javascript','application/x-javascript','text/javascript');


				//organize by importance
				$accept = array();
				$i = 1;
				foreach($matches as $match){
					if( isset($match[2]) ){
						$accept[$match[1]] = $match[2];
					}else{
						$accept[$match[1]] = $i++;
					}
				}
				arsort($accept);

				//get matching mime
				foreach($accept as $part => $priority){
					if( in_array(trim($part),$mimes) ){
						$mime = $part;
						break;
					}
				}
			}

			//add charset
			header('Content-Type: '.$mime.'; charset=UTF-8');
		}


		/**
		 * Return a list of all headers
		 *
		 */
		static function RequestHeaders($which = false){
			$headers = array();
			foreach($_SERVER as $key => $value) {
				if( substr($key, 0, 5) <> 'HTTP_' ){
					continue;
				}

				$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));

				if( $which ){
					if( strnatcasecmp($which,$header) === 0){
						return $value;
					}
				}

				$headers[$header] = $value;
			}
			if( !$which ){
				return $headers;
			}
		}

		static function InlineEdit($section_data){

			$section_data			+= array('type'=>'','content'=>'');
			$scripts				= array();
			$scripts[]				= array('object'=>'gp_editing','file'=>'/include/js/inline_edit/inline_editing.js');



			$type = 'text';
			if( !empty($section_data['type']) ){
				$type = $section_data['type'];
			}
			switch($type){

				case 'gallery':
					$scripts = self::InlineEdit_Gallery($scripts);
				break;

				case 'include':
					$scripts = self::InlineEdit_Include($scripts);
				break;

				case 'text';
					$scripts = self::InlineEdit_Text($scripts);
				break;

				case 'image';
					echo 'var gp_blank_img = '.self::quote(\gp\tool::GetDir('/include/imgs/blank.gif')).';';
					$scripts[] = '/include/js/jquery.auto_upload.js';
					$scripts[] = '/include/js/inline_edit/image_common.js';
					$scripts[] = '/include/js/inline_edit/image_edit.js';
				break;
			}

			$scripts = \gp\tool\Plugins::Filter('InlineEdit_Scripts',array($scripts,$type));

			//replace resized images with their originals
			if( isset($section_data['resized_imgs']) && is_array($section_data['resized_imgs']) && count($section_data['resized_imgs']) ){
				$section_data['content'] = \gp\tool\Editing::RestoreImages($section_data['content'],$section_data['resized_imgs']);
			}

			//create the section object that will be passed to gp_init_inline_edit
			$section_object = \gp\tool::JsonEncode($section_data);


			//send scripts and call gp_init_inline_edit()
			echo '(function(){';
			self::SendScripts($scripts);

			echo ';if( typeof(gp_init_inline_edit) == "function" ){';
			echo 'gp_init_inline_edit(';
			echo self::quote($_GET['area_id']);
			echo ','.$section_object;
			echo ');';
			echo '}else{alert("gp_init_inline_edit() is not defined");}';
			echo '})();';
		}

		/**
		 * Send content of all files in the $scripts array to the client
		 *
		 */
		static function SendScripts($scripts){
			global $dataDir, $dirPrefix;

			self::Header();
			Header('Vary: Accept,Accept-Encoding');// for proxies

			$sent				= array();
			$scripts			= self::RemoveSent($scripts);


			//send all scripts
			foreach($scripts as $script){

				if( is_array($script) ){

					if( !empty($script['code']) ){
						echo "\n\n/** Code **/\n\n";
						echo $script['code'];
					}

					if( empty($script['file']) ){
						continue;
					}
					$script = $script['file'];
				}



				//absolute paths don't need $dataDir
				$full_path = $script;
				if( !empty($dataDir) && strpos($script,$dataDir) !== 0 ){

					//fix addon paths that use $addonRelativeCode
					if( !empty($dirPrefix) && strpos($script,$dirPrefix) === 0 ){
						$script = substr($script,strlen($dirPrefix));
					}
					$full_path = $dataDir.$script;
				}

				//only send each script once
				if( isset($sent[$full_path]) ){
					continue;
				}
				$sent[$full_path] = true;

				if( !file_exists($full_path) ){
					if( \gp\tool::LoggedIn() ){
						$msg = 'Admin Notice: The following file could not be found: \n\n'.$full_path;
						echo 'if(isadmin){alert('.json_encode($msg).');}';
					}
					continue;
				}

				echo "\n\n/** $script **/\n\n";
				readfile($full_path);
			}
		}


		/**
		 * Remove scripts that have already been sent to the server
		 *
		 */
		static function RemoveSent($scripts){

			$cleansed			= array();
			$defined_objects	= explode(',',$_REQUEST['defined_objects']);

			foreach($scripts as $script){

				$object = false;

				if( is_array($script) && !empty($script['object']) ){
					$object = $script['object'];

				}elseif( is_string($script) && isset(self::$script_objects[$script]) ){
					$object = self::$script_objects[$script];

				}

				if( $object !== false && in_array($object, $defined_objects) ){
					echo "\n\n/** Object Already Defined: ".$object." **/\n\n";
					continue;
				}


				$cleansed[] = $script;
			}

			return $cleansed;
		}


		/**
		 * Get scripts for editing inline text using ckeditor
		 *
		 */
		static function InlineEdit_Text($scripts){

			// autocomplete
			$scripts[]		= array(
								'code'		=> \gp\tool\Editing::AutoCompleteValues(true),
								'object'	=> 'gptitles',
								);

			// ckeditor basepath and configuration
			$options = array(
							'extraPlugins' => 'sharedspace',
							'sharedSpaces' => array( 'top' => 'ckeditor_top', 'bottom' =>' ckeditor_bottom' )
							);

			$ckeditor_basepath = \gp\tool::GetDir('/include/thirdparty/ckeditor_34/');
			echo 'CKEDITOR_BASEPATH = '.self::quote($ckeditor_basepath).';';

			// config
			$scripts[]		= array(
								'code'		=> 'var gp_ckconfig = '.\gp\tool\Editing::CKConfig( $options, 'json', $plugins ).';',
								'object'	=> 'gp_ckconfig',
								);


			// extra plugins
			$scripts[]		= array(
								'code'		=> 'var gp_add_plugins = '.json_encode( $plugins ).';',
								'object'	=> 'gp_add_plugins',
								);


			// CKEDITOR
			$scripts[]		= array(
								'file'		=> '/include/thirdparty/ckeditor_34/ckeditor.js',
								'object'	=> 'CKEDITOR',
								);

			$scripts[]		= array(
								'file'		=> '/include/js/ckeditor_config.js',
								'object'	=> 'CKEDITOR',
								);

			$scripts[] = '/include/js/inline_edit/inlineck.js';

			return $scripts;
		}

		static function InlineEdit_Include($scripts){
			$scripts[] = '/include/js/inline_edit/include_edit.js';
			return $scripts;
		}

		static function InlineEdit_Gallery($scripts){
			$scripts[] = '/include/js/jquery.auto_upload.js';
			$scripts[] = '/include/js/inline_edit/image_common.js';
			$scripts[] = '/include/js/inline_edit/gallery_edit_202.js';
			return $scripts;
		}

	}
}

namespace{
	class gpAjax extends \gp\tool\Output\Ajax{}
}
