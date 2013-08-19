<?php

defined('is_running') or die('Not an entry point...');

gpPlugin::incl('SimpleBlogCommon.php','require_once');


class AdminSimpleBlog extends SimpleBlogCommon{

	function AdminSimpleBlog(){
		global $langmessage;

		$this->Init();

		$cmd = common::GetCommand();
		switch($cmd){
			//regen
			case 'regen':
				$this->GenStaticContent();
				message($langmessage['SAVED']);
			break;


			//config
			case 'save_config':
				if( $this->SaveConfig() ){
					$this->GenStaticContent();
				}
			break;

		}

		$this->Config();

	}


	/**
	 * Check and Save the user submitted configuration
	 * @return bool
	 *
	 */
	function SaveConfig(){
		global $langmessage;


		$options = self::Options();
		if( isset($_POST['urls']) && isset($options['urls'][$_POST['urls']]) ){
			SimpleBlogCommon::$data['urls'] = $_POST['urls'];
		}

		if( is_numeric($_POST['per_page']) ){
			SimpleBlogCommon::$data['per_page'] = (int)$_POST['per_page'];
		}

		if( is_numeric($_POST['post_abbrev']) ){
			SimpleBlogCommon::$data['post_abbrev'] = (int)$_POST['post_abbrev'];
		}elseif( empty($_POST['post_abbrev']) ){
			SimpleBlogCommon::$data['post_abbrev'] = '';
		}

		if( is_numeric($_POST['gadget_entries']) ){
			SimpleBlogCommon::$data['gadget_entries'] = (int)$_POST['gadget_entries'];
		}

		if( is_numeric($_POST['gadget_abbrev']) ){
			SimpleBlogCommon::$data['gadget_abbrev'] = (int)$_POST['gadget_abbrev'];
		}

		$format = htmlspecialchars($_POST['strftime_format']);
		if( @strftime($format) ){
			SimpleBlogCommon::$data['strftime_format'] = $format;
		}


		if( is_numeric($_POST['feed_entries']) ){
			SimpleBlogCommon::$data['feed_entries'] = (int)$_POST['feed_entries'];
		}

		if( is_numeric($_POST['feed_abbrev']) ){
			SimpleBlogCommon::$data['feed_abbrev'] = (int)$_POST['feed_abbrev'];
		}

		//comments
		if( isset($_POST['allow_comments']) ){
			SimpleBlogCommon::$data['allow_comments'] = true;
		}else{
			SimpleBlogCommon::$data['allow_comments'] = false;
		}
		SimpleBlogCommon::$data['commenter_website'] = (string)$_POST['commenter_website'];

		if( isset($_POST['comment_captcha']) ){
			SimpleBlogCommon::$data['comment_captcha'] = true;
		}else{
			SimpleBlogCommon::$data['comment_captcha'] = false;
		}

		SimpleBlogCommon::$data['subtitle_separator'] = (string)$_POST['subtitle_separator'];
		SimpleBlogCommon::$data['email_comments'] = $_POST['email_comments'];

		if( !$this->SaveIndex() ){
			message($langmessage['OOPS']);
			return false;
		}


		message($langmessage['SAVED']);
		return true;
	}


	/**
	 * Show the configuration form
	 *
	 */
	function Config(){
		global $langmessage, $addonFolderName, $gpversion;


		$defaults = SimpleBlogCommon::Defaults();
		$array =& SimpleBlogCommon::$data;

		$label = gpOutput::SelectText('Blog');

		$page->css_admin[] = '/include/css/addons.css'; //for hmargin css pre gpEasy 3.6

		echo '<div class="'. get_class($this) .'">';
		echo '<h2 class="hmargin">';
		echo common::Link('Special_Blog',$label);
		echo ' &#187; ';
		echo $langmessage['configuration'];
		echo ' <span>|</span> ';
		echo common::Link('Admin_BlogCategories','Categories');
		echo ' <span>|</span> ';
		$comments = gpOutput::SelectText('Comments');
		echo common::Link('Admin_BlogComments',$comments);
		echo '</h2>';

		echo '<form class="renameform" action="'.common::GetUrl('Admin_Blog').'" method="post">';
		echo '<table style="width:100%" class="bordered">';
		echo '<tr><th>';
		echo 'Option';
		echo '</th><th>';
		echo 'Value';
		echo '</th><th>';
		echo 'Default';
		echo '</th></tr>';


		$options = self::Options();

		//Pretty Urls
		echo '<tr><td>Urls</td><td>';
		if( version_compare($gpversion,'4.0','>=') ){
			self::Radio('urls',$options['urls'],$array['urls']);
		}else{
			echo 'Available in gpEasy 4.0+';
		}
		echo '</td><td>';
		echo $defaults['urls'];
		echo '</td></tr>';


		//Entries Per Page
		echo '<tr><td>Entries Per Page</td><td>';
		echo '<input type="text" name="per_page" size="20" value="'.htmlspecialchars($array['per_page']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['per_page'];
		echo '</td></tr>';


		//Entries Abbreviation Length
		echo '<tr><td>';
		echo 'Entries Abbreviation Length';
		echo '</td><td>';
		echo '<input type="text" name="post_abbrev" size="20" value="'.htmlspecialchars($array['post_abbrev']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['post_abbrev'];
		echo '</td></tr>';


		//Entries For Gadget
		echo '<tr><td>';
		echo 'Entries For Gadget';
		echo '</td><td>';
		echo '<input type="text" name="gadget_entries" size="20" value="'.htmlspecialchars($array['gadget_entries']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['gadget_entries'];
		echo '</td></tr>';


		//Gadget Abbreviation Length
		echo '<tr><td>';
		echo 'Gadget Abbreviation Length';
		echo '</td><td>';
		echo '<input type="text" name="gadget_abbrev" size="20" value="'.htmlspecialchars($array['gadget_abbrev']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['gadget_abbrev'];
		echo '</td></tr>';


		//Date Format
		echo '<tr><td>';
		echo 'Date Format';
		//echo ' (<a href="http://php.net/manual/en/function.date.php" target="_blank">About</a>)';
		echo ' (<a href="http://www.php.net/manual/en/function.strftime.php" target="_blank">About</a>)';
		echo '</td><td>';
		//echo '<input type="text" name="date_format" size="20" value="'.htmlspecialchars($array['date_format']).'" class="gpinput" />';
		echo '<input type="text" name="strftime_format" size="20" value="'.htmlspecialchars($array['strftime_format']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['strftime_format'];
		echo '</td></tr>';


		//Entries For Feed
		echo '<tr><td>';
		echo 'Entries For Feed';
		echo '</td><td>';
		echo '<input type="text" name="feed_entries" size="20" value="'.htmlspecialchars($array['feed_entries']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['feed_entries'];
		echo '</td></tr>';


		//Feed Abbreviation Length
		echo '<tr><td>';
		echo 'Feed Abbreviation Length';
		echo '</td><td>';
		echo '<input type="text" name="feed_abbrev" size="20" value="'.htmlspecialchars($array['feed_abbrev']).'" class="gpinput" />';
		echo '</td><td>';
		echo $defaults['feed_abbrev'];
		echo '</td></tr>';


		//Subtitle Separator
		echo '<tr><td>';
		echo 'Subtitle Separator';
		echo '</td><td>';
		echo '<input type="text" name="subtitle_separator" size="20" value="'.htmlspecialchars($array['subtitle_separator']).'" class="gpinput" />';
		echo '</td><td>';
		echo htmlspecialchars($defaults['subtitle_separator']);
		echo '</td></tr>';


		//Comments
		echo '<tr><th>';
		echo 'Comments';
		echo '</th><th>';
		echo 'Value';
		echo '</th><th>';
		echo 'Default';
		echo '</th></tr>';


		//Allow Comments
		echo '<tr><td>';
		echo 'Allow Comments';
		echo '</td><td>';
		if( $array['allow_comments'] ){
			echo '<input type="checkbox" name="allow_comments" value="allow" checked="checked" />';
		}else{
			echo '<input type="checkbox" name="allow_comments" value="allow" />';
		}
		echo '</td><td></td></tr>';


		//Email New Comment
		echo '<tr><td>';
		echo 'Email New Comments';
		echo '</td><td>';
		echo '<input type="text" name="email_comments" value="'.htmlspecialchars($array['email_comments']).'"  />';
		echo '</td><td></td></tr>';



		echo '<tr><td>';
		echo 'Commenter Website';
		echo '</td><td>';
		echo '<select name="commenter_website" class="gpselect">';
			if( $array['commenter_website'] == 'nofollow' ){
				echo '<option value="">Hide</option>';
				echo '<option value="nofollow" selected="selected">Nofollow Link</option>';
				echo '<option value="link">Follow Link</option>';
			}elseif( $array['commenter_website'] == 'link' ){
				echo '<option value="">Hide</option>';
				echo '<option value="nofollow" selected="selected">Nofollow Link</option>';
				echo '<option value="link" selected="selected">Follow Link</option>';
			}else{
				echo '<option value="">Hide</option>';
				echo '<option value="nofollow">Nofollow Link</option>';
				echo '<option value="link">Follow Link</option>';
			}
		echo '</select>';
		echo '</td><td>';
		echo 'Hide';
		echo '</td></tr>';

		echo '<tr><td>';
		echo 'reCaptcha';
		echo '</td><td>';

		if( !gp_recaptcha::isActive() ){
			$disabled = ' disabled="disabled" ';
		}else{
			$disabled = '';
		}

		if( $array['comment_captcha'] ){
			echo '<input type="checkbox" name="comment_captcha" value="allow" checked="checked" '.$disabled.'/>';
		}else{
			echo '<input type="checkbox" name="comment_captcha" value="allow" '.$disabled.'/>';
		}
		echo '</td><td>';
		echo '';
		echo '</td></tr>';


		echo '<tr><td></td>';
		echo '<td colspan="2">';
		echo '<input type="hidden" name="cmd" value="save_config" />';
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" /> ';
		echo '</td></tr>';

		echo '</table>';


		echo '<p style="text-align:center">';
			echo common::Link('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode($addonFolderName),' title="'.urlencode($langmessage['editable_text']).'" name="gpabox" ');
			echo ' &nbsp; &nbsp; ';
			echo common::Link('Admin_Blog','Regenerate Gadget','cmd=regen',' name="creq"');
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}

	static function Options(){

		return array(

			'urls' => array(
				'Default'	=> 'Default - /Blog?id=1234',
				'Tiny'		=> 'Tiny - /Blog/1234',
				'Full'		=> 'Full - /Blog/1234_Post_Title'
			),

		);

	}

	function Select($name,$options,$current){
		echo '<select name="'.$name.'" class="gpselect">';
		foreach($options as $value => $label){
			$selected = '';
			if( $current == $value){
				$selected = ' selected="selected"';
			}
			echo '<option value="'.$value.'"'.$selected.'">'.$label.'</option>';
		}
		echo '</select>';
	}

	function Radio($name,$options,$current){

		foreach($options as $value => $label){
			echo '<div><label>';
			$checked = '';
			if( $current == $value){
				$checked = ' checked="checked"';
			}
			echo '<input type="radio" name="'.$name.'" value="'.$value.'"'.$checked.'" /> ';
			echo $label;
			echo '</label></div>';
		}
	}



}
