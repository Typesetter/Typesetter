<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');

class Permalinks{

	public $rule_file_name		= '';
	public $rule_file			= '';
	public $undo_if_failed		= false;
	public $hide_index			= false;
	public $server_name;
	public $www_avail			= false;
	public $www_setting			= null;
	public $orig_rules			= null;
	public $new_rules			= '';
	private $FileSystem;



	public function __construct(){
		global $langmessage,$dataDir;


		$this->server_name = \gp\tool::ServerName(true);

		//get current rules
		$this->rule_file_name	= self::IIS() ? 'web.config' : '.htaccess';
		$this->rule_file		= $dataDir.'/'.$this->rule_file_name;
		if( file_exists($this->rule_file) ){
			$this->orig_rules = file_get_contents($this->rule_file);
		}


		$this->FileSystem = \gp\tool\FileSystem::init($this->rule_file);
		$this->WWWAvail();

		echo '<h2>'.$langmessage['permalink_settings'].'</h2>';


		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'continue':
				if( !$this->SaveHtaccess() ){
					break;
				}

			default:
				$this->ShowForm();
			break;
		}
	}


	/**
	 * Determine if we're able to change the www redirect of the server
	 *
	 */
	public function WWWAvail(){

		if( !$this->server_name ){
			return;
		}

		if( self::IIS() ){
			return;
		}


		// already has www settings?
		if( !is_null($this->orig_rules) ){
			if( strpos($this->orig_rules,'# with www') !== false ){
				$this->www_setting	= true;
				$this->www_avail	= true;
				return;
			}
			if( strpos($this->orig_rules,'# without www') !== false ){
				$this->www_setting	= false;
				$this->www_avail	= true;
				return;
			}
		}


		// check non-www site
		$url			= $this->WWWUrl(false,'special_site_map');
		if( !self::ConfirmGet($url) ){
			return;
		}


		// check www site
		$url			= $this->WWWUrl(true,'special_site_map');
		if( !self::ConfirmGet($url) ){
			return;
		}


		$this->www_avail = true;
	}


	/**
	 * Return url with or without www
	 *
	 */
	public function WWWUrl($with_www = true, $slug = ''){

		$schema			= ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
		$host			= $this->server_name;

		if( $with_www ){
			$host = 'www.'.$this->server_name;
		}

		return $schema.$host.\gp\tool::GetUrl($slug,'',false);
	}


	/**
	 * Confirm getting the url retreives the current installation
	 *
	 */
	public static function ConfirmGet($url, $check_redirect = true ){
		global $config;

		$result			= \gp\tool\RemoteGet::Get_Successful($url);

		if( !$result ){
			return false;
		}

		if( isset($config['gpuniq']) ){
			$mdu_check		= substr(md5($config['gpuniq']),0,20);

			if( strpos($result,$mdu_check) === false ){
				return false;
			}
		}

		//if redirected, make sure it's on the same host
		if( $check_redirect && \gp\tool\RemoteGet::$redirected ){
			$redirect_a		= parse_url(\gp\tool\RemoteGet::$redirected);
			$req_a			= parse_url($url);
			if( $redirect_a['host'] !== $req_a['host'] ){
				return false;
			}
		}


		return true;
	}


	/**
	 * Display Permalink Options
	 *
	 */
	public function ShowForm(){
		global $langmessage;



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

		echo '<br/>';
		echo '<form method="post" action="'.\gp\tool::GetUrl('Admin/Permalinks').'">';
		echo '<table class="bordered middle">';

		echo '<tr><th colspan="2">index.php</th></tr>';

		//default
		$checked = '';
		if( !$_SERVER['gp_rewrite'] ){
			$checked = 'checked="checked"';
		}

		echo '<tr><td>';
		echo '<label class="all_checkbox">';
		echo '<input type="radio" name="rewrite_setting" value="no_rewrite" '.$checked.' />';
		echo '<span>'.$langmessage['use_index.php'].'</span>';
		echo '</label>';

		echo '</td><td>';
		echo ' <pre>';
		echo $this->ExampleUrl(true);
		echo '</pre>';
		echo '</td></tr>';

		//hide index.php
		$checked = '';
		if( $_SERVER['gp_rewrite'] ){
			$checked = 'checked="checked"';
		}

		echo '<tr><td>';
		echo '<label class="all_checkbox">';
		echo '<input type="radio" name="rewrite_setting" value="hide_index" '.$checked.' />';
		echo '<span>'.$langmessage['hide_index'].'</span>';
		echo '</label>';

		echo '</td><td>';
		echo ' <pre>';
		echo $this->ExampleUrl(false);
		echo '</pre>';
		echo '</td></tr>';

		//www
		if( $this->www_avail ){
			echo '<tr><th colspan="2">www</th></tr>';

			$checked = 'checked';
			echo '<tr><td>';
			echo '<label class="all_checkbox">';
			echo '<input type="radio" name="www_setting" value="" '.$checked.' />';
			echo '<span>'.$langmessage['Not_Set'].'</span>';
			echo '</label>';
			echo '</td><td>';
			echo '<pre class="inline">';
			echo $this->WWWUrl(false);
			echo '</pre>';
			echo ' &amp; ';
			echo '<pre class="inline">';
			echo $this->WWWUrl(true);
			echo '</pre>';
			echo '</td></tr>';


			//without www
			$checked = ($this->www_setting === false) ? 'checked' : '';
			echo '<tr><td>';
			echo '<label class="all_checkbox">';
			echo '<input type="radio" name="www_setting" value="without" '.$checked.' />';
			echo '<span>Without www</span>';
			echo '</label>';

			echo '</td><td>';
			echo ' <pre>';
			echo $this->WWWUrl(false);
			echo '</pre>';
			echo '</td></tr>';


			//with www
			$checked = ($this->www_setting === true) ? 'checked' : '';
			echo '<tr><td>';
			echo '<label class="all_checkbox">';
			echo '<input type="radio" name="www_setting" value="with" '.$checked.' />';
			echo '<span>With www</span>';
			echo '</label>';

			echo '</td><td>';
			echo ' <pre>';
			echo $this->WWWUrl(true);
			echo '</pre>';
			echo '</td></tr>';
		}


		echo '</table>';

		echo '<br/>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="continue" />';
		echo '<input type="submit" name="" class="gpsubmit" value="'.$langmessage['continue'].'"/>';
		echo '</p>';

		echo '</form>';

	}


	/**
	 * Return an example url based on potential gp_rewrite setting
	 *
	 */
	public function ExampleUrl($index_php){
		global $dirPrefix;

		$schema			= ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
		$temp_prefix	= $index_php ? $dirPrefix.'/index.php' : $dirPrefix;

		if( $this->server_name ){
			return $schema.$this->server_name.$temp_prefix.'/sample-page';
		}

		return $temp_prefix;
	}


	/**
	 * Determine how to save the htaccess file to the server (ftp,direct,manual) and give user the appropriate options
	 *
	 * @return boolean true if the .htaccess file is saved
	 */
	public function SaveHtaccess(){
		global $langmessage, $dirPrefix;


		//hide index ?
		if( isset($_POST['rewrite_setting']) && $_POST['rewrite_setting'] == 'hide_index' ){
			$this->hide_index		= true;
			$this->undo_if_failed	= true;
		}

		// www preference
		$www = null;
		if( isset($_POST['www_setting']) ){
			if( $_POST['www_setting'] === 'with' ){
				$www					= true;
				$this->undo_if_failed	= true;

			}elseif( $_POST['www_setting'] === 'without' ){
				$www					= false;
				$this->undo_if_failed	= true;
			}
		}


		$this->new_rules	= self::Rewrite_Rules( $this->hide_index, $dirPrefix, $this->orig_rules, $www );


		// only proceed with hide if we can test the results
		if( !$this->CanTestRules() ){
			$this->ManualMethod();
			return false;
		}


		if( !$this->SaveRules() ){
			$this->FileSystem->CompleteForm($_POST,'Admin/Permalinks');
			$this->ManualMethod();
			return false;
		}

		msg($langmessage['SAVED']);

		//redirect to new permalink structure
		$_SERVER['gp_rewrite'] = $this->hide_index;
		\gp\tool::SetLinkPrefix();
		$redir = \gp\tool::GetUrl('Admin/Permalinks');
		\gp\tool::Redirect($redir,302);

		return false;
	}


	/**
	 * Determine if we will be able tot test the results
	 *
	 */
	public function CanTestRules(){

		if( \gp\tool\RemoteGet::Test() === false ){
			return false;
		}


		if( !$this->FileSystem || !$this->FileSystem->ConnectOrPrompt('Admin/Permalinks') ){
			return false;
		}

		return true;
	}


	/**
	 * Display instructions for manually creating the htaccess/web.config file
	 *
	 */
	public function ManualMethod(){
		global $langmessage, $dirPrefix;

		echo '<h3>'.$langmessage['manual_method'].'</h3>';
		echo '<p>';
		echo str_replace('.htaccess',$this->rule_file_name,$langmessage['manual_htaccess']);
		echo '</p>';

		//display rewrite code in textarea
		$lines = explode("\n",$this->new_rules);
		$len = 70;
		foreach($lines as $line){
			$line_len = strlen($line)+(substr_count($line,"\t")*3);
			$len = max($len,$line_len);
		}
		$len = min(140,$len);
		echo '<textarea cols="'.$len.'" rows="'.(count($lines)+1).'" readonly="readonly" onClick="this.focus();this.select();" class="gptextarea">';
		echo htmlspecialchars($this->new_rules);
		echo '</textarea>';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Permalinks').'" method="get">';
		echo '<input type="submit" value="'.$langmessage['continue'].'" class="gpsubmit"/>';
		echo '</form>';

	}


	/**
	 * Save the htaccess rule to the server using $filesystem and test to make sure we aren't getting 500 errors
	 *
	 * @access public
	 * @since 1.7
	 *
	 * @return boolean
	 */
	public function SaveRules(){
		global $langmessage, $dirPrefix;

		if( $this->new_rules === false ){
			return false;
		}

		$filesystem_base = $this->FileSystem->get_base_dir();
		if( $filesystem_base === false ){
			return false;
		}

		$filesystem_path = $filesystem_base.'/'.$this->rule_file_name;

		if( !$this->FileSystem->put_contents($filesystem_path,$this->new_rules) ){
			return false;
		}


		return $this->TestSave($filesystem_path);
	}


	/**
	 * Make sure the save hasn't broken the installation
	 * if TestResponse Fails, undo the changes
	 *
	 */
	public function TestSave($filesystem_path){

		//only need to test if we might needt to undo
		if( !$this->undo_if_failed ){
			return true;
		}


		if( !self::TestResponse($this->hide_index) ){

			if( is_null($this->orig_rules) ){
				$this->FileSystem->unlink($filesystem_path);
			}else{
				$this->FileSystem->put_contents($filesystem_path,$this->orig_rules);
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
	public static function TestResponse($new_rewrite = true){

		//get url, force gp_rewrite to $new_gp_rewrite
		$rewrite_before				= $_SERVER['gp_rewrite'];
		$_SERVER['gp_rewrite']		= $new_rewrite;
		\gp\tool::SetLinkPrefix();


		//without server name, we can't get a valid absoluteUrl
		if( \gp\tool::ServerName() === false ){
			return false;
		}


		$abs_url					= \gp\tool::AbsoluteUrl('Site_Map','',true,false); //can't be special_site_map, otherwise \gp\tool::IndexToTitle() will be called during install
		$_SERVER['gp_rewrite']		= $rewrite_before;
		\gp\tool::SetLinkPrefix();


		return self::ConfirmGet($abs_url, false);
	}


	/**
	 * Strip rules enclosed by comments
	 *
	 * @access public
	 * @static
	 * @since 1.7
	 *
	 * @param string $contents .htaccess file contents
	 */
	public static function StripRules(&$contents){

		//strip code
		$pos = strpos($contents,'# BEGIN Typesetter');
		if( $pos === false ){
			return;
		}

		$end_comment = '# END Typesetter';
		$pos2 = strpos($contents,$end_comment);
		if( $pos2 > $pos ){
			$contents = substr_replace($contents,'',$pos,$pos2-$pos+strlen($end_comment));
		}else{
			$contents = substr($contents,0,$pos);
		}

		$contents = rtrim($contents);
	}


	/**
	 * Return the .htaccess code that can be used to hide index.php
	 * add/remove cms rules from $original_contents to get new $contents
	 *
	 */
	public static function Rewrite_Rules( $hide_index = true, $home_root, $existing_contents = null, $www = null ){

		if( is_null($existing_contents) ){
			$existing_contents = '';
		}

		// IIS
		if( self::IIS() ){
			return self::Rewrite_RulesIIS( $hide_index, $existing_contents );
		}

		return self::Rewrite_RulesApache($hide_index, $home_root, $existing_contents, $www);
	}


	/**
	 * Generate rewrite rules for the apache server
	 *
	 */
	public static function Rewrite_RulesApache( $hide_index, $home_root, $contents, $www ){

		// Apache
		self::StripRules($contents);

		if( !$hide_index && is_null($www) ){
			return $contents;
		}

		$home_root			= rtrim($home_root,'/').'/';
		$new_lines			= array();
		$server_name		= \gp\tool::ServerName(true);


		// with www
		if( $www ){
			$new_lines[]	= '# with www';
			$new_lines[]	= 'RewriteCond %{HTTPS} off';
			$new_lines[]	= 'RewriteCond %{HTTP_HOST} "^'.$server_name.'"';
			$new_lines[]	= 'RewriteRule (.*) "http://www.'.$server_name.'/$1" [R=301,L]';

			$new_lines[]	= '';
			$new_lines[]	= '# with www and https';
			$new_lines[]	= 'RewriteCond %{HTTPS} on';
			$new_lines[]	= 'RewriteCond %{HTTP_HOST} "^'.$server_name.'"';
			$new_lines[]	= 'RewriteRule (.*) "https://www.'.$server_name.'/$1" [R=301,L]';


		// without www
		}elseif( $www === false ){
			$new_lines[]	= '# without www';
			$new_lines[]	= 'RewriteCond %{HTTPS} off';
			$new_lines[]	= 'RewriteCond %{HTTP_HOST} "^www.'.$server_name.'"';
			$new_lines[]	= 'RewriteRule (.*) "http://'.$server_name.'/$1" [R=301,L]';


			$new_lines[]	= '';
			$new_lines[]	= '# without www and https';
			$new_lines[]	= 'RewriteCond %{HTTPS} on';
			$new_lines[]	= 'RewriteCond %{HTTP_HOST} "^www.'.$server_name.'"';
			$new_lines[]	= 'RewriteRule (.*) "https://'.$server_name.'/$1" [R=301,L]';
		}

		$new_lines[]		= "\n";


		// hide index.php
		if( $hide_index ){
			$new_lines[]	= 'RewriteBase "'.$home_root.'"';
			$new_lines[]	= '';

			$new_lines[]	= '# Don\'t rewrite multiple times';
			$new_lines[]	= 'RewriteCond %{QUERY_STRING} gp_rewrite';
			$new_lines[]	= 'RewriteRule .* - [L]';
			$new_lines[]	= '';

			$new_lines[]	= '# Redirect away from requests with index.php';
			$new_lines[]	= 'RewriteRule index\.php(.*) "'.rtrim($home_root,'/').'$1" [R=302,L]';
			$new_lines[]	= '';

			$new_lines[]	= '# Add gp_rewrite to root requests';
			$new_lines[]	= 'RewriteRule ^$ "'.$home_root.'index.php?gp_rewrite" [qsa,L]';
			$new_lines[]	= '';

			$new_lines[]	= '# Don\'t rewrite for static files';
			$new_lines[]	= 'RewriteCond %{REQUEST_FILENAME} -f [OR]';
			$new_lines[]	= 'RewriteCond %{REQUEST_FILENAME} -d [OR]';
			$new_lines[]	= 'RewriteCond %{REQUEST_URI} \.(js|css|jpe?g|jpe|gif|png|ico)$ [NC]';
			$new_lines[]	= 'RewriteRule .* - [L]';
			$new_lines[]	= '';

			$new_lines[]	= '# Send all other requests to index.php';
			$new_lines[]	= '# Append the gp_rewrite argument to tell cms not to use index.php and to prevent multiple rewrites';
			$new_lines[]	= 'RewriteRule /?(.*) "'.$home_root.'index.php?gp_rewrite=$1" [qsa,L]';
			$new_lines[]	= '';
		}



		return $contents.'

# BEGIN Typesetter
<IfModule mod_rewrite.c>
	RewriteEngine On

	'.implode("\n\t",$new_lines).'
</IfModule>
# END Typesetter';

	}


	/**
	 * Optimally, generating rules for IIS would involve parsing the xml and integrating cms rules
	 *
	 */
	public function Rewrite_RulesIIS( $hide_index = true, $existing_contents ){


		// anything less than 80 characters can be replaced safely
		// other than that, users should handle manually
		// Example empty configuration: < ?xml version="1.0" encoding="UTF-8" ? > <configuration> </configuration>
		if( strlen($existing_contents) > 80 ){
			return false;
		}

		if( !$hide_index ){
			return '<?xml version="1.0" encoding="UTF-8" ?>
<configuration>
</configuration>';
		}



		return '<?xml version="1.0" encoding="UTF-8" ?>
<configuration>
<system.webServer>
	<rewrite>
		<rules>
			<rule name="Redirect index.php" stopProcessing="true">
				<match url="index\.php/?(.*)" />
				<action type="Redirect" url="{R:1}" appendQueryString="false" redirectType="Found" />
			</rule>

			<rule name="Rewrite index.php" stopProcessing="true">
				<match url="(.*)" />
				<conditions>
					<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
					<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
				</conditions>
				<action type="Rewrite" url="index.php?gp_rewrite={R:1}" />
			</rule>

			<rule name="Rewrite Root" stopProcessing="true">
				<match url="^$" />
				<action type="Rewrite" url="index.php?gp_rewrite" />
			</rule>
		</rules>
	</rewrite>
</system.webServer>
</configuration>';

	}


	/**
	 * Determine if installed on an IIS Server
	 *
	 */
	public static function IIS(){

		if( !isset($_SERVER['SERVER_SOFTWARE']) ){
			return false;
		}

		if( strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false ){
			return true;
		}
		return false;
	}

}

