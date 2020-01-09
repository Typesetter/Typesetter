<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');

class Base extends \gp\Base{

	protected $page;

	public function __construct($args){
		if( array_key_exists('page',$args) ){
			$this->page	= $args['page'];
		}
	}
}
