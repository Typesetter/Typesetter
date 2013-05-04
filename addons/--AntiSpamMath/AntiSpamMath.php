<?php
defined('is_running') or die('Not an entry point...');

class AntiSpamMath{
	var $operators = array(1=>'plus',2=>'minus',3=>'divided by',4=>'times');

	function Form($html){
		$operator_key = array_rand($this->operators);
		$operator = $this->operators[$operator_key];

		$asm_1 = rand(1,10);
		$asm_3 = rand(1,10);
		if ($operator_key == 3) $asm_1 = $asm_1 * $asm_3;


		$inputs = array();
		$inputs[] = ' <input type="hidden" name="asm_1" value="'.$asm_1.'" /> ';
		$inputs[] = ' <input type="hidden" name="asm_2" value="'.$operator_key.'" /> ';
		$inputs[] = ' <input type="hidden" name="asm_3" value="'.$asm_3.'" /> ';
		shuffle($inputs);

		ob_start();
		echo implode('',$inputs);

		echo '<span class="anti_spam_math">';
		echo $asm_1;
		echo '  ';
		echo gpOutput::GetAddonText($operator);
		echo '  ';
		echo $asm_3;
		echo '  ';
		echo gpOutput::GetAddonText('equals');
		echo ' <input type="text" name="asm_4" value="" size="4" maxlength="6" /> ';
		echo '</span>';
		$html .= ob_get_clean();

		return $html;
	}

	function Check($passed){
		$message = gpOutput::SelectText('Sorry, your answer to the verification challenge was incorrect. Please try again.');

		if( empty($_POST['asm_1']) || empty($_POST['asm_2']) || empty($_POST['asm_3']) ){
			message($message.' (1)');
			return false;
		}

		$operator_key = $_POST['asm_2'];
		if( !isset($this->operators[$operator_key]) ){
			message($message.' (2)');
			return false;
		}

		switch($operator_key){
			case 1:
				$result = $_POST['asm_1'] + $_POST['asm_3'];
			break;
			case 2:
				$result = $_POST['asm_1'] - $_POST['asm_3'];
			break;
			case 3:
				$result = $_POST['asm_1'] / $_POST['asm_3'];
			break;
			case 4:
				$result = $_POST['asm_1'] * $_POST['asm_3'];
			break;
		}

		$compare = $_POST['asm_4'];
		//message('result: '.$result.' vs submitted: '.$compare);

		if( $compare != $result ){
			message($message.' (3)');
			return false;
		}

		//message('passed');

		return $passed;
	}

}
