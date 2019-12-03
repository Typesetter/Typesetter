<?php

namespace gp\admin{

	defined('is_running') or die('Not an entry point...');

	class Notifications{

		public static $notifications	= array();

		public static function ShowNotifications(){
			global $langmessage;

			\gp\Admin\Tools::CheckNotifications();

			$notifications = \gp\Admin\Tools::$notifications;

			$filter	= !empty($_REQUEST['type']) ? rawurldecode($_REQUEST['type']) : false;

			// debug('$notifications = ' . pre($notifications));

			echo '<div class="inline_box">';
			// echo '<h3>' . $langmessage['Notifications']; . '</h3>';

			foreach( $notifications as $type => $notification ){
				if( $filter && $type != $filter){
					continue;
				}
				$title = isset($langmessage[$notification['title']]) ?
					$langmessage[$notification['title']] :
					htmlspecialchars($notification['title']);
				echo '<h3>' . $title . '</h3>'; 
				echo '<table class="bordered full_width">';
				echo '<tbody>';
				echo '<tr><th>' . $langmessage['Item'] . '</th><th>' . $langmessage['options'] . '</th></tr>';
				foreach( $notification['items'] as $item ){
					echo '<tr>';
					echo 	'<td>' . $item['label']  . '</td>';
					echo 	'<td>' . $item['action'] . '</td>';
					echo '</tr>';
				}
				echo '</table>';
			}

			echo '<p><button class="admin_box_close gpcancel">' . $langmessage['Close'] . '</button></p>';

			echo '</div>';
		}

	}

}
