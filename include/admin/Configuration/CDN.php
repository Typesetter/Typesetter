<?php

namespace gp\admin\Configuration;

defined('is_running') or die('Not an entry point...');


class CDN extends \gp\admin\Configuration{

	public function __construct(){
		global $langmessage;

		$langmessage['jquery']						= 'jQuery';
		$langmessage['ui-core']						= 'jQuery UI';
		$langmessage['ui-theme']					= 'jQuery UI CSS';
		$langmessage['fontawesome']					= 'Font Awesome';

		$this->variables = array(
						'CDN'					=> false,
						'cdn'
						);



		$cmd = \common::GetCommand();
		switch($cmd){
			case 'save_config':
				$this->SaveConfig();
			break;
		}

		$this->showForm();
	}


	/**
	 * Get possible cdn values
	 *
	 */
	protected function getPossible(){
		global $langmessage;

		$possible			= array();
		$possible['cdn']	= array();

		foreach(\gp\tool\Combine::$scripts as $key => $script_info){

			if( !isset($script_info['cdn']) ){
				continue;
			}

			foreach($script_info['cdn'] as $cdn => $url){
				$possible['cdn'][] = $cdn;
			}
		}
		$possible['cdn']		= array_combine($possible['cdn'],$possible['cdn']);
		$possible['cdn']['']	= $langmessage['None'];

		return $possible;
	}


	/**
	 * Show CDN Options
	 *
	 */
	protected function ShowForm(){
		global $page, $langmessage, $config;

		$possible	= $this->getPossible();

		echo '<form action="'.\common::GetUrl($page->requested).'" method="post">';
		echo '<h2>CDN</h2>';


		echo '<table class="bordered"><tr><td></td>';
		foreach($possible['cdn'] as $cdn_val => $cdn){

			$checked = ( $cdn_val === $config['cdn'] ) ? 'checked' : '';


			echo '<td>';
			echo '<label class="all_checkbox">';
			echo '<input type="radio" name="cdn" value="'.$cdn_val.'" '.$checked.'/>';
			echo '<span>'.$cdn.'</span>';
			echo '</label> ';
			echo '</td>';
		}
		echo '</tr>';


		//display which scripts can be served bythe cdn
		foreach(\gp\tool\Combine::$scripts as $key => $script_info){

			if( !isset($script_info['cdn']) ){
				continue;
			}

			$config_key					= 'cdn_'.$key;

			echo '<tr><td>';
			echo $langmessage[$key];
			echo '</td>';

			foreach($possible['cdn'] as $cdn){
				echo '<td class="text-center">';
				if( isset($script_info['cdn'][$cdn]) ){
					echo '<i class="fa fa-check"></i>';
				}
				echo '</td>';
			}

			echo '</tr>';
		}


		echo '</table>';
		$this->SaveButtons();
		echo '</form>';
	}

}
