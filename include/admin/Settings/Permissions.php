<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');

class Permissions extends Users{

	protected $cmds				= [];
	protected $cmds_post		= ['SaveFilePermissions'=>''];


	public function __construct($args){
		parent::__construct($args);
	}



	/**
	 * Display the permission options for a file
	 *
	 */
	public function DefaultDisplay(){
		global $langmessage;

		$indexes 		= $this->RequestedIndexes();
		if( !$indexes ){
			return;
		}

		$count			= count($indexes);
		$first_index	= $indexes[0];


		echo '<div class="inline_box">';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Permissions').'" method="post">';
		echo '<input type="hidden" name="cmd" value="SaveFilePermissions">';
		echo '<input type="hidden" name="index" value="'.htmlspecialchars($_REQUEST['index']).'">';


		//heading
		echo '<h2>'.\gp\tool::Link('Admin/Users',$langmessage['user_permissions']).' &#187; <i>';
		if( $count > 1 ){
			echo sprintf($langmessage['%s Pages'],$count);
		}else{
			echo strip_tags(\gp\tool::GetLabelIndex($indexes[0]));
		}
		echo '</i></h2>';


		//list of files
		if( $count > 1 ){
			$labels = array();
			foreach( $indexes as $index ){
				$labels[] = strip_tags(\gp\tool::GetLabelIndex($index));
			}
			echo '<p>';
			echo implode(', ',$labels);
			echo '</p>';
		}


		//list of users
		echo '<div class="all_checkboxes">';
		foreach($this->users as $username => $userinfo){
			$attr = '';
			if( $userinfo['editing'] == 'all' ){
				$attr = ' checked="checked"';
			}elseif( strpos($userinfo['editing'],','.$first_index.',') !== false ){
				$attr = ' checked="checked"';
			}
			echo '<label class="all_checkbox">';
			echo '<input type="checkbox" name="users['.htmlspecialchars($username).']" value="'.htmlspecialchars($username).'" '.$attr.'/>';
			echo '<span>'.$username.'</span>';
			echo '</label> ';
		}
		echo '</div>';

		echo '<p>';
		echo '<input type="submit" name="aaa" value="'.$langmessage['save'].'" class="gpabox gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}


	/**
	 * Save the permissions for a specific file
	 *
	 */
	public function SaveFilePermissions(){
		global $langmessage, $gp_index, $gpAdmin;

		$indexes 		= $this->RequestedIndexes();
		if( !$indexes ){
			return;
		}


		foreach($this->users as $username => $userinfo){


			// get array of editing indexes for the current user
			$editing		= explode(',',$userinfo['editing']);

			if( $userinfo['editing'] == 'all'){
				$editing		= array_values($gp_index);
			}

			$editing			= array_intersect($gp_index,$editing);
			$editing_before		= $editing;


			// add page index to user
			if( isset($_POST['users'][$username]) ){
				$editing	= array_merge($editing,$indexes);

			// remove page index from user
			}else{
				$editing	= array_diff($editing,$indexes);

			}

			$editing = array_intersect($gp_index,$editing);

			// don't save if there haven't been any changes
			// keeps editing = all from being changed to editing = [list of all indexes]
			if( $editing_before === $editing ){
				continue;
			}


			$editing_str = '';
			if( count($editing) ){
				$editing_str = ','.implode(',',$editing).',';
			}

			$this->users[$username]['editing'] = $editing_str;
			$this->UserFileDetails($username);
		}

		return $this->SaveUserFile(false);
	}

}
