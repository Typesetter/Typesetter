<?php
defined('is_running') or die('Not an entry point...');


class section_content{

	static $title = '';
	static $label = '';
	static $meta = array();


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

		$content = '';
		foreach($sections as $section_num => $section_data){
			$section_data += array('attributes' => array() );
			$content .= '<div'.self::SectionAttributes($section_data['attributes'],$section_data['type']).'>';
			$content .= self::SectionToContent($section_data,$section_num);
			$content .= '<div class="gpclear"></div>';
			$content .= '</div>';
		}
		return $content;
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
			$types['text']['label']		= $langmessage['editable_text'];
			$types['gallery']['label']	= $langmessage['Image Gallery'];
			$types['include']['label']	= $langmessage['File Include'];
			$types = gpPlugin::Filter('SectionTypes',array($types));
		}

		return $types;
	}



	static function SetVars($title,$meta){
		self::$title = $title;
		self::$label = common::GetLabel($title);
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
	static function SectionToContent(&$section_data,$section_num){

		$section_data = gpPlugin::Filter('SectionToContent',array($section_data,$section_num));

		switch($section_data['type']){
			case 'text':
			return self::TextContent($section_data['content']);

			case 'include':
			return self::IncludeContent($section_data);

			case 'gallery':
				common::ShowingGallery();
			return $section_data['content'];
		}
		return $section_data['content'];
	}

	/**
	 * Replace gpEasy content variables in $content
	 *
	 */
	static function TextContent(&$content){

		self::$meta += array('modified'=>'');

		//variables
		$vars = array(
			'dirPrefix' => $GLOBALS['dirPrefix'],
			'linkPrefix' => common::HrefEncode($GLOBALS['linkPrefix']),
			'fileModTime' => self::$meta['modified'],
			'title' => self::$title,
			'label' => self::$label,
			);

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


		$file = gpFiles::PageFile($title);
		if( !file_exists($file) ){
			self::ReplaceContent($content,$pos2);
			return;
		}

		$includes++;
		$file_sections = array();
		ob_start();
		require($file);
		ob_get_clean();

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
			$requested = common::IndexToTitle($data['index']);
		}else{
			$requested = $data['content'];
		}

		if( empty($requested) ){
			return '<p>'.$langmessage['File Include'].'</p>';
		}

		if( self::$title == $requested ){
			if( common::LoggedIn() ){
				message('Infinite loop detected: '.htmlspecialchars($requested) );
			}
			return;
		}

		if( isset($data['include_type']) ){
			$type = $data['include_type'];
		}else{
			$type = common::SpecialOrAdmin($requested);
		}

		switch($type){
			case 'gadget':
			return self::IncludeGadget($requested);

			case 'special':
			return self::IncludeSpecial($requested);

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
		gpOutput::GetGadget($requested);
		return ob_get_clean();
	}


	/**
	 * Include the content of a special page
	 * @param string $requested The name of the special page to include
	 *
	 */
	static function IncludeSpecial($requested){
		global $langmessage;
		includeFile('special.php');

		$scriptinfo = special_display::GetScriptInfo( $requested, false );

		if( $scriptinfo === false ){
			return '<p>'.$langmessage['File Include'].'</p>';
		}

		return special_display::ExecInfo($scriptinfo);
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

		$file = gpFiles::PageFile($requested);
		if( !file_exists($file) ){
			return '{{'.htmlspecialchars($requested).'}}';
		}

		$file_sections = array();
		require($file);
		return self::Render($file_sections,self::$title,self::$meta);
	}

	static function SectionAttributes($attributes,$type){

		$attributes += array('class' => '' );
		$attributes['class'] = trim('GPAREA filetype-'.$type.' '.$attributes['class']);

		$attr_string = '';
		foreach($attributes as $attr => $value){
			$attr_string .= ' '.htmlspecialchars($attr).'="'.htmlspecialchars($value).'"';
		}
		return $attr_string;
	}

}