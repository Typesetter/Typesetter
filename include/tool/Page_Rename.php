<?php
defined('is_running') or die('Not an entry point...');


class gp_rename{

	static function RenameForm($title,$action){
		global $langmessage,$page,$gp_index,$gp_titles;


		$id = $gp_index[$title];
		$label = common::GetLabel($title);

		$title_info = $gp_titles[$id];

		if( empty($_REQUEST['new_title']) ){
			$new_title = common::LabelSpecialChars($label);
		}else{
			$new_title = htmlspecialchars($_REQUEST['new_title']);
		}
		$new_title = str_replace('_',' ',$new_title);


		//show more options?
		$hidden_rows = false;

		ob_start();
		echo '<div class="inline_box">';
		echo '<form action="'.$action.'" method="post" id="gp_rename_form">';
		echo '<input type="hidden" name="old_title" value="'.htmlspecialchars(str_replace('_',' ',$title)).'" />';

		echo '<h2>'.$langmessage['rename/details'].'</h2>';

		echo '<input type="hidden" name="title" value="'.htmlspecialchars($title).'" />';

		echo '<table class="bordered full_width" id="gp_rename_table">';
		echo '<thead>';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

		//label
		echo '<tbody>';
		echo '<tr><td class="formlabel">'.$langmessage['label'].'</td>';
		echo '<td><input type="text" class="title_label gpinput" name="new_label" maxlength="100" size="50" value="'.$new_title.'" />';
		echo '</td></tr>';

		//slug
		$attr = '';
		$class = 'new_title';
		$editable = true;

		if( $title == admin_tools::LabelToSlug($label) ){
			$attr = 'disabled="disabled" ';
			$class .= ' sync_label';
		}
		echo '<tr><td class="formlabel">'.$langmessage['Slug/URL'].'</td>';
		echo '<td><input type="text" class="'.$class.' gpinput" name="new_title" maxlength="100" size="50" value="'.htmlspecialchars($title).'" '.$attr.'/>';
		if( $editable ){
			echo ' <div class="label_synchronize">';
			if( empty( $attr ) ){
				echo '<a href="#">'.$langmessage['sync_with_label'].'</a>';
				echo '<a href="#" class="slug_edit nodisplay">'.$langmessage['edit'].'</a>';
			}else{
				echo '<a href="#" class="nodisplay">'.$langmessage['sync_with_label'].'</a>';
				echo '<a href="#" class="slug_edit">'.$langmessage['edit'].'</a>';
			}
			echo '</div>';
		}
		echo '</td>';
		echo '</tr>';



		//browser title defaults to label
			$attr = '';
			$class = 'browser_title';
			if( isset($title_info['browser_title']) ){
				echo '<tr>';
				$browser_title = $title_info['browser_title'];
			}else{
				echo '<tr class="nodisplay">';
				$hidden_rows = true;
				$browser_title = $label;
				$attr = 'disabled="disabled" ';
				$class .= ' sync_label';
			}
			echo '<td class="formlabel">';
			echo $langmessage['browser_title'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="'.$class.' gpinput" size="50" name="browser_title" value="'.$browser_title.'" '.$attr.'/>';
			echo ' <div class="label_synchronize">';
			if( empty( $attr ) ){
				echo '<a href="#">'.$langmessage['sync_with_label'].'</a>';
				echo '<a href="#" class="slug_edit nodisplay">'.$langmessage['edit'].'</a>';
			}else{
				echo '<a href="#" class="nodisplay">'.$langmessage['sync_with_label'].'</a>';
				echo '<a href="#" class="slug_edit">'.$langmessage['edit'].'</a>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';

		//meta keywords
			$keywords = '';
			if( isset($title_info['keywords']) ){
				echo '<tr>';
				$keywords = $title_info['keywords'];
			}else{
				echo '<tr class="nodisplay">';
				$hidden_rows = true;
			}
			echo '<td class="formlabel">';
			echo $langmessage['keywords'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="gpinput" size="50" name="keywords" value="'.$keywords.'" />';
			echo '</td>';
			echo '</tr>';

		//meta description
			$description = '';
			if( isset($title_info['description']) ){
				echo '<tr>';
				$description = $title_info['description'];
			}else{
				echo '<tr class="nodisplay">';
				$hidden_rows = true;
			}
			echo '<td class="formlabel">';
			echo $langmessage['description'];
			echo '</td>';
			echo '<td>';
			//echo '<input type="text" class="gpinput" size="50" name="description" value="'.$description.'" />';
			echo '<textarea class="gptextarea show_character_count" rows="2" cols="50" name="description">'.$description.'</textarea>';

			$count_label = sprintf($langmessage['_characters'],'<span>'.strlen($description).'</span>');
			echo '<div class="character_count">'.$count_label.'</div>';

			echo '</td>';
			echo '</tr>';

		//robots
			$rel = '';
			if( isset($title_info['rel']) ){
				echo '<tr>';
				$rel = $title_info['rel'];
			}else{
				echo '<tr class="nodisplay">';
				$hidden_rows = true;
			}
			echo '<td class="formlabel">';
			echo $langmessage['robots'];
			echo '</td>';
			echo '<td>';

			echo '<label>';
			$checked = (strpos($rel,'nofollow') !== false) ? 'checked="checked"' : '';
			echo '<input type="checkbox" name="nofollow" value="nofollow" '.$checked.'/> ';
			echo '  Nofollow ';
			echo '</label>';

			echo '<label>';
			$checked = (strpos($rel,'noindex') !== false) ? 'checked="checked"' : '';
			echo '<input type="checkbox" name="noindex" value="noindex" '.$checked.'/> ';
			echo ' Noindex';
			echo '</label>';

			echo '</td>';
			echo '</tr>';

		echo '</tbody>';
		echo '</table>';


		//redirection
		echo '<p id="gp_rename_redirect" class="nodisplay">';
		echo '<label>';
		echo '<input type="checkbox" name="add_redirect" value="add" /> ';
		echo sprintf($langmessage['Auto Redirect'],'"'.$title.'"');
		echo '</label>';
		echo '</p>';

		echo '<p>';
		if( $hidden_rows )  echo ' &nbsp; <a data-cmd="showmore" >+ '.$langmessage['more_options'].'</a>';
		echo '</p>';

		echo '<p>';
			echo '<input type="hidden" name="cmd" value="renameit"/> ';
			echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'" class="menupost gpsubmit"/>';
			echo '<input type="button" class="admin_box_close gpcancel" name="" value="'.$langmessage['cancel'].'" />';
			echo '</p>';

		echo '</form>';
		echo '</div>';

		$content = ob_get_clean();

		$page->ajaxReplace = array();


		$array = array();
		$array[0] = 'admin_box_data';
		$array[1] = '';
		$array[2] = $content;
		$page->ajaxReplace[] = $array;



		//call renameprep function after admin_box
		$array = array();
		$array[0] = 'renameprep';
		$array[1] = '';
		$array[2] = '';
		$page->ajaxReplace[] = $array;


	}


	static function RenameFile($title){
		global $langmessage, $page, $gp_index, $gp_titles;

		//change the title
		$title = gp_rename::RenameFileWorker($title);
		if( $title === false ){
			return false;
		}


		if( !isset($gp_index[$title]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$id = $gp_index[$title];
		$title_info = &$gp_titles[$id];

		//change the label
		$title_info['label'] = admin_tools::PostedLabel($_POST['new_label']);
		if( isset($title_info['lang_index']) ){
			unset($title_info['lang_index']);
		}


		//change the browser title
		$auto_browser_title = strip_tags($title_info['label']);
		$custom_browser_title = false;
		if( isset($_POST['browser_title']) ){
			$browser_title = $_POST['browser_title'];
			$browser_title = htmlspecialchars($browser_title);

			if( $browser_title != $auto_browser_title ){
				$title_info['browser_title'] = trim($browser_title);
				$custom_browser_title = true;
			}
		}
		if( !$custom_browser_title ){
			unset($title_info['browser_title']);
		}

		//keywords
		if( isset($_POST['keywords']) ){
			$title_info['keywords'] = htmlspecialchars($_POST['keywords']);
			if( empty($title_info['keywords']) ){
				unset($title_info['keywords']);
			}
		}


		//description
		if( isset($_POST['description']) ){
			$title_info['description'] = htmlspecialchars($_POST['description']);
			if( empty($title_info['description']) ){
				unset($title_info['description']);
			}
		}


		//robots
		$title_info['rel'] = '';
		if( isset($_POST['nofollow']) ){
			$title_info['rel'] = 'nofollow';
		}
		if( isset($_POST['noindex']) ){
			$title_info['rel'] .= ',noindex';
		}
		$title_info['rel'] = trim($title_info['rel'],',');
		if( empty($title_info['rel']) ) unset($title_info['rel']);


		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (R1)');
			return false;
		}

		message($langmessage['SAVED']);
		return $title;
	}



	static function RenameFileWorker($title){
		global $langmessage,$dataDir,$gp_index;

		//use new_label or new_title
		if( isset($_POST['new_title']) ){
			$new_title = admin_tools::PostedSlug($_POST['new_title']);
		}else{
			$new_title = admin_tools::LabelToSlug($_POST['new_label']);
		}

		//title unchanged
		if( $new_title == $title ){
			return $title;
		}

		$special_file = false;
		if( common::SpecialOrAdmin($title) ){
			$special_file = true;
		}

		if( !admin_tools::CheckTitle($new_title,$message) ){
			message($message);
			return false;
		}

		$old_gp_index = $gp_index;

		//re-index: make the new title point to the same data index
		$old_file = gpFiles::PageFile($title);
		$file_index = $gp_index[$title];
		unset($gp_index[$title]);
		$gp_index[$new_title] = $file_index;


		//rename the php file
		if( !$special_file ){
			$new_file = gpFiles::PageFile($new_title);

			//we don't have to rename files if we're using the index naming convention. See gpFiles::PageFile() for more info
			if( $new_file == $old_file ){

			//if the file being renamed doesn't use the index naming convention, then we'll still need to rename it
			}elseif( !rename($old_file,$new_file) ){
				message($langmessage['OOPS'].' (N3)');
				$gp_index = $old_gp_index;
				return false;
			}

			//gallery rename
			includeFile('special/special_galleries.php');
			special_galleries::RenamedGallery($title,$new_title);
		}


		//create a 301 redirect
		if( isset($_POST['add_redirect']) && $_POST['add_redirect'] == 'add' ){
			includeFile('admin/admin_missing.php');
			admin_missing::AddRedirect($title,$new_title);
		}


		gpPlugin::Action('RenameFileDone',array($file_index, $title, $new_title));

		return $new_title;
	}

}
