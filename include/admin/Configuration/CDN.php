<?php

namespace gp\admin\Configuration;

defined('is_running') or die('Not an entry point...');


class CDN extends \gp\admin\Configuration{

	public function __construct(){
		global $langmessage;

		$langmessage['cdn_jquery']						= 'jQuery';
		$langmessage['cdn_ui-core']						= 'jQuery UI';
		$langmessage['cdn_ui-theme']					= 'jQuery UI CSS';
		$langmessage['cdn_fontawesome']					= 'Font Awesome';

		$this->variables = array(
						'CDN'					=> false,
						'cdn_jquery'			=> null,
						'cdn_ui-core'			=> null,
						'cdn_ui-theme'			=> null,
						'cdn_fontawesome'		=> null,
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
	 * Get possible configuration values
	 *
	 */
	protected function getPossible(){
		global $dataDir,$langmessage;

		$possible = $this->variables;


		//CDN
		foreach(\gp\tool\Combine::$scripts as $key => $script_info){
			if( !isset($script_info['cdn']) ){
				continue;
			}

			$config_key              = 'cdn_'.$key;

			if( !array_key_exists($config_key, $possible) ){
				continue;
			}

			$opts                     = array_keys($script_info['cdn']);
			$possible[$config_key]    = array_combine($opts, $opts);
			array_unshift($possible[$config_key],$langmessage['None']);
		}

		gpSettingsOverride('configuration',$possible);

		return $possible;
	}

}
