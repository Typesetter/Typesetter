<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::incl('PageProtect.php');

class AdminProtect extends PageProtect{

	function AdminProtect(){
		global $gp_menu;

		$this->PageProtect();

		echo '<h2>';
		echo common::Link('Admin_Protect','Protected Pages');
		echo '</h2>';

		$cmd = common::GetCommand();
		switch($cmd){
			case 'savecontent':
				$this->SaveContent();
			case 'editcontent':
				$this->EditContent();
			return;
		}


		echo '<p>';
		echo '<b>'.common::Link('Admin_Protect','Protected Content','cmd=editcontent').'</b> ';
		echo 'Edit the content users will see if they navigate to a protected page and aren\'t logged in.';
		echo '</p>';


		if( !count($this->config['pages']) ){
			echo '<p>There aren\'t any protected pages.</p>';
			return;
		}

		echo '<table class="bordered">';
		echo '<tr><th>Pages</th><th>Child Pages</th></tr>';
		foreach($this->config['pages'] as $page_index => $bool){

			$title = common::IndexToTitle($page_index);

			//may be deleted
			if( !$title ){
				continue;
			}

			echo '<tr>';
			echo '<td>';
			echo common::Link_Page($title);
			echo '</td>';
			echo '<td>';

			$affected = common::Descendants($page_index,$gp_menu);
			$titles = array();
			foreach($affected as $temp_index){
				$title = common::IndexToTitle($temp_index);
				$titles[] = common::Link_Page($title);
			}
			echo implode(', ',$titles);
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

	}

	function SaveContent(){
		global $langmessage;

		$content =& $_POST['content'];
		gpFiles::cleanText($content);

		$this->config['content'] = $content;
		if( $this->SaveConfig() ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS']);
		}
	}

	function EditContent(){
		global $langmessage;

		$content = $this->config['content'];
		if( !empty($_POST['content']) ){
			$content = $_POST['content'];
		}

		echo '<form method="post" action="'.common::GetUrl('Admin_Protect').'">';
		echo '<input type="hidden" name="cmd" value="savecontent"/>';
		common::UseCK($content,'content');
		echo '<input type="submit" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel" />';
		echo '</form>';

	}



}


