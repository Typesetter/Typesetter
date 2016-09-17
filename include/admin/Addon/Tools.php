<?php

namespace gp\admin\Addon;

defined('is_running') or die('Not an entry point...');



class Tools extends \gp\special\Base{

	public $rate_testing		= false; //for testing on local server

	public $ShowRatingText		= true;
	public $config_index		= 'addons';
	protected $scriptUrl		= 'Admin/Addons';
	public $addonHistory		= array();
	public $addonReviews		= array();


	public $messages			= array();
	public $addon_info			= array();
	public $dataFile;

	public $invalid_folders		= array();

	private $pass_arg;



	//
	// Rating
	//

	public function InitRating(){

		$this->page->head_js[] = '/include/js/rate.js';

		//clear the data file ...
		$this->GetAddonData();
	}

	/**
	 * Get addon history and review data
	 *
	 */
	public function GetAddonData(){
		global $dataDir;

		$this->dataFile = $dataDir.'/data/_site/addonData.php';
		$addonData		= \gp\tool\Files::Get('_site/addonData');

		if( $addonData ){
			$this->addonHistory = $addonData['history'];
			$this->addonReviews = $addonData['reviews'];
		}

	}

	public function SaveAddonData(){

		if( !isset($this->dataFile) ){
			trigger_error('dataFile not set');
			return;
		}

		$addonData = array();

		while( count($this->addonHistory) > 30 ){
			array_shift($this->addonHistory);
		}

		$addonData['history'] = $this->addonHistory;
		$addonData['reviews'] = $this->addonReviews;
		return \gp\tool\Files::SaveData($this->dataFile,'addonData',$addonData);
	}


	/**
	 * Display clickable rating stars
	 * $arg is the addon id for plugins, folder for themes
	 *
	 */
	public function ShowRating($arg,$rating){

		ob_start();
		echo '<span class="rating">';

		for($i = 1;$i<6;$i++){
			$class = '';
			if( $i > $rating ){
				$class = ' class="unset"';
			}
			echo \gp\tool::Link($this->scriptUrl,'','cmd=ReviewAddonForm&rating='.$i.'&arg='.rawurlencode($arg),' data-rating="'.$i.'" data-cmd="gpabox" '.$class);
		}

		echo '<input type="hidden" name="rating" value="'.htmlspecialchars($rating).'" readonly="readonly"/>';

		echo '</span> ';
		return ob_get_clean();
	}


	/**
	 * Return ini info if the addon is installable
	 *
	 * @return false|array
	 */
	public function GetAvailInstall($dir){
		global $langmessage;

		$iniFile	= $dir.'/Addon.ini';
		$dirname	= basename($dir);

		if( !file_exists($iniFile) ){

			if( is_readable($dir) ){
				$this->invalid_folders[$dirname]	= 'Addon.ini is not readable or does not exist';
			}else{
				$this->invalid_folders[$dirname]	= 'Directory is not readable';
			}

			return false;
		}

		$array = \gp\tool\Ini::ParseFile($iniFile);
		if( $array === false ){
			$this->invalid_folders[$dirname]	= $langmessage['Ini_Error'];
			return false;
		}

		if( !isset($array['Addon_Name']) ){
			$this->invalid_folders[$dirname]	= $langmessage['Ini_No_Name'];
			return false;
		}

		$array += array('Addon_Version'=>'');
		return $array;
	}


	/**
	 * Manage addon ratings
	 *
	 */
	public function AdminAddonRating(){

		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'SendAddonReview';
			if( $this->SendAddonReview() ){
				return;
			}
		}

		$this->ReviewAddonForm();
	}


	public function ReviewAddonForm(){
		global $config, $dirPrefix, $langmessage;

		if( !$this->CanRate() ){
			return;
		}

		$this->page->head_js[]	= '/include/js/rate.js';


		//get appropriate variables
		$id = $this->addon_info['id'];

		if( isset($_REQUEST['rating']) ){
			$rating = $_REQUEST['rating'];
		}elseif( isset($this->addonReviews[$id]) ){
			$rating = $this->addonReviews[$id]['rating'];
		}else{
			$rating = 5;
		}

		if( isset($_REQUEST['review']) ){
			$review = $_REQUEST['review'];
		}elseif( isset($this->addonReviews[$id]) ){
			$review = $this->addonReviews[$id]['review'];
		}else{
			$review = '';
		}

		echo '<h2>';
		echo $this->addon_info['name'].' &#187; '.'Rate';
		echo '</h2>';

		if( isset($this->addonReviews[$id]) ){
			echo 'You posted the following review on '.\gp\tool::date($langmessage['strftime_date'],$this->addonReviews[$id]['time']);
		}


		echo '<form action="'.\gp\tool::GetUrl($this->scriptUrl).'" method="post">';
		echo '<input type="hidden" name="arg" value="'.htmlspecialchars($this->pass_arg).'"/>';


		echo '<table class="rating_table">';

		echo '<tr><td>Rating</td><td>';

		echo '<span class="rating">';
		for($i=1;$i<6;$i++){
			$class = '';
			if( $i > $rating ){
				$class = ' class="unset"';
			}
			echo '<a data-rating="'.$i.'"'.$class.'></a>';
		}
		echo '<input type="hidden" name="rating" value="'.htmlspecialchars($rating).'" />';
		echo '</span> ';
		echo '</td></tr>';


		echo '<tr><td>Review</td><td>';
		echo '<textarea name="review" cols="50" rows="7" class="gptextarea">';
		echo htmlspecialchars($review);
		echo '</textarea>';
		echo '</td></tr>';


		$server		= \gp\tool::ServerName();
		$host		= $server.$dirPrefix;

		echo '<tr><td>From</td><td>';
		echo '<input type="text" name="host"  size="50" value="'.htmlspecialchars($host).'" readonly="readonly" class="gpinput gpreadonly" />';
		echo '<br/>';
		echo '<input type="checkbox" name="show_site" value="hidden" /> Click to hide your site information on '.CMS_READABLE_DOMAIN.'.';
		echo '</td></tr>';

		echo '<tr><td></td><td>';

		if( isset($this->addonReviews[$id]) ){
			echo '<button type="submit" name="cmd" value="SendAddonReview" class="gppost gpsubmit">Update Review</button>';
		}else{
			echo '<button type="submit" name="cmd" value="SendAddonReview" class="gppost gpsubmit">Send Review</button>';
		}

		echo ' ';
		echo '<input type="submit" name="cmd" value="Cancel" class="admin_box_close gpcancel"/>';
		echo '</td></tr>';


		echo '</table>';
		echo '</form>';

		return true;
	}


	/**
	 * Get Addon info for rating
	 * Return true if it can be rated
	 *
	 */
	public function CanRate(){

		$this->GetAddonData();

		$arg =& $_REQUEST['arg'];

		switch($this->config_index){
			case 'themes':
				$this->GetAddonRateInfoTheme($arg);
			break;
			case 'addons':
				$this->GetAddonRateInfoPlugin($arg);
			break;
			default:
			return false;
		}


		if( !\gp\tool::IniGet('allow_url_fopen') ){
			$this->messages[] = 'Your installation of PHP does not support url fopen wrappers.';
		}

		if( count($this->messages) > 0 ){
			$message = 'Oops, you are currently unable to rate this addon for the following reasons:';
			$message .= '<ul>';
			$message .= '<li>'.implode('</li><li>',$this->messages).'</li>';
			$message .= '</ul>';
			message($message);
			$this->ShowRatingText = false;
			return false;
		}
		return true;
	}


	public function GetAddonRateInfoTheme($dir){
		global $dataDir, $langmessage;

		$dir = str_replace('\\','/',$dir);
		$dir = str_replace('../','./',$dir);
		$full_dir = $dataDir.$dir;

		if( !file_exists($full_dir) ){
			$this->messages[] = $langmessage['OOPS'].' (directory doesn\'t exist)';
			return false;
		}

		$ini = $this->GetAvailInstall($full_dir);

		if( $ini === false ){
			$this->messages[] = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}

		if( !isset($ini['Addon_Unique_ID']) ){
			$this->messages[] = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}


		$this->pass_arg				= $dir;
		$this->addon_info['id']		= $ini['Addon_Unique_ID'];
		$this->addon_info['name']	= $ini['Addon_Name'];

		return true;
	}


	public function GetAddonRateInfoPlugin($arg){
		global $config;

		if( isset($config['addons'][$arg]) && isset($config['addons'][$arg]['id']) ){

			$this->pass_arg					= $config['addons'][$arg]['id'];;
			$this->addon_info['id']			= $config['addons'][$arg]['id'];
			$this->addon_info['name']		= $config['addons'][$arg]['name'];
			return true;

		}

		if( !is_numeric($arg) ){
			$this->messages[] = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}


		foreach($config['addons'] as $addonDir => $data){
			if( isset($data['id']) && ($data['id'] == $arg) ){

				$this->pass_arg					= $arg;

				$this->addon_info['id']			= $data['id'];
				$this->addon_info['name']		= $data['name'];
				return true;
			}
		}

		foreach($this->addonHistory as $data ){
			if( isset($data['id']) && ($data['id'] == $arg) ){

				$this->pass_arg					= $arg;
				$this->addon_info['id']			= $data['id'];
				$this->addon_info['name']		= $data['name'];
				return true;
			}
		}
		$this->messages[] = 'The supplied add-on ID is not in your add-on history.';
		return false;
	}


	/**
	 * Send the addon rating to server
	 *
	 */
	public function SendAddonReview(){
		global $langmessage, $config, $dirPrefix;
		$this->page->ajaxReplace = array();
		$data = array();

		if( !$this->CanRate() ){
			return;
		}

		if( !is_numeric($_POST['rating']) || ($_POST['rating'] < 1) || ($_POST['rating'] > 5 ) ){
			message($langmessage['OOPS'].' (Invalid Rating)');
			return false;
		}

		$id = $this->addon_info['id'];

		//don't send if it hasn't chagned
		if( isset($this->addonReviews[$id]) ){
			$data['review_id'] = $this->addonReviews[$id]['review_id'];

			//if it hasn't changed..
			if( ($_POST['rating'] == $this->addonReviews[$id]['rating'])
				&& ($_POST['review'] == $this->addonReviews[$id]['review']) ){
					$this->ShowRatingText = false;
					message('Your review has been saved and will be posted pending approval.');
					return true;
			}
		}


		//send rating
		$data['addon_id']		= $id;
		$data['rating']			= (int)$_POST['rating'];
		$data['review']			= $_POST['review'];
		$data['cmd']			= 'rate';
		$data['HTTP_HOST']		= \gp\tool::ServerName();
		$data['SERVER_ADDR']	= $_SERVER['SERVER_ADDR'];
		$data['dirPrefix']		= $dirPrefix;
		if( isset($_POST['show_site']) && $_POST['show_site'] == 'hidden' ){
			$data['show_site'] = 'hidden';
		}
		$review_id = $this->PingRating($data);
		if( $review_id === false ){
			return false;
		}


		//save review information
		$this->addonReviews[$id]				= array();
		$this->addonReviews[$id]['rating']		= (int)$_POST['rating'];
		$this->addonReviews[$id]['review']		= substr($_POST['review'],0,500);
		$this->addonReviews[$id]['review_id']	= $review_id;
		$this->addonReviews[$id]['time']		= time();
		$this->SaveAddonData();

		$this->ShowRatingText = false;
		message('Your review has been saved and will be posted pending approval.');
		return true;
	}

	public function PingRating($data){

		$path		= CMS_DOMAIN.'/index.php/Special_Addons?'.http_build_query($data,'','&');
		$contents	= \gp\tool\RemoteGet::Get_Successful($path);

		return $this->RatingResponse($contents);
	}

	public function RatingResponse($contents){
		global $langmessage;
		if( empty($contents) ){
			message($langmessage['OOPS'].' (empty rating)');
			return false;
		}

		//!! these responses should be more detailed
		list($response,$detail) = explode(':',$contents);
		$response = trim($response);
		$detail = trim($detail);
		if( $response == 'successful_rating_request' ){
			return $detail;
		}

		//invalid_rating_request
		switch($detail){
			case 'no_addon';
				message('The supplied addon id was invalid.');
			break;

			default:
				message($langmessage['OOPS'].' (Detail:'.htmlspecialchars($detail).')');
				//message($contents);
			break;
		}
		return false;
	}


	/**
	 * Get a list of installed addons
	 *
	 */
	public function GetInstalledComponents($from,$addon){
		$result = array();
		if( !is_array($from) ){
			return $result;
		}

		foreach($from as $name => $info){
			if( !isset($info['addon']) ){
				continue;
			}
			if( $info['addon'] !== $addon ){
				continue;
			}
			$result[] = $name;
		}
		return $result;
	}




	//remove gadgets from $gpLayouts
	public function RemoveFromHandlers($gadgets){
		global $gpLayouts;

		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}


		foreach($gpLayouts as $theme => $containers){
			if( !is_array($containers) || !isset($containers['handlers']) || !is_array($containers['handlers']) ){
				continue;
			}
			foreach($containers['handlers'] as $container => $handlers){
				if( !is_array($handlers) ){
					continue;
				}

				foreach($handlers as $index => $handle){
					$pos = strpos($handle,':');
					if( $pos > 0 ){
						$handle = substr($handle,0,$pos);
					}

					foreach($gadgets as $gadget){
						if( $handle === $gadget ){
							$handlers[$index] = false; //set to false
						}
					}
				}

				$handlers = array_diff($handlers, array(false)); //remove false entries
				$handlers = array_values($handlers); //reset keys
				$gpLayouts[$theme]['handlers'][$container] = $handlers;
			}
		}
	}



	public function CleanHooks($addon,$keep_hooks = array()){
		global $config, $gp_hooks;

		if( !isset($config['hooks']) ){
			return;
		}

		foreach($config['hooks'] as $hook_name => $hook_array){

			foreach($hook_array as $hook_dir => $hook_args){

				//not cleaning other addons
				if( $hook_dir != $addon ){
					continue;
				}

				if( !isset($keep_hooks[$hook_name]) ){
					unset($config['hooks'][$hook_name][$hook_dir]);
					unset($gp_hooks[$hook_name][$hook_dir]);
					//message('remove this hook: '.$hook_name);
				}
			}
		}

		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
				unset($gp_hooks[$hook_name]);
			}
		}

	}



	/**
	 * Determine if the addon (identified by $ini_info and $source_folder) is an upgrade to an existing addon
	 *
	 * @return mixed
	 */
	public function UpgradePath($ini_info,$config_key='addons'){
		global $config, $dataDir;

		if( !isset($config[$config_key]) ){
			return false;
		}

		//by id
		if( isset($ini_info['Addon_Unique_ID']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( !isset($data['id']) || !is_numeric($data['id']) ){
					continue;
				}

				if( (int)$data['id'] == (int)$ini_info['Addon_Unique_ID'] ){
					return $addon_key;
				}
			}
		}

		//by name
		if( isset($ini_info['Addon_Name']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( isset($data['name']) && $data['name'] == $ini_info['Addon_Name'] ){
					return $addon_key;
				}
			}
		}

		//by path
		if( isset($ini_info['source_folder']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( !isset($data['code_folder_part']) ){
					continue;
				}
				$source_folder = $dataDir.$data['code_folder_part'];

				if( $source_folder === $ini_info['source_folder'] ){
					return $addon_key;
				}
			}
		}

		return false;
	}



	public function AddonPanelGroup($addon_key, $show_hooks = true, $format = false ){

		$this->AddonPanel_Special($addon_key,$format);
		$this->AddonPanel_Admin($addon_key,$format);
		$this->AddonPanel_Gadget($addon_key,$format);

		if( $show_hooks ){
			$this->AddonPanel_Hooks($addon_key,$format);
		}
	}

	public function AdminLinkList($links, $label, $format){
		$_links = array();
		foreach($links as $linkName => $linkInfo){
			$_links[] = \gp\tool::Link($linkName,$linkInfo['label']);
		}
		$this->FormatList($_links,$label,$format);
	}

	public function FormatList($links, $label, $format = false){
		if( empty($links) ){
			return;
		}

		if( !$format ){
			$format				= array();
			$format['start']	= '<li class="expand_child_click"><a>%s <span>(%s)</span></a>';
			$format['end']		= '</li>';
		}

		echo sprintf($format['start'], $label, count($links));

		echo '<ul>';
		foreach($links as $link){
			echo '<li>'.$link.'</li>';
		}
		echo '</ul>';
		echo $format['end'];
	}

	//show Special Links
	public function AddonPanel_Special($addon_key, $format){
		$sublinks = \gp\admin\Tools::GetAddonTitles( $addon_key );
		$this->AdminLinkList($sublinks,'Special Links',$format);
	}

	//show Admin Links
	public function AddonPanel_Admin($addon_key,$format){
		global $langmessage, $config;

		$sublinks = \gp\admin\Tools::GetAddonComponents($config['admin_links'],$addon_key);
		$this->AdminLinkList($sublinks,'Admin Links',$format);
	}

	//show Gadgets
	public function AddonPanel_Gadget($addon_key, $format){
		global $langmessage, $config;

		$gadgets	= \gp\admin\Tools::GetAddonComponents($config['gadgets'],$addon_key);
		$links		= array();
		foreach($gadgets as $name => $value){
			$links[] = $this->GadgetLink($name);
		}
		$this->FormatList($links,$langmessage['gadgets'],$format);
	}

	//hooks
	public function AddonPanel_Hooks($addon_key, $format){

		$hooks = self::AddonHooks($addon_key);
		$links = array();

		foreach($hooks as $name => $hook_info){
			$links[] = '<a href="'.CMS_DOMAIN.'/Plugin_Hooks?hook='.$name.'" target="_blank">'.str_replace('_',' ',$name).'</a>';
		}
		$this->FormatList($links,'Hooks',$format);
	}


	/**
	 * Return array of hooks associated with the addon
	 *
	 */
	public static function AddonHooks($addon_key){
		global $config;
		$hooks = array();

		if( !isset($config['hooks']) || !is_array($config['hooks']) ){
			return $hooks;
		}
		foreach($config['hooks'] as $hook => $hook_array){
			if( isset($hook_array[$addon_key]) ){
				$hooks[$hook] = $hook_array[$addon_key];
			}
		}
		return $hooks;
	}

	public static function DetailLink( $type, $id, $label = 'Details', $q = '', $attr='' ){
		return '<a href="'.self::DetailUrl($type,$id,$q).'" data-cmd="remote" '.$attr.'>'.$label.'</a>';
	}

	public static function DetailUrl($type,$id,$q=''){
		$url = 'Themes';
		if( $type == 'plugins' ){
			$url = 'Plugins';
		}
		if( !empty($q) ){
			$q = '?'.$q;
		}
		return addon_browse_path.'/'.$url.'/'.$id.$q;
	}
}



