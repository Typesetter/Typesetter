<?php

/**
 * Test the \gp\tool\Editing\HTML
 *
 */
class phpunit_HTML extends gptest_bootstrap{

	private $dir;

	function setUp(){
		$this->dir = __DIR__ . '/HtmlFixtures';
	}

	/**
	 * Loop through all the files in the /HtmlFixtures directory
	 * Test each *.from.html file with *.to.html
	 *
	 */
	function testHTML(){

		$files	= scandir($this->dir);

		foreach($files as $file){

			if( strpos($file,'.from.html') === false ){
				continue;
			}

			$parts		= explode('.',$file);
			$name		= array_shift($parts);
			$this->CheckHtml($name);

		}
	}


	/**
	 * Compare the results of parsing a *.from.html file with the contents of a *.to.html file
	 *
	 */
	function CheckHtml($name){

		$path_from	= $this->dir.'/'.$name.'.from.html';
		$path_to	= $this->dir.'/'.$name.'.to.html';

		$from_html	= file_get_contents($path_from);
		$to_html	= file_get_contents($path_to);

		$gp_html_output = new \gp\tool\Editing\HTML($from_html);

		self::AssertEquals( $to_html, $gp_html_output->result );
	}



}