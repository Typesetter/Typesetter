<?php
defined('is_running') or die('Not an entry point...');


/*
 * wordpress info http://codex.wordpress.org/Creating_an_Error_404_Page
 * ability to see from url, something like /index.php/Special_Missing so that users can set "ErrorDocument 404 /index.php/Special_Missing?code=404" in .htaccess
 *
 */

includeFile('special/special_missing.php');

class admin_missing extends special_missing{

	function admin_missing(){
		global $langmessage;

		$this->Init();
		gp_edit::PrepAutoComplete(true,false);


		$cmd = common::GetCommand();
		$show = true;
		switch($cmd){

			case 'save404':
				$show = $this->Save404();
			break;
			case 'edit404':
				$this->Edit404();
			return;

			case 'editredir':
				$this->EditRedir();
			return;
			case 'saveredir';
				$this->SaveRedir();
			break;
			case 'updateredir':
				$this->UpdateRedir();
			break;

			case 'rmredir':
				$this->RmRedir();
			break;

			case 'newform':
				$this->RedirForm();
			return;

		}

		if( $show ){
			$this->Show();
		}
	}

	/* static */

	function AddRedirect($source,$target){
		global $dataDir;
		$error_data = array();
		$datafile = $dataDir.'/data/_site/error_data.php';
		if( file_exists($datafile) ){
			require($datafile);
		}
		$changed = false;

		//remove redirects from the $target
		if( isset($error_data['redirects'][$target]) ){
			unset($error_data['redirects'][$target]);
			$changed = true;
		}

		//redirect already exists for $source
		if( !isset($error_data['redirects'][$source]) ){
			$error_data['redirects'][$source]['target'] = $target;
			$error_data['redirects'][$source]['code'] = '301';
			$changed = true;
		}


		if( $changed ){
			gpFiles::SaveArray($datafile,'error_data',$error_data);
		}
	}


	function SaveData_Message(){
		global $langmessage;

		if( $this->SaveData() ){
			message($langmessage['SAVED']);
			return true;
		}else{
			message($langmessage['OOPS']);
			return false;
		}
	}
	function GetCodeLanguage($code){
		global $langmessage;
		switch($code){
			case '301':
			return $langmessage['301 Moved Permanently'];
			case '302':
			return $langmessage['302 Moved Temporarily'];
		}
		return '';
	}

	/**
	 * Show 404 info and Redirection list
	 *
	 */
	function Show(){
		global $langmessage;

		echo '<h2>'.$langmessage['Link Errors'].'</h2>';
		echo '<p>'.$langmessage['404_Usage'].'</p>';

		//404 Page
		echo '<table class="bordered full_width"><tr><th>'.$langmessage['404_Page'].'</th></tr></table>';

		echo '<div id="Page_404">';
		echo '<p>'.$langmessage['About_404_Page'].'</p>';
		echo '<p>';
		echo common::Link('Special_Missing',$langmessage['preview']);
		echo ' - ';
		echo common::Link('Admin_Missing',$langmessage['edit'],'cmd=edit404');
		echo '</p>';
		echo '</div>';


		//redirection
		echo '<table class="bordered full_width"><tr><th>'.$langmessage['Redirection'].'</th></tr></table>';
		echo '<div id="Redirection">';
		$this->ShowRedirection();
		echo '</div>';
	}


	function Save404(){

		$text =& $_POST['gpcontent'];
		gpFiles::cleanText($text);
		$this->error_data['404_TEXT'] = $text;
		if( $this->SaveData_Message() ){
			return true;
		}

		$this->Edit404($text);
		return false;
	}

	function Edit404($text=false){
		global $langmessage;
		if( $text === false ){
			if( isset($this->error_data['404_TEXT']) ){
				$text = $this->error_data['404_TEXT'];
			}else{
				$text = special_missing::DefaultContent();
			}
		}

		echo '<h2>'.$langmessage['Link Errors'].' &#187; '.$langmessage['404_Page'].'</h2>';


		echo '<form action="'.common::GetUrl('Admin_Missing').'" method="post">';
		echo '<input type="hidden" name="cmd" value="save404" />';

		gp_edit::UseCK($text);

		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel"/>';
		echo '</form>';

		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['Useful Variables'];
		echo '</th>';
		echo '<th>';
		echo '&nbsp;';
		echo '</th>';
		echo '</tr>';


		echo '<tr><td>';
		echo '{{Similar_Titles}}';
		echo '</td>';
		echo '<td>';
		echo $langmessage['Similar_Titles'];
		echo '</td>';
		echo '</tr></table>';

	}

	/*
	 *
	 * Redirection Functions
	 *
	 */

	function ShowRedirection(){
		global $langmessage,$page, $gp_index, $config;

		$page->head_js[] = '/include/thirdparty/tablesorter/tablesorter.js';
		$page->jQueryCode .= '$("table.tablesorter").tablesorter({cssHeader:"gp_header",cssAsc:"gp_header_asc",cssDesc:"gp_header_desc"});';


		echo '<p>'.$langmessage['About_Redirection'].'</p>';
		echo common::Link('Admin_Missing',$langmessage['New Redirection'],'cmd=newform',array('data-cmd'=>'gpabox'));


		if( empty($this->error_data['redirects']) ){
			return;
		}

		echo '<table class="bordered tablesorter full_width">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['Source URL'];
		echo '</th><th>';
		echo $langmessage['Target URL'];
		echo '</th><th>';
		echo $langmessage['Similarity'];
		echo '</th><th>';
		echo $langmessage['Method'];
		echo '</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';
		echo '</thead>';

		echo '<tbody>';
		$has_invalid_target = false;
		$admin_urls = admin_tools::AdminScripts();

		foreach($this->error_data['redirects'] as $source => $data){
			echo '<tr><td>';
			$raw_source = $source;
			if( !empty($data['raw_source']) ){
				$raw_source = $data['raw_source'];
			}
			echo htmlspecialchars($raw_source);
			echo '</td><td>';

			$target_show = $data['target'];
			if( strlen($target_show) > 40 ){
				$target_show = substr($target_show,0,15).' ... '.substr($target_show,-15);
			}
			$full_target = $this->GetTarget($data['target'],false);

			echo '<a href="'.htmlspecialchars($full_target).'">'.str_replace(' ','&nbsp;',htmlspecialchars($target_show)).'</a>';

			$is_gplink = $this->isGPLink($data['target']);
			if( !empty($data['target']) && $is_gplink ){
				if( !isset($gp_index[$data['target']]) && !isset($admin_urls[$data['target']]) ){ //(common::SpecialOrAdmin($data['target'] !== 'admin'))
					$has_invalid_target = true;
					echo ' <img src="'.common::GetDir('/include/imgs/error.png').'" alt="" height="16" width="16" style="vertical-align:middle" title="'.$langmessage['Target URL Invalid'].'"/> ';
				}
			}

			echo '</td><td>';
			if( $is_gplink ){
				$lower_source = strtolower($raw_source);
				$lower_target = strtolower($target_show);
				similar_text($lower_source,$lower_target,$percent);

				if( $config['auto_redir'] > 0 && $percent >= $config['auto_redir'] ){
					//echo '<span style="color:orange">'.number_format($percent,1).'%</span>';
					echo number_format($percent,1).'%';
				}else{
					echo number_format($percent,1).'%';
				}
			}else{
				echo '&nbsp;';
			}
			echo '</td><td>';
			echo $this->GetCodeLanguage($data['code']);
			echo '</td><td>';

			echo common::Link('Admin_Missing',$langmessage['edit'],'cmd=editredir&source='.urlencode($source),array('data-cmd'=>'gpabox'));

			echo ' &nbsp; ';
			echo common::Link($source,$langmessage['Test']);

			echo ' &nbsp; ';
			$title = sprintf($langmessage['generic_delete_confirm'],$source);
			echo common::Link('Admin_Missing',$langmessage['delete'],'cmd=rmredir&link='.urlencode($source),array('data-cmd'=>'postlink','title'=>$title,'class'=>'gpconfirm'));

			echo '</td></tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '<p>';
		echo common::Link('Admin_Missing',$langmessage['New Redirection'],'cmd=newform',array('data-cmd'=>'gpabox'));
		echo '</p>';


		if( $has_invalid_target ){
			echo '<p>';
			echo ' <img src="'.common::GetDir('/include/imgs/error.png').'" alt="" height="16" width="16" style="vertical-align:middle" title="'.$langmessage['Target URL Invalid'].'"/> ';
			echo $langmessage['Target URL Invalid'];
			echo '</p>';
		}
	}


	//using inline_box for this one for autocomplete init
	function RedirForm($values=array()){
		global $langmessage,$page;

		$values += array('cmd'=>'saveredir','source'=>'','target'=>'','code'=>'','orig_source'=>'');

		$codes = array('301'=>$langmessage['301 Moved Permanently'],'302'=>$langmessage['302 Moved Temporarily']);

		echo '<div class="inline_box" id="gp_redir">';
		echo '<h2>'.$langmessage['New Redirection'].'</h2>';
		echo '<form method="post" action="'.common::GetUrl('Admin_Missing').'">';
		echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($values['cmd']).'"/>';
		echo '<input type="hidden" name="orig_source" value="'.htmlspecialchars($values['orig_source']).'"/>';


		echo '<table class="bordered">';
		echo '<tr><th colspan="2">'.$langmessage['options'].'</th></tr>';

		echo '<tr><td>';
		echo $langmessage['Source URL'];
		echo '</td><td>';
		echo common::GetUrl('');
		echo '<input type="text" name="source" value="'.htmlspecialchars($values['source']).'" size="20" class="gpinput"/>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['Target URL'];
		echo '</td><td>';
		echo '<input type="text" name="target" value="'.htmlspecialchars($values['target']).'" class="autocomplete gpinput" size="40" />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['Method'];
		echo '</td><td>';
		echo '<select name="code" class="gpselect">';
		foreach($codes as $code_key => $code_value){
			$selected = '';
			if( $code_key == $values['code'] ){
				$selected = ' selected="selected"';
			}
			echo '<option value="'.$code_key.'"'.$selected.'>'.htmlspecialchars($code_value).'</option>';
		}
		echo '</select>';
		echo '</td></tr>';

		echo '</table>';

		echo '<p>';
		echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'" class="gpsubmit" />'; //not using gppost because of autocomplete
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';


		echo '</form>';
		echo '</div>';

	}

	function CheckRedir(){
		global $langmessage;

		if( empty($_POST['source']) ){
			message($langmessage['OOPS'].' (Empty Source)');
			return false;
		}

		if( $_POST['source'] == $_POST['target'] ){
			message($langmessage['OOPS'].' (Infinite Loop)');
			return false;
		}

		if( admin_tools::PostedSlug($_POST['source']) == admin_tools::PostedSlug($_POST['target']) ){
			message($langmessage['OOPS'].' (Infinite Loop)');
			return false;
		}

		if( $_POST['code'] != '302' ){
			$_POST['code'] = 301;
		}

		return true;

	}

	/**
	 * Update the settings for an existing redirection
	 *
	 */
	function UpdateRedir(){
		global $langmessage;

		if( !$this->CheckRedir() ){
			return false;
		}

		$orig_source = $_POST['orig_source'];
		$source = admin_tools::PostedSlug( $orig_source );

		if( !isset($this->error_data['redirects'][$orig_source]) ){
			message($langmessage['OOPS'].' (Entry not found)');
			return false;
		}

		$data = array();
		$data['target'] = $_POST['target'];
		$data['code'] = $_POST['code'];
		$data['raw_source'] = $_POST['source'];

		if( !gpFiles::ArrayReplace($orig_source,$source,$data,$this->error_data['redirects']) ){
			message($langmessage['OOPS']);
			return false;
		}

		return $this->SaveData_Message();
	}

	/**
	 * Edit an existing redirection
	 *
	 */
	function EditRedir(){

		$source = admin_tools::PostedSlug( $_REQUEST['source'] );
		if( !isset($this->error_data['redirects'][$source]) ){
			message($langmessage['OOPS'].' (Invalid Redirect)');
			return false;
		}

		$args = $this->error_data['redirects'][$source];
		$args['cmd'] = 'updateredir';
		$args['orig_source'] = $source;
		$args['source'] = $source;
		$this->RedirForm($args);
	}

	/**
	 * Save a new redirection
	 *
	 */
	function SaveRedir(){
		global $langmessage;

		if( !$this->CheckRedir() ){
			return false;
		}

		$source = admin_tools::PostedSlug( $_POST['source'] );

		if( isset($this->error_data['redirects'][$source]) ){
			message($langmessage['OOPS'].' (Redirect Already Set)');
			return false;
		}

		$this->error_data['redirects'][$source] = array();
		$this->error_data['redirects'][$source]['target'] = $_POST['target'];
		$this->error_data['redirects'][$source]['code'] = $_POST['code'];
		$this->error_data['redirects'][$source]['raw_source'] = $_POST['source'];

		return $this->SaveData_Message();
	}

	/**
	 * Remove a redirection
	 *
	 */
	function RmRedir(){
		global $langmessage;

		$link =& $_POST['link'];
		if( !isset($this->error_data['redirects'][$link]) ){
			message($langmessage['OOPS']);
			return false;
		}

		unset($this->error_data['redirects'][$link]);
		return $this->SaveData_Message();
	}


}




