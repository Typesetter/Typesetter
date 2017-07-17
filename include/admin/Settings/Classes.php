<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');

class Classes extends \gp\special\Base{

	var $admin_link;

	function __construct($args){

		parent::__construct($args);

		$this->admin_link = \gp\tool::GetUrl('Admin/Classes');

		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'SaveClasses':
				$this->SaveClasses();
			break;
		}
		$this->ClassesForm();
	}

	/**
	 * Get the current classes
	 *
	 */
	static function GetClasses(){

		$classes		= \gp\tool\Files::Get('_config/classes');
		if( $classes ){
			return $classes;
		}

		//defaults
		return self::Defaults();
	}

	static function Defaults(){
		return array(
			array(
				'names'		=> 'gpRow',
				'desc'		=> CMS_NAME.' Grid Row - for wrapper sections',
			),
			array(
				'names'		=> 'gpCol-1 gpCol-2 gpCol-3 gpCol-4 gpCol-5 gpCol-6 gpCol-7 gpCol-8 gpCol-9 gpCol-10 gpCol-11 gpCol-12',
				'desc'		=> CMS_NAME.' Grid Columns - for content sections',
			),
		);
	}

	static function Bootstrap(){
		return array (
			array (
				'names'		=> 'jumbotron',
				'desc'		=> 'Bootstrap: everything big for calling extra attention to some special content',
			),
			array (
				'names'		=> 'text-left text-center text-right text-justify',
				'desc'		=> 'Bootstrap: text alignment',
			),
			array (
				'names'		=> 'row container',
				'desc'		=> 'Bootstrap Grid: use with Wrapper Sections',
			),
			array (
				'names'		=> 'col-xs-1 col-xs-2 col-xs-3 col-xs-4 col-xs-5 col-xs-6 col-xs-7 col-xs-8 col-xs-9 col-xs-10 col-xs-11 col-xs-12',
				'desc'		=> 'Bootstrap Grid: column width on x-small screens (up to 767px)',
			),
			array (
				'names'		=> 'col-sm-1 col-sm-2 col-sm-3 col-sm-4 col-sm-5 col-sm-6 col-sm-7 col-sm-8 col-sm-9 col-sm-10 col-sm-11 col-sm-12',
				'desc'		=> 'Bootstrap Grid: column width on small screens (768–991px)',
			),
			array (
				'names'		=> 'col-md-1 col-md-2 col-md-3 col-md-4 col-md-5 col-md-6 col-md-7 col-md-8 col-md-9 col-md-10 col-md-11 col-md-12',
				'desc'		=> 'Bootstrap Grid: column width on medium screens (992–1199px)',
			),
			array (
				'names'		=> 'col-lg-1 col-lg-2 col-lg-3 col-lg-4 col-lg-5 col-lg-6 col-lg-7 col-lg-8 col-lg-9 col-lg-10 col-lg-11 col-lg-12',
				'desc'		=> 'Bootstrap Grid: column width on large screens (1200 and wider)',
			),
			array (
				'names'		=> 'col-xs-push-1 col-xs-push-2 col-xs-push-3 col-xs-push-4 col-xs-push-5 col-xs-push-6 col-xs-push-7 col-xs-push-8 col-xs-push-9 col-xs-push-10 col-xs-push-11 col-xs-push-12',
				'desc'		=> 'Bootstrap Grid: push colum to the right on on x-small screens (up to 767px)',
			),
			array (
				'names'		=> 'col-sm-push-1 col-sm-push-2 col-sm-push-3 col-sm-push-4 col-sm-push-5 col-sm-push-6 col-sm-push-7 col-sm-push-8 col-sm-push-9 col-sm-push-10 col-sm-push-11 col-sm-push-12',
				'desc'		=> 'Bootstrap Grid: push colum to the right on small screens (768–991px)',
			),
			array (
				'names'		=> 'col-md-push-1 col-md-push-2 col-md-push-3 col-md-push-4 col-md-push-5 col-md-push-6 col-md-push-7 col-md-push-8 col-md-push-9 col-md-push-10 col-md-push-11 col-md-push-12',
				'desc'		=> 'Bootstrap Grid: push colum to the right on medium screens (992–1199px)',
			),
			array (
				'names'		=> 'col-lg-push-1 col-lg-push-2 col-lg-push-3 col-lg-push-4 col-lg-push-5 col-lg-push-6 col-lg-push-7 col-lg-push-8 col-lg-push-9 col-lg-push-10 col-lg-push-11 col-lg-push-12',
				'desc'		=> 'Bootstrap Grid: push colum to the right on large screens (1200 and wider)',
			),
			array (
				'names'		=> 'col-xs-pull-1 col-xs-pull-2 col-xs-pull-3 col-xs-pull-4 col-xs-pull-5 col-xs-pull-6 col-xs-pull-7 col-xs-pull-8 col-xs-pull-9 col-xs-pull-10 col-xs-pull-11 col-xs-pull-12',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on on x-small screens (up to 767px)',
			),
			array (
				'names'		=> 'col-sm-pull-1 col-sm-pull-2 col-sm-pull-3 col-sm-pull-4 col-sm-pull-5 col-sm-pull-6 col-sm-pull-7 col-sm-pull-8 col-sm-pull-9 col-sm-pull-10 col-sm-pull-11 col-sm-pull-12',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on small screens (768–991px)',
			),
			array (
				'names'		=> 'col-md-pull-1 col-md-pull-2 col-md-pull-3 col-md-pull-4 col-md-pull-5 col-md-pull-6 col-md-pull-7 col-md-pull-8 col-md-pull-9 col-md-pull-10 col-md-pull-11 col-md-pull-12',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on medium screens (992–1199px)',
			),
			array (
				'names'		=> 'col-lg-pull-1 col-lg-pull-2 col-lg-pull-3 col-lg-pull-4 col-lg-pull-5 col-lg-pull-6 col-lg-pull-7 col-lg-pull-8 col-lg-pull-9 col-lg-pull-10 col-lg-pull-11 col-lg-pull-12',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on large screens (1200 and wider)',
			),
			array (
				'names'		=> 'col-xs-offset-1 col-xs-offset-2 col-xs-offset-3 col-xs-offset-4 col-xs-offset-5 col-xs-offset-6 col-xs-offset-7 col-xs-offset-8 col-xs-offset-9 col-xs-offset-10 col-xs-offset-11 col-xs-offset-12',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on on x-small screens (up to 767px)',
			),
			array (
				'names'		=> 'col-sm-offset-1 col-sm-offset-2 col-sm-offset-3 col-sm-offset-4 col-sm-offset-5 col-sm-offset-6 col-sm-offset-7 col-sm-offset-8 col-sm-offset-9 col-sm-offset-10 col-sm-offset-11 col-sm-offset-12',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on small screens (768–991px)',
			),
			array (
				'names'		=> 'col-md-offset-1 col-md-offset-2 col-md-offset-3 col-md-offset-4 col-md-offset-5 col-md-offset-6 col-md-offset-7 col-md-offset-8 col-md-offset-9 col-md-offset-10 col-md-offset-11 col-md-offset-12',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on medium screens (992–1199px)',
			),
			array (
				'names'		=> 'col-lg-offset-1 col-lg-offset-2 col-lg-offset-3 col-lg-offset-4 col-lg-offset-5 col-lg-offset-6 col-lg-offset-7 col-lg-offset-8 col-lg-offset-9 col-lg-offset-10 col-lg-offset-11 col-lg-offset-12',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on large screens (1200 and wider)',
			),
		);
	}

	/**
	 * Display form for selecting classes
	 *
	 */
	function ClassesForm(){
		global $dataDir, $langmessage;

		echo '<h2 class="hmargin">' . $langmessage['Manage Classes'] . '</h2>';

		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'LoadDefault':
				$classes = self::Defaults();
			break;
			case 'LoadBootstrap':
				$classes = self::Bootstrap();
			break;
			default:
				$classes = self::GetClasses();
			break;
		}
		$classes[] = array('names'=>'','desc'=>'');


		$this->page->jQueryCode .= '$(".sortable_table").sortable({items : "tr",handle: "td"});';

		// FORM
		echo '<form action="' . $this->admin_link . '" method="post">';
		echo '<table class="bordered full_width sortable_table">';
		echo '<thead><tr><th>className(s)</th><th>Description (optional)</th></tr></thead>';
		echo '<tbody>';

		foreach( $classes as $key => $classArray ){
			echo '<tr><td>';
			echo '<img alt="" src="'.\gp\tool::GetDir('/include/imgs/drag_handle.gif').'" /> &nbsp; ';
			echo '<input size="16" class="gpinput" type="text" name="class_names[]" value="' . $classArray['names'] . '"/>';
			echo '</td><td>';
			echo '<input size="64" class="gpinput" type="text" name="class_desc[]" value="' . $classArray['desc'] . '"/> ';
			echo '<a class="gpbutton rm_table_row" title="Remove Item" data-cmd="rm_table_row"><i class="fa fa-trash"></i></a>';
			echo '</td></tr>';
		}

		echo '<tr><td colspan="3">';
		echo '<a data-cmd="add_table_row">Add Row</a>';
		echo '</td></tr>';
		echo '</tbody>';
		echo '</table>';

		echo '<br/>';

		// SAVE / CANCEL BUTTONS
		echo '<button type="submit" name="cmd" value="SaveClasses" class="gpsubmit">'.$langmessage['save'].'</button>';
		echo '<button type="submit" name="cmd" value="" class="gpcancel">'.$langmessage['cancel'].'</button>';

		echo '<div style="margin-top:2em; border:1px solid #ccc; background:#fafafa; border-radius:3px; padding:12px;">';
		echo 'CSS classNames you set here will be easily selectable in the Section Attributes dialog.';
		echo '<ul>';
		echo '<li>Single classNames (like <em>gpRow</em>) will show as checkboxes</li>';
		echo '<li>Multiple, space separated classNames (like <em>gpCol-1 gpCol-2 gpCol-3 [&hellip;]</em> will show as checkable dropdown list.</li>';
		echo '<li>The list is drag&rsquo;n&rsquo;drop sortable.</li>';
		echo '</ul><hr/>';


		echo '</form>';


		//$tooltip = $isBootswatchTheme ? ":-) Your current default theme is Bootstrap based - cleared for Take Off!" : ":-/ You will have to use a Bootstrap based theme for this preset!";
		echo '<p>';
		echo '<form action="' . $this->admin_link . '" method="get">';
		echo '<button class="gpbutton" name="cmd" value="LoadBootstrap">Load the Bootstrap Preset</button> ';
		echo '<button class="gpbutton" name="cmd" value="LoadDefault">Load the Default Preset</button>';
		echo '</p>';
		echo '</div>';


	}

	/**
	 * Save the posted data
	 *
	 */
	function SaveClasses(){
		global $langmessage;

		$classes = array();
		foreach($_POST['class_names'] as $i => $class_names){

			$class_names = trim($class_names);
			if( empty($class_names) ){
				continue;
			}

			$classes[] = array(
				'names'		=> $class_names,
				'desc' 		=> $_POST['class_desc'][$i],
			);
		}


		if( \gp\tool\Files::SaveData('_config/classes','classes',$classes) ){
			msg($langmessage['SAVED']);
		}else{
			msg($langmessage['OOPS'].' (Not Saved)');
		}
	}


}
