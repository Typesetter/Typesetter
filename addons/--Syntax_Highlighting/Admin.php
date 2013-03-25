<?php
defined('is_running') or die('Not an entry point...');


class HighlighterSettings{

	var $themes = array();
	var $config = array();

	function HighlighterSettings(){

		$this->config = gpPlugin::GetConfig();
		$this->config += array('theme'=>'default');

		$this->themes = array(
			'default'    => 'Default',
			'django'     => 'Django',
			'eclipse'    => 'Eclipse',
			'emacs'      => 'Emacs',
			'fadetogrey' => 'Fade to Grey',
			'midnight'   => 'Midnight',
			'rdark'      => 'RDark',
			'none'       => '[None]',
		);

		$this->themes = gpPlugin::Filter('syntaxhighlighter_themes', array( $this->themes ) );

		$cmd = common::GetCommand();
		switch($cmd){
			case 'save';
				$this->Save();
			break;
		}

		$this->ShowForm();
	}

	function Save(){
		global $langmessage;

		$theme =& $_POST['theme'];
		if( isset($this->themes[$theme]) ){
			$this->config['theme'] = $theme;
		}

		if( gpPlugin::SaveConfig($this->config) ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS']);
		}
	}

	function ShowForm(){
		global $langmessage;

		if( count($_POST) ){
			$values = $_POST;
		}else{
			$values = $this->config;
		}

		echo '<h2>Syntax Highlighter Settings</h2>';

		echo '<form method="post" action="'.common::GetUrl('Admin_HighlighterSettings').'">';
		echo '<table class="bordered">';

		echo '<tr><th>'.$langmessage['options'].'</th><th>&nbsp;</th></tr>';

		echo '<tr><td>Color Theme</td><td>';
		echo '<select name="theme" id="syntaxhighlighter-theme">';
		foreach( $this->themes as $theme => $name ){
			$selected = '';
			if( $values['theme'] == $theme ){
				$selected = 'selected="selected"';
			}
			echo '<option value="'.htmlspecialchars($theme).'" '.$selected . '>' . htmlspecialchars( $name ) . "&nbsp;</option>\n";
		}
		echo '</select>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo '</td><td>';
		echo '<input type="hidden" name="cmd" value="save" />';
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" />';
		echo '</td></tr>';

		echo '</table>';
		echo '</form>';
	}


}


