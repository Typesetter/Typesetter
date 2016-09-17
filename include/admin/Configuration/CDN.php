<?php

namespace gp\admin\Configuration;

defined('is_running') or die('Not an entry point...');


class CDN extends \gp\admin\Configuration{

	public function __construct($args){

		parent::__construct($args);

		$this->variables = array(
						'CDN'					=> false,
						'cdn'
						);

	}

	public function RunScript(){

		$cmd = \gp\tool::GetCommand();
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

		foreach(\gp\tool\Output\Combine::$scripts as $key => $script_info){

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
		global $config;

		$possible	= $this->getPossible();

		echo '<form action="'.\gp\tool::GetUrl($this->page->requested).'" method="post">';
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
		foreach(\gp\tool\Output\Combine::$scripts as $key => $script_info){

			if( !isset($script_info['cdn']) || !isset($script_info['label']) ){
				continue;
			}

			$code						= '\\gp\\tool::LoadComponents(\''.$key.'\');';

			echo '<tr><td title="'.htmlspecialchars($code).'">';
			echo $script_info['label'];
			echo '</td>';

			foreach($possible['cdn'] as $cdn){
				echo '<td class="text-center">';
				if( isset($script_info['cdn'][$cdn]) ){
					echo '<i class="fa fa-check"></i>';
				}
				echo '</td>';
			}

			echo '</div></td></tr>';
		}


		echo '</table>';
		$this->SaveButtons();
		echo '</form>';
	}

}
