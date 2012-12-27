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


		if( is_numeric($_POST['per_page']) ){
			$this->blogData['per_page'] = (int)$_POST['per_page'];
		}

		if( is_numeric($_POST['post_abbrev']) ){
			$this->blogData['post_abbrev'] = (int)$_POST['post_abbrev'];
		}elseif( empty($_POST['post_abbrev']) ){
			$this->blogData['post_abbrev'] = '';
		}

		if( is_numeric($_POST['gadget_entries']) ){
			$this->blogData['gadget_entries'] = (int)$_POST['gadget_entries'];
		}

		if( is_numeric($_POST['gadget_abbrev']) ){
			$this->blogData['gadget_abbrev'] = (int)$_POST['gadget_abbrev'];
		}

		$format = htmlspecialchars($_POST['strftime_format']);
		if( @strftime($format) ){
			$this->blogData['strftime_format'] = $format;
		}


		if( is_numeric($_POST['feed_entries']) ){
			$this->blogData['feed_entries'] = (int)$_POST['feed_entries'];
		}

		if( is_numeric($_POST['feed_abbrev']) ){
			$this->blogData['feed_abbrev'] = (int)$_POST['feed_abbrev'];
		}

		//comments
		if( isset($_POST['allow_comments']) ){
			$this->blogData['allow_comments'] = true;
		}else{
			$this->blogData['allow_comments'] = false;
		}
		$this->blogData['commenter_website'] = (string)$_POST['commenter_website'];

		if( isset($_POST['comment_captcha']) ){
			$this->blogData['comment_captcha'] = true;
		}else{
			$this->blogData['comment_captcha'] = false;
		}


		$this->blogData['subtitle_separator'] = (string)$_POST['subtitle_separator'];


		//twitter/bitly
/*
		$this->blogData['twitter_username'] = (string)$_POST['twitter_username'];
		$this->blogData['twitter_password'] = (string)$_POST['twitter_password'];
		$this->blogData['bitly_login'] = (string)$_POST['bitly_login'];
		$this->blogData['bitly_key'] = (string)$_POST['bitly_key'];
*/



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
		global $langmessage,$addonFolderName;


		$defaults = SimpleBlogCommon::Defaults();
		$array =& $this->blogData;

		$label = gpOutput::SelectText('Blog');

		echo '<h2>';
		echo common::Link('Special_Blog',$label);
		echo ' &#187; ';
		echo $langmessage['configuration'];
		echo '</h2>';

		echo '<form class="renameform" action="'.common::GetUrl('Admin_Blog').'" method="post">';
		echo '<table style="width:100%" class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo 'Option';
			echo '</th>';
			echo '<th>';
			echo 'Value';
			echo '</th>';
			echo '<th>';
			echo 'Default';
			echo '</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>Entries Per Page</td>';
			echo '<td>';
			echo '<input type="text" name="per_page" size="20" value="'.htmlspecialchars($array['per_page']).'" class="gpinput" />';
			echo '</td><td>';
			echo $defaults['per_page'];
			echo '</td></tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Entries Abbreviation Length';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="post_abbrev" size="20" value="'.htmlspecialchars($array['post_abbrev']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['post_abbrev'];
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Entries For Gadget';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="gadget_entries" size="20" value="'.htmlspecialchars($array['gadget_entries']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['gadget_entries'];
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Gadget Abbreviation Length';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="gadget_abbrev" size="20" value="'.htmlspecialchars($array['gadget_abbrev']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['gadget_abbrev'];
			echo '</td>';
			echo '</tr>';


		echo '<tr>';
			echo '<td>';
			echo 'Date Format';
			//echo ' (<a href="http://php.net/manual/en/function.date.php" target="_blank">About</a>)';
			echo ' (<a href="http://www.php.net/manual/en/function.strftime.php" target="_blank">About</a>)';
			echo '</td>';
			echo '<td>';
			//echo '<input type="text" name="date_format" size="20" value="'.htmlspecialchars($array['date_format']).'" class="gpinput" />';
			echo '<input type="text" name="strftime_format" size="20" value="'.htmlspecialchars($array['strftime_format']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['strftime_format'];
			echo '</td>';
			echo '</tr>';


		echo '<tr>';
			echo '<td>';
			echo 'Entries For Feed';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="feed_entries" size="20" value="'.htmlspecialchars($array['feed_entries']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['feed_entries'];
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Feed Abbreviation Length';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="feed_abbrev" size="20" value="'.htmlspecialchars($array['feed_abbrev']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo $defaults['feed_abbrev'];
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Subtitle Separator';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="subtitle_separator" size="20" value="'.htmlspecialchars($array['subtitle_separator']).'" class="gpinput" />';
			echo '</td>';
			echo '<td>';
			echo htmlspecialchars($defaults['subtitle_separator']);
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<th>';
			echo 'Comments';
			echo '</th>';
			echo '<th>';
			echo 'Value';
			echo '</th>';
			echo '<th>';
			echo 'Default';
			echo '</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Allow Comments';
			echo '</td>';
			echo '<td>';
			if( $array['allow_comments'] ){
				echo '<input type="checkbox" name="allow_comments" value="allow" checked="checked" />';
			}else{
				echo '<input type="checkbox" name="allow_comments" value="allow" />';
			}
			echo '</td>';
			echo '<td>';
			echo '';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Commenter Website';
			echo '</td>';
			echo '<td>';
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
			echo '</td>';
			echo '<td>';
			echo 'Hide';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'reCaptcha';
			echo '</td>';
			echo '<td>';

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
			echo '</td>';
			echo '<td>';
			echo '';
			echo '</td>';
			echo '</tr>';


		echo '<tr><td></td>';
			echo '<td colspan="2">';
			echo '<input type="hidden" name="cmd" value="save_config" />';
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" /> ';
			echo '</td>';
			echo '</tr>';

		echo '</table>';


		echo '<p style="text-align:center">';
			echo ' &nbsp; &nbsp; ';
			echo common::Link('Special_Blog','Back to Your Blog');
			echo ' &nbsp; &nbsp; ';
			echo common::Link('Admin_BlogCategories','Categories Admin');
			echo ' &nbsp; &nbsp; ';
			echo common::Link('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode($addonFolderName),' title="'.urlencode($langmessage['editable_text']).'" name="ajax_box" ');
			echo ' &nbsp; &nbsp; ';
			echo common::Link('Admin_Blog','Regenerate Gadget','cmd=regen',' name="creq"');
		echo '</p>';

		echo '</form>';
	}




}
