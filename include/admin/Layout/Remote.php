<?php

namespace gp\admin\Layout;

defined('is_running') or die('Not an entry point...');

class Remote extends \gp\admin\Addon\Remote{

	public $config_index			= 'themes';
	protected $scriptUrl			= 'Admin_Theme_Content';
	public $code_folder_name		= '_themes';
	public $path_remote				= 'Admin_Theme_Content/Remote';


}
