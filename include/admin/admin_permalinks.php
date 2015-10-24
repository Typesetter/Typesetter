<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/FileSystem.php');
includeFile('tool/RemoteGet.php');

class admin_permalinks{

	var $rule_file_name = '';
	var $rule_file = '';
	var $changed_to_hide = false;


	function __construct(){
		global $langmessage,$dataDir;


		$iis = self::IIS();
		if( $iis ){
			$this->rule_file_name = 'web.config';
		}else{
			$this->rule_file_name = '.htaccess';
		}
		$this->rule_file = $dataDir.'/'.$this->rule_file_name;

		gp_filesystem_base::init($this->rule_file);

		echo '<h2>'.$langmessage['permalink_settings'].'</h2>';


		$cmd = common::GetCommand();
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
	 * Display Permalink Options
	 *
	 */
	function ShowForm(){
		global $langmessage, $gp_filesystem;



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


		echo '<form method="post" action="'.common::GetUrl('Admin_Permalinks').'">';
		echo '<table class="bordered">';

		echo '<tr><th colspan="2">'.$langmessage['options'].'</th></tr>';

		//default
			$checked = '';
			if( !$_SERVER['gp_rewrite'] ){
				$checked = 'checked="checked"';
			}

			echo '<tr><td>';

			echo '<label>';
			echo '<input type="radio" name="rewrite_setting" value="no_rewrite" '.$checked.' /> '.$langmessage['use_index.php'];
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

			echo '<label>';
			echo '<input type="radio" name="rewrite_setting" value="hide_index" '.$checked.' /> '.$langmessage['hide_index'];
			echo '</label>';

			echo '</td><td>';
			echo ' <pre>';
			echo $this->ExampleUrl(false);
			echo '</pre>';
			echo '</td></tr>';

		echo '</table>';

		echo '<p>';

		echo '<input type="hidden" name="cmd" value="continue" />';
		echo '<input type="submit" name="" class="gpsubmit" value="'.$langmessage['continue'].'"/>';
		echo '</p>';

		echo '</form>';

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


	/**
	 * Determine how to save the htaccess file to the server (ftp,direct,manual) and give user the appropriate options
	 *
	 * @return boolean true if the .htaccess file is saved
	 */
	function SaveHtaccess(){
		global $gp_filesystem, $langmessage, $dirPrefix;

		if( isset($_POST['rewrite_setting']) && $_POST['rewrite_setting'] == 'hide_index' ){
			$this->changed_to_hide = true;
		}


		// only proceed with hide if we can test the results
		if( !gpRemoteGet::Test() ){
			$this->ManualMethod();
			return false;
		}


		if( !$gp_filesystem || !$gp_filesystem->ConnectOrPrompt('Admin_Permalinks') ){
			$this->ManualMethod();
			return false;
		}

		if( !$this->SaveRules() ){
			$gp_filesystem->CompleteForm($_POST,'Admin_Permalinks');
			$this->ManualMethod();
			return false;
		}

		message($langmessage['SAVED']);

		//redirect to new permalink structure
		$_SERVER['gp_rewrite'] = $this->changed_to_hide;
		common::SetLinkPrefix();
		$redir = common::GetUrl('Admin_Permalinks');
		common::Redirect($redir,302);

		return false;
	}


	/**
	 * Display instructions for manually creating the htaccess/web.config file
	 *
	 */
	function ManualMethod(){
		global $langmessage, $dirPrefix;

		$rules = admin_permalinks::Rewrite_Rules( $this->changed_to_hide, $dirPrefix );

		echo '<h3>'.$langmessage['manual_method'].'</h3>';
		echo '<p>';
		echo str_replace('.htaccess',$this->rule_file_name,$langmessage['manual_htaccess']);
		echo '</p>';

		//display rewrite code in textarea
		$lines = explode("\n",$rules);
		$len = 70;
		foreach($lines as $line){
			$line_len = strlen($line)+(substr_count($line,"\t")*3);
			$len = max($len,$line_len);
		}
		$len = min(140,$len);
		echo '<textarea cols="'.$len.'" rows="'.(count($lines)+1).'" readonly="readonly" onClick="this.focus();this.select();" class="gptextarea">';
		echo htmlspecialchars($rules);
		echo '</textarea>';
		echo '<form action="'.common::GetUrl('Admin_Permalinks').'" method="get">';
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
	function SaveRules(){
		global $gp_filesystem, $langmessage, $dirPrefix;

		//get current .htaccess
		$original_contents = false;
		if( file_exists($this->rule_file) ){
			$original_contents = file_get_contents($this->rule_file);
		}

		//add/remove gpEasy rules from $original_contents to get new $contents
		$contents = admin_permalinks::Rewrite_Rules( $this->changed_to_hide, $dirPrefix, $original_contents );

		if( $contents === false ){
			return false;
		}

		$filesystem_base = $gp_filesystem->get_base_dir();
		if( $filesystem_base === false ){
			return false;
		}

		$filesystem_path = $filesystem_base.'/'.$this->rule_file_name;

		if( !$gp_filesystem->put_contents($filesystem_path,$contents) ){
			return false;
		}


		//if TestResponse Fails, undo the changes
		//only need to test for hiding
		if( $this->changed_to_hide && !admin_permalinks::TestResponse() ){

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

		$end_comment = '# END gpEasy';
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
	 *
	 */
	static function Rewrite_Rules( $hide_index = true, $home_root, $existing_contents = '' ){

		if( !$existing_contents ){
			$existing_contents = '';
		}

		// IIS
		if( self::IIS() ){
			return self::Rewrite_RulesIIS( $hide_index, $existing_contents );
		}

		// Apache
		admin_permalinks::StripRules($existing_contents);

		if( !$hide_index ){
			return $existing_contents;
		}

		$home_root = rtrim($home_root,'/').'/';
		return $existing_contents . "\n\n".'# BEGIN gpEasy
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase "'.$home_root.'"


	# Don\'t rewrite multiple times
	RewriteCond %{QUERY_STRING} gp_rewrite
	RewriteRule .* - [L]

	# Redirect away from requests with index.php
	RewriteRule index\.php(.*) $1 [R=302,L]

	# Add gp_rewrite to root requests
	RewriteRule ^$ "'.$home_root.'index.php?gp_rewrite" [qsa,L]

	# Don\'t rewrite for static files
	RewriteCond %{REQUEST_FILENAME} -f [OR]
	RewriteCond %{REQUEST_FILENAME} -d [OR]
	RewriteCond %{REQUEST_URI} \.(js|css|jpe?g|jpe|gif|png|ico)$ [NC]
	RewriteRule .* - [L]

	# Send all other requests to index.php
	# Append the gp_rewrite argument to tell gpEasy not to use index.php and to prevent multiple rewrites
	RewriteRule /?(.*) "'.$home_root.'index.php?gp_rewrite=$1" [qsa,L]

</IfModule>
# END gpEasy';
	}


	/**
	 * Optimally, generating rules for IIS would involve parsing the xml and integrating gpEasy rules
	 *
	 */
	function Rewrite_RulesIIS( $hide_index = true, $existing_contents ){


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
	 * Determine if gpEasy installed on an IIS Server
	 *
	 */
	static function IIS(){

		if( strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false ){
			return true;
		}
		return false;
	}


}

