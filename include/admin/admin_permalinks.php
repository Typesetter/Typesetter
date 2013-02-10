<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/FileSystem.php');
includeFile('tool/RemoteGet.php');

class admin_permalinks{

	var $changed_to_hide = false;

	function admin_permalinks(){
		global $langmessage,$dataDir;

		$this->htaccess_file = $dataDir.'/.htaccess';
		gp_filesystem_base::init($this->htaccess_file);

		echo '<h2>'.$langmessage['permalink_settings'].'</h2>';


		$cmd = common::GetCommand();
		switch($cmd){
			case 'continue':
				$this->SaveHtaccess();
			break;

			default:
				$this->ShowForm();
			break;
		}
	}

	function ShowForm(){
		global $langmessage,$gp_filesystem;



		$confirmed_mod_rewrite = false;
		if( function_exists('apache_get_modules') ){
			$mods = apache_get_modules();
			if( in_array('mod_rewrite',$mods) !== false ){
				$confirmed_mod_rewrite = true;
			}
		}

		if( !$confirmed_mod_rewrite ){
			echo '<p class="gp_notice">';
			echo $langmessage['limited_mod_rewrite'];
			echo '</p>';
		}


		$this->CheckHtaccess();

		echo '<table class="padded_table">';
		//default
			$checked = '';
			if( !$_SERVER['gp_rewrite'] ){
				$checked = 'checked="checked"';
			}

			echo '<tr><td>';


			$label = '<input type="radio" name="none" '.$checked.' /> '.$langmessage['use_index.php'];
			echo common::Link('Admin_Permalinks',$label,'cmd=continue&rewrite_setting=no_rewrite',array('data-cmd'=>'postlink','class'=>'gpsubmit'));

			echo '</td><td>';
			echo ' <tt>';
			echo $this->ExampleUrl(true);
			echo '</tt>';
			echo '</td></tr>';

		//hide index.php
			$checked = '';
			if( $_SERVER['gp_rewrite'] ){
				$checked = 'checked="checked"';
			}

			echo '<tr><td>';

			$label = '<input type="radio" name="none" '.$checked.' /> '.$langmessage['hide_index'];
			echo common::Link('Admin_Permalinks',$label,'cmd=continue&rewrite_setting=hide_index',array('data-cmd'=>'postlink','class'=>'gpsubmit'));

			echo '</td><td>';
			echo ' <tt>';
			echo $this->ExampleUrl(false);
			echo '</tt>';
			echo '</td></tr>';


		echo '</table>';
	}

	function ExampleUrl($index_php){
		global $dirPrefix;

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}

		$schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';


		if( $index_php ){
			$temp_prefix = $dirPrefix.'/index.php';
		}else{
			$temp_prefix = $dirPrefix;
		}

		return $schema.$server.$temp_prefix.'/sample-page';
	}


	//check to see MOD_ENV is working
	function CheckHtaccess(){
		global $langmessage;

		if( !file_exists($this->htaccess_file) ){
			return;
		}

		//it's working
		if( $_SERVER['gp_rewrite'] ){
			return;
		}


		$contents = file_get_contents($this->htaccess_file);

		//strip gpEasy code
		$pos = strpos($contents,'# BEGIN gpEasy');
		if( $pos === false ){
			return;
		}

		$pos2 = strpos($contents,'# END gpEasy');
		if( $pos2 > $pos ){
			$contents = substr($contents,$pos, $pos2-$pos);
		}else{
			$contents = substr($contents,$pos);
		}

		$lines = explode("\n",$contents);
		$HasRule = false;
		foreach($lines as $line){
			$line = trim($line);
			if( strpos($line,'RewriteRule') !== false ){
				$HasRule = true;
			}
		}

		if( $HasRule ){
			echo '<p class="gp_notice">';
			echo $langmessage['gp_indexphp_note'];
			echo '</p>';
		}

	}


	/**
	 * Determine how to save the htaccess file to the server (ftp,direct,manual) and give user the appropriate options
	 *
	 * @return boolean true if the .htaccess file is saved
	 */
	function SaveHtaccess(){
		global $gp_filesystem,$config,$langmessage, $dirPrefix;

		if( isset($_POST['rewrite_setting']) && $_POST['rewrite_setting'] == 'hide_index' ){
			$this->changed_to_hide = true;
		}

		$rules = admin_permalinks::Rewrite_Rules($this->changed_to_hide,$dirPrefix,$config['gpuniq']);


		//only proceed with hide if we can test the results
		if( gpRemoteGet::Test() ){
			if( $gp_filesystem->ConnectOrPrompt('Admin_Permalinks') ){
				if( $this->SaveRules($this->htaccess_file,$rules) ){
					message($langmessage['SAVED']);

					$_SERVER['gp_rewrite'] = $this->changed_to_hide;
					common::SetLinkPrefix();

					echo '<form method="GET" action="'.common::GetUrl('Admin_Permalinks').'">';
					echo '<input type="submit" value="'.$langmessage['continue'].'" class="gpsubmit" />';
					echo '</form>';

					return true;
				}

				message($langmessage['OOPS']);
				$gp_filesystem->CompleteForm($_POST,'Admin_Permalinks');
			}
		}


		echo '<h3>'.$langmessage['manual_method'].'</h3>';
		echo '<p>';
		echo $langmessage['manual_htaccess'];
		echo '</p>';

		echo '<textarea cols="60" rows="7" readonly="readonly" onClick="this.focus();this.select();" class="gptextarea">';
		echo htmlspecialchars($rules);
		echo '</textarea>';

		return false;

	}


	/**
	 * Save the htaccess rule to the server using $filesystem and test to make sure we aren't getting 500 errors
	 *
	 * @access public
	 * @since 1.7
	 *
	 * @param string $path The path to the local .htaccess file
	 * @param string $rules The rules to be added to the .htaccess file
	 * @return boolean
	 */
	function SaveRules($path,$rules){
		global $gp_filesystem, $langmessage;

		//force a 500 error for testing
		//$rules .= "\n</IfModule>";


		//get current .htaccess
		$contents = '';
		$original_contents = false;
		if( file_exists($path) ){
			$original_contents = $contents = file_get_contents($path);
		}

		// new gpeasy rules
		admin_permalinks::StripRules($contents);
		$contents .= $rules;

		$filesystem_base = $gp_filesystem->get_base_dir();
		if( $filesystem_base === false ){
			return false;
		}

		$filesystem_path = $filesystem_base.'/.htaccess';

		if( !$gp_filesystem->put_contents($filesystem_path,$contents) ){
			return false;
		}

		//if TestResponse Fails, undo the changes
		//only need to test for hiding
		if( $this->changed_to_hide && !admin_permalinks::TestResponse() ){
			message('hmm');
			if( $original_contents === false ){
				$gp_filesystem->unlink($filesystem_path);
			}else{
				$gp_filesystem->put_contents($filesystem_path,$original_contents);
			}
			return false;
		}

		return true;
	}


	/**
	 * Try to fetch a response using RemoteGet to see if we're getting a 500 error
	 *
	 * @access public
	 * @static
	 * @since 1.7
	 *
	 * @return boolean
	 */
	static function TestResponse(){
		global $config;

		//get url, force gp_rewrite to $new_gp_rewrite
		$rewrite_before = $_SERVER['gp_rewrite'];
		$_SERVER['gp_rewrite'] = true;
		common::SetLinkPrefix();


		$abs_url = common::AbsoluteUrl('Site_Map','',true,false);
		$_SERVER['gp_rewrite'] = $rewrite_before;
		common::SetLinkPrefix();

		$result = gpRemoteGet::Get_Successful($abs_url);
		if( !$result ){
			return false;
		}

		return true;
	}


	/**
	 * Strip rules enclosed by gpEasy comments
	 *
	 * @access public
	 * @static
	 * @since 1.7
	 *
	 * @param string $contents .htaccess file contents
	 */
	static function StripRules(&$contents){
		//strip gpEasy code
		$pos = strpos($contents,'# BEGIN gpEasy');
		if( $pos === false ){
			return;
		}

		$pos2 = strpos($contents,'# END gpEasy');
		if( $pos2 > $pos ){
			$contents = substr_replace($contents,'',$pos,$pos2-$pos+12);
		}else{
			$contents = substr($contents,0,$pos);
		}

		$contents = rtrim($contents);
	}

	/**
	 * Return the .htaccess code that can be used to hide index.php
	 *
	 */
	static function Rewrite_Rules($HideRules = true,$home_root,$uniq=false){

		$home_root = rtrim($home_root,'/').'/';

		$RuleArray = array();
		if( $HideRules ){

			$RuleArray[] = '# BEGIN gpEasy';
			$RuleArray[] = '<IfModule mod_rewrite.c>';
			$RuleArray[] = '<IfModule mod_env.c>';
			if( $uniq ){
				$RuleArray[] = 'SetEnv gp_rewrite '.substr($uniq,0,7);
			}else{
				$RuleArray[] = 'SetEnv gp_rewrite On';
			}
			$RuleArray[] = '</IfModule>';

			$RuleArray[] = 'RewriteEngine On';
			$RuleArray[] = 'RewriteBase "'.$home_root.'"';
			$RuleArray[] = 'RewriteRule ^index\.php$ - [L]'; // Prevent -f checks on index.php.

			$RuleArray[] = 'RewriteCond %{REQUEST_FILENAME} !-f';

			//comment to give gpEasy files preference over directories
			//uncomment if directories need to be accessible... sub installations
			$RuleArray[] = 'RewriteCond %{REQUEST_FILENAME} !-d';

			//append the requested title to the end for systems using mod_cache. Also reported in wordpress http://core.trac.wordpress.org/ticket/12175
			$RuleArray[] = '<IfModule mod_cache.c>';
			$RuleArray[] = 'RewriteRule /?(.*) "'.$home_root.'index.php?$1" [qsa,L]';
			$RuleArray[] = '</IfModule>';
			$RuleArray[] = '<IfModule !mod_cache.c>';
			$RuleArray[] = 'RewriteRule . "'.$home_root.'index.php" [L]';
			$RuleArray[] = '</IfModule>';

			$RuleArray[] = '</IfModule>';
			$RuleArray[] = '# END gpEasy';
		}

		return "\n" . implode("\n",$RuleArray) . "\n";
	}


}

