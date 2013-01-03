<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_menu_tools.php');

class page_layout{

	var $from_page = false;
	var $show_popup = false;
	var $title = false;

	function page_layout($cmd,$url,$query_string=''){
		global $gp_index;

		//if the request is made from the page, we want to remember that and send an appropriate response
		if( isset($_REQUEST['from']) && $_REQUEST['from'] == 'page' ){
			$query_string .= '&from=page';
			$this->from_page = true;
		}
		$query_string .= '&';
		$query_string = ltrim($query_string,'&');

		switch($cmd){
			case 'layout':
				$this->SelectLayout($url,$query_string);
				$this->show_popup = true;
			return;
			case 'uselayout':
				$this->SetLayout();
			return;
			case 'restorelayout':
				$this->RestoreLayout();
			return;
		}
	}

	function Result(){
		global $page;

		if( $this->from_page && $this->title){
			if( !$this->show_popup ){
				$url = common::AbsoluteUrl($this->title,'',true,false);
				$page->ajaxReplace[] = array('location',$url,0);
			}
			return true;
		}

		return $this->show_popup;
	}

	/**
	 * Remove any layout setting from a page.
	 * The page will revert to inheriting the layout setting from the site configuration or a parent page
	 *
	 */
	function RestoreLayout(){
		global $gp_titles,$gp_index,$langmessage;

		$index = $_POST['index'];
		$title = common::IndexToTitle($index);

		if( !$title ){
			message($langmessage['OOPS']);
			return;
		}
		$this->title = $title;

		if( !common::verify_nonce('restore') ){
			message($langmessage['OOPS']);
			return;
		}


		unset($gp_titles[$index]['gpLayout']);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			return false;
		}

		//reset the layout array
		message($langmessage['SAVED']);
	}


	/**
	 * Assign a layout to the $title. Child pages without a layout assigned will inherit this setting
	 * @param string $title
	 */
	function SetLayout(){
		global $gp_index, $gp_titles, $langmessage, $gpLayouts;

		$index = $_POST['index'];
		$title = common::IndexToTitle($index);

		if( !$title ){
			message($langmessage['OOPS']);
			return;
		}
		$this->title = $title;

		$layout = $_POST['layout'];
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return;
		}

		if( !common::verify_nonce('use_'.$layout) ){
			message($langmessage['OOPS']);
			return;
		}


		//unset, then reset if needed
		unset($gp_titles[$index]['gpLayout']);
		$currentLayout = display::OrConfig($index,'gpLayout');
		if( $currentLayout != $layout ){
			$gp_titles[$index]['gpLayout'] = $layout;
		}

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}

		message($langmessage['SAVED']);
	}


	/**
	 * Display current layout, list of available layouts and list of titles affected by the layout setting for $title
	 *
	 */
	function SelectLayout($url,$query_string){
		global $gp_titles, $gpLayouts, $langmessage, $config, $gp_index;

		$index = $_REQUEST['index'];
		$title = common::IndexToTitle($index);
		if( !$title ){
			echo $langmessage['OOPS'];
			return;
		}
		$this->title = $title;

		$Inherit_Info = admin_menu_tools::Inheritance_Info();
		$curr_layout = admin_menu_tools::CurrentLayout($index);
		$curr_info = $gpLayouts[$curr_layout];


		echo '<div class="inline_box">';

		echo '<h3>';
		echo $langmessage['current_layout'].': &nbsp; ';
		echo '<span class="layout_color_id" style="background-color:'.$curr_info['color'].';" title="'.$curr_info['color'].'"></span> &nbsp; ';
		echo str_replace('_',' ',$curr_info['label']);
		echo '</h3>';

		if( !empty($gp_titles[$index]['gpLayout']) ){
			echo '<p>';

			if( isset($Inherit_Info[$index]['parent_layout']) ){
				$parent_layout = $Inherit_Info[$index]['parent_layout'];
			}else{
				$parent_layout = $config['gpLayout'];
			}
			$parent_info = $gpLayouts[$parent_layout];

			echo $langmessage['restore'].': ';
			$span = '<span class="layout_color_id" style="background-color:'.$parent_info['color'].';" title="'.$parent_info['color'].'"></span> ';
			echo common::Link($url,$span.$parent_info['label'],$query_string.'cmd=restorelayout&index='.urlencode($index),array('data-cmd'=>'postlink','title'=>$langmessage['restore']),'restore');
			echo '</p>';
		}


		echo '<table class="bordered full_width">';

		echo '<tr>';
			echo '<th>';

			echo $langmessage['available_layouts'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['theme'];
			echo '</th>';
			echo '</tr>';

		if( count($gpLayouts) < 2 ){
			echo '<tr><td colspan="2">';
			echo $langmessage['Empty'];
			echo '</td></tr>';
			echo '</table>';
			echo common::Link('Admin_Theme_Content',$langmessage['new_layout']);
			echo '</div>';
			return;
		}

		foreach($gpLayouts as $layout => $info){
			if( $layout == $curr_layout ){
				continue;
			}
			echo '<tr>';
			echo '<td>';
			echo '<span class="layout_color_id" style="background-color:'.$info['color'].';" title="'.$info['color'].'">';
			echo '</span> ';
			if( $layout != $curr_layout ){
				echo common::Link($url,$info['label'],$query_string.'cmd=uselayout&index='.urlencode($index).'&layout='.urlencode($layout),array('data-cmd'=>'postlink'),'use_'.$layout);

			}
			echo '</td>';
			echo '<td>';
			echo $info['theme'];
			echo '</td>';
			echo '</tr>';

		}
		echo '</table>';


		//show affected pages
		$affected = page_layout::GetAffectedFiles($index);

		echo '<br/>';

		echo '<table class="bordered full_width">';
		echo '<tr><th>'.$langmessage['affected_files'].'</th></tr></table>';

		echo '<p class="sm">'.$langmessage['about_layout_change'].'</p>';
		echo '<p class="admin_note" style="width:35em">';

		$label = common::GetLabelIndex($index,false);
		echo common::LabelSpecialChars($label);

		$i = 0;
		foreach($affected as $affected_label){
			$i++;
			echo ', '.$affected_label;
		}
		echo '</p>';

		echo '<p>';
		echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
		echo '</p>';

		echo '<p class="admin_note">';
		echo '<b>'.$langmessage['see_also'].'</b> ';
		echo common::Link('Admin_Theme_Content',$langmessage['layouts']);
		echo '</p>';

		echo '</div>';
	}

	/**
	 * Get a list of titles that inherit layout settings from the page with $index
	 * @param string $index
	 *
	 */
	function GetAffectedFiles($index){
		global $gp_titles, $gp_menu;

		$temp = $gp_menu;
		reset($temp);
		$result = array();

		$i = 0;
		do{
			$menu_key = key($temp);
			$info = current($temp);
			if( !isset($info['level']) ){
				break;
			}
			$level = $info['level'];

			unset($temp[$menu_key]);
			if( $index === $menu_key ){
				page_layout::InheritingLayout($level+1,$temp,$result);
			}
			$i++;
		}while( (count($temp) > 0) );
		return $result;
	}

	function InheritingLayout($searchLevel,&$menu,&$result){
		global $gp_titles;

		$children = true;
		do{
			$menu_key = key($menu);
			$info = current($menu);
			if( !isset($info['level']) ){
				break;
			}
			$level = $info['level'];

			if( $level < $searchLevel ){
				return;
			}
			if( $level > $searchLevel ){
				if( $children ){
					page_layout::InheritingLayout($level,$menu,$result);
				}else{
					unset($menu[$menu_key]);
				}
				continue;
			}

			unset($menu[$menu_key]);
			if( !empty($gp_titles[$menu_key]['gpLayout']) ){
				$children = false;
				continue;
			}
			$children = true;

			//exclude external links
			if( $menu_key[0] == '_' ){
				continue;
			}

			$label = common::GetLabelIndex($menu_key,false);
			$result[] = common::LabelSpecialChars($label);
		}while( count($menu) > 0 );

	}



}
