<?php

namespace gp\tool\Output;

defined('is_running') or die('Not an entry point...');

class Menu{



	public static function GetFullMenu($arg=''){
		$source_menu_array = self::GetMenuArray($arg);
		self::OutputMenu($source_menu_array,0,$source_menu_array);
	}

	public static function GetExpandLastMenu($arg=''){
		global $page;
		$source_menu_array = self::GetMenuArray($arg);

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 0) || ($level == 1) ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}


			if( $foundGroup ){
				$menu[$key] = $level;
			}elseif( ($level == 0) || ($level == 1) ){
				$menu[$key] = $level;
			}else{
				$submenu[$key] = $level;
			}
		}

		self::OutputMenu($menu,0,$source_menu_array);
	}

	public static function GetMenu($arg=''){
		$source_menu_array = self::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $info){
			if( (int)$info['level'] !== 0 ){
				continue;
			}
			$sendMenu[$key] = true;
		}

		self::OutputMenu($sendMenu,0,$source_menu_array);
	}

	public static function GetSubMenu($arg='',$info=false,$search_level=false){
		global $page;
		$source_menu_array = self::GetMenuArray($arg);

		$reset_level = 0;
		if( !empty($search_level) ){
			$reset_level = max(0,$search_level-1);
		}


		$menu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $titleInfo){
			if( !isset($titleInfo['level']) ){
				break;
			}
			$level = $titleInfo['level'];

			if( $foundGroup ){
				if( $level <= $reset_level ){
					break;
				}
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
			}

			if( $level <= $reset_level ){
				$menu = array();
				continue;
			}

			if( empty($search_level) ){
				$menu[$key] = $level;
			}elseif( $level == $search_level ){
				$menu[$key] = $level;
			}

		}

		if( !$foundGroup ){
			self::OutputMenu(array(),$reset_level+1,$source_menu_array);
		}else{
			self::OutputMenu($menu,$reset_level+1,$source_menu_array);
		}
	}

	public static function GetTopTwoMenu($arg=''){
		$source_menu_array = self::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			if( $titleInfo['level'] >= 2 ){
				continue;
			}
			$sendMenu[$key] = true;
		}
		self::OutputMenu($sendMenu,0,$source_menu_array);
	}


	public static function GetBottomTwoMenu($arg=''){
		$source_menu_array = self::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 1) || ($level == 2) ){
				$sendMenu[$key] = true;
			}
		}
		self::OutputMenu($sendMenu,1,$source_menu_array);
	}


	public static function GetSecondSubMenu($arg,$info){
		self::GetSubMenu($arg,$info,1);
	}
	public static function GetThirdSubMenu($arg,$info){
		self::GetSubMenu($arg,$info,2);
	}


	public static function GetExpandMenu($arg=''){
		global $page;
		$source_menu_array = self::GetMenuArray($arg);

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $info){
			$level = $info['level'];

			if( $level == 0 ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}

			if( $foundGroup ){
				$menu[$key] = $level;
			}elseif( $level == 0 ){
				$menu[$key] = $level;
			}else{
				$submenu[$key] = $level;
			}

		}
		self::OutputMenu($menu,0,$source_menu_array);
	}


	/**
	 * Return the data for the requested menu, return the main menu if the requested menu doesn't exist
	 * @param string $id String identifying the requested menu
	 * @return array menu data
	 */
	public static function GetMenuArray($id){
		global $dataDir, $gp_menu;


		$menu_file = $dataDir.'/data/_menus/'.$id.'.php';
		if( empty($id) || !\gp\tool\Files::Exists($menu_file) ){
			return \gp\tool\Plugins::Filter('GetMenuArray',array($gp_menu));
		}


		$menu = \gp\tool\Files::Get('_menus/'.$id,'menu');

		if( \gp\tool\Files::$last_version && version_compare(\gp\tool\Files::$last_version,'3.0b1','<') ){
			$menu = self::FixMenu($menu);
		}

		return \gp\tool\Plugins::Filter('GetMenuArray',array($menu));
	}



	/**
	 * Update menu entries to 3.0 state
	 * .. htmlspecialchars label for external links
	 * @since 3.0b1
	 */
	public static function FixMenu($menu){

		//fix external links, prior to 3.0, escaping was done when the menu was output
		foreach($menu as $key => $value){

			if( !isset($value['url']) ){
				continue;
			}

			//make sure it has a label
			if( empty($value['label']) ){
				$menu[$key]['label'] = $value['url'];
			}

			//make sure the title attr is escaped
			if( !empty($value['title_attr']) ){
				$menu[$key]['title_attr'] = htmlspecialchars($menu[$key]['title_attr']);
			}

			//make sure url and label are escape
			$menu[$key]['url'] = htmlspecialchars($menu[$key]['url']);
			$menu[$key]['label'] = htmlspecialchars($menu[$key]['label']);
		}
		return $menu;
	}


	public static function MenuReduce_ExpandAll($menu,$expand_level,$curr_title_key,$top_level){

		$result_menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($menu as $title_key => $level){

			if( $level < $expand_level ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $title_key == $curr_title_key ){
				$foundGroup = true;
				$result_menu = $result_menu + $submenu; //not using array_merge because of numeric indexes
			}


			if( $foundGroup ){
				$result_menu[$title_key] = $level;
			}elseif( $level < $expand_level ){
				$result_menu[$title_key] = $level;
			}else{
				$submenu[$title_key] = $level;
			}
		}

		return $result_menu;
	}


	//Reduce titles deeper than $expand_level || $current_level
	public static function MenuReduce_Expand($menu,$expand_level,$curr_title_key,$top_level){
		$result_menu = array();
		$submenu = array();


		//if $top_level is set, we need to take it into consideration
		$expand_level = max( $expand_level, $top_level);

		//titles higher than the $expand_level
		$good_titles = array();
		foreach($menu as $title_key => $level){
			if( $level < $expand_level ){
				$good_titles[$title_key] = $level;
			}
		}


		if( isset($menu[$curr_title_key]) ){
			$curr_level = $menu[$curr_title_key];
			$good_titles[$curr_title_key] = $menu[$curr_title_key];


			//titles below selected
			// cannot use $submenu because $foundTitle may require titles above the $submenu threshold
			$foundTitle = false;
			foreach($menu as $title_key => $level){

				if( $title_key == $curr_title_key ){
					$foundTitle = true;
					continue;
				}

				if( !$foundTitle ){
					continue;
				}

					if( ($curr_level+1) == $level ){
						$good_titles[$title_key] = $level;
					}elseif( $curr_level < $level ){
						continue;
					}else{
						break;
					}
			}



			//reduce the menu to the current group
			$submenu = self::MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level);


			// titles even-with selected title within group
			$even_temp = array();
			$even_group = false;
			foreach($submenu as $title_key => $level){

				if( $title_key == $curr_title_key ){
					$even_group = true;
					$good_titles = $good_titles + $even_temp;
					continue;
				}

				if( $level < $curr_level ){
					if( $even_group ){
						$even_group = false; //done
					}else{
						$even_temp = array(); //reset
					}
				}

				if( $level == $curr_level ){
					if( $even_group ){
						$good_titles[$title_key] = $level;
					}else{
						$even_temp[$title_key] = $level;
					}
				}
			}


			// titles above selected title, deeper than $expand_level, and within the group
			self::MenuReduce_Sub($good_titles,$submenu,$curr_title_key,$expand_level,$curr_level);
			self::MenuReduce_Sub($good_titles,array_reverse($submenu),$curr_title_key,$expand_level,$curr_level);
		}



		//rebuild $good_titles in order
		// array_intersect_assoc() would be useful here, it's php4.3+ and there's no indication if the order of the first argument is preserved
		foreach($menu as $title => $level){
			if( isset($good_titles[$title]) ){
				$result_menu[$title] = $level;
			}
		}

		return $result_menu;

	}



	// reduce the menu to the group
	public static function MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level){
		$result = array();
		$group_temp = array();
		$found_title = false;

		foreach($menu as $title_key => $level){

			//back at the top
			if( $level < $expand_level ){
				$group_temp = array();
				$found_title = false;
			}


			if( $title_key == $curr_title_key ){
				$found_title = true;
				$result = $group_temp;
			}

			if( $level >= $expand_level ){
				if( $found_title ){
					$result[$title_key] = $level;
				}else{
					$group_temp[$title_key] = $level;
				}
			}
		}

		return $result;
	}

	// titles above selected title, deeper than $expand_level, and within the group
	public static function MenuReduce_Sub(&$good_titles,$menu,$curr_title_key,$expand_level,$curr_level){
		$found_title = false;
		$test_level = $curr_level;
		foreach($menu as $title_key => $level){

			if( $title_key == $curr_title_key ){
				$found_title = true;
				$test_level = $curr_level;
				continue;
			}

			//after the title is found
			if( !$found_title ){
				continue;
			}
			if( $level < $expand_level ){
				break;
			}
			if( ($level >= $expand_level) && ($level < $test_level ) ){
				$test_level = $level+1; //prevent showing an adjacent menu trees
				$good_titles[$title_key] = $level;
			}
		}
	}

	//Reduce the menu to titles deeper than ($show_level-1)
	public static function MenuReduce_Top($menu,$show_level,$curr_title_key){
		$result_menu = array();
		$foundGroup = false;

		//current title not in menu, so there won't be a submenu
		if( !isset($menu[$curr_title_key]) ){
			return $result_menu;
		}

		$top_level = $show_level-1;

		foreach($menu as $title_key => $level){

			//no longer in subgroup, we can stop now
			if( $foundGroup && ($level <= $top_level) ){
				break;
			}

			if( $title_key == $curr_title_key ){
				$foundGroup = true;
			}

			//we're back at the $top_level, start over
			if( $level <= $top_level ){
				$result_menu = array();
				continue;
			}

			//we're at the correct level, put titles in $result_menu in case $page->title is found
			if( $level > $top_level ){
				$result_menu[$title_key] = $level;
			}
		}

		if( !$foundGroup ){
			return array();
		}

		return $result_menu;
	}


	//Reduce the menu to titles above $bottom_level value
	public static function MenuReduce_Bottom($menu,$bottom_level){
		$result_menu = array();

		foreach($menu as $title => $level){
			if( $level < $bottom_level ){
				$result_menu[$title] = $level;
			}
		}
		return $result_menu;
	}


	/**
	 * @param string $arg comma seperated argument list: $top_level, $bottom_level, $options
	 *		$top_level  (int)  The upper level of the menu to show, if deeper (in this case > ) than 0, only the submenu is shown
	 *		$bottom_level  (int)  The lower level of menu to show
	 *		$expand_level (int)  The upper level from where to start expanding sublinks, if -1 no expansion
	 * 		$expand_all (int)	Whether or not to expand all levels below $expand_level (defaults to 0)
	 * 		$source_menu (string)	Which menu to use
	 *
	 */
	public static function CustomMenu($arg,$title=false){
		global $page, $gp_index;

		//from output functions
		if( is_array($title) ){
			$title = $page->title;
		}

		$title_index = false;
		if( isset($gp_index[$title]) ){
			$title_index = $gp_index[$title];
		}

		$args = explode(',',$arg);
		$args += array( 0=>0, 1=>3, 2=>-1, 3=>1, 4=>'' ); //defaults
		list($top_level,$bottom_level,$expand_level,$expand_all,$source_menu) = $args;


		//get menu array
		$source_menu_array = self::GetMenuArray($source_menu);



		//reduce array to $title => $level
		$menu = array();
		foreach($source_menu_array as $temp_key => $titleInfo){
			if( !isset($titleInfo['level']) ){
				break;
			}
			$menu[$temp_key] = $titleInfo['level'];
		}

		//Reduce for expansion
		//first reduction
		if( (int)$expand_level >= 1 ){
			if( $expand_all ){
				$menu = self::MenuReduce_ExpandAll($menu,$expand_level,$title_index,$top_level);
			}else{
				$menu = self::MenuReduce_Expand($menu,$expand_level,$title_index,$top_level);
			}
		}


		//Reduce if $top_level >= 0
		//second reduction
		if( (int)$top_level > 0 ){
			$menu = self::MenuReduce_Top($menu,$top_level,$title_index);
		}else{
			$top_level = 0;
		}

		//Reduce by trimming off titles below $bottom_level
		// last reduction : in case the selected link is below $bottom_level
		if( $bottom_level > 0 ){
			$menu = self::MenuReduce_Bottom($menu,$bottom_level);
		}

		self::OutputMenu($menu,$top_level,$source_menu_array);
	}

	/**
	 * Output a navigation menu
	 * @static
	 */
	public static function OutputMenu( $menu, $start_level, $source_menu=false ){
		global $page, $gp_menu, $gp_titles, $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES;

		//source menu
		if( $source_menu === false ){
			$source_menu =& $gp_menu;
		}

		self::PrepMenuOutput();
		$clean_attributes		= array( 'attr'=>'', 'class'=>array(), 'id'=>'' );




		// opening ul
		$attributes_ul = $clean_attributes;
		$attributes_ul['class']['menu_top'] = $GP_MENU_CLASSES['menu_top'];
		if( \gp\tool\Output::$edit_area_id ){
			$attributes_ul['id'] = \gp\tool\Output::$edit_area_id;
			$attributes_ul['class']['editable_area'] = 'editable_area';
		}

		// Without any output the menu wouldn't be editable
		// An empty <ul> is not valid
		if( !count($menu) ){
			$attributes_ul['class']['empty_menu'] = 'empty_menu';
			self::FormatMenuElement('div',$attributes_ul);
			echo '</div>';
			return;
		}


		$prev_level				= $start_level;
		$page_title_full		= \gp\tool::GetUrl($page->title);
		$open					= false;
		$li_count				= array();
		$parents				= \gp\tool::Parents($page->gp_index,$source_menu);


		//output
		self::FormatMenuElement('ul',$attributes_ul);


		$menu			= array_keys($menu);
		$hidden_level	= null;

		foreach($menu as $menu_ii => $menu_key){

			$menu_info			= $source_menu[$menu_key];
			$this_level			= $menu_info['level'];


			// hidden pages
			if( !is_null($hidden_level) ){
				if( $this_level > $hidden_level ){
					continue;
				}
				$hidden_level = null;
			}

			if( isset($gp_titles[$menu_key]['vis']) ){
				$hidden_level = $this_level;
				continue;
			}



			//the next entry
			$next_info			= false;
			$next_index			= $menu_ii+1;

			if( array_key_exists($next_index,$menu) ){
				$next_index		= $menu[$next_index];
				$next_info		= $source_menu[$next_index];
			}

			$attributes_a		= self::MenuAttributesA($menu_key, $menu_info);
			$attributes_li		= $clean_attributes;
			$attributes_ul		= $clean_attributes;


			//ordered or "indexed" classes
			if( $page->menu_css_ordered ){
				for($i = $prev_level;$i > $this_level; $i--){
					unset($li_count[$i]);
				}
				if( !isset($li_count[$this_level]) ){
					$li_count[$this_level] = 0;
				}else{
					$li_count[$this_level]++;
				}
				if( !empty($GP_MENU_CLASSES['li_']) ){
					$attributes_li['class']['li_'] = $GP_MENU_CLASSES['li_'].$li_count[$this_level];
				}
			}

			if( $page->menu_css_indexed && !empty($GP_MENU_CLASSES['li_title_']) ){
				$attributes_li['class']['li_title_'] = $GP_MENU_CLASSES['li_title_'].$menu_key;
			}


			//selected classes
			if( $this_level < $next_info['level'] ){
				$attributes_a['class']['haschildren']			= $GP_MENU_CLASSES['haschildren'];
				$attributes_li['class']['haschildren_li']		= $GP_MENU_CLASSES['haschildren_li'];
			}

			if( isset($menu_info['url']) && ($menu_info['url'] == $page->title || $menu_info['url'] == $page_title_full) ){
				$attributes_a['class']['selected']				= $GP_MENU_CLASSES['selected'];
				$attributes_li['class']['selected_li']			= $GP_MENU_CLASSES['selected_li'];

			}elseif( $menu_key == $page->gp_index ){
				$attributes_a['class']['selected']				= $GP_MENU_CLASSES['selected'];
				$attributes_li['class']['selected_li']			= $GP_MENU_CLASSES['selected_li'];

			}elseif( in_array($menu_key,$parents) ){
				$attributes_a['class']['childselected']			= $GP_MENU_CLASSES['childselected'];
				$attributes_li['class']['childselected_li']		= $GP_MENU_CLASSES['childselected_li'];

			}


			//current is a child of the previous
			if( $this_level > $prev_level ){

				if( $menu_ii === 0 ){ //only needed if the menu starts below the start_level
					self::FormatMenuElement('li',$attributes_li);
				}

				if( !empty($GP_MENU_CLASSES['child_ul']) ){
					$attributes_ul['class'][] = $GP_MENU_CLASSES['child_ul'];
				}

				if( $this_level > $prev_level ){
					$open_loops = $this_level - $prev_level;

					for($i = 0; $i<$open_loops; $i++){
						self::FormatMenuElement('ul',$attributes_ul);
						if( $i < $open_loops-1 ){
							echo '<li>';
						}
						$prev_level++;
						$attributes_ul = $clean_attributes;
					}
				}

			//current is higher than the previous
			}elseif( $this_level <= $prev_level ){

				self::OutputMenu_CloseLevel($this_level, $prev_level);

				if( $open ){
					echo '</li>';
				}
			}


			self::FormatMenuElement('li',$attributes_li);
			self::FormatMenuElement('a',$attributes_a);


			$prev_level		= $this_level;
			$open			= true;
		}

		self::OutputMenu_CloseLevel( $start_level, $prev_level);
	}


	/**
	 * Output breadcrumb nav
	 *
	 */
	public static function BreadcrumbNav($arg=''){
		global $page, $gp_index, $GP_MENU_CLASSES;

		$source_menu_array	= self::GetMenuArray($arg);
		$output				= array();
		$thisLevel			= -1;
		$last_index			= '';

		$rmenu = array_reverse($source_menu_array);
		foreach($rmenu as $index => $info){
			$level = $info['level'];

			if( $thisLevel >= 0 ){
				if( $thisLevel == $level ){
					array_unshift($output,$index);
					$last_index = $index;
					if( $thisLevel == 0 ){
						break;
					}
					$thisLevel--;
				}
			}

			if( $index == $page->gp_index ){
				array_unshift($output,$index);
				$thisLevel = $level-1;
				$last_index = $index;
			}
		}


		reset($source_menu_array);

		//add homepage
		$first_index = key($source_menu_array);
		if( $last_index != $first_index ){
			array_unshift($output,$first_index);
		}



		self::PrepMenuOutput();
		$clean_attributes = array( 'attr'=>'', 'class'=>array(), 'id'=>'' );


		// opening ul
		$attributes_ul = $clean_attributes;
		$attributes_ul['class']['menu_top'] = $GP_MENU_CLASSES['menu_top'];
		if( \gp\tool\Output::$edit_area_id ){
			$attributes_ul['id'] = \gp\tool\Output::$edit_area_id;
			$attributes_ul['class']['editable_area'] = 'editable_area';
		}
		self::FormatMenuElement('ul',$attributes_ul);


		//
		$len = count($output);
		for( $i = 0; $i < $len; $i++){

			$index					= $output[$i];
			$title					= \gp\tool::IndexToTitle($index);
			$attributes_li			= $clean_attributes;
			$attributes_a			= self::MenuAttributesA($index);

			if( $title == $page->title ){
				$attributes_a['class']['selected']		= $GP_MENU_CLASSES['selected'];
				$attributes_li['class']['selected_li']	= $GP_MENU_CLASSES['selected_li'];
			}


			self::FormatMenuElement('li',$attributes_li);

			if( $i < $len-1 ){
				self::FormatMenuElement('a',$attributes_a);
			}else{
				self::FormatMenuElement('a',$attributes_a);
			}
			echo '</li>';
		}

		echo '</ul>';
	}


	/**
	 * Add list item closing tags till $prev_level == $this_level
	 *
	 */
	protected static function OutputMenu_CloseLevel( $this_level, &$prev_level){
		while( $this_level < $prev_level){
			echo '</li></ul>';

			$prev_level--;
		}
	}


	/**
	 * Start the link attributes array
	 *
	 */
	protected static function MenuAttributesA($menu_key, $menu_info = array() ){
		global $gp_titles;

		$attributes = array('href' => '', 'attr' => '', 'value' => '', 'title' => '', 'class' =>array() );

		//external
		if( isset($menu_info['url']) ){
			if( empty($menu_info['title_attr']) ){
				$menu_info['title_attr'] = strip_tags($menu_info['label']);
			}

			$attributes['href']			= $menu_info['url'];
			$attributes['value']		= $menu_info['label'];
			$attributes['title']		= $menu_info['title_attr'];

			if( isset($menu_info['new_win']) ){
				$attributes['target'] = '_blank';
			}

		//internal link
		}else{

			$title						= \gp\tool::IndexToTitle($menu_key);
			$attributes['href']			= \gp\tool::GetUrl($title);
			$attributes['value']		= \gp\tool::GetLabel($title);
			$attributes['title']		= \gp\tool::GetBrowserTitle($title);

			//get valid rel attr
			if( !empty($gp_titles[$menu_key]['rel']) ){
				$rel = explode(',',$gp_titles[$menu_key]['rel']);
				$attributes['rel'] = array_intersect( array('alternate','author','bookmark','help','icon','license','next','nofollow','noreferrer','prefetch','prev','search','stylesheet','tag'), $rel);
			}
		}

		return $attributes;
	}



	public static function FormatMenuElement( $node, $attributes){
		global $GP_MENU_LINKS, $GP_MENU_ELEMENTS;


		// build attr
		foreach($attributes as $key => $value){
			if( $key == 'title' || $key == 'href' || $key == 'value' ){
				continue;
			}
			if( is_array($value) ){
				$value = array_filter($value);
				$value = implode(' ',$value);
			}
			if( empty($value) ){
				continue;
			}
			$attributes['attr'] .= ' '.$key.'="'.$value.'"';
		}


		// call template defined function
		if( !empty($GP_MENU_ELEMENTS) && is_callable($GP_MENU_ELEMENTS) ){
			$return = call_user_func($GP_MENU_ELEMENTS, $node, $attributes);
			if( is_string($return) ){
				echo $return;
				return;
			}
		}

		if( $node == 'a' ){
			$search = array('{$href_text}','{$attr}','{$label}','{$title}');
			echo str_replace( $search, $attributes, $GP_MENU_LINKS );
		}else{
			echo '<'.$node.$attributes['attr'].'>';
		}
	}

	public static function PrepMenuOutput(){
		global $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES;

		//menu classes
		if( !is_array($GP_MENU_CLASSES) ){
			$GP_MENU_CLASSES = array();
		}
		if( empty($GP_MENU_CLASS) ){
			$GP_MENU_CLASS = 'menu_top';
		}
		$GP_MENU_CLASSES += array(
							'menu_top'			=> $GP_MENU_CLASS,
							'selected'			=> 'selected',
							'selected_li'		=> 'selected_li',
							'childselected'		=> 'childselected',
							'childselected_li'	=> 'childselected_li',
							'li_'				=> 'li_',
							'li_title'			=> 'li_title',
							'haschildren'		=> 'haschildren',
							'haschildren_li'	=> '',
							'child_ul'			=> '',
							);

		if( empty($GP_MENU_LINKS) ){
			$GP_MENU_LINKS = '<a href="{$href_text}" title="{$title}"{$attr}>{$label}</a>';
		}

	}

}
