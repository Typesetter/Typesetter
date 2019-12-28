<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');

class Permissions extends Users{

	protected $cmds				= ['save_file_permissions'=>''];

	public function __construct($args){
		parent::__construct($args);
	}



	/**
	 * Display the permission options for a file
	 *
	 */
	public function DefaultDisplay(){
		global $gp_titles, $langmessage;

		$indexes 		= $this->RequestedIndexes();
		if( !$indexes ){
			return;
		}

		$count			= count($indexes);
		$first_index	= $indexes[0];


		echo '<div class="inline_box">';
		echo '<form action="'.\gp\tool::GetUrl('Admin/Permissions').'" method="post">';
		echo '<input type="hidden" name="cmd" value="save_file_permissions">';
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
			if( $userinfo['editing'] == 'all'){
				$attr = ' checked="checked" disabled="disabled"';
			}elseif(strpos($userinfo['editing'],','.$first_index.',') !== false ){
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
		global $gp_titles, $langmessage, $gp_index, $gpAdmin;

		$indexes 		= $this->RequestedIndexes();
		if( !$indexes ){
			return;
		}


		foreach($this->users as $username => $userinfo){

			if( $userinfo['editing'] == 'all'){
				continue;
			}

			$editing = $userinfo['editing'];

			foreach($indexes as $index){

				if( isset($_POST['users'][$username]) ){
					$editing .= $index.',';
				}else{
					$editing = str_replace( ','.$index.',', ',', $editing);
				}
			}

			$editing = explode(',',trim($editing,','));
			$editing = array_intersect($editing,$gp_index);
			if( count($editing) ){
				$editing = ','.implode(',',$editing).',';
			}else{
				$editing = '';
			}

			$this->users[$username]['editing'] = $editing;
			$is_curr_user = ($gpAdmin['username'] == $username);
			$this->UserFileDetails($username,$is_curr_user);
		}

		return $this->SaveUserFile(false);
	}

}
