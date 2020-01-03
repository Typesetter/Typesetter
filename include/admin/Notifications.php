<?php

namespace gp\admin{

	defined('is_running') or die('Not an entry point...');

	class Notifications{

		public 			$notifications	= [];
		public 			$filters		= [];
		private 		$debug			= false;
		private static	$singleton;


		public function __construct(){
			global $gpAdmin;

			self::$singleton = $this;

			// get all notifications
			$this->CheckNotifications();
			$this->ApplyFilters();


			// Get active filters from the admin sessio
			if( !empty($gpAdmin['notification_filters']) ){
				$this->filters = $gpAdmin['notification_filters'];

			}elseif( isset($gpAdmin['notifications']['filters']) ){
				$this->filters = $gpAdmin['notifications']['filters'];
				unset($gpAdmin['notifications']);
			}

		}


		public static function GetSingleton(){
			if( !self::$singleton ){
				new self();
			}
		}


		/**
		 * Outputs a list of notifications
		 * To be rendered in a gpabox
		 * The list output can be filtered by optional $_REQUEST['type'] value
		 *
		 */
		public function ListNotifications(){
			global $langmessage;

			$this->FilterUserDefined();

			$this->debug('$notifications = ' . pre($this->notifications));

			echo '<div class="inline_box show-notifications-box">';



			$filter_list_by = '';
			if( isset($_REQUEST['type']) ){
				$filter_list_by = rawurldecode($_REQUEST['type']);
				$this->Tabs($filter_list_by);
			}


			foreach( $this->notifications as $type => $notification ){


				if( empty($filter_list_by) ){
					$title = $this->GetTitle($notification['title']);
					echo '<h3>' . $title . '</h3>';
				}elseif( $type != $filter_list_by ){
					continue;
				}


				echo '<table class="bordered full_width">';
				echo '<tbody>';
				echo '<tr>';
				echo '<th>' . $langmessage['Item'] . '</th>';
				echo '<th>' . $langmessage['options'] . '</th>';
				echo '<th style="text-align:right;">' . $langmessage['Visibility'] . '</th>';
				echo '</tr>';

				foreach( $notification['items'] as $id => $item ){

					$tr_class	= '';
					$link_icon	= '<i class="fa fa-bell"></i>';
					$link_title	= $langmessage['Hide'];
					if( $item['priority'] < 0 ){
						$tr_class	= ' class="notification-item-muted"';
						$link_icon	= '<i class="fa fa-bell-slash"></i>';
						$link_title	= $langmessage['Show'];
					}

					echo '<tr' . $tr_class . '>';
					echo 	'<td>' . $item['label']  . '</td>';
					echo 	'<td>' . $item['action'] . '</td>';

					echo 	'<td style="text-align:right;">';

					echo 	\gp\tool::Link(
								'Admin/Notifications/Manage',
								$link_icon,
								'cmd=toggle_priority'
									. '&id=' . rawurlencode($id)
									. '&type=' . rawurlencode($filter_list_by),
								array(
									'title'		=> $link_title,
									'class'		=> 'toggle-notification',
									'data-cmd'	=> 'gpabox',
								)
							);

					echo	'</td>';

					echo '</tr>';
				}
				echo '</table>';
			}

			echo '<p>';
			echo '<button style="float:right;margin-right:0;" class="admin_box_close gpcancel">';
			echo $langmessage['Close'];
			echo '</button>';
			echo '</p>';

			echo '</div>';
		}

		/**
		 * Tabs
		 *
		 */
		public function Tabs($filter_list_by){
			echo '<div class="layout_links">';
			foreach( $this->notifications as $type => $notification ){

				$class = '';

				if( $filter_list_by && $type == $filter_list_by ){
					$class = 'selected';
				}


				echo \gp\tool::Link(
					'Admin/Notifications',
					$this->GetTitle($notification['title']) . ' ('.$notification['count'].')',
					'cmd=ShowNotifications&type=' . rawurlencode($type),
					array(
						'title'		=> $this->GetTitle($notification['title']),
						'class'		=> $class,
						'style'		=> 'margin-right:0.5em;',
						'data-cmd'	=> 'gpabox',
					)
				);


			}
			echo '</div>';
		}


		/**
		 * Notification Title
		 */
		public function GetTitle($title){
			global $langmessage;

			if( isset($langmessage[$title]) ){
				return $langmessage[$title];
			}
			return htmlspecialchars($title);
		}

		/**
		 * Manage Notifications
		 * Set display filters and priority for notification items by $_REQUEST
		 *
		 */
		public function ManageNotifications(){
			global $gpAdmin;

			$cmd = \gp\tool::GetCommand();

			switch( $cmd ){
				case 'toggle_priority':
					if( !empty($_REQUEST['id']) ){
						$this->SetFilter($_REQUEST['id'], 'toggle_priority');
					}
					break;

				case 'set_priority':
					if( !empty($_REQUEST['id']) &&
						isset($_REQUEST['new_priority']) &&
						is_numeric($_REQUEST['new_priority'])
						){
						$this->SetFilter($_REQUEST['id'], 'set_priority', $_REQUEST['new_priority']);
					}
					break;
			}


			$gpAdmin['notification_filters'] = array_filter($this->filters); // Save filters to the admin session

			$this->ListNotifications();
			self::UpdateNotifications();
		}


		/**
		 * Set a single filter
		 * @param string $id of notification
		 * @param string $do filter action
		 * @param string $val filter value
		 *
		 */
		public function SetFilter($id, $do, $val=false){

			// check if id exists in notifications
			$id_exists = false;
			foreach( $this->notifications as $type => $notification ){
				if( array_key_exists($id,$notification['items']) ){
					$id_exists = true;
					break;
				}
			}

			if( !$id_exists ){
				// notification id no longer exists, purge possible stray filter
				if( isset($this->filters[$id]) ){
					unset($this->filters[$id]);
				}
				return;
			}

			switch( $do ){

				case 'toggle_priority':
					if( isset($this->filters[$id]['priority']) ){
						unset($this->filters[$id]['priority']);
					}else{
						$this->filters[$id]['priority'] = -1;
					}
					break;

				case 'set_priority':
					$this->filters[$id]['priority'] = (int)$val;
					return;
			}

			$this->debug(
					'Notifications SetFilter Error: unknown command "'
					. htmlspecialchars($do) . '"'
				);

		}



		/**
		 * Apply filters to the notifications array
		 * Remove inapropriate items for users lacking permissions to deal with them
		 * Apply user defined (display) filters
		 *
		 */
		public function ApplyFilters(){
			global $gpAdmin;


			// Remove items lacking user permissions and therefore cannot be dealt with anyway

			// debug / development
			if( $gpAdmin['granted'] != 'all' || $gpAdmin['editing'] != 'all' ){
				$this->FilterType('Development','superuser');
			}

			// extra content draft
			if( !\gp\admin\Tools::HasPermission('Admin/Extra') ){
				$this->FilterType('Working Drafts','extra');
			}

			// theme update
			if( !\gp\admin\Tools::HasPermission('Admin_Theme_Content/Remote') ){
				$this->FilterType('updates','theme');
			}

			// addon update
			if( !\gp\admin\Tools::HasPermission('Admin/Addons/Remote') ){
				$this->FilterType('updates','plugin');
			}

			// core update
			if( !\gp\admin\Tools::HasPermission('Admin/Uninstall') ){ // can't find a permission for core updates so I use Uninstall
				$this->FilterType('updates','core');
			}

			// page draft
			$this->FilterCallback('Working Drafts',function($item){
				if( $item['type'] == 'page' && !\gp\admin\Tools::CanEdit($item['title']) ){
					return true;
				}
				return false;
			});


			// private page
			$this->FilterCallback('Private Pages',function($item){
				if( !\gp\admin\Tools::CanEdit($item['title']) ){
					return true;
				}
				return false;
			});

		}


		/**
		 * Apply user defined (display) filters
		 * Remove empty, count items, and get priority
		 *
		 */
		public function FilterUserDefined(){

			foreach( $this->notifications as $notification_type => &$notification ){

				if( empty($this->notifications[$notification_type]['items']) ){
					unset($this->notifications[$notification_type]);
					continue;
				}

				$count				= 0;
				$priority			= 0;
				$total_priority		= 0;
				foreach( $notification['items'] as $id => &$item ){

					$total_priority	= max( $item['priority'], $total_priority );

					if( isset($this->filters[$id]) ){
						$item = $this->filters[$id] + $item;
					}

					if( $item['priority'] > 0 ){
						$priority			= max( $item['priority'], $priority );
						$count++;
					}

				}

				$notification['count']				= $count;
				$notification['priority']			= $priority;
				$notification['total_priority']		= $total_priority;

			}

			// sort by priority
			uasort($this->notifications,function($a,$b){

				if( $b['priority'] !== $a['priority'] ){
					return strnatcmp($b['priority'],$a['priority']);
				}

				if( $b['total_priority'] !== $a['total_priority'] ){
					return strnatcmp($b['total_priority'],$a['total_priority']);
				}

				return strnatcmp($b['title'],$a['title']);
			});
		}



		/**
		 * Filter notifications matching a notification type and item type
		 *
		 */
		public function FilterType( $notification_type, $item_type ){

			if( !isset($this->notifications[$notification_type]) ){
				return;
			}

			foreach( $this->notifications[$notification_type]['items'] as $id => $item ){

				if( $item['type'] !== $item_type ){
					continue;
				}

				unset($this->notifications[$notification_type]['items'][$id]);
			}

		}

		/**
		 * Filter notifications matching a notification type with a callback
		 *
		 */
		public function FilterCallback( $notification_type, $callback ){
			if( !isset($this->notifications[$notification_type]) ){
				return;
			}
			foreach( $this->notifications[$notification_type]['items'] as $id => $item ){
				if( $callback($item) === true ){
					unset($this->notifications[$notification_type]['items'][$id]);
				}
			}
		}


		/**
		* Aggregate all sources of notifications
		* @return array array of notifications
		*
		*/
		public function CheckNotifications(){

			$this->notifications = array();


			$items = $this->GetDrafts();
			$this->Add('Working Drafts', $items, '#329880');


			$items = $this->GetPrivatePages();
			$this->Add('Private Pages', $items, '#ad5f45');


			$items = $this->GetUpdatesNotifications();
			$this->Add('updates', $items, '#3153b7');


			$items = $this->GetDebugNotifications();
			$this->Add('Development', $items, '#ff8c00', '#000');


			\gp\tool\Plugins::Action('Notifications',[$this]);

		}


		/**
		 * Add Notifications
		 *
		 */
		public function Add( $title, $items, $bg, $color = '#fff'){

			if( empty($items) ){
				return;
			}

			if( !isset($this->notifications[$title]) ){
				$this->notifications[$title] = [
					'title'			=> $title,
					'badge_bg'		=> $bg,
					'badge_color'	=> $color,
					'items'			=> [],
				];
			}

			foreach( $items as $item ){

				if( !isset($item['id']) ){
					trigger_error('id not set for notification '.pre($item)); // should we create an id?
					continue;
				}

				$item				+= ['priority'=>0];
				$item['priority']	= (int)$item['priority'];
				$id					= hash('crc32b', $item['id']);

				unset($item['id']);

				$this->notifications[$title]['items'][$id]	= $item;
			}


		}


		/**
		* Get Notifications
		* Outputs a Notifications panelgroup
		* @param boolean $in_panel if panelgroup shall be rendered in admin menu
		*
		*/
		public function GetNotifications($in_panel=true){
			global $langmessage;

			$this->FilterUserDefined();

			if( count($this->notifications) < 1 ){
				return;
			}


			$total_count			= 0;
			$main_badge_style		= '';
			$expand_class			= ''; // expand_child_click
			$badge_format			= ' <span class="dashboard-badge">(%2$d)</b>';
			$panel_class			= '';
			$default_style			= ['badge_bg'=>'transparent','color'=>'#fff'];

			if( $in_panel ){
				$badge_format		= ' <b class="admin-panel-badge" style="%1$s">%2$d</b>';
				$expand_class		= 'expand_child';
				$panel_class		= 'admin-panel-notifications';
			}



			ob_start();
			foreach($this->notifications as $type => $notification ){

				if( empty($notification['items']) ){
					$this->debug('notification => items subarray mising or empty');
					continue;
				}


				$total_count		+= $notification['count'];
				$title				= $this->GetTitle($notification['title']);
				$badge_html			= '';
				$badge_style		= '';
				$notification		+= $default_style;


				if( $notification['count'] > 0 ){
					$badge_style		= 'background-color:'.$notification['badge_bg'].';color:'.$notification['badge_color'].';';
					$badge_html			= sprintf($badge_format, $badge_style, $notification['count']);
				}


				echo '<li class="' . $expand_class . '">';
				echo \gp\tool::Link(
						'Admin/Notifications',
						$title . $badge_html,
						'cmd=ShowNotifications&type=' . rawurlencode($type),
						array(
							'title'		=> $notification['count'] . ' ' . $title,
							'class'		=> 'admin-panel-notification', // . '-' . rawurlencode($type)',
							'data-cmd'	=> 'gpabox',
						)
					);
				echo '</li>';

				if( empty($main_badge_style) ){
					$main_badge_style	= $badge_style;
				}
			}

			$links = ob_get_clean();

			$panel_label	= $langmessage['Notifications'];

			$badge_html		= $total_count > 0 ?
								'<b class="admin-panel-badge" style="' . $main_badge_style . '">' . $total_count . '</b>' :
								'';

			\gp\Admin\Tools::_AdminPanelLinks(
				$in_panel,
				$links,
				$panel_label,
				'fa fa-bell',
				'notifications',
				$panel_class,	// new param 'class'
				$badge_html		// new param 'badge'
			);

		}


		/**
		* Get a Notification array for debugging / development relevant information
		* @return array single notification containing items
		*
		*/
		public function GetDebugNotifications(){
			global $langmessage;

			$debug_note = array();

			if( ini_get('display_errors') ){
				$label = '<strong>ini_set(display_errors,' . htmlspecialchars(ini_get('display_errors')) . ')</em></strong>';
				$debug_note[] = array(
					'type'		=> 'any_user',
					'label'		=> $label,

					// Adding the server name to the hashed value makes sure the item id will change when moving the site (e.g. when going live)
					// Thus possible set hide filters will invalidate and the warning will show up again.
					'id'		=> $label . \gp\tool::ServerName(),

					'priority'	=> 500, // that's a high priority
					'action'	=> 'edit gpconfig.php or notify administrator! <br/>This should only be enabled in exceptional cases.',
				);
			}

			if( defined('gpdebug') && \gpdebug ){
				$label = 'gpdebug is enabled';
				$debug_note[] = array(
					'type'		=> 'superuser',
					'label'		=> $label,
					'id'		=> $label . \gp\tool::ServerName(),
					'priority'	=> 75,
					'action'	=> 'edit gpconfig.php',
				);
			}

			if( defined('create_css_sourcemaps') && \create_css_sourcemaps ){
				$label = 'create_css_sourcemaps is enabled';
				$debug_note[] = array(
					'type'		=> 'superuser',
					'label'		=> $label,
					'id'		=> $label . \gp\tool::ServerName(),
					'priority'	=> 75,
					'action'	=> 'edit gpconfig.php',
				);
			}

			return $debug_note;
		}



		/**
		* Convert $new_versions to notification array
		* @return array single notification containing items
		*
		*/
		public function GetUpdatesNotifications(){
			global $langmessage;

			$updates = array();

			if( gp_remote_update && isset(\gp\Admin\Tools::$new_versions['core']) ){
				$label = \CMS_NAME . ' ' . \gp\Admin\Tools::$new_versions['core'];
				$updates[] = array(
					'type'		=> 'cms_core',
					'label'		=> $label,
					'id'		=> $label,
					'priority'	=> 60,
					'action'	=> '<a href="' . \gp\tool::GetDir('/include/install/update.php') . '">'
										. $langmessage['upgrade'] . '</a>',
				);
			}

			foreach(\gp\Admin\Tools::$new_versions as $addon_id => $new_addon_info){

				if( !is_numeric($addon_id) ){
					continue;
				}

				$label		= $new_addon_info['name'] . ':  ' . $new_addon_info['version'];
				$url		= \gp\admin\Tools::RemoteUrl( $new_addon_info['type'] );

				if( $url === false ){
					continue;
				}
				$updates[] = array(
					'type'		=> $new_addon_info['type'],
					'label'		=> $label,
					'id'		=> $label,
					'priority'	=> 60,
					'action'	=> '<a href="' . $url . '/' . $addon_id . '" data-cmd="remote">'
										. $langmessage['upgrade'] . '</a>',
				);

			}

			return $updates;
		}



		/**
		* Update the Notifications panelgroup in Admin Menu via AJAX
		*
		*/
		public static function UpdateNotifications(){
			global $page;


			if( !\gp\admin\Tools::HasPermission('Admin/Notifications') ){
				return;
			}


			self::GetSingleton();

			ob_start();
			self::$singleton->GetNotifications();
			$panelgroup = ob_get_clean();


			$page->ajaxReplace[] = array('replace', '.admin-panel-notifications', $panelgroup);

		}


		/**
		* Get information about working drafts
		* @returns {array} of current working drafts
		*
		*/
		public function GetDrafts(){
			global $dataDir, $gp_index, $gp_titles, $langmessage;

			$draft_types = array(
				'page'	=> $dataDir . '/data/_pages',
				'extra'	=> $dataDir . '/data/_extra',
			);

			$drafts = array();

			foreach( $draft_types as $type => $dir ){

				$folders	= \gp\tool\Files::readDir($dir,1);

				foreach( $folders as $folder ){

					$draft_path = $dir . '/' . $folder . '/draft.php';
					if( !\gp\tool\Files::Exists($draft_path) ){
						continue;
					}

					$draft = array(
						'type'		=> $type,
						'id'		=> $folder,
						'priority'	=> 100,
					);

					switch( $type ){
						case  'extra':
							$draft['label']		= str_replace('_', ' ', $folder) . ' (' . $langmessage['theme_content'] . ')';
							$draft['action']	= \gp\tool::Link(
								'Admin/Extra',
								$langmessage['theme_content'],
								'',
								array(
									'class' => 'getdrafts-extra-content-link',
									'title' => $langmessage['theme_content'],
								)
							);
							$draft['preview_link']		= \gp\tool::Link(
								'Admin/Extra',
								$langmessage['preview'],
								'cmd=PreviewText&file=' . $folder,
								array(
									'class' => 'getdrafts-extra-preview',
									'title' => $langmessage['preview'],
								)
							);
							$draft['publish_link']	= \gp\tool::Link(
								'Admin/Extra',
								$langmessage['Publish Draft'],
								'cmd=PublishDraft&file=' . $folder,
								array(
									'data-cmd'	=> 'gpajax',
									'class'		=> 'getdrafts-extra-publish',
									'title'		=> $langmessage['Publish Draft'],
								)
							);
							$draft['folder']	= $folder;
							break;

						case  'page':
							$draft['index'] 	= substr($folder, strpos($folder, "_") + 1);
							$draft['title'] 	= \gp\tool::IndexToTitle($draft['index']);
							$draft['label'] 	= \gp\tool::GetLabel($draft['title']) . ' (' . $langmessage['Page'] . ')';
							$draft['action']	= \gp\tool::Link(
								$draft['title'],
								$langmessage['view/edit_page'], //$draft['label'],
								'',
								array(
									'class' => 'getdrafts-page-link',
									'title' => $langmessage['view/edit_page'],
								)
							);
							$draft['publish_link']	= \gp\tool::Link(
								$draft['title'],
								$langmessage['Publish Draft'],
								'cmd=PublishDraft',
								array(
									'data-cmd'	=> 'creq',
									'class'		=> 'getdrafts-page-publish',
									'title'		=> $langmessage['Publish Draft'],
								)
							);
							break;
					}

					$drafts[] = $draft;

				}

			}

			return $drafts;
		}


		/**
		* Get information of all private (invisible) pages
		* @returns {array} of current private pages
		*
		*/
		public function GetPrivatePages(){
			global $gp_titles, $langmessage, $page;

			$private_pages = array();
			foreach( $gp_titles as $index => $title ){

				if( !isset($title['vis']) || $title['vis'] !== 'private' ){
					continue;
				}

				$private_page = array(
					'index'		=> $index,
					'title'		=> \gp\tool::IndexToTitle($index),
					'id'		=> 'private_page' . $index,
					'priority'	=> 40,
					'label'		=> \gp\tool::GetLabelIndex($index),
				);

				// increase priority by 100 when viewing the current page
				if( isset($page->gp_index) && $page->gp_index == $index ){
					$private_page['priority'] += 100;
				}

				$private_page['action']	= \gp\tool::Link(
					$private_page['title'],
					$langmessage['view/edit_page'],
					'',
					array(
						'class' => 'getprivate-page-link',
						'title' => $langmessage['view/edit_page'],
					)
				);
				$private_page['make_public_link']	= \gp\tool::Link(
					'Admin/Menu/Ajax',
					$langmessage['Visibility'] . '<i class="fa fa-long-arrow-right"></i> ' . $langmessage['Public'],
					'cmd=ToggleVisibility&index=' . $index,
					array(
						'data-cmd'	=> 'postlink',
						'class'		=> 'getprivate-make-public',
						'title'		=> $langmessage['Publish Draft'],
					)
				);
				$private_pages[] = $private_page;

			}

			return $private_pages;
		}


		public function debug($msg){
			$this->debug && debug($msg);
		}


	}
}
