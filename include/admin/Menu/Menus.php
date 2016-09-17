<?php

namespace gp\admin\Menu;

defined('is_running') or die('Not an entry point...');

class Menus extends \gp\admin\Menu{

	public function RunScript(){

		$cmd = \gp\tool::GetCommand();

		switch($cmd){

			//remove
			case 'MenuRemove':
				$this->MenuRemove();
				$this->Redirect();
			break;


			//rename
			case 'MenuRenamePrompt':
				$this->MenuRenamePrompt();
			return;

			case 'MenuRename':
				$this->MenuRename();
				$this->Redirect();
			break;


			//new
			case 'NewMenuPrompt':
				$this->NewMenuPrompt();
			return;

			case 'NewMenuCreate':
				$this->NewMenuCreate();
				$this->Redirect();
			break;
		}

		$this->ShowForm();
	}

	function Redirect(){
		$url = \gp\tool::GetUrl('Admin/Menu','',false);
		\gp\tool::Redirect($url);
	}


	/**
	 * Display a form for creating a new menu
	 *
	 */
	public function NewMenuPrompt(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Menu/Menus').'" method="post">';

		echo '<h3>';
		echo $langmessage['Add New Menu'];
		echo '</h3>';

		echo '<p>';
		echo $langmessage['label'];
		echo ' &nbsp; ';
		echo '<input type="text" name="menu_name" class="gpinput" />';
		echo '</p>';

		echo '<p>';
		echo '<button type="submit" name="cmd" value="NewMenuCreate" class="gpsubmit" >'.htmlspecialchars($langmessage['continue']).'</button> ';
		echo '<button type="submit" class="admin_box_close gpcancel">'.htmlspecialchars($langmessage['cancel']).'</button>';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}



	/**
	 * Create an alternate menu
	 *
	 */
	public function NewMenuCreate(){
		global $config, $langmessage, $dataDir;

		$menu_name = $this->AltMenu_NewName();
		if( !$menu_name ){
			return;
		}

		$new_menu = \gp\admin\Menu\Tools::AltMenu_New();

		//get next index
		$index = 0;
		if( isset($config['menus']) && is_array($config['menus']) ){
			foreach($config['menus'] as $id => $label){
				$id = substr($id,1);
				$index = max($index,$id);
			}
		}
		$index++;
		$id = 'm'.$index;

		$menu_file = $dataDir.'/data/_menus/'.$id.'.php';
		if( !\gp\tool\Files::SaveData($menu_file,'menu',$new_menu) ){
			msg($langmessage['OOPS'].' (Menu Not Saved)');
			return false;
		}

		$config['menus'][$id] = $menu_name;
		if( \gp\admin\Tools::SaveConfig(true) ){
			$url = \gp\tool::GetUrl('Admin/Menu','menu='.$id,false);
			\gp\tool::Redirect($url);
		}
	}


	/**
	 * Check the posted name of a menu
	 *
	 */
	public function AltMenu_NewName(){
		global $langmessage;

		$menu_name = \gp\tool\Editing::CleanTitle($_POST['menu_name'],' ');
		if( empty($menu_name) ){
			msg($langmessage['OOPS'].' (Empty Name)');
			return false;
		}

		if( array_search($menu_name,$this->avail_menus) !== false ){
			msg($langmessage['OOPS'].' (Name Exists)');
			return false;
		}

		return $menu_name;
	}


	/**
	 * Display a form for editing the name of an alternate menu
	 *
	 */
	public function MenuRenamePrompt(){
		global $langmessage;

		$menu_id =& $_GET['id'];

		if( !\gp\admin\Menu\Tools::IsAltMenu($menu_id) ){
			echo '<div class="inline_box">';
			echo $langmessage['OOPS'];
			echo '</div>';
			return;
		}

		$menu_name = $this->avail_menus[$menu_id];

		echo '<div class="inline_box">';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Menu/Menus').'" method="post">';
		echo '<input type="hidden" name="cmd" value="MenuRename" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($menu_id).'" />';

		echo '<h3>';
		echo $langmessage['rename'];
		echo '</h3>';

		echo '<p>';
		echo $langmessage['label'];
		echo ' &nbsp; ';
		echo '<input type="text" name="menu_name" value="'.htmlspecialchars($menu_name).'" class="gpinput" />';
		echo '</p>';


		echo '<p>';
		echo '<button type="submit" name="cmd" value="MenuRename" class="gpsubmit">'.htmlspecialchars($langmessage['continue']).'</button> ';
		echo '<button type="submit" class="admin_box_close gpcancel">'.htmlspecialchars($langmessage['cancel']).'</button> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}



	/**
	 * Rename a menu
	 *
	 */
	protected function MenuRename(){
		global $langmessage,$config;

		$menu_id =& $_POST['id'];

		if( !\gp\admin\Menu\Tools::IsAltMenu($menu_id) ){
			msg($langmessage['OOPS']);
			return;
		}

		$menu_name = $this->AltMenu_NewName();
		if( !$menu_name ){
			return;
		}

		$config['menus'][$menu_id] = $menu_name;
		if( \gp\admin\Tools::SaveConfig(true) ){
			$this->avail_menus[$menu_id] = $menu_name;
		}
	}



	/**
	 * Remove an alternate menu from the configuration and delete the data file
	 *
	 */
	public function MenuRemove(){
		global $langmessage,$config,$dataDir;

		$menu_id =& $_POST['id'];
		if( !\gp\admin\Menu\Tools::IsAltMenu($menu_id) ){
			msg($langmessage['OOPS']);
			return;
		}

		unset($config['menus'][$menu_id]);
		unset($this->avail_menus[$menu_id]);

		\gp\admin\Tools::SaveConfig(true,true);


		//delete menu file
		$menu_file = $dataDir.'/data/_menus/'.$menu_id.'.php';
		if( \gp\tool\Files::Exists($menu_file) ){
			unlink($menu_file);
		}
	}


}