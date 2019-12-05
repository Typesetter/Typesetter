<?php


class phpunit_Languages extends gptest_bootstrap{

	function testKeys(){
		global $langmessage, $languages;

		//get en language
		\gp\tool::GetLangFile('main.inc','en');
		$keys_en = array_keys($langmessage);

		//compare keys in other languages
		foreach($languages as $code => $lang){
			$langmessage = array();
			\gp\tool::GetLangFile('main.inc',$code);
			$keys = array_keys($langmessage);

			self::AssertEquals( $keys_en, $keys, 'Keys in language file don\'t match for '.$lang.' ('.$code.')');
		}


	}

}
