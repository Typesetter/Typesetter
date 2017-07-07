<?php

namespace gp\tool\Output{

	defined('is_running') or die('Not an entry point...');

	class Sections{

		static $title	= '';
		static $label	= '';
		static $meta	= array();


		/**
		 * Loop through all sections and collect the fomatted content
		 * @param $sections array The unformatted data of the page
		 * @param $title string The title of the page
		 * @param $meta array Meta content of the page
		 * @return string
		 *
		 */
		static function Render($sections,$title,$meta = array()){
			self::SetVars($title,$meta);


			$content			= '';
			$section_num		= 0;
			$sections_count		= count($sections);
			$sections			= array_values($sections);

			while( $section_num < $sections_count ){
				$content .= self::GetSection($sections, $section_num);
			}

			return $content;
		}

		private static function GetSection($sections, &$section_num ){

			$content			= '';
			$curr_section_num	= $section_num;
			$section_data		= $sections[$curr_section_num];
			$section_num++;

			//make sure section_data is an array
			$type				= gettype($section_data);
			if( $type !== 'array' ){
				trigger_error('$section_data is '.$type.'. Array expected');
				return;
			}
			$section_data		+= array('attributes' => array() );

			if( $section_data['type'] == 'wrapper_section' ){
				if( isset($section_data['contains_sections']) ){
					for( $cc=0; $cc < $section_data['contains_sections']; $cc++ ){
						$content			.= self::GetSection($sections, $section_num);
					}
				}
			}else{
				$content				.= self::SectionToContent($section_data,$curr_section_num);
			}

			$is_hidden = 		isset($section_data['gp_hidden']) && $section_data['gp_hidden'] == 'true';
			$is_hidden = 		\gp\tool\Plugins::Filter('SectionIsHidden', array($is_hidden, $section_data, $curr_section_num));

			if( $is_hidden ){
				return ''; // this of course could be done a lot smarter
			}

			if( !isset($section_data['nodeName']) ){
				$content 			= '<div'.self::SectionAttributes($section_data['attributes'],$section_data['type']).'>'
									. $content
									. '<div class="gpclear"></div></div>';
			}else{

				if( empty($content) ){
					$content 			= '<'.$section_data['nodeName'].self::SectionAttributes($section_data['attributes'],$section_data['type']).' />';
				}else{
					$content 			= '<'.$section_data['nodeName'].self::SectionAttributes($section_data['attributes'],$section_data['type']).'>'
										. $content
										. self::EndTag($section_data['nodeName']);
				}

			}

			return $content;
		}


		/**
		 * Output an end tag if appropriate
		 *
		 */
		static function EndTag($node_name){

			$empty_nodes = array('link','track','param','area','command','col','base','meta','hr','source','img','keygen','br','wbr','input');
			if( in_array($node_name,$empty_nodes) ){
				return;
			}
			return '</'.$node_name.'>';
		}


		/**
		 * Render the content of a single page section
		 * @param $section array The unformatted data of the page
		 * @param $section_num int The unformatted data of the page
		 * @param $title string The title of the page
		 * @param $meta array Meta content of the page
		 * @return string
		 *
		 */
		static function RenderSection($section,$section_num,$title,$meta = array()){
			self::SetVars($title,$meta);
			return self::SectionToContent($section,$section_num);
		}


		/**
		 * Return the data types available for content areas
		 * @since 3.6
		 */
		static function GetTypes(){
			global $langmessage;
			static $types = false;

			if( !$types ){
				$types['text']['label']				= $langmessage['editable_text'];
				$types['image']['label']			= $langmessage['Image'];
				$types['gallery']['label']			= $langmessage['Image Gallery'];
				$types['include']['label']			= $langmessage['File Include'];
				$types['wrapper_section']['label']	= $langmessage['Section Wrapper'];

				$types = \gp\tool\Plugins::Filter('SectionTypes',array($types));
			}

			return $types;
		}



		static function SetVars($title,$meta){
			self::$title = $title;
			self::$label = \gp\tool::GetLabel($title);
			self::$meta = array();
			if( is_array($meta) ){
				self::$meta = $meta;
			}
		}


		/**
		 * Return formatted content for the $section_data
		 * @return string
		 *
		 */
		static function SectionToContent($section_data,$section_num){

			$section_data = \gp\tool\Plugins::Filter('SectionToContent',array($section_data,$section_num));

			switch($section_data['type']){
				case 'text':
				return self::TextContent($section_data['content']);

				case 'include':
				return self::IncludeContent($section_data);

				case 'gallery':
					\gp\tool::ShowingGallery();
				return $section_data['content'];
			}
			return $section_data['content'];
		}

		/**
		 * Replace content variables in $content
		 *
		 */
		static function TextContent(&$content){

			self::$meta += array('modified'=>'');

			//variables
			$vars = array(
				'dirPrefix'		=> $GLOBALS['dirPrefix'],
				'linkPrefix'	=> \gp\tool::HrefEncode($GLOBALS['linkPrefix']),
				'fileModTime'	=> self::$meta['modified'],
				'title'			=> self::$title,
				'label'			=> self::$label,
				'currentYear'	=> date('Y'),
				'currentMonths'	=> date('m'),
				'currentDay'	=> date('d'),
				);
			$vars = \gp\tool\Plugins::Filter('ReplaceContentVars', array($vars));

			$offset = 0;
			$i = 0;
			do{
				$i++;

				$pos = strpos($content,'$',$offset);
				if( $pos === false ){
					break;
				}

				//escaped?
				if( $pos > 0 ){
					$prev_char = $content{$pos-1};
					if( $prev_char == '\\' ){
						$offset = $pos+1;
						continue;
					}
				}

				$len = strspn($content,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',$pos+1);
				if( $len == 0 ){
					$offset = $pos+1;
					continue;
				}

				$var = substr($content,$pos+1,$len);
				if( isset($vars[$var]) ){
					$content = substr_replace($content,$vars[$var],$pos,$len+1);
				}

				$offset = $pos+$len;

			}while(true);

			/* Testing old includes system ... this breaks editing */
			self::ReplaceContent($content);

			return $content;
		}

		/**
		 * Include the content of another page into the current content by replacing {{--page_name--}} with the content of page_name
		 * @param string $content The content of the current page
		 * @param int $offset The current string position of the page parser
		 *
		 */
		static function ReplaceContent(&$content,$offset=0){
			global $gp_index;
			static $includes = 0;

			//prevent too many inlcusions
			if( $includes >= 10 ){
				return;
			}

			$pos = strpos($content,'{{',$offset);
			if( $pos === false ){
				return;
			}
			$pos2 = strpos($content,'}}',$pos);
			if( $pos2 === false ){
				return;
			}

			$title = substr($content,$pos+2,$pos2-$pos-2);
			$title = str_replace(' ','_',$title);
			if( !isset($gp_index[$title]) ){
				self::ReplaceContent($content,$pos2);
				return;
			}


			$file			= \gp\tool\Files::PageFile($title);
			$file_sections	= \gp\tool\Files::Get($file,'file_sections');

			if( !$file_sections ){
				self::ReplaceContent($content,$pos2);
				return;
			}

			$includes++;
			$replacement = '';
			foreach($file_sections as $section_num => $section_data){
				$replacement .= '<div class="gpinclude" title="'.$title.'" >'; //contentEditable="false"
				$replacement .= self::SectionToContent($section_data,$section_num);
				$replacement .= '</div>';
			}

			//is {{...}} wrapped by <p>..</p>?
			$pos3 = strpos($content,'</p>',$pos2);
			if( $pos3 > 0 ){
				$pieceAfter = substr($content,$pos2,($pos3-$pos2));
				if( strpos($pieceAfter,'<') == false ){
					$replacement = "</p>\n".$replacement."\n<p>";
				}
			}

			$content = substr_replace($content,$replacement,$pos,$pos2-$pos+2);
			self::ReplaceContent($content,$pos);
		}


		/**
		 * Include the content of a page or gadget as specified in $data
		 * @param array $data
		 * @param string The included content
		 */
		static function IncludeContent($data){
			global $langmessage, $gp_index;

			if( isset($data['index']) ){
				$requested = \gp\tool::IndexToTitle($data['index']);
			}else{
				$requested = $data['content'];
			}

			if( empty($requested) ){
				return '<p>'.$langmessage['File Include'].'</p>';
			}

			if( self::$title == $requested ){
				if( \gp\tool::LoggedIn() ){
					msg('Infinite loop detected: '.htmlspecialchars($requested) );
				}
				return;
			}

			if( isset($data['include_type']) ){
				$type = $data['include_type'];
			}else{
				$type = \gp\tool::SpecialOrAdmin($requested);
			}

			switch($type){
				case 'gadget':
				return self::IncludeGadget($requested);

				case 'special':
				return self::IncludeSpecial($requested);

				case 'extra':
				return self::IncludeExtra($requested);

				default:
				return self::IncludePage($requested);
			}
		}


		/**
		 * Include the content of a gadget
		 * @param string $requested The name of the gadget to include
		 *
		 */
		static function IncludeGadget($requested){
			global $config;

			if( !isset($config['gadgets'][$requested]) ){
				return '{{Gadget Not Found: '.htmlspecialchars($requested).'}}';
			}

			ob_start();
			\gp\tool\Output::GetGadget($requested);
			return ob_get_clean();
		}


		/**
		 * Include the content of a special page
		 * @param string $requested The name of the special page to include
		 *
		 */
		static function IncludeSpecial($requested){
			global $langmessage;

			$scriptinfo = \gp\special\Page::GetScriptInfo( $requested, false );

			if( $scriptinfo === false ){
				return '<p>'.$langmessage['File Include'].'</p>';
			}

			return \gp\special\Page::ExecInfo($scriptinfo);
		}


		/**
		 * Include the content of an extra content area
		 * @param string $requested The name of the extra content to include
		 *
		 */
		static function IncludeExtra($requested){
			if( \gp\admin\Content\Extra::AreaExists($requested) === false && \gp\admin\Content\Extra::AreaExists($requested.'.php') === false ){
				return '{{Extra Area Not Found: '.htmlspecialchars($requested).'}}';
			}

			ob_start();
			\gp\tool\Output::GetExtra($requested);
			return ob_get_clean();
		}


		/**
		 * Include the content of a normal page
		 * @param string $requested The name of the page to include
		 *
		 */
		static function IncludePage($requested){
			global $gp_index;

			$requested = str_replace(' ','_',$requested);

			if( !isset($gp_index[$requested]) ){
				return '{{'.htmlspecialchars($requested).'}}';
			}

			$file			= \gp\tool\Files::PageFile($requested);
			$file_sections	= \gp\tool\Files::Get($file,'file_sections');

			if( !$file_sections ){
				return '{{'.htmlspecialchars($requested).'}}';
			}

			return self::Render($file_sections,self::$title,self::$meta);
		}

		/**
		 * Convert array of html attributes into a string for output
		 *
		 */
		static function SectionAttributes($attrs,$type){

			switch($type){
				case 'image':
					$attrs['src'] = \gp\tool::GetDir($attrs['src']);
				break;
			}


			$attrs				+= array('class' => '' );
			$attrs['class']		= trim('GPAREA filetype-'.$type.' '.$attrs['class']);

			$attr_string = '';
			foreach($attrs as $attr => $value){
				$attr_string .= ' '.htmlspecialchars($attr).'="'.htmlspecialchars($value).'"';
			}

			return $attr_string;
		}

	}
}

namespace {
	class section_content extends \gp\tool\Output\Sections{}
}