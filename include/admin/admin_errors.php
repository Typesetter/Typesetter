<?php
defined('is_running') or die('Not an entry point...');

class admin_errors{

	function __construct(){

		$error_log = ini_get('error_log');

		echo '<h2>Error Log</h2>';

		if( !file_exists($error_log) ){
			if( !empty($error_log) ){
				echo '<p>Error log at "'.$error_log.'" not found</p>';
			}else{
				echo '<p>Error log not found</p>';
			}
			return;
		}

		if( !is_readable($error_log) ){
			echo '<p>Error log not readable</p>';
			return;
		}

		$contents = file_get_contents($error_log);
		$lines = trim($contents);
		$lines = explode("\n",$contents);
		$lines = array_reverse($lines);

		$time = null;
		$displayed = array();
		foreach($lines as $line){
			$line = trim($line);
			if( empty($line) ){
				continue;
			}
			preg_match('#^\[[a-zA-Z0-9:\- ]*\]#',$line,$date);
			if( count($date) ){
				$date = $date[0];
				$line = substr($line,strlen($date));
				$date = trim($date,'[]');
				$new_time = strtotime($date);
				if( $new_time !== $time ){
					if( $time ){
						echo '</pre>';
					}
					echo self::Elapsed( time() - $new_time ).' ago ('.$date.')';
					echo '<pre>';
					$time = $new_time;
					$displayed = array();
				}
			}


			$line_hash = md5($line);
			if( in_array($line_hash,$displayed) ){
				continue;
			}
			echo $line;
			$displayed[] = $line_hash;
			echo "\n";
		}
		echo '</pre>';

	}

	static function Elapsed($difference){
		$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		$lengths = array("60","60","24","7","4.35","12","10");

		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		   $difference /= $lengths[$j];
		}

		$difference = round($difference);

		if($difference != 1) {
		   $periods[$j].= "s";
		}

		return $difference.' '.$periods[$j];
	}

}

