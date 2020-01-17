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
		public static function Render($sections,$title,$meta = array()){
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

		public static function GetSection($sections, &$section_num ){

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
		public static function EndTag($node_name){

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
		public static function RenderSection($section,$section_num,$title,$meta = array()){
			self::SetVars($title,$meta);
			return self::SectionToContent($section,$section_num);
		}


		/**
		 * Return the data types available for content areas
		 * @since 3.6
		 */
		public static function GetTypes(){
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



		public static function SetVars($title,$meta){
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
		public static function SectionToContent($section_data,$section_num){

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
		public static function TextContent(&$content){
			global $dirPrefix, $linkPrefix;

			self::$meta += array('modified'=>'');

			//variables
			$vars = array(
				'dirPrefix'		=> $dirPrefix,
				'linkPrefix'	=> \gp\tool::HrefEncode($linkPrefix),
				'fileModTime'	=> self::$meta['modified'],
				'title'			=> self::$title,
				'label'			=> self::$label,
				'currentYear'	=> date('Y'),
				'currentMonths'	=> date('m'),
				'currentDay'	=> date('d'),
				);
			$vars = \gp\tool\Plugins::Filter('ReplaceContentVars', array($vars));

			preg_match_all('#(\\\*)\$([a-zA-Z]+)#',$content,$matches,PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
			$matches = array_reverse($matches);
			foreach($matches as $match){

				$match_start	= $match[0][1];
				$match_len		= strlen($match[0][0]);
				$var			= $match[2][0];

				if( !isset($vars[$var]) ){
					continue;
				}

				// escaped ?
				$escape_chars = strlen($match[1][0]);
				if( $escape_chars % 2 == 1 ){
					$escape_chars	= ($escape_chars-1)/2;
					$replacement	= str_repeat('\\',$escape_chars) . '$' . $var;
					$content		= substr_replace($content, $replacement, $match_start, $match_len);
					continue;
				}

				$escape_chars	= ($escape_chars/2);
				$replacement	= str_repeat('\\',$escape_chars) . $vars[$var];
				$content		= substr_replace($content, $replacement, $match_start, $match_len);

			}

			self::ReplaceContent($content);

			return $content;
		}


		/**
		 * Include the content of another page into the current content by replacing {{--page_name--}} with the content of page_name
		 * @param string $content The content of the current page
		 * @param int $offset The current string position of the page parser
		 *
		 */
		public static function ReplaceContent(&$content,$offset=0){
			global $gp_index;
			static $includes = 0;

			//prevent too many inlcusions
			if( $includes >= 10 ){
				return;
			}

			if( !preg_match('#(<p>)?\s*{{(.*)}}\s*(</p>)?#',$content, $match, PREG_OFFSET_CAPTURE, $offset) ){
				return;
			}


			$matched_string		= $match[0][0];
			$match_pos			= $match[0][1];
			$next_offset		= $match_pos + 2;
			$title				= $match[2][0];
			$title				= str_replace(' ','_',$title);


			if( !isset($gp_index[$title]) ){
				self::ReplaceContent($content,$next_offset);
				return;
			}


			$file			= \gp\tool\Files::PageFile($title);
			$file_sections	= \gp\tool\Files::Get($file,'file_sections');

			if( !$file_sections ){
				self::ReplaceContent($content,$next_offset);
				return;
			}

			$includes++;
			$replacement = '';
			foreach($file_sections as $section_num => $section_data){
				$replacement .= '<div class="gpinclude" title="'.$title.'" >'; //contentEditable="false"
				$replacement .= self::SectionToContent($section_data,$section_num);
				$replacement .= '</div>';
			}

			//is {{...}} wrapped by <p>..</p>? ... replace the whole matched string
			if( !empty($match[1][0]) && !empty($match[3][0]) ){
				$start		= $match_pos;
				$len		= strlen($matched_string);

			// only replace the {{...}}
			}else{
				$start		= $match[2][1]-2;
				$len		= strlen($match[2][0]) + 4;
			}

			$content = substr_replace($content,$replacement,$start,$len);

			self::ReplaceContent($content,$match_pos);
		}


		/**
		 * Include the content of a page or gadget as specified in $data
		 * @param array $data
		 * @return string The included content
		 */
		public static function IncludeContent($data){
			global $langmessage, $gp_index;

			$data		+= ['include_type'=>'file'];
			$type		= $data['include_type'];
			$requested	= $data['content'];

			if( isset($data['index']) ){
				$requested	= \gp\tool::IndexToTitle($data['index']);
				$type		= \gp\tool::SpecialOrAdmin($requested);
			}

			if( empty($requested) ){
				return '<p>'.$langmessage['File Include'].'</p>';
			}

			if( self::$title == $requested ){
				debug('Infinite loop detected: '.htmlspecialchars($requested) );
				return '';
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
		public static function IncludeGadget($requested){
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
		public static function IncludeSpecial($requested){
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
		public static function IncludeExtra($requested){
			return \gp\tool\Output\Extra::GetExtra($requested);
		}


		/**
		 * Include the content of a normal page
		 * @param string $requested The name of the page to include
		 *
		 */
		public static function IncludePage($requested){
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
		public static function SectionAttributes($attrs,$type){

			switch($type){
				case 'image':
					$attrs			+= ['src'=>'/include/imgs/default_image.jpg'];
					$attrs['src']	= \gp\tool::GetDir($attrs['src']);
				break;
			}


			$attrs				+= array('class' => '' );
			$attrs['class']		= trim('GPAREA filetype-'.$type.' '.$attrs['class']);

			return \gp\tool\HTML::Attributes($attrs);
		}

	}
}

namespace {
	class section_content extends \gp\tool\Output\Sections{}
}
