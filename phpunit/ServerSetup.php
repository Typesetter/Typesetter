<?php

if( !class_exists('\PHPUnit_Framework_BaseTestListener') ){
	if( class_exists('\PHPUnit\Framework\TestListener') ){
    	class_alias('\PHPUnit\Framework\TestListener', '\PHPUnit_Framework_BaseTestListener');
	}else{
		throw new \Exception('\PHPUnit\Framework\TestListener class doesnt exist');
	}
}


class ServerSetup extends PHPUnit_Framework_BaseTestListener{

    public function startTestSuite($suite){
		echo pre('start test suite');
    }

    public function endTestSuite($suite){
		echo pre('end test suite');
    }
}
