<?php

namespace gp\admin\Menu;

defined('is_running') or die('Not an entry point...');

class Search{


	public function SearchDisplay(){
		global $langmessage, $gpLayouts, $gp_index, $gp_menu;

		$Inherit_Info = \gp\admin\Menu\Tools::Inheritance_Info();

		switch($this->curr_menu_id){
			case 'search':
				$show_list = $this->GetSearchList();
			break;
			case 'all':
				$show_list = array_keys($gp_index);
			break;
			case 'hidden':
				$show_list = \gp\admin\Menu\Tools::GetAvailable();
			break;
			case 'nomenus':
				$show_list = $this->GetNoMenus();
			break;

		}

		$show_list = array_values($show_list); //to reset the keys
		$show_list = array_reverse($show_list); //show newest first
		$max = count($show_list);
		while( ($this->search_page * $this->search_max_per_page) > $max ){
			$this->search_page--;
		}
		$start = $this->search_page*$this->search_max_per_page;
		$stop = min( ($this->search_page+1)*$this->search_max_per_page, $max);


		ob_start();
		echo '<div class="gp_search_links">';
		echo '<span class="showing">';
		echo sprintf($langmessage['SHOWING'],($start+1),$stop,$max);
		echo '</span>';

		echo '<span>';

		if( ($start !== 0) || ($stop < $max) ){
			for( $i = 0; ($i*$this->search_max_per_page) < $max; $i++ ){
				$class = '';
				if( $i == $this->search_page ){
					$class = ' class="current"';
				}
				echo $this->Link('Admin/Menu',($i+1),'page='.$i,'data-cmd="gpajax"'.$class);
			}
		}

		echo $this->Link('Admin/Menu/Ajax',$langmessage['create_new_file'],'cmd=AddHidden',array('title'=>$langmessage['create_new_file'],'data-cmd'=>'gpabox'));
		echo '</span>';
		echo '</div>';
		$links = ob_get_clean();

		echo $links;

		echo '<table class="bordered striped">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Content Type'];
		echo '</th><th>';
		echo $langmessage['Child Pages'];
		echo '</th><th>';
		echo $langmessage['File Size'];
		echo '</th><th>';
		echo $langmessage['Modified'];
		echo '</th></tr>';
		echo '</thead>';


		echo '<tbody>';

		if( count($show_list) > 0 ){
			for( $i = $start; $i < $stop; $i++ ){
				$title = $show_list[$i];
				$this->SearchDisplayRow($title);
			}
		}

		echo '</tbody>';
		echo '</table>';

		if( count($show_list) == 0 ){
			echo '<p>';
			echo $langmessage['Empty'];
			echo '</p>';
		}

		echo '<br/>';
		echo $links;
	}



	/**
	 * Get a list of titles matching the search criteria
	 *
	 */
	public function GetSearchList(){
		global $gp_index;


		$key =& $_REQUEST['q'];

		if( empty($key) ){
			return array();
		}

		$key = strtolower($key);
		$show_list = array();
		foreach($gp_index as $title => $index ){

			if( strpos(strtolower($title),$key) !== false ){
				$show_list[$index] = $title;
				continue;
			}

			$label = \common::GetLabelIndex($index);
			if( strpos(strtolower($label),$key) !== false ){
				$show_list[$index] = $title;
				continue;
			}
		}
		return $show_list;
	}



	/**
	 * Get an array of titles that is not represented in any of the menus
	 *
	 */
	public function GetNoMenus(){
		global $gp_index;


		//first get all titles in a menu
		$menus = $this->GetAvailMenus('menu');
		$all_keys = array();
		foreach($menus as $menu_id => $label){
			$menu_array = \gpOutput::GetMenuArray($menu_id);
			$keys = array_keys($menu_array);
			$all_keys = array_merge($all_keys,$keys);
		}
		$all_keys = array_unique($all_keys);

		//then check $gp_index agains $all_keys
		foreach( $gp_index as $title => $index ){
			if( in_array($index, $all_keys) ){
				continue;
			}
			$avail[] = $title;
		}
		return $avail;
	}


	/**
	 * Display row
	 *
	 */
	public function SearchDisplayRow($title){
		global $langmessage, $gpLayouts, $gp_index, $gp_menu, $gp_titles;

		$title_index		= $gp_index[$title];
		$is_special			= \common::SpecialOrAdmin($title);
		$file				= \gpFiles::PageFile($title);
		$stats				= @stat($file);
		$mtime				= false;
		$size				= false;
		$layout				= \gp\admin\Menu\Tools::CurrentLayout($title_index);
		$layout_info		= $gpLayouts[$layout];


		if( $stats ){
			$mtime = $stats['mtime'];
			$size = $stats['size'];
		}


		echo '<tr><td>';

		$label = \common::GetLabel($title);
		echo \common::Link($title,\common::LabelSpecialChars($label));


		//area only display on mouseover
		echo '<div><div>';//style="position:absolute;bottom:0;left:10px;right:10px;"

		echo $this->Link('Admin/Menu/Ajax',$langmessage['rename/details'],'cmd=renameform&index='.urlencode($title_index),array('title'=>$langmessage['rename/details'],'data-cmd'=>'gpajax'));


		$q		= 'cmd=ToggleVisibility&index='.urlencode($title_index);
		if( isset($gp_titles[$title_index]['vis']) ){
			$label	= $langmessage['Visibility'].': '.$langmessage['Private'];
		}else{
			$label	= $langmessage['Visibility'].': '.$langmessage['Public'];
			$q		.= '&visibility=private';
		}

		$attrs	= array('title'=>$label,'data-cmd'=>'gpajax');
		echo $this->Link('Admin/Menu/Ajax',$label,$q,$attrs);

		if( $is_special === false ){
			echo \common::Link($title,$langmessage['Revision History'],'cmd=ViewHistory','class="view_edit_link not_multiple" data-cmd="gpabox"');
		}

		echo $this->Link('Admin/Menu/Ajax',$langmessage['Copy'],'cmd=CopyForm&index='.urlencode($title_index),array('title'=>$langmessage['Copy'],'data-cmd'=>'gpabox'));

		echo '<span>';
		echo $langmessage['layout'].': ';
		echo $this->Link('Admin/Menu',$layout_info['label'],'cmd=layout&index='.urlencode($title_index),array('title'=>$langmessage['layout'],'data-cmd'=>'gpabox'));
		echo '</span>';

		if( $is_special === false ){
			echo $this->Link('Admin/Menu/Ajax',$langmessage['delete'],'cmd=MoveToTrash&index='.urlencode($title_index),array('title'=>$langmessage['delete_page'],'data-cmd'=>'postlink','class'=>'gpconfirm'));
		}

		\gpPlugin::Action('MenuPageOptions',array($title,$title_index,false,$layout_info));

		//stats
		if( gpdebug ){
			echo '<span>Data Index: '.$title_index.'</span>';
		}
		echo '</div>&nbsp;</div>';

		//types
		echo '</td><td>';
		$this->TitleTypes($title_index);

		//children
		echo '</td><td>';
		if( isset($Inherit_Info[$title_index]) && isset($Inherit_Info[$title_index]['children']) ){
			echo $Inherit_Info[$title_index]['children'];
		}elseif( isset($gp_menu[$title_index]) ){
			echo '0';
		}else{
			echo $langmessage['Not In Main Menu'];
		}

		//size
		echo '</td><td>';
		if( $size ){
			echo \admin_tools::FormatBytes($size);
		}

		//modified
		echo '</td><td>';
		if( $mtime ){
			echo \common::date($langmessage['strftime_datetime'],$mtime);
		}

		echo '</td></tr>';
	}



	/**
	 * List section types
	 *
	 */
	public function TitleTypes($title_index){
		global $gp_titles;

		$types		= explode(',',$gp_titles[$title_index]['type']);
		$types		= array_filter($types);
		$types		= array_unique($types);

		foreach($types as $i => $type){
			if( isset($this->section_types[$type]) && isset($this->section_types[$type]['label']) ){
				$types[$i] = $this->section_types[$type]['label'];
			}
		}

		echo implode(', ',$types);
	}
}