<?php

namespace gp\Page{

	defined('is_running') or die('Not an entry point...');

	class Rename{

		static $hidden_rows = false;

		/**
		 * Display form in popup for renaming page given by $index
		 *
		 */
		public static function RenameForm(){
			global $langmessage, $page, $gp_index, $gp_titles, $config;

			$index			= $_REQUEST['index'];
			if( !isset($gp_titles[$index]) ){
				msg($langmessage['OOPS'].' (Invalid Request)');
				return;
			}

			$action			= \gp\tool::GetUrl($page->requested);
			$label			= \gp\tool::GetLabelIndex($index);
			$title			= \gp\tool::IndexToTitle($index);
			$title_info		= $gp_titles[$index];

			$title_info		+= array(
								'browser_title'	=> '',
								'keywords'		=> '',
								'description'	=> '',
								'rel'			=> '',
								);

			if( empty($_REQUEST['new_title']) ){
				$new_title = \gp\tool::LabelSpecialChars($label);
			}else{
				$new_title = htmlspecialchars($_REQUEST['new_title']);
			}
			$new_title = str_replace('_',' ',$new_title);


			ob_start();
			echo '<div class="inline_box">';
			echo '<form action="'.$action.'" method="post" id="gp_rename_form">';

			echo '<input type="hidden" name="title" id="old_title" value="'.htmlspecialchars($title).'" />';
			echo '<input type="hidden" id="gp_space_char" value="'.htmlspecialchars($config['space_char']).'" />';


			echo '<h2>'.$langmessage['rename/details'].'</h2>';


			echo '<table class="bordered full_width" id="gp_rename_table">';
			echo '<thead>';
			echo '<tr><th colspan="2">';
			echo $langmessage['options'];
			echo '</th></tr>';
			echo '</thead>';
			echo '<tbody>';

			//label
			self::FormLabel('label');
			echo '<input type="text" class="title_label gpinput" name="new_label" maxlength="100" size="50" value="'.$new_title.'" />';
			echo '</td></tr>';


			//slug (title)
			$attr		= '';
			$class		= 'new_title';

			if( $title == \gp\admin\Tools::LabelToSlug($label) ){
				$attr = 'disabled="disabled" ';
				$class .= ' sync_label';
			}
			self::FormLabel('Slug/URL');
			echo '<input type="text" class="'.$class.' gpinput" name="new_title" maxlength="100" size="50" value="'.htmlspecialchars($title).'" '.$attr.'/>';
			self::ToggleSync($attr);
			echo '</td></tr>';



			//browser title defaults to label
			$attr			= '';
			$class			= 'browser_title';
			$browser_title	= $title_info['browser_title'];
			self::FormLabel('browser_title',$title_info['browser_title']);

			if( empty($title_info['browser_title']) ){
				$browser_title = htmlspecialchars($label);
				$attr = 'disabled="disabled" ';
				$class .= ' sync_label';
			}

			echo '<input type="text" class="'.$class.' gpinput" size="50" name="browser_title" value="'.$browser_title.'" '.$attr.'/>';
			self::ToggleSync($attr);
			echo '</td></tr>';


			//meta keywords
			self::FormLabel('keywords',$title_info['keywords']);
			echo '<input type="text" class="gpinput" size="50" name="keywords" value="'.$title_info['keywords'].'" />';
			echo '</td></tr>';


			//meta description
			self::FormLabel('description',$title_info['description']);
			$count_label = sprintf($langmessage['_characters'],'<span>'.strlen($title_info['description']).'</span>');
			echo '<span class="show_character_count gptextarea">';
			echo '<textarea rows="2" cols="50" name="description">'.$title_info['description'].'</textarea>';
			echo '<span class="character_count">'.$count_label.'</span>';
			echo '</span>';
			echo '</td></tr>';


			//robots
			self::FormLabel('robots',$title_info['rel']);
			echo '<label>';
			$checked = (strpos($title_info['rel'],'nofollow') !== false) ? 'checked="checked"' : '';
			echo '<input type="checkbox" name="nofollow" value="nofollow" '.$checked.'/> ';
			echo '  Nofollow ';
			echo '</label>';

			echo '<label>';
			$checked = (strpos($title_info['rel'],'noindex') !== false) ? 'checked="checked"' : '';
			echo '<input type="checkbox" name="noindex" value="noindex" '.$checked.'/> ';
			echo ' Noindex';
			echo '</label>';

			echo '</td></tr>';

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
			if( self::$hidden_rows )  echo ' &nbsp; <a data-cmd="showmore" >+ '.$langmessage['more_options'].'</a>';
			echo '</p>';

			echo '<p>';
			echo '<input type="hidden" name="cmd" value="RenameFile"/> ';
			echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'...'.'" class="gpsubmit" data-cmd="gppost"/>';
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

		protected static function FormLabel($lang_key, $hidden_if_empty = 'not-empty' ){
			global $langmessage;

			if( empty($hidden_if_empty) ){
				echo '<tr class="nodisplay">';
				self::$hidden_rows = true;
			}else{
				echo '<tr>';
			}
			echo '<td class="formlabel">';
			echo $langmessage[$lang_key];
			echo '</td><td>';
		}


		/**
		 * Display Sync Toggle
		 *
		 */
		protected static function ToggleSync($attr){
			global $langmessage;

			echo ' <div class="label_synchronize">';
			if( empty( $attr ) ){
				echo '<a data-cmd="ToggleSync">'.$langmessage['sync_with_label'].'</a>';
				echo '<a data-cmd="ToggleSync" class="slug_edit nodisplay">'.$langmessage['edit'].'</a>';
			}else{
				echo '<a data-cmd="ToggleSync" class="nodisplay">'.$langmessage['sync_with_label'].'</a>';
				echo '<a data-cmd="ToggleSync" class="slug_edit">'.$langmessage['edit'].'</a>';
			}
			echo '</div>';
		}


		/**
		 * Handle renaming a page based on POSTed data
		 *
		 */
		public static function RenameFile($title){
			global $langmessage, $page, $gp_index, $gp_titles;

			$page->ajaxReplace = array();


			//change the title
			$title = self::RenameFileWorker($title);
			if( $title === false ){
				return false;
			}


			if( !isset($gp_index[$title]) ){
				msg($langmessage['OOPS']);
				return false;
			}

			$id				= $gp_index[$title];
			$title_info		= &$gp_titles[$id];

			//change the label
			$title_info['label'] = \gp\admin\Tools::PostedLabel($_POST['new_label']);
			if( isset($title_info['lang_index']) ){
				unset($title_info['lang_index']);
			}

			//browser_title, keywords, description
			self::SetInfo($title_info, 'browser_title');
			self::SetInfo($title_info, 'keywords');
			self::SetInfo($title_info, 'description');
			self::SetRobots($title_info);


			//same as auto-generated?
			$auto_browser_title = strip_tags($title_info['label']);
			if( isset($title_info['browser_title']) && $title_info['browser_title'] == $auto_browser_title ){
				unset($title_info['browser_title']);
			}

			if( !\gp\admin\Tools::SavePagesPHP(true,true) ){
				return false;
			}

			return $title;
		}


		/**
		 * Set the title_info value if not emptpy
		 * Otherwise, unset the key
		 *
		 */
		private static function SetInfo( &$title_info, $key){

			if( isset($_POST[$key]) ){
				$title_info[$key] = htmlspecialchars($_POST[$key]);
				if( empty($title_info[$key]) ){
					unset($title_info[$key]);
				}
			}
		}

		/**
		 * Set the robot visibility
		 *
		 */
		private static function SetRobots(&$title_info){

			$title_info['rel'] = '';
			if( isset($_POST['nofollow']) ){
				$title_info['rel'] = 'nofollow';
			}

			if( isset($_POST['noindex']) ){
				$title_info['rel'] .= ',noindex';
			}

			$title_info['rel'] = trim($title_info['rel'],',');
			if( empty($title_info['rel']) ){
				unset($title_info['rel']);
			}
		}


		private static function RenameFileWorker($title){
			global $langmessage,$dataDir,$gp_index;

			//use new_label or new_title
			if( isset($_POST['new_title']) ){
				$new_title = \gp\admin\Tools::PostedSlug($_POST['new_title']);
			}else{
				$new_title = \gp\admin\Tools::LabelToSlug($_POST['new_label']);
			}

			//title unchanged
			if( $new_title == $title ){
				return $title;
			}

			$special_file = false;
			if( \gp\tool::SpecialOrAdmin($title) !== false ){
				$special_file = true;
			}

			if( !\gp\admin\Tools::CheckTitle($new_title,$message) ){
				msg($message);
				return false;
			}

			$old_gp_index = $gp_index;

			//re-index: make the new title point to the same data index
			$old_file = \gp\tool\Files::PageFile($title);
			$file_index = $gp_index[$title];
			unset($gp_index[$title]);
			$gp_index[$new_title] = $file_index;


			//rename the php file
			if( !$special_file ){
				$new_file = \gp\tool\Files::PageFile($new_title);

				//if the file being renamed doesn't use the index naming convention, then we'll still need to rename it
				if( $new_file != $old_file ){
					$new_dir = dirname($new_file);
					$old_dir = dirname($old_file);
					if( !\gp\tool\Files::Rename($old_dir,$new_dir) ){
						msg($langmessage['OOPS'].' (N3)');
						$gp_index = $old_gp_index;
						return false;
					}
				}

				//gallery rename
				\gp\special\Galleries::RenamedGallery($title,$new_title);
			}


			//create a 301 redirect
			if( isset($_POST['add_redirect']) && $_POST['add_redirect'] == 'add' ){
				\gp\admin\Settings\Missing::AddRedirect($title,$new_title);
			}


			\gp\tool\Plugins::Action('RenameFileDone',array($file_index, $title, $new_title));

			return $new_title;
		}

		/**
		 * Rename a page
		 *
		 */
		public static function RenamePage(){
			global $langmessage, $gp_index, $page;

			$new_title = self::RenameFile($page->title);
			if( ($new_title !== false) && $new_title != $page->title ){
				msg(sprintf($langmessage['will_redirect'],\gp\tool::Link_Page($new_title)));

				$page->head				.= '<meta http-equiv="refresh" content="15;url='.\gp\tool::GetUrl($new_title).'">';
				$page->ajaxReplace[]	= array('location',\gp\tool::GetUrl($new_title),15000);
				return true;
			}
			return false;
		}

	}

}

namespace{
	class gp_rename extends \gp\Page\Rename{}
}
