<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Text extends \gp\admin\Layout{

	public function RunScript(){


		$this->cmds['EditText']					= '';
		$this->cmds['SaveText']					= 'ReturnHeader';

		$this->cmds['AddonTextForm']			= '';
		$this->cmds['SaveAddonText']			= 'ReturnHeader';


		$cmd = \gp\tool::GetCommand();
		$this->RunCommands($cmd);
	}



	public function AddonTextForm(){
		global $langmessage,$config;

		$addon = \gp\tool\Editing::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);

		//not set up correctly
		if( $texts === false ){
			$this->EditText();
			return;
		}


		echo '<div class="inline_box" style="text-align:right">';
		echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content/Text').'" method="post">';
		echo '<input type="hidden" name="cmd" value="SaveAddonText" />';
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($addon).'" />'; //will be populated by javascript


		$this->AddonTextFields($texts);
		echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';


		echo '</form>';
		echo '</div>';

	}

	public function AddonTextFields($array){
		global $langmessage,$config;
		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['default'];
		echo '</th><th>';
		echo '</th></tr>';

		$key =& $_GET['key'];
		foreach($array as $text){

			$value = $text;
			if( isset($langmessage[$text]) ){
				$value = $langmessage[$text];
			}
			if( isset($config['customlang'][$text]) ){
				$value = $config['customlang'][$text];
			}

			$style = '';
			if( $text == $key ){
				$style = ' style="background-color:#f5f5f5"';
			}

			echo '<tr'.$style.'><td>';
			echo $text;
			echo '</td><td>';
			echo '<input type="text" name="values['.htmlspecialchars($text).']" value="'.$value.'" class="gpinput"/>'; //value has already been escaped with htmlspecialchars()
			echo '</td></tr>';

		}
		echo '</table>';
	}


	public function EditText(){
		global $config, $langmessage;

		if( !isset($_GET['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		$default = $value = $key = $_GET['key'];
		if( isset($langmessage[$key]) ){
			$default = $value = $langmessage[$key];
		}else{
			$default = $value = htmlspecialchars($key);
		}
		if( isset($config['customlang'][$key]) ){
			$value = $config['customlang'][$key];
		}else{
			$value = htmlspecialchars($key);
		}



		echo '<div class="inline_box">';
		echo '<form action="'.\gp\tool::GetUrl('Admin_Theme_Content/Text').'" method="post">';
		echo '<input type="hidden" name="cmd" value="savetext" />';
		echo '<input type="hidden" name="key" value="'.$value.'" />';

		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['default'];
		echo '</th><th>';
		echo '</th></tr>';
		echo '<tr><td>';
		echo $default;
		echo '</td><td>';
		//$value is already escaped using htmlspecialchars()
		echo '<input type="text" name="value" value="'.$value.'" class="gpinput"/>';
		echo '<p>';
		echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpsubmit"/>';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';
		echo '</td></tr>';
		echo '</table>';

		echo '</form>';
		echo '</div>';
	}



	public function SaveText(){
		global $config, $langmessage;

		if( !isset($_POST['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !isset($_POST['value']) ){
			message($langmessage['OOPS'].' (1)');
			return;
		}

		$default = $key = $_POST['key'];
		if( isset($langmessage[$key]) ){
			$default = $langmessage[$key];
		}

		$config['customlang'][$key] = $value = htmlspecialchars($_POST['value']);
		if( ($value === $default) || (htmlspecialchars($default) == $value) ){
			unset($config['customlang'][$key]);
		}


		$this->SaveConfig();
	}


	public function SaveAddonText(){
		global $langmessage,$config;

		$addon = \gp\tool\Editing::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);
		//not set up correctly
		if( $texts === false ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		foreach($texts as $text){
			if( !isset($_POST['values'][$text]) ){
				continue;
			}


			$default = $text;
			if( isset($langmessage[$text]) ){
				$default = $langmessage[$text];
			}

			$value = htmlspecialchars($_POST['values'][$text]);

			if( ($value === $default) || (htmlspecialchars($default) == $value) ){
				unset($config['customlang'][$text]);
			}else{
				$config['customlang'][$text] = $value;
			}
		}


		if( $this->SaveConfig() ){
			$this->UpdateAddon($addon);
		}

	}



	public function UpdateAddon($addon){
		if( !function_exists('OnTextChange') ){
			return;
		}

		\gp\tool\Plugins::SetDataFolder($addon);

		OnTextChange();

		\gp\tool\Plugins::ClearDataFolder();
	}

	public function GetAddonTexts($addon){
		global $langmessage,$config;


		$addon_config = \gp\tool\Plugins::GetAddonConfig($addon);
		$addonDir = $addon_config['code_folder_full'];
		if( !is_dir($addonDir) ){
			return false;
		}

		//not set up correctly
		if( !isset($config['addons'][$addon]['editable_text']) ){
			return false;
		}

		$file = $addonDir.'/'.$config['addons'][$addon]['editable_text'];
		if( !file_exists($file) ){
			return false;
		}

		$texts = array();
		include($file);

		if( empty($texts) ){
			return false;
		}

		return $texts;
	}

}