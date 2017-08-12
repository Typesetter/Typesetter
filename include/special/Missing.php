<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');


class Missing extends \gp\special\Base{

	public $datafile;
	public $error_data = array();
	public $requested;


	public function Init(){

		$this->datafile		= '_site/error_data';
		$this->error_data	= \gp\tool\Files::Get($this->datafile,'error_data');

		$this->error_data	+= array(
			'redirects'			=> array(
			)
		);

	}

	public function __construct($args){
		global $langmessage;

		parent::__construct($args);
		$this->Init();

		$this->requested	= $this->page->requested;
	}

	public function RunScript(){
		$cmd = \gp\tool::GetCommand();
		$this->RunCommands($cmd);
	}

	public function DefaultDisplay(){

		if( $this->page->gp_index !== 'special_missing' ){
			$this->CheckRedirect();
			$this->CheckSimilar();
		}

		$this->Get404();
	}

	/**
	 * Redirect the request if a redirection entry matches the requested page
	 *
	 */
	public function CheckRedirect(){

		if( is_null($this->requested) ){
			return;
		}

		$parts =		explode('/',$this->requested);
		$first_part	=	array_shift($parts);

		if( !isset($this->error_data['redirects'][$first_part]) ){
			return;
		}


		$target = $this->error_data['redirects'][$first_part]['target'];

		$target = $this->GetTarget($target);

		if( $target === false ){
			return;
		}

		if( !empty($parts) && is_string($target) ){
			$target .= '/'.implode('/',$parts);
		}

		$code = $this->error_data['redirects'][$first_part]['code'];
		\gp\tool::Redirect($target,$code);
	}

	/**
	 * Redirect the request if the requested page closely matches an existing page
	 * If it's just a difference of case, then the similarity will be 100%
	 *
	 */
	public function CheckSimilar(){
		global $config;

		$requested			= trim($this->requested,'/');
		$similar			= $this->SimilarTitleArray($requested);
		$first_title		= key($similar);
		$first_percent		= current($similar);

		if( $config['auto_redir'] > 0 && $first_percent >= $config['auto_redir'] ){
			$redirect = \gp\tool::GetUrl($first_title,http_build_query($_GET),false);
			\gp\tool::Redirect($redirect);
		}
	}



	/**
	 * Translate the $target url to a url that can be used with Header() or in a link
	 *
	 * @param string $target The user supplied value for redirection
	 * @param boolean $get_final If true, GetTarget() will check for additional redirection and $target existence before returning the url. Maximum of 10 redirects.
	 * @return string|false
	 */
	public function GetTarget($target,$get_final = true){
		global $gp_index;
		static $redirects = 0;

		if( empty($target) ){
			return \gp\tool::GetUrl('');
		}

		if( !$this->isGPLink($target) ){
			return $target;
		}

		if( !$get_final ){
			return \gp\tool::GetUrl($target);
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
			return \gp\tool::GetUrl($target);
		}

		$scripts = \gp\admin\Tools::AdminScripts();
		if( isset($scripts[$target]) ){
			return \gp\tool::GetUrl($target);
		}

		return false;
	}

	public function isGPLink($target){
		//has a url scheme (aka protocol)
		$reg = '#^[a-zA-Z][a-zA-Z0-9\+\.\-]+:#';
		if( preg_match($reg,$target,$matches) ){
			return false;
		}

		//strings beginning with / could be gplinks, they could also links to non-cms managed pages
		// we could do additional testing, but we could never be certain what the user intent is
		if( strpos($target,'/') === 0 ){
			return false;
		}

		return true;
	}



	public function Get404(){
		global $langmessage,$page;

		\gp\tool\Output::AddHeader('Not Found',true,404);
		$page->head .= '<meta name="robots" content="noindex,nofollow" />'; //this isn't getting to the template because $page isn't available yet

		//message for admins
		if( \gp\tool::LoggedIn() ){
			if( $this->requested && \gp\tool::SpecialOrAdmin($this->requested) === false ){
				$with_spaces = htmlspecialchars($this->requested);
				$link = \gp\tool::GetUrl('Admin/Menu/Ajax','cmd=AddHidden&redir=redir&title='.rawurlencode($this->requested)).'" title="'.$langmessage['create_new_file'].'" data-cmd="gpabox';
				$message = sprintf($langmessage['DOESNT_EXIST'],$with_spaces,$link);
				msg($message);
			}
		}

		echo '<div class="GPAREA filetype-special_missing">';
		//Contents of 404 page
		$wrap = \gp\tool\Output::ShowEditLink('Admin/Missing');
		if( $wrap ){
			echo \gp\tool\Output::EditAreaLink($edit_index,'Admin/Missing',$langmessage['edit'],'cmd=edit404',' title="'.$langmessage['404_Page'].'" ');
			echo '<div class="editable_area" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
		}

		echo self::Get404Output();

		if( $wrap ){
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Return the custom 404 page content if it exists, otherwise return the default content
	 *
	 */
	public function Get404Output(){

		if( isset($this->error_data['404_TEXT']) ){
			$text = $this->error_data['404_TEXT'];
		}else{
			$text = self::DefaultContent();
		}

		return str_replace('{{Similar_Titles}}',$this->SimilarTitles(),$text);
	}

	/**
	 * Get a comma separated list of links to titles similar to the requested page
	 * @return string
	 *
	 */
	public function SimilarTitles(){

		$similar	= $this->SimilarTitleArray($this->requested);
		$similar	= array_slice($similar,0,7,true);
		$result		= '';

		foreach($similar as $title => $percent_similar){
			$result .= \gp\tool::Link_Page($title).', ';
		}

		return rtrim($result,', ');
	}

	/**
	 * Get a list of existing titles similar to the requested page
	 * @return array
	 *
	 */
	public function SimilarTitleArray($title){
		global $gp_index, $gp_titles;

		$similar			= array();
		$lower				= str_replace(' ','_',strtolower($title));
		$admin				= \gp\tool::LoggedIn();

		foreach($gp_index as $title => $index){

			//skip private pages
			if( !$admin ){

				if( isset($gp_titles[$index]['vis']) ){
					continue;
				}
			}

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
	public function DefaultContent(){
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
