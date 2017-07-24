<?php

namespace gp\tool\Output;

defined('is_running') or die('Not an entry point...');

class Menu{

	protected $clean_attributes		= array( 'attr'=>'', 'class'=>array(), 'id'=>'' );

	private $page_title;
	private $parents				= array();

	private $curr_menu;
	private $curr_key;
	private $curr_level;
	private $curr_info;
	private $prev_level;
	private $hidden_level;

	private $custom_child_ul_classes;


	public function __construct(){
		global $page, $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES;

		$this->page_title		= \gp\tool::GetUrl($page->title);


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


	public function GetFullMenu($arg=''){
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);
		$this->OutputMenu($source_menu_array,0,$source_menu_array);
	}

	public function GetExpandLastMenu($arg=''){
		global $page;
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);

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

		$this->OutputMenu($menu,0,$source_menu_array);
	}

	public function GetMenu($arg=''){
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $info){
			if( (int)$info['level'] !== 0 ){
				continue;
			}
			$sendMenu[$key] = true;
		}

		$this->OutputMenu($sendMenu,0,$source_menu_array);
	}

	public function GetSubMenu($arg='',$info=false,$search_level=false){
		global $page;
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);

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
			$this->OutputMenu(array(),$reset_level+1,$source_menu_array);
		}else{
			$this->OutputMenu($menu,$reset_level+1,$source_menu_array);
		}
	}

	public function GetTopTwoMenu($arg=''){
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			if( $titleInfo['level'] >= 2 ){
				continue;
			}
			$sendMenu[$key] = true;
		}
		$this->OutputMenu($sendMenu,0,$source_menu_array);
	}


	public function GetBottomTwoMenu($arg=''){
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 1) || ($level == 2) ){
				$sendMenu[$key] = true;
			}
		}
		$this->OutputMenu($sendMenu,1,$source_menu_array);
	}

	/* alias */
	public function GetSecondSubMenu($arg,$info){
		$this->GetSubMenu($arg,$info,1);
	}

	/* alias */
	public function GetThirdSubMenu($arg,$info){
		$this->GetSubMenu($arg,$info,2);
	}


	public function GetExpandMenu($arg=''){
		global $page;
		$this->curr_menu = $arg;
		$source_menu_array = $this->GetMenuArray($arg);

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
		$this->OutputMenu($menu,0,$source_menu_array);
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

			//make sure url and label are escaped
			$menu[$key]['url'] = htmlspecialchars($menu[$key]['url']);
			$menu[$key]['label'] = htmlspecialchars($menu[$key]['label']);
		}
		return $menu;
	}


	public function MenuReduce_ExpandAll($menu,$expand_level,$curr_title_key,$top_level){

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
	public function MenuReduce_Expand($menu,$expand_level,$curr_title_key,$top_level){
		$result_menu = array();


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
			$submenu = $this->MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level);


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
			$this->MenuReduce_Sub($good_titles,$submenu,$curr_title_key,$expand_level,$curr_level);
			$this->MenuReduce_Sub($good_titles,array_reverse($submenu),$curr_title_key,$expand_level,$curr_level);
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
	public function MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level){
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
	public function MenuReduce_Sub(&$good_titles,$menu,$curr_title_key,$expand_level,$curr_level){
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
	public function MenuReduce_Top($menu,$show_level,$curr_title_key){
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
	public function MenuReduce_Bottom($menu,$bottom_level){
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
	public function CustomMenu($arg, $title=false){
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
		$source_menu_array = $this->GetMenuArray($source_menu);



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
				$menu = $this->MenuReduce_ExpandAll($menu,$expand_level,$title_index,$top_level);
			}else{
				$menu = $this->MenuReduce_Expand($menu,$expand_level,$title_index,$top_level);
			}
		}


		//Reduce if $top_level >= 0
		//second reduction
		if( (int)$top_level > 0 ){
			$menu = $this->MenuReduce_Top($menu,$top_level,$title_index);
		}else{
			$top_level = 0;
		}

		//Reduce by trimming off titles below $bottom_level
		// last reduction : in case the selected link is below $bottom_level
		if( $bottom_level > 0 ){
			$menu = $this->MenuReduce_Bottom($menu,$bottom_level);
		}

		$this->OutputMenu($menu,$top_level,$source_menu_array);
	}


	/**
	 * Output a navigation menu
	 *
	 */
	public function OutputMenu( $menu, $start_level, $source_menu=false ){
		global $page, $gp_menu, $gp_titles, $GP_MENU_LINKS, $GP_MENU_CLASS, $GP_MENU_CLASSES;

		//source menu
		if( $source_menu === false ){
			$source_menu =& $gp_menu;
		}


		// opening ul
		$attr_ul = $this->clean_attributes;
		$attr_ul['class']['menu_top'] = $GP_MENU_CLASSES['menu_top'];
		if( \gp\tool\Output::$edit_area_id ){
			$attr_ul['id'] = \gp\tool\Output::$edit_area_id;
			$attr_ul['class']['editable_area'] = 'editable_area';
		}

		// Without any output the menu wouldn't be editable
		// An empty <ul> is not valid
		if( !count($menu) ){
			$attr_ul['class']['empty_menu'] = 'empty_menu';
			$this->FormatMenuElement('div', $attr_ul);
			echo '</div>';
			return;
		}


		$this->prev_level		= $start_level;
		$open					= false;
		$li_count				= array();
		$this->parents			= \gp\tool::Parents($page->gp_index, $source_menu);

		//output
		$this->FormatMenuElement('ul', $attr_ul);


		$menu			= array_keys($menu);

		foreach($menu as $menu_ii => $menu_key){

			$this->curr_key		= $menu_key;
			$this->curr_info	= $source_menu[$menu_key];
			$this->curr_level	= $this->curr_info['level'];


			if( $this->HiddenLevel() ){
				continue;
			}


			$attr_a			= $this->MenuAttributesA();
			$attr_li		= $this->clean_attributes;
			$attr_ul		= $this->clean_attributes;


			//ordered or "indexed" classes
			if( $page->menu_css_ordered && !empty($GP_MENU_CLASSES['li_']) ){
				for($i = $this->prev_level; $i > $this->curr_level; $i--){
					unset($li_count[$i]);
				}

				if( !isset($li_count[$this->curr_level]) ){
					$li_count[$this->curr_level] = 0;
				}else{
					$li_count[$this->curr_level]++;
				}

				$attr_li['class']['li_'] = $GP_MENU_CLASSES['li_'].$li_count[$this->curr_level];
			}

			if( $page->menu_css_indexed && !empty($GP_MENU_CLASSES['li_title_']) ){
				$attr_li['class']['li_title_'] = $GP_MENU_CLASSES['li_title_'].$this->curr_key;
			}

			if( isset($this->curr_info['classes_li']) ){
				$attr_li['class']['custom'] = $this->curr_info['classes_li'];
			}

			if( isset($this->curr_info['classes_a']) ){
				$attr_a['class']['custom'] = $this->curr_info['classes_a'];
			}

			if( isset($this->curr_info['classes_child_ul']) ){
				$this->custom_child_ul_classes = $this->curr_info['classes_child_ul'];
			}

			//selected classes
			$next_index			= $menu_ii+1;
			if( array_key_exists($next_index,$menu) ){
				$next_index		= $menu[$next_index];
				if( $this->curr_level < $source_menu[$next_index]['level'] ){
					$attr_a['class']['haschildren']			= $GP_MENU_CLASSES['haschildren'];
					$attr_li['class']['haschildren_li']		= $GP_MENU_CLASSES['haschildren_li'];
				}else{
					$this->custom_child_ul_classes = '';
				}
			}


			$this->Attrs($attr_a, $attr_li);
			$this->FormatStart($menu_ii, $attr_li, $attr_ul, $open);
			$this->FormatMenuElement('li', $attr_li);
			if( isset($this->curr_info['area']) ){
				\gp\tool\Output::Get('Extra', $this->curr_info['area']);
			}else{
				$this->FormatMenuElement('a', $attr_a);
			}


			$this->prev_level	= $this->curr_level;
			$open				= true;
		}

		$this->CloseLevel( $start_level );
		if( $open ){
			echo '</li></ul>';
		}
	}



	/**
	 * Output breadcrumb nav
	 *
	 */
	public function BreadcrumbNav($arg=''){
		global $page, $gp_index, $GP_MENU_CLASSES;

		$source_menu_array	= $this->GetMenuArray($arg);
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




		// opening ul
		$attr_ul = $this->clean_attributes;
		$attr_ul['class']['menu_top'] = $GP_MENU_CLASSES['menu_top'];
		if( \gp\tool\Output::$edit_area_id ){
			$attr_ul['id'] = \gp\tool\Output::$edit_area_id;
			$attr_ul['class']['editable_area'] = 'editable_area';
		}
		$this->FormatMenuElement('ul', $attr_ul);


		//
		foreach($output as $i => $curr_key){

			$this->curr_key		= $curr_key;
			$this->curr_level	= $this->curr_info['level'];


			$attr_li			= $this->clean_attributes;
			$attr_a				= $this->MenuAttributesA();

			$this->Attrs($attr_a, $attr_li);
			$this->FormatMenuElement('li', $attr_li);
			$this->FormatMenuElement('a', $attr_a);
			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Set menu element attributes
	 *
	 */
	protected function Attrs( &$attr_a, &$attr_li){
		global $page, $GP_MENU_CLASSES;

		if( isset($this->curr_info['url']) && ($this->curr_info['url'] == $page->title || $this->curr_info['url'] == $this->page_title) ){
			$attr_a['class']['selected']				= $GP_MENU_CLASSES['selected'];
			$attr_li['class']['selected_li']			= $GP_MENU_CLASSES['selected_li'];

		}elseif( $this->curr_key == $page->gp_index ){
			$attr_a['class']['selected']				= $GP_MENU_CLASSES['selected'];
			$attr_li['class']['selected_li']			= $GP_MENU_CLASSES['selected_li'];

		}elseif( in_array($this->curr_key,$this->parents) ){
			$attr_a['class']['childselected']			= $GP_MENU_CLASSES['childselected'];
			$attr_li['class']['childselected_li']		= $GP_MENU_CLASSES['childselected_li'];

		}
	}


	/**
	 * Return true if the current menu level is hidden
	 *
	 */
	private function HiddenLevel(){
		global $gp_titles;


		// hidden pages
		if( !is_null($this->hidden_level) ){
			if( $this->curr_level > $this->hidden_level ){
				return true;;
			}
			$this->hidden_level = null;
		}

		if( isset($gp_titles[$this->curr_key]['vis']) ){
			$this->hidden_level = $this->curr_level;
			return true;
		}

		return false;
	}


	/**
	 * Format the start of a menu level
	 *
	 */
	protected function FormatStart($menu_ii, $attr_li, $attr_ul, $open){
		global $GP_MENU_CLASSES;

		//current is a child of the previous
		if( $this->curr_level > $this->prev_level ){

			if( $menu_ii === 0 ){ //only needed if the menu starts below the start_level
				$this->FormatMenuElement('li', $attr_li);
			}

			if( !empty($GP_MENU_CLASSES['child_ul']) ){
				$attr_ul['class'][] = $GP_MENU_CLASSES['child_ul'];
			}

			if( !empty($this->custom_child_ul_classes) ){
				$attr_ul['class']['custom'] = $this->custom_child_ul_classes;
			}

			$open_loops = $this->curr_level - $this->prev_level;

			for($i = 0; $i<$open_loops; $i++){
				$this->FormatMenuElement('ul', $attr_ul);
				if( $i < $open_loops-1 ){
					echo '<li>';
				}
				$this->prev_level++;
				$attr_ul = $this->clean_attributes;
			}

			return;
		}

		//current is higher than the previous
		$this->CloseLevel($this->curr_level);

		if( $open ){
			echo '</li>';
		}
	}


	/**
	 * Add list item closing tags till $this->prev_level == $level
	 *
	 */
	protected  function CloseLevel( $level){
		while( $level < $this->prev_level){
			echo '</li></ul>';

			$this->prev_level--;
		}
	}


	/**
	 * Start the link attributes array
	 *
	 */
	protected function MenuAttributesA(){
		global $gp_titles;

		$attributes = array(
			'href'	=> '', 
			'attr'	=> '', 
			'value'	=> '', 
			'title'	=> '', 
			'class'	=> array(),
		);

		//external
		if( isset($this->curr_info['url']) ){
			if( empty($this->curr_info['title_attr']) ){
				$this->curr_info['title_attr'] = strip_tags($this->curr_info['label']);
			}

			$attributes['href']			= $this->curr_info['url'];
			$attributes['value']		= $this->curr_info['label'];
			$attributes['title']		= $this->curr_info['title_attr'];

			if( isset($this->curr_info['new_win']) ){
				$attributes['target'] = '_blank';
			}

		//internal link
		}else{

			$title						= \gp\tool::IndexToTitle($this->curr_key);
			$attributes['href']			= \gp\tool::GetUrl($title);
			$attributes['value']		= \gp\tool::GetLabel($title);
			$attributes['title']		= \gp\tool::GetBrowserTitle($title);

			//get valid rel attr
			if( !empty($gp_titles[$this->curr_key]['rel']) ){
				$rel = explode(',',$gp_titles[$this->curr_key]['rel']);
				$attributes['rel'] = array_intersect( 
					array(
						'alternate', 'author', 'bookmark', 'help',
						'icon','license', 'next', 'nofollow',
						'noreferrer', 'prefetch', 'prev', 'search',
						'stylesheet', 'tag',
					), 
					$rel
				);
			}
		}

		return $attributes;
	}



	public function FormatMenuElement($node, $attributes){
		global $GP_MENU_LINKS, $GP_MENU_ELEMENTS;


		// build attr
		foreach($attributes as $key => $value){
			if( $key == 'title' || $key == 'href' || $key == 'value' ){
				continue;
			}
			if( is_array($value) ){
				$value = array_filter($value);
				$value = implode(' ', $value);
			}
			if( empty($value) ){
				continue;
			}
			$attributes['attr'] .= ' ' . $key . '="' . $value . '"';
		}


		// call template defined function
		if( !empty($GP_MENU_ELEMENTS) && is_callable($GP_MENU_ELEMENTS) ){
			$return = call_user_func($GP_MENU_ELEMENTS, $node, $attributes, $this->curr_level, $this->curr_menu);
			if( is_string($return) ){
				echo $return;
				return;
			}
		}

		if( $node == 'a' ){
			$search = array('{$href_text}', '{$attr}', '{$label}', '{$title}');
			echo str_replace( $search, $attributes, $GP_MENU_LINKS );
		}else{
			echo '<' . $node . $attributes['attr'] . '>';
		}
	}

	public function PrepMenuOutput(){}


}
