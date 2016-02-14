<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');


/*
 * wordpress info http://codex.wordpress.org/Creating_an_Error_404_Page
 * ability to see from url, something like /index.php/Special_Missing so that users can set "ErrorDocument 404 /index.php/Special_Missing?code=404" in .htaccess
 *
 */

class Missing extends \gp\special\Missing{

	protected $page;
	protected $codes;


	public function __construct($args){
		global $langmessage;

		$this->page		= $args['page'];
		$this->codes	= array('301'=>$langmessage['301 Moved Permanently'],'302'=>$langmessage['302 Moved Temporarily']);

		$this->Init();
		\gp\tool\Editing::PrepAutoComplete();


		$cmd = \gp\tool::GetCommand();
		$show = true;
		switch($cmd){

			case 'Save404':
				$show = $this->Save404();
			break;
			case 'Edit404':
				$this->Edit404();
			return;


			//editing
			case 'EditRedir':
				$this->EditRedir();
			return;
			case 'updateredir':
				$this->UpdateRedir();
			break;

			//new redirect
			case 'SaveRedir';
				$this->SaveRedir();
			break;

			case 'RmRedir':
				$this->RmRedir();
			break;
		}

		if( $show ){
			$this->Show();
		}
	}


	/**
	 * Add instructions for a 301 or 302 redirect
	 *
	 */
	public static function AddRedirect($source,$target){
		global $dataDir;


		$datafile		= $dataDir.'/data/_site/error_data.php';
		$error_data		= \gp\tool\Files::Get('_site/error_data');
		if( !$error_data ){
			$error_data = array();
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
			\gp\tool\Files::SaveData($datafile,'error_data',$error_data);
		}
	}


	protected function SaveMissingData(){
		global $langmessage;

		if( !\gp\tool\Files::SaveData($this->datafile,'error_data',$this->error_data) ){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);
		return true;
	}

	protected function GetCodeLanguage($code){
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
	protected function Show(){
		global $langmessage;

		echo '<h2>'.$langmessage['Link Errors'].'</h2>';
		echo '<p>'.$langmessage['404_Usage'].'</p>';

		//404 Page
		echo '<h3>'.$langmessage['404_Page'].'</h3>';

		echo '<div id="Page_404">';
		echo '<p>'.$langmessage['About_404_Page'].'</p>';
		echo '<p>';
		echo \gp\tool::Link('Special_Missing',$langmessage['preview'],'','class="gpsubmit"');
		echo ' &nbsp; ';
		echo \gp\tool::Link('Admin/Missing',$langmessage['edit'],'cmd=Edit404','class="gpsubmit"');
		echo '</p>';
		echo '</div>';


		//redirection
		echo '<br/><h3>'.$langmessage['Redirection'].'</h3>';
		echo '<div id="Redirection">';
		$this->ShowRedirection();
		echo '</div>';
	}


	protected function Save404(){

		$text =& $_POST['gpcontent'];
		\gp\tool\Files::cleanText($text);
		$this->error_data['404_TEXT'] = $text;
		if( $this->SaveMissingData() ){
			return true;
		}

		$this->Edit404($text);
		return false;
	}

	protected function Edit404($text=false){
		global $langmessage;
		if( $text === false ){
			if( isset($this->error_data['404_TEXT']) ){
				$text = $this->error_data['404_TEXT'];
			}else{
				$text = self::DefaultContent();
			}
		}

		echo '<h2>';
		echo \gp\tool::Link('Admin/Missing',$langmessage['Link Errors']);
		echo ' &#187; '.$langmessage['404_Page'].'</h2>';


		echo '<form action="'.\gp\tool::GetUrl('Admin/Missing').'" method="post">';
		echo '<input type="hidden" name="cmd" value="save404" />';

		\gp\tool\Editing::UseCK($text);

		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel"/>';
		echo '</form>';

		echo '<br/>';

		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['Useful Variables'];
		echo '</th><th></th></tr>';


		echo '<tr><td>';
		echo '{{Similar_Titles}}';
		echo '</td><td>';
		echo $langmessage['Similar_Titles'];
		echo '</td>';
		echo '</tr></table>';

	}


	/**
	 * Display current redirection settings
	 *
	 */
	protected function ShowRedirection(){
		global $langmessage, $gp_index, $config;

		$this->page->head_js[]		= '/include/thirdparty/tablesorter/tablesorter.js';
		$this->page->jQueryCode		.= '$("table.tablesorter").tablesorter({cssHeader:"gp_header",cssAsc:"gp_header_asc",cssDesc:"gp_header_desc"});';


		echo '<p class="cf">';
		echo $langmessage['About_Redirection'];
		echo '</p>';


		echo '<form method="post" action="'.\gp\tool::GetUrl('Admin/Missing').'">';
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
		$has_invalid_target		= false;
		$admin_urls				= \gp\admin\Tools::AdminScripts();

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


			$is_gplink = $this->isGPLink($data['target']);
			if( !empty($data['target']) && $is_gplink ){
				if( !isset($gp_index[$data['target']]) && !isset($admin_urls[$data['target']]) ){
					$has_invalid_target = true;
					echo ' <i class="fa fa-exclamation-triangle" title="'.$langmessage['Target URL Invalid'].'"></i> &nbsp; ';
				}
			}

			echo '<a href="'.htmlspecialchars($full_target).'">'.str_replace(' ','&nbsp;',htmlspecialchars($target_show)).'</a>';

			echo '</td><td>';
			if( $is_gplink ){
				$lower_source = strtolower($raw_source);
				$lower_target = strtolower($target_show);
				similar_text($lower_source,$lower_target,$percent);

				if( $config['auto_redir'] > 0 && $percent >= $config['auto_redir'] ){
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

			echo \gp\tool::Link('Admin/Missing',$langmessage['edit'],'cmd=EditRedir&source='.urlencode($source),array('data-cmd'=>'gpabox'));

			echo ' &nbsp; ';
			echo \gp\tool::Link($source,$langmessage['Test']);

			echo ' &nbsp; ';
			$title = sprintf($langmessage['generic_delete_confirm'],$source);
			echo \gp\tool::Link('Admin/Missing',$langmessage['delete'],'cmd=RmRedir&link='.urlencode($source),array('data-cmd'=>'postlink','title'=>$title,'class'=>'gpconfirm'));

			echo '</td></tr>';
		}
		echo '</tbody>';

		$this->AddMissingRow();
		echo '</table>';
		echo '</form>';

		echo '<br/>';

		echo '<p>';

		if( $has_invalid_target ){
			echo ' &nbsp; <span><i class="fa fa-exclamation-triangle"></i> &nbsp; ';
			echo $langmessage['Target URL Invalid'];
			echo '</span>';
		}
		echo '</p>';

	}


	/**
	 * Add Redirection form for <tfoot>
	 *
	 */
	protected function AddMissingRow(){
		global $langmessage;

		$_REQUEST += array('source'=>'','target'=>'','code'=>'','orig_source'=>'');

		//source
		echo '<tfoot>';
		echo '<tr><td>';
		echo '<input type="text" name="source" value="'.htmlspecialchars($_REQUEST['source']).'" size="20" class="gpinput" required />';

		//target
		echo '</td><td>';
		echo '<input type="text" name="target" value="'.htmlspecialchars($_REQUEST['target']).'" class="title-autocomplete gpinput" size="40" />';

		//code
		echo '</td><td>';
		echo '</td><td>';
		$this->CodeSelect($_REQUEST['code']);

		echo '</td><td>';
		echo '<button type="submit" name="cmd" value="SaveRedir">'.$langmessage['New Redirection'].'</button>';

		echo '</td></tr>';
		echo '</tfoot>';
	}


	/**
	 * Edit an existing redirection
	 *
	 */
	protected function EditRedir(){

		$source = \gp\admin\Tools::PostedSlug( $_REQUEST['source'] );
		if( !isset($this->error_data['redirects'][$source]) ){
			message($langmessage['OOPS'].' (Invalid Redirect)');
			return false;
		}

		$args					= $this->error_data['redirects'][$source];
		$args['cmd']			= 'updateredir';
		$args['orig_source']	= $source;
		$args['source']			= $source;

		$this->RedirForm($args);
	}


	/**
	 * Using inline_box for this one for autocomplete init
	 *
	 */
	protected function RedirForm($values=array()){
		global $langmessage, $gp_index;

		$values += array('cmd'=>'saveredir','source'=>'','target'=>'','code'=>'','orig_source'=>'');


		echo '<div class="inline_box" id="gp_redir">';
		echo '<h2>'.$langmessage['New Redirection'].'</h2>';
		echo '<form method="post" action="'.\gp\tool::GetUrl('Admin/Missing').'">';
		echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($values['cmd']).'"/>';
		echo '<input type="hidden" name="orig_source" value="'.htmlspecialchars($values['orig_source']).'"/>';


		echo '<table class="bordered full_width">';
		echo '<tr><th colspan="2">'.$langmessage['options'].'</th></tr>';


		//source url
		echo '<tr><td>';
		echo $langmessage['Source URL'];
		echo '</td><td>';
		echo \gp\tool::GetUrl('');
		echo '<input type="text" name="source" value="'.htmlspecialchars($values['source']).'" size="20" class="gpinput" required />';
		echo '</td></tr>';


		//method
		echo '<tr><td>';
		echo $langmessage['Method'];
		echo '</td><td>';
		$this->CodeSelect($values['code']);
		echo '</td></tr>';


		//target url
		echo '<tr><td>';
		echo $langmessage['Target URL'];
		echo '</td><td>';
		echo '<input type="text" name="target" value="'.htmlspecialchars($values['target']).'" class="title-autocomplete gpinput" size="40" />';
		echo '</td></tr>';


		echo '</table>';

		echo '<p>';
		echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'" class="gpsubmit" />'; //not using gppost because of autocomplete
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';


		echo '</form>';
		echo '</div>';
	}

	/**
	 * Display select for redirect code
	 *
	 */
	protected function CodeSelect($value){
		echo '<select name="code" class="gpselect">';
		foreach($this->codes as $code_key => $code_value){
			$selected = '';
			if( $code_key == $value ){
				$selected = ' selected="selected"';
			}
			echo '<option value="'.$code_key.'"'.$selected.'>'.htmlspecialchars($code_value).'</option>';
		}
		echo '</select>';
	}

	protected function CheckRedir(){
		global $langmessage;

		if( empty($_POST['source']) ){
			message($langmessage['OOPS'].' (Empty Source)');
			return false;
		}

		if( $_POST['source'] == $_POST['target'] ){
			message($langmessage['OOPS'].' (Infinite Loop)');
			return false;
		}

		if( \gp\admin\Tools::PostedSlug($_POST['source']) == \gp\admin\Tools::PostedSlug($_POST['target']) ){
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
	protected function UpdateRedir(){
		global $langmessage;

		if( !$this->CheckRedir() ){
			return false;
		}

		$orig_source	= $_POST['orig_source'];
		$source			= \gp\admin\Tools::PostedSlug( $orig_source );

		if( !isset($this->error_data['redirects'][$orig_source]) ){
			message($langmessage['OOPS'].' (Entry not found)');
			return false;
		}

		$data					= array();
		$data['target']			= $_POST['target'];
		$data['code']			= $_POST['code'];
		$data['raw_source']		= $_POST['source'];

		if( !\gp\tool\Files::ArrayReplace($orig_source,$source,$data,$this->error_data['redirects']) ){
			message($langmessage['OOPS']);
			return false;
		}

		return $this->SaveMissingData();
	}


	/**
	 * Save a new redirection
	 *
	 */
	protected function SaveRedir(){
		global $langmessage, $gp_index;

		if( !$this->CheckRedir() ){
			return false;
		}

		$source = \gp\admin\Tools::PostedSlug( $_POST['source'] );

		if( isset($this->error_data['redirects'][$source]) ){
			message($langmessage['OOPS'].' (Redirect Already Set)');
			return false;
		}


		$redirect = array(
			'target'		=> $_POST['target'],
			'code'			=> (int)$_POST['code'],
			'source'		=> $_POST['source'],
			);

		$this->error_data['redirects'][$source]		= $redirect;

		return $this->SaveMissingData();
	}

	/**
	 * Remove a redirection
	 *
	 */
	protected function RmRedir(){
		global $langmessage;

		$link =& $_POST['link'];
		if( !isset($this->error_data['redirects'][$link]) ){
			message($langmessage['OOPS']);
			return false;
		}

		unset($this->error_data['redirects'][$link]);
		return $this->SaveMissingData();
	}


}




