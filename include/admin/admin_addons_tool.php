<?php
defined('is_running') or die('Not an entry point...');
includeFile('tool/parse_ini.php');


class admin_addons_tool{
	var $rate_testing = false; //for testing on local server

	var $ShowRatingText = true;
	var $scriptUrl = 'Admin_Addons';
	var $addonHistory = array();
	var $addonReviews = array();


	var $type;
	var $CanRate = true;
	var $messages = array();
	var $addon_info = array();
	var $dataFile;



	//
	// Rating
	//

	function InitRating(){
		global $page;

		$page->head_js[] = '/include/js/rate.js';

		//clear the data file ...
		$this->GetAddonData();
	}

	function GetAddonData(){
		global $dataDir;
		//review data
		$this->dataFile = $dataDir.'/data/_site/addonData.php';

		if( file_exists($this->dataFile) ){
			require($this->dataFile);
			$this->addonHistory = $addonData['history'];
			$this->addonReviews = $addonData['reviews'];
		}

	}

	function SaveAddonData(){

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
		return gpFiles::SaveArray($this->dataFile,'addonData',$addonData);
	}


	/**
	 * Display clickable rating stars
	 * $arg is the addon id for plugins, folder for themes
	 *
	 */
	function ShowRating($arg,$rating){

		ob_start();
		echo '<span class="rating">';

		for($i = 1;$i<6;$i++){
			$class = '';
			if( $i > $rating ){
				$class = ' class="unset"';
			}
			echo common::Link($this->scriptUrl,'','cmd=rate&rating='.$i.'&arg='.rawurlencode($arg),' data-rating="'.$i.'" data-cmd="gpabox" '.$class);
		}

		echo '<input type="hidden" name="rating" value="'.htmlspecialchars($rating).'" readonly="readonly"/>';

		echo '</span> ';
		return ob_get_clean();
	}


	static function GetAvailInstall($fullPath){

		$iniFile = $fullPath.'/Addon.ini';
		if( !file_exists($iniFile) ){
			return false;
		}
		$array = gp_ini::ParseFile($iniFile);
		if( $array === false ){
			return false;
		}

		if( !isset($array['Addon_Name']) ){
			return false;
		}
		$array += array('Addon_Version'=>'');
		return $array;
	}

	/**
	 * Manage addon ratings
	 *
	 */
	function admin_addon_rating($type,$url){
		global $page;

		$this->type = $type;
		$this->scriptUrl = $url;
		$page->head_js[] = '/include/js/rate.js';
		$this->GetAddonData();


		$arg =& $_REQUEST['arg'];

		switch($this->type){
			case 'theme':
				$this->GetAddonRateInfoTheme($arg);
			break;
			case 'plugin':
				$this->GetAddonRateInfoPlugin($arg);
			break;
		}

		if( !$this->CanRate($arg) ){
			return;
		}

		$cmd = common::GetCommand();
		switch($cmd){
			case 'Update Review';
			case 'Send Review':
			if( $this->SendRating() ){
				return;
			}

			case 'rate':
			return $this->RateForm();
		}

		return;
	}


	function RateForm(){
		global $config, $dirPrefix,$langmessage;


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
			echo 'You posted the following review on '.common::date($langmessage['strftime_date'],$this->addonReviews[$id]['time']);
		}


		//echo '<form action="'.common::GetUrl($this->scriptUrl,'cmd=rate&arg='.$this->addon_info['pass_arg']).'" method="post">';
		echo '<form action="'.common::GetUrl($this->scriptUrl).'" method="post">';
		echo '<input type="hidden" name="arg" value="'.$this->addon_info['pass_arg'].'"/>';
		echo '<input type="hidden" name="cmd" value="rate" />';


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



		echo '<tr><td>From</td><td>';
		$host = $_SERVER['HTTP_HOST'].$dirPrefix;
		echo '<input type="text" name="host"  size="50" value="'.htmlspecialchars($host).'" readonly="readonly" class="gpinput gpreadonly" />';
		echo '<br/>';
		echo '<input type="checkbox" name="show_site" value="hidden" /> Click to hide your site information on gpEasy.com.';
		echo '</td></tr>';

		echo '<tr><td></td><td>';

		if( isset($this->addonReviews[$id]) ){
			echo '<input type="submit" name="cmd" value="Update Review" class="gppost gpsubmit"/>';
		}else{
			echo '<input type="submit" name="cmd" value="Send Review" class="gppost gpsubmit"/>';
		}

		echo ' ';
		echo '<input type="submit" name="cmd" value="Cancel" class="admin_box_close gpcancel"/>';
		echo '</td></tr>';


		echo '</table>';
		echo '</form>';

		return true;
	}

	function CanRate(){

		/*
		if( $this->rate_testing ){
			message('rate_testing is enabled');
		}elseif( strpos($_SERVER['SERVER_ADDR'],'127') === 0 ){
			$this->messages[] = 'This installation of gpEasy is on a local server and is not accessible via the internet.';
		}
		*/

		if( !common::IniGet('allow_url_fopen') ){
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


	function GetAddonRateInfoTheme($dir){
		global $dataDir, $langmessage;

		$dir = str_replace('\\','/',$dir);
		$dir = str_replace('../','./',$dir);
		$full_dir = $dataDir.$dir;
		if( !file_exists($full_dir) ){
			$this->CanRate = false;
			$this->messages[] = $langmessage['OOPS'].' (directory doesn\'t exist)';
			return false;
		}

		$ini = admin_addons_tool::GetAvailInstall($full_dir);

		if( $ini === false ){
			$this->CanRate = false;
			$this->messages[] = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}

		if( !isset($ini['Addon_Unique_ID']) ){
			$this->CanRate = false;
			$this->messages[] = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}

		$this->addon_info['pass_arg'] = $dir;
		$this->addon_info['id'] = $ini['Addon_Unique_ID'];
		$this->addon_info['name'] = $ini['Addon_Name'];

		return true;
	}


	function GetAddonRateInfoPlugin($arg){
		global $config;

		if( isset($config['addons'][$arg]) && isset($config['addons'][$arg]['id']) ){

			$this->addon_info['pass_arg'] = $config['addons'][$arg]['id'];
			$this->addon_info['id'] = $config['addons'][$arg]['id'];
			$this->addon_info['name'] = $config['addons'][$arg]['name'];
			$this->addon_info['addonDir'] = $arg;
			return true;

		}

		if( !is_numeric($arg) ){
			$this->CanRate = false;
			$this->messages[] = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}


		foreach($config['addons'] as $addonDir => $data){
			if( isset($data['id']) && ($data['id'] == $arg) ){

				$this->addon_info['id'] = $arg;
				$this->addon_info['pass_arg'] = $arg;
				$this->addon_info['name'] = $data['name'];
				$this->addon_info['addonDir'] = $addonDir;
				return true;
			}
		}

		foreach($this->addonHistory as $data ){
			if( isset($data['id']) && ($data['id'] == $arg) ){

				$this->addon_info['id'] = $arg;
				$this->addon_info['pass_arg'] = $arg;
				$this->addon_info['name'] = $data['name'];
				return true;
			}
		}

		$this->CanRate = false;
		$this->messages[] = 'The supplied add-on ID is not in your add-on history.';
		return false;
	}


	/**
	 * Send the addon rating to gpEasy.com
	 *
	 */
	function SendRating(){
		global $langmessage, $config, $dirPrefix, $page;
		$page->ajaxReplace = array();
		$data = array();

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
		$data['addon_id'] = $id;
		$data['rating'] = $_POST['rating'];
		$data['review'] = $_POST['review'];
		$data['cmd'] = 'rate';
		$data['HTTP_HOST'] =& $_SERVER['HTTP_HOST'];
		$data['SERVER_ADDR'] =& $_SERVER['SERVER_ADDR'];
		$data['dirPrefix'] = $dirPrefix;
		if( isset($_POST['show_site']) && $_POST['show_site'] == 'hidden' ){
			$data['show_site'] = 'hidden';
		}
		$review_id = $this->PingRating($data);
		if( $review_id === false ){
			return false;
		}


		//save review information
		$this->addonReviews[$id] = array();
		$this->addonReviews[$id]['rating'] = $_POST['rating'];
		$this->addonReviews[$id]['review'] = substr($_POST['review'],0,500);
		$this->addonReviews[$id]['review_id'] = $review_id;
		$this->addonReviews[$id]['time'] = time();
		$this->SaveAddonData();

		$this->ShowRatingText = false;
		message('Your review has been saved and will be posted pending approval.');
		return true;
	}

	function PingRating($data){

		$path = 'http://gpeasy.loc/glacier/index.php/Special_Addons';
		$path = 'http://gpeasy.com/index.php/Special_Addons';
		$path .= '?'.http_build_query($data,'','&');
		$contents = file_get_contents($path);

		return $this->RatingResponse($contents);
	}

	function RatingResponse($contents){
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
	function GetInstalledComponents($from,$addon){
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
	function RemoveFromHandlers($gadgets){
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



	function CleanHooks($addon,$keep_hooks = array()){
		global $config;

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
					//message('remove this hook: '.$hook_name);
				}
			}
		}

		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
			}
		}

	}



	/**
	 * Determine if the addon (identified by $ini_info and $source_folder) is an upgrade to an existing addon
	 *
	 * @return mixed
	 */
	function UpgradePath($ini_info,$config_key='addons'){
		global $config;

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

		return false;
	}



	function AddonPanelGroup($addon_key, $addon_config, $show_hooks = true ){
		global $langmessage, $config;

		$new_version = false;


		//show Special Links
		$sublinks = admin_tools::GetAddonTitles( $addon_key );
		if( !empty($sublinks) ){
			echo '<li class="expand_child_click">';
			echo '<a>Special Links ('.count($sublinks).')</a>';
			echo '<ul>';
			foreach($sublinks as $linkName => $linkInfo){
				echo '<li>'.common::Link($linkName,$linkInfo['label']).'</li>';
			}
			echo '</ul></li>';
		}


		//show Admin Links
		$sublinks = admin_tools::GetAddonComponents($config['admin_links'],$addon_key);
		if( !empty($sublinks) ){
			echo '<li class="expand_child_click">';
			echo '<a>Admin Links ('.count($sublinks).')</a>';
			echo '<ul>';
			foreach($sublinks as $linkName => $linkInfo){
				echo '<li>'.common::Link($linkName,$linkInfo['label']).'</li>';
			}
			echo '</ul></li>';
		}

		//show Gadgets
		$gadgets = admin_tools::GetAddonComponents($config['gadgets'],$addon_key);
		if( is_array($gadgets) && (count($gadgets) > 0) ){
			echo '<li class="expand_child_click">';
			echo '<a>'.$langmessage['gadgets'].' ('.count($gadgets).')</a>';
			echo '<ul>';
			foreach($gadgets as $name => $value){
				echo '<li>';
				echo $this->GadgetLink($name);
				echo '</li>';
			}
			echo '</ul></li>';
		}

		//hooks
		if( $show_hooks ){
			$hooks = self::AddonHooks($addon_key);
			if( count($hooks) > 0 ){
				echo '<li class="expand_child_click">';
				echo '<a>Hooks</a>';
				echo '<ul>';
				foreach($hooks as $name => $hook_info){
					echo '<li><a>'.str_replace('_',' ',$name).'</a></li>';
				}
				echo '</ul></li>';
			}
		}

	}


	/**
	 *
	 * @static
	 */
	function AddonHooks($addon){
		global $config;
		$hooks = array();

		if( !isset($config['hooks']) || !is_array($config['hooks']) ){
			return $hooks;
		}
		foreach($config['hooks'] as $hook => $hook_array){
			if( isset($hook_array[$addon]) ){
				$hooks[$hook] = $hook_array[$addon];
			}
		}
		return $hooks;
	}
}



