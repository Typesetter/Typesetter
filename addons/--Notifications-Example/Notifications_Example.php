<?php 
/**
 * Notifications Example plugin
 * demonstrates the use of the new plugin filter hook 'Notifications'.
 * 
 * 
 */

defined('is_running') or die('Not an entry point...');

class Notifications_Example{

	static function Notifications($notifications){
		global $langmessage;

		$notifications['example'] = array(	// unique identifier string. using 'drafts', 'private_pages' or 'updates' would overwrite possible existing notes

			'title'			=> 'Plugin Example', 	// required: will automatically be checked against $langmessage
			'badge_bg' 		=> '#c594e8',			// optional: badge background color
			'badge_color' 	=> '#000',				// optional: badge text color
			'priority'		=> 11, 					// optional: an integer > 10 will overule working drafts notifications

			'items'			=> array(		// required
				array(
					'label'		=> 'Check back home!',
					'action'	=> '<a target="_blank" href="' . CMS_DOMAIN . '">' . CMS_READABLE_DOMAIN . '</a>',
				),

				array(
					'label'		=> 'GitHub',
					'action'	=> '<a target="_blank" href="https://github.com/Typesetter/Typesetter/issues">View issues</a>',
				),

				array(
					'label'		=> 'This Plugin',
					'action'	=> \gp\tool::Link('Admin/Addons', $langmessage['uninstall'], 'cmd=uninstall&addon=Notifications-Example', array('data-cmd'=>'gpabox', 'title'=>$langmessage['uninstall'])),
				),
			),




		);

		return $notifications;
	}

}
