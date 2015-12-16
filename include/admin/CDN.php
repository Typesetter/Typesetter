<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');



class CDN extends Configuration{

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



	}

}
