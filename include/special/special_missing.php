<?php
defined('is_running') or die('Not an entry point...');


class special_missing{

	var $datafile;
	var $error_data = array();
	var $requested = false;


	function Init(){
		global $dataDir;
		$this->datafile = $dataDir.'/data/_site/error_data.php';
		if( file_exists($this->datafile) ){
			require($this->datafile);
			$this->error_data = $error_data;
		}
	}

	function SaveData(){
		return gpFiles::SaveArray($this->datafile,'error_data',$this->error_data);
	}

	function special_missing($requested=false){
		global $langmessage;

		$this->Init();

		if( !is_array($requested) ){
			$this->requested = $requested;

			$this->CheckRedirect();
			$this->CheckSimilar();
		}
		$this->Get404();
	}

	/**
	 * Redirect the request if a redirection entry matches the requested page
	 *
	 */
	function CheckRedirect(){

		if( $this->requested === false ){
			return;
		}

		if( !isset($this->error_data['redirects'][$this->requested]) ){
			return;
		}

		$target = $this->error_data['redirects'][$this->requested]['target'];
		$target = $this->GetTarget($target);
		if( $target == false ){
			return;
		}

		$code = $this->error_data['redirects'][$this->requested]['code'];
		common::Redirect($target,$code);
	}

	/**
	 * Redirect the request if the requested page closely matches an existing page
	 * If it's just a difference of case, then the similarity will be 100%
	 */
	function CheckSimilar(){
		global $config;
		$requested = trim($this->requested,'/');
		$similar = $this->SimilarTitleArray($requested);
		reset($similar);
		$first_title = key($similar);
		$first_percent = current($similar);

		if( $config['auto_redir'] > 0 && $first_percent >= $config['auto_redir'] ){
			$redirect = common::GetUrl($first_title,http_build_query($_GET),false);
			common::Redirect($redirect);
		}
	}



	/**
	 * Translate the $target url to a url that can be used with Header() or in a link
	 *
	 * @param string $target The user supplied value for redirection
	 * @param boolean $get_final If true, GetTarget() will check for additional redirection and $target existence before returning the url. Maximum of 10 redirects.
	 * @return string|false
	 */
	function GetTarget($target,$get_final = true){
		global $gp_index;
		static $redirects = 0;

		if( empty($target) ){
			return common::GetUrl('');
		}

		if( !$this->isGPLink($target) ){
			return $target;
		}

		if( !$get_final ){
			return common::GetUrl($target);
		}


		//check for more redirects
		if( isset($this->error_data['redirects'][$target]) ){
			$redirects++;
			if( $redirects > 10 ){
				return false;
			}

			$target = $this->error_data['redirects'][$target]['target'];
			return $this->GetTarget($target);
		}


		//check for target existence
		if( isset($gp_index[$target]) ){
			return common::GetUrl($target);
		}

		includeFile('admin/admin_tools.php');
		$scripts = admin_tools::AdminScripts();
		if( isset($scripts[$target]) ){
			return common::GetUrl($target);
		}

		return false;
	}

	function isGPLink($target){
		//has a url scheme (aka protocol)
		$reg = '#^[a-zA-Z][a-zA-Z0-9\+\.\-]+:#';
		if( preg_match($reg,$target,$matches) ){
			return false;
		}

		//strings beginning with / could be gplinks, they could also links to non-gpEasy managed pages
		// we could do additional testing, but we could never be certain what the user intent is
		if( strpos($target,'/') === 0 ){
			return false;
		}

		return true;
	}



	function Get404(){
		global $langmessage,$page;

		gpOutput::AddHeader('Not Found',true,404);
		$page->head .= '<meta name="robots" content="noindex,nofollow" />'; //this isn't getting to the template because $page isn't available yet

		//message for admins
		if( common::LoggedIn() ){
			if( $this->requested && !common::SpecialOrAdmin($this->requested) ){
				$with_spaces = htmlspecialchars($this->requested);
				$link = common::GetUrl('Admin_Menu','cmd=add_hidden&redir=redir&title='.rawurlencode($this->requested)).'" title="'.$langmessage['create_new_file'].'" data-cmd="gpajax';
				$message = sprintf($langmessage['DOESNT_EXIST'],$with_spaces,$link);
				message($message);
			}
		}

		//Contents of 404 page
		$wrap = gpOutput::ShowEditLink('Admin_Missing');
		if( $wrap ){
			echo gpOutput::EditAreaLink($edit_index,'Admin_Missing',$langmessage['edit'],'cmd=edit404',' title="'.$langmessage['404_Page'].'" ');
			echo '<div class="editable_area" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
		}

		echo special_missing::Get404Output();

		if( $wrap ){
			echo '</div>';
		}

	}

	/**
	 * Return the custom 404 page content if it exists, otherwise return the default content
	 *
	 */
	function Get404Output(){

		if( isset($this->error_data['404_TEXT']) ){
			$text = $this->error_data['404_TEXT'];
		}else{
			$text = special_missing::DefaultContent();
		}

		return str_replace('{{Similar_Titles}}',$this->SimilarTitles(),$text);
	}

	/**
	 * Get a comma separated list of links to titles similar to the requested page
	 * @return string
	 */
	function SimilarTitles(){

		$similar = $this->SimilarTitleArray($this->requested);
		$similar = array_slice($similar,0,7,true);

		$result = '';
		foreach($similar as $title => $percent_similar){
			$result .= common::Link_Page($title).', ';
		}
		return rtrim($result,', ');
	}

	/**
	 * Get a list of existing titles similar to the requested page
	 * @return array
	 */
	function SimilarTitleArray($title){
		global $gp_index;

		$similar = array();
		$percent_similar = array();
		$lower = str_replace(' ','_',strtolower($title));
		foreach($gp_index as $title => $id){
			similar_text($lower,strtolower($title),$percent);
			$similar[$title] = $percent;
		}

		arsort($similar);

		return $similar;
	}


	/**
	 * Returnt the default content of the 404 page
	 *
	 */
	function DefaultContent(){
		global $langmessage;
		$text = '<h2>'.$langmessage['Not Found'].'</h2>';
		$text .= '<p>';
		$text .= $langmessage['OOPS_TITLE'];
		$text .= '</p>';
		$text .= '<p>';
		$text .= '<b>'.$langmessage['One of these titles?'].'</b>';
		$text .= '<div class="404_suggestions">{{Similar_Titles}}</div>';
		$text .= '</p>';
		return $text;
	}

}
