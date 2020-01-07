<?php

namespace gp\admin\Addon;

defined('is_running') or die('Not an entry point...');


class Available extends \gp\admin\Addons{

	function __construct( $args ){
		parent::__construct( $args );
	}


	function DefaultDisplay(){
		global $langmessage;

		$this->ShowHeader();

		echo '<div class="nodisplay" id="gpeasy_addons"></div>';

		if( count($this->avail_addons) == 0 ){
			//echo ' -empty- ';
		}else{
			echo '<table class="bordered full_width">';
			echo '<tr><th>';
			echo $langmessage['name'];
			echo '</th><th>';
			echo $langmessage['version'];
			echo '</th><th>';
			echo $langmessage['options'];
			echo '</th><th>';
			echo $langmessage['description'];
			echo '</th></tr>';

			$avail_addons = $this->avail_addons;

			// sort available addons by name
			uasort($avail_addons, function($a, $b) {
				return strnatcmp($a['Addon_Name'], $b['Addon_Name']);
			});

			$i=0;
			foreach($avail_addons as $folder => $info ){

				if( $info['upgrade_key'] ){
					continue;
				}

				$info += array('About' => '');

				echo '<tr class="' . ($i % 2 ? 'even' : '') . '"><td>';
				echo str_replace(' ', '&nbsp;', $info['Addon_Name']);
				echo '<br/><em class="admin_note">/addons/' . $folder . '</em>';
				echo '</td><td>';
				echo $info['Addon_Version'];
				echo '</td><td>';
				echo \gp\tool::Link(
					'Admin/Addons',
					$langmessage['Install'],
					'cmd=LocalInstall&source=' . $folder,
					array('data-cmd' => 'cnreq')
				);
				echo '</td><td>';
				echo $info['About'];
				if( isset($info['Addon_Unique_ID']) && is_numeric($info['Addon_Unique_ID']) ){
					echo '<br/>';
					echo $this->DetailLink('plugins', $info['Addon_Unique_ID'], 'More Info...');
				}
				echo '</td></tr>';
				$i++;
			}
			echo '</table>';

		}

		$this->InvalidFolders();
		$this->Instructions();

	}


	/**
	 * Show folders in /addons that didn't make it into the available list
	 *
	 */
	public function InvalidFolders(){
		global $langmessage;

		if( empty($this->invalid_folders) ){
			return;
		}

		echo '<br/>';
		echo '<h3>Invalid Addon Folders</h3>';
		echo '<table class="bordered full_width striped">';
		echo '<tr><th>';
		echo $langmessage['name'];
		echo '</th><th>&nbsp;</th></tr>';
		foreach($this->invalid_folders as $folder => $msg){

			if( isset($this->avail_addons[$folder]) ){
				continue; //skip false positives
			}

			echo '<tr><td>';
			echo htmlspecialchars($folder);
			echo '</td><td>';
			echo htmlspecialchars($msg);
			echo '</td></tr>';
		}
		echo '</table>';

	}

}
