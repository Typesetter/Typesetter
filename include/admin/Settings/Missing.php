<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');


/*
 * wordpress info http://codex.wordpress.org/Creating_an_Error_404_Page
 * ability to see from url, something like /index.php/Special_Missing so 
 * that users can set "ErrorDocument 404 /index.php/Special_Missing?code=404" in .htaccess
 *
 */

class Missing extends \gp\special\Missing{

	protected $page;
	protected $codes;


	public function __construct($args){
		global $langmessage;

		parent::__construct($args);

		$this->page		= $args['page'];
		$this->codes	= [
			'301'	=> $langmessage['301 Moved Permanently'],
			'302'	=> $langmessage['302 Moved Temporarily'],
		];

		\gp\tool\Editing::PrepAutoComplete();

		$this->cmds['Save404']			= 'Edit404';
		$this->cmds['Edit404']			= '';
		$this->cmds['EditRedir']		= '';
		$this->cmds['UpdateRedir']		= 'DefaultDisplay';
		$this->cmds['SaveRedir']		= 'DefaultDisplay';
		$this->cmds['RmRedir']			= 'DefaultDisplay';
	}


	/**
	 * Add instructions for a 301 or 302 redirect
	 *
	 */
	public static function AddRedirect($source,$target){
		global $dataDir;

		$datafile		= $dataDir . '/data/_site/error_data.php';
		$error_data		= \gp\tool\Files::Get('_site/error_data');
		if( !$error_data ){
			$error_data = [];
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
			\gp\tool\Files::SaveData($datafile, 'error_data', $error_data);
		}
	}


	protected function SaveMissingData(){
		global $langmessage;

		if( !\gp\tool\Files::SaveData($this->datafile, 'error_data', $this->error_data) ){
			msg($langmessage['OOPS']);
			return false;
		}

		msg($langmessage['SAVED']);
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
	public function DefaultDisplay(){
		global $langmessage;

		echo '<h2>' . $langmessage['Link Errors'] . '</h2>';
		echo '<p>' . $langmessage['404_Usage'] . '</p>';

		//404 Page
		echo '<h3>' . $langmessage['404_Page'] . '</h3>';

		echo '<div id="Page_404">';
		echo	'<p>' . $langmessage['About_404_Page'] . '</p>';
		echo	'<p>';
		echo		\gp\tool::Link('Special_Missing',
						$langmessage['preview'],
						'',
						['class' => 'gpsubmit']
					);
		echo		' &nbsp; ';
		echo		\gp\tool::Link('Admin/Missing',
						$langmessage['edit'],
						'cmd=Edit404',
						['class' => 'gpsubmit']
					);
		echo	'</p>';
		echo '</div>';

		//redirection
		echo '<br/>';
		echo '<h3>' . $langmessage['Redirection'] . '</h3>';
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


	/**
	 * Display form for editing the 404 page content
	 *
	 */
	protected function Edit404($text=null){
		global $langmessage;

		if( is_null($text) ){
			if( isset($this->error_data['404_TEXT']) ){
				$text = $this->error_data['404_TEXT'];
			}else{
				$text = self::DefaultContent();
			}
		}

		echo '<h2>';
		echo	\gp\tool::Link('Admin/Missing', $langmessage['Link Errors']);
		echo	' &raquo; ';
		echo	$langmessage['404_Page'];
		echo '</h2>';

		echo '<form action="' . \gp\tool::GetUrl('Admin/Missing') . '" method="post">';
		echo	'<input type="hidden" name="cmd" value="save404" />';

		\gp\tool\Editing::UseCK($text);

		echo	'<input type="submit" name="" class="gpsubmit"';
		echo		' value="' . $langmessage['save'] . '"/>';
		echo	' <input type="submit" name="cmd" class="gpcancel"';
		echo		' value="' . $langmessage['cancel'] . '" />';
		echo '</form>';

		echo '<br/>';

		echo '<table class="bordered">';

		echo	'<tr>';
		echo		'<th>';
		echo			$langmessage['Useful Variables'];
		echo		'</th>';
		echo		'<th></th>';
		echo	'</tr>';

		echo	'<tr>';
		echo		'<td>';
		echo			'{{Similar_Titles}}';
		echo		'</td>';
		echo		'<td>';
		echo			$langmessage['Similar_Titles'];
		echo		'</td>';
		echo	'</tr>';

		echo '</table>';
	}


	/**
	 * Display current redirection settings
	 *
	 */
	protected function ShowRedirection(){
		global $langmessage, $gp_index, $config;

		$this->page->head_js[]		= '/include/thirdparty/tablesorter/tablesorter.js';
		$this->page->jQueryCode		.= '$("table.tablesorter").tablesorter({' .
											'cssHeader : "gp_header",' .
											'cssAsc : "gp_header_asc",' .
											'cssDesc : "gp_header_desc"' .
										'});';
		$has_invalid_target			= false;


		echo '<p class="cf">';
		echo	$langmessage['About_Redirection'];
		echo '</p>';

		echo '<form method="post" action="' . \gp\tool::GetUrl('Admin/Missing') . '">';

		echo	'<table class="bordered tablesorter full_width">';

		echo		'<thead>';
		echo			'<tr>';
		echo				'<th>' . $langmessage['Source URL'] . '</th>';
		echo				'<th>' . $langmessage['Target URL'] . '</th>';
		echo				'<th>' . $langmessage['Similarity'] . '</th>';
		echo				'<th>' . $langmessage['Method'] . '</th>';
		echo				'<th>' . $langmessage['options'] . '</th>';
		echo			'</tr>';
		echo		'</thead>';
		
		echo		'<tbody>';

		foreach($this->error_data['redirects'] as $source => $data){
			echo '<tr>';

			echo '<td>';
			$raw_source = $source;
			if( !empty($data['raw_source']) ){
				$raw_source = $data['raw_source'];
			}
			echo \gp\tool::GetUrl('');
			echo htmlspecialchars($raw_source);
			echo '</td>';

			echo '<td>';
			$target_show = $data['target'];
			if( strlen($target_show) > 40 ){
				// truncate middle
				$target_show = substr($target_show, 0, 15) . ' &hellip; ' . substr($target_show, -15);
			}

			$full_target	= $this->GetTarget($data['target'], false);
			$is_gplink		= $this->isGPLink($data['target']);
			$valid_target	= $this->ValidTarget($data['target']);

			if( !$valid_target ){
				$has_invalid_target = true;
				echo ' <i class="fa fa-exclamation-triangle"';
				echo	' title="' . $langmessage['Target URL Invalid'] . '">';
				echo '</i> &nbsp; ';
			}

			echo	'<a href="' . htmlspecialchars($full_target) . '">';
			echo		str_replace(' ', '&nbsp;', htmlspecialchars($target_show));
			echo	'</a>';

			echo '</td>';

			echo '<td>';
			if( $is_gplink ){
				$lower_source = strtolower($raw_source);
				$lower_target = strtolower($target_show);
				similar_text($lower_source, $lower_target, $percent);
				echo number_format($percent, 1) . '%';
			}
			echo '&nbsp;</td>';

			echo '<td>';
			echo	$this->GetCodeLanguage($data['code']);
			echo '</td>';

			echo '<td>';
			echo	\gp\tool::Link(
						'Admin/Missing',
						$langmessage['edit'],
						'cmd=EditRedir&source=' . urlencode($source),
						['data-cmd' => 'gpabox']
					);

			echo	' &nbsp; ';
			echo	\gp\tool::Link($source, $langmessage['Test']);

			echo	' &nbsp; ';
			$title = sprintf($langmessage['generic_delete_confirm'], $source);
			echo 	\gp\tool::Link(
						'Admin/Missing',
						$langmessage['delete'],
						'cmd=RmRedir&link=' . urlencode($source),
						[
							'data-cmd'	=> 'postlink',
							'title'		=> $title,
							'class'		=> 'gpconfirm',
						]
					);
			echo '</td>';

			echo '</tr>';
		}
		echo '</tbody>';

		$this->AddMissingRow();
		echo '</table>';
		echo '</form>';

		echo '<br/>';

		if( $has_invalid_target ){
			echo '<p>';
			echo	' &nbsp; <span><i class="fa fa-exclamation-triangle"></i> &nbsp; ';
			echo		$langmessage['Target URL Invalid'];
			echo	'</span>';
			echo '</p>';
		}
	}


	/**
	* Return true if the target is a valid url
	 *
	 * @return bool
	 */
	public function ValidTarget($target){
		global $gp_index;

		if( empty($target) ){
			return true;
		}

		if( !$this->isGPLink($target) ){
			return true;
		}

		if( isset($gp_index[$target]) ){
			return true;
		}

		$type = \gp\tool::SpecialOrAdmin($target);
		if( $type == 'admin' ){
			return true;
		}

		return false;
	}


	/**
	 * Add Redirection form for <tfoot>
	 *
	 */
	protected function AddMissingRow(){
		global $langmessage;

		$_REQUEST += [
			'source'		=> '',
			'target'		=> '',
			'code'			=> '',
			'orig_source'	=> '',
		];

		echo '<tfoot>';
		echo	'<tr>';

		//source
		echo		'<td>';
		echo 			\gp\tool::GetUrl('');
		echo			'<input type="text" name="source"';
		echo				' value="' . htmlspecialchars($_REQUEST['source']) . '"';
		echo				' size="20" class="gpinput" required="required" />';
		echo		'</td>';

		//target
		echo		'<td>';
		echo			'<input type="text" name="target"';
		echo				' value="' . htmlspecialchars($_REQUEST['target']) . '"';
		echo				' class="title-autocomplete gpinput" size="40" />';
		echo		'</td>';

		echo		'<td></td>';

		//code
		echo		'<td>';
		$this->CodeSelect($_REQUEST['code']);
		echo		'</td>';

		echo		'<td>';
		echo			'<button class="gpbutton" type="submit" name="cmd"';
		echo				' value="SaveRedir" data-cmd="gpajax">';
		echo				$langmessage['New Redirection'];
		echo			'</button>';
		echo		'</td>';

		echo	'</tr>';
		echo '</tfoot>';
	}


	/**
	 * Edit an existing redirection
	 *
	 */
	protected function EditRedir(){
		global $langmessage;

		$source = \gp\admin\Tools::PostedSlug($_REQUEST['source']);
		if( !isset($this->error_data['redirects'][$source]) ){
			msg($langmessage['OOPS'] . ' (Invalid Redirect)');
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
	protected function RedirForm($values=[]){
		global $langmessage, $gp_index;

		$values += [
			'cmd'			=> 'saveredir',
			'source'		=> '',
			'target'		=> '',
			'code'			=> '',
			'orig_source'	=> '',
		];


		echo '<div class="inline_box" id="gp_redir">';
		echo '<h2>' . $langmessage['New Redirection'] . '</h2>';

		echo '<form method="post" action="' . \gp\tool::GetUrl('Admin/Missing') . '">';
		echo	'<input type="hidden" name="cmd"';
		echo		' value="' . htmlspecialchars($values['cmd']) . '"/>';
		echo	'<input type="hidden" name="orig_source"';
		echo		' value="' . htmlspecialchars($values['orig_source']) . '"/>';

		echo '<table class="bordered full_width">';

		echo	'<tr>';
		echo		'<th colspan="2">' . $langmessage['options'] . '</th>';
		echo	'</tr>';

		//source url
		echo	'<tr>';
		echo		'<td>';
		echo			$langmessage['Source URL'];
		echo		'</td>';
		echo		'<td>';
		echo			\gp\tool::GetUrl('');
		echo			'<input type="text" name="source" size="20"';
		echo				' class="gpinput" required="required"';
		echo				' value="' . htmlspecialchars($values['source']) . '" />';
		echo		'</td>';
		echo	'</tr>';

		//method
		echo	'<tr>';
		echo		'<td>';
		echo			$langmessage['Method'];
		echo		'</td>';
		echo		'<td>';
		$this->CodeSelect($values['code']);
		echo		'</td>';
		echo	'</tr>';

		//target url
		echo	'<tr>';
		echo		'<td>';
		echo			$langmessage['Target URL'];
		echo		'</td>';
		echo		'<td>';
		echo			'<input type="text" name="target" size="40"';
		echo				' class="title-autocomplete gpinput"';
		echo				' value="' . htmlspecialchars($values['target']) . '" />';
		echo		'</td>';
		echo	'</tr>';

		echo '</table>';

		echo '<p>';
		echo	'<input type="submit" name=""';
		echo		' class="gpsubmit" data-cmd="gppost"';
		echo		' value="' . $langmessage['save_changes'] . '" />';
		echo	' <input type="button" name=""';
		echo		' class="admin_box_close gpcancel"';
		echo		' value="' . $langmessage['cancel'] . '" />';
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
			echo '<option value="' . $code_key . '"' . $selected . '>';
			echo	htmlspecialchars($code_value);
			echo '</option>';
		}
		echo '</select>';
	}


	protected function CheckRedir(){
		global $langmessage;

		if( empty($_POST['source']) ){
			msg($langmessage['OOPS'].' (Empty Source)');
			return false;
		}

		if( $_POST['source'] == $_POST['target'] ){
			msg($langmessage['OOPS'].' (Infinite Loop)');
			return false;
		}

		if( \gp\admin\Tools::PostedSlug($_POST['source']) ==
			\gp\admin\Tools::PostedSlug($_POST['target'])
		){
			msg($langmessage['OOPS'] . ' (Infinite Loop)');
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
		$source			= \gp\admin\Tools::PostedSlug($orig_source);

		if( !isset($this->error_data['redirects'][$orig_source]) ){
			msg($langmessage['OOPS'] . ' (Entry not found)');
			return false;
		}

		$data					= [];
		$data['target']			= $_POST['target'];
		$data['code']			= $_POST['code'];
		$data['raw_source']		= $_POST['source'];

		if(
			!\gp\tool\Files::ArrayReplace(
				$orig_source,
				$source,
				$data,
				$this->error_data['redirects']
			)
		){
			msg($langmessage['OOPS']);
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

		$source = \gp\admin\Tools::PostedSlug($_POST['source']);

		if( isset($this->error_data['redirects'][$source]) ){
			msg($langmessage['OOPS'] . ' (Redirect Already Set)');
			return false;
		}

		$redirect = [
			'target'		=> $_POST['target'],
			'code'			=> (int)$_POST['code'],
			'source'		=> $_POST['source'],
		];

		$this->error_data['redirects'][$source] = $redirect;

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
			msg($langmessage['OOPS']);
			return false;
		}

		unset($this->error_data['redirects'][$link]);
		return $this->SaveMissingData();
	}

}
