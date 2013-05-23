<?php
defined('is_running') or die('Not an entry point...');

class PageProtect{
	var $parent_protected = false;
	var $is_protected = false;

	function PageProtect(){
		global $addonPathData;


		//get the configuration
		$this->config_file = $addonPathData.'/search_config.php';
		$config = array();
		if( file_exists($this->config_file) ){
			include($this->config_file);
		}
		$this->config = $config;

		$default_content ='<h2>Protected Page</h2><p>Sorry, this page cannot be viewed unless you are logged in.</p>';

		$this->config += array('pages'=>array(),'content'=>$default_content);
	}

	function Command($cmd){
		global $page;

		$this->IsProtected($page->gp_index);

		if( admin_tools::HasPermission('Admin_Protect') ){
			$page->admin_links[] = common::Link($page->title,'Page Protect','cmd=passprotect','name="gpajax"');
			return $this->Admin($cmd,$page->gp_index);
		}

		if( !$this->is_protected ){
			return $cmd;
		}


		//if protected,
		$page->contentBuffer = $this->config['content'];

		return 'return';
	}

	function IsProtected($index){
		global $gp_menu;
		if( isset($this->config['pages'][$index]) ){
			$this->is_protected = true;
			return 1;
		}

		$parents = common::Parents($index,$gp_menu);
		foreach($parents as $parent_index){
			if( isset($this->config['pages'][$parent_index]) ){
				$this->is_protected = true;
				$this->parent_protected = true;
				return 2;
			}
		}
		return false;
	}



	function Admin($cmd,$index){
		global $gp_titles,$langmessage,$page;

		if( !admin_tools::HasPermission('Admin_Protect') ){
			return;
		}


		switch($cmd){
			case 'passprotect':
				$this->OptionsForm($index);
			return 'return';

			case 'rm_protection':
				$page->ajaxReplace = array();
				unset($this->config['pages'][$index]);
				if( $this->SaveConfig() ){
					$this->is_protected = false;
					message($langmessage['SAVED']);
				}else{
					message($langmessage['OOPS']);
				}
			return $cmd;
			case 'protect_page':
				$page->ajaxReplace = array();
				$this->config['pages'][$index] = true;
				if( $this->SaveConfig() ){
					$this->is_protected = true;
					message($langmessage['SAVED']);
				}else{
					message($langmessage['OOPS']);
				}
			return $cmd;
		}

		if( $this->is_protected ){
			message('Notice: This page is currently protected and can only be viewed by logged in users.');
		}

		return $cmd;
	}

	function OptionsForm($index){
		global $page,$langmessage;
		$page->ajaxReplace = array();


		ob_start();
		echo '<div><h3>Protect This Page</h3>';
		echo '<form method="post" action="'.common::GetUrl($page->title).'">';
		echo '<input type="hidden" name="index" value="'.htmlspecialchars($index).'" />';


		if( $this->parent_protected ){
			echo '<p>This page is currently protected and can only be viewed by logged in users.</p>';
			echo '<p>This file is protected because a parent page (as set in the '.common::Link('Admin_Menu',$langmessage['Main Menu']).') is protected.</p>';
			echo '<p> <input type="button" name="" value="'.$langmessage['Close'].'" class="admin_box_close gpcancel"/></p>';


		}elseif( $this->is_protected ){
			echo '<p>This page, and all child pages, are currently protected and can only be viewed by logged in users.</p>';
			echo '<p>Would you like to make these pages viewable by everyone?</p>';

			echo '<p>';
			echo '<input type="hidden" name="cmd" value="rm_protection" />';
			echo '<input type="submit" name="" value="Remove Protection" class="gppost gpsubmit"/>';
			echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
			echo '</p>';

		}else{
			echo '<p>This page is not protected and can be viewed by anyone.</p>';
			echo '<p>Would you like to restrict viewing of this page, and all child pages, to logged in users?</p>';

			echo '<p>';
			echo '<input type="hidden" name="cmd" value="protect_page" />';
			echo '<input type="submit" name="" value="Protect This Page" class="gppost gpsubmit" />';
			echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel"/>';
			echo '</p>';
		}


		echo '</form>';

		echo '<p class="sm">';
		echo common::Link('Admin_Protect','Page Protect Admin');
		echo '</p>';

		echo '</div>';

		$content = ob_get_clean();
		$page->ajaxReplace[] = array('admin_box_data','',$content);
	}

	function SaveConfig(){
		return gpFiles::SaveArray($this->config_file,'config',$this->config);
	}

}
