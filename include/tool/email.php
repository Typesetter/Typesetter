<?php
defined('is_running') or die('Not an entry point...');

/**
 * @deprecated Use email_mailer.php instead
 * used by minishop addon
 */
trigger_error('Deprecated class, please use email_mailer.php instead');
class gp_email{


	function SendEmail($to,$subject,$message,$headers=array(),$from_info=array() ){
		global $config;

		trigger_error('gp_email::SendEmail() should not be used');

		if(!defined('PHP_EOL'))	define('PHP_EOL', strtoupper(substr(PHP_OS,0,3) == 'WIN') ? "\r\n" : "\n");
		$message = str_replace("\n.", "\n..", $message); //for windows, see mail() documentation
		$subject = str_replace(array("\r","\n","\v"),array(" "),$subject);


		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=UTF-8';
		$headers[] = 'Content-Transfer-Encoding: quoted-printable';



		//from header
		if( !empty($from_info['address']) && isset($config['from_use_user']) && $config['from_use_user'] ){
			$name = '';
			if( !empty($from_info['name']) ){
				$name = $from_info['name'];
			}
			$headers[] = 'From: '.$name.' <'.$from_info['address'].'>';

		}else{

			$sendmail_from = ini_get('sendmail_from');
			if( empty($sendmail_from) ){
				$from = gp_email::From_Address();
				$headers[] = 'From: Automated Sender <'.$from.'>';
			}
		}

		$headers = implode(PHP_EOL,$headers);
		$headers .= PHP_EOL;


		// encode subject
		//=?UTF-8?Q?encoded_text?=
		// work a round: for subject with wordwrap
		// not fixed, no possibility to have one in a single char
		$subject = wordwrap($subject, 25, "\n", FALSE);
		$subject = explode("\n", $subject);
		foreach($subject as $key => $value){
			$subject[$key] = gp_email::imap_8bit($value);
		}
		$subject = implode("\r\n ", $subject);
		$subject = "=?UTF-8?Q?".$subject."?=";


		//encode
		$message = gp_email::imap_8bit($message);


		if( mail($to, $subject, $message, $headers) ){
			return true;
		}
		return false;
	}

	function From_Address(){
		global $config;

		if( !empty($config['from_address']) ){
			return $config['from_address'];
		}

		$from = ini_get('sendmail_from');
		if( !empty($sendmail_from) ){
			return $from;
		}
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}
		if( substr( $server, 0, 4 ) == 'www.' ){
			$server = substr( $server, 4 );
		}
		return 'AutomatedSender@'.$server;
	}

	function From_Name(){
		global $config;

		if( !empty($config['from_name']) ){
			return $config['from_name'];
		}
		return 'Automated Sender';
	}
	function Mail_Method(){
		global $config;
		if( !empty($config['mail_method']) ){
			if( $config['mail_method'] == 'smpt' ){ //bug in 1.7
				return 'smtp';
			}
			return $config['mail_method'];
		}
		return 'mail';
	}

	function imap_8bit(&$text){
		if( function_exists('imap_8bit') ){
			return imap_8bit($text);
		}
		return gp_email::quoted_printable_encode($text);
	}


	function quoted_printable_encode($sText,$bEmulate_imap_8bit=true) {
	  // split text into lines
	  $aLines=explode(chr(13).chr(10),$sText);

	  for ($i=0;$i<count($aLines);$i++) {
		$sLine =& $aLines[$i];
		if (strlen($sLine)===0) continue; // do nothing, if empty

		$sRegExp = '/[^\x09\x20\x21-\x3C\x3E-\x7E]/e';

		// imap_8bit encodes x09 everywhere, not only at lineends,
		// for EBCDIC safeness encode !"#$@[\]^`{|}~,
		// for complete safeness encode every character :)
		if ($bEmulate_imap_8bit)
		  $sRegExp = '/[^\x20\x21-\x3C\x3E-\x7E]/e';

		$sReplmt = 'sprintf( "=%02X", ord ( "$0" ) ) ;';
		$sLine = preg_replace( $sRegExp, $sReplmt, $sLine );

		// encode x09,x20 at lineends
		{
		  $iLength = strlen($sLine);
		  $iLastChar = ord($sLine{$iLength-1});

		  //              !!!!!!!!
		  // imap_8_bit does not encode x20 at the very end of a text,
		  // here is, where I don't agree with imap_8_bit,
		  // please correct me, if I'm wrong,
		  // or comment next line for RFC2045 conformance, if you like
		  if (!($bEmulate_imap_8bit && ($i==count($aLines)-1)))

		  if (($iLastChar==0x09)||($iLastChar==0x20)) {
			$sLine{$iLength-1}='=';
			$sLine .= ($iLastChar==0x09)?'09':'20';
		  }
		}    // imap_8bit encodes x20 before chr(13), too
		// although IMHO not requested by RFC2045, why not do it safer :)
		// and why not encode any x20 around chr(10) or chr(13)
		if ($bEmulate_imap_8bit) {
		  $sLine=str_replace(' =0D','=20=0D',$sLine);
		  //$sLine=str_replace(' =0A','=20=0A',$sLine);
		  //$sLine=str_replace('=0D ','=0D=20',$sLine);
		  //$sLine=str_replace('=0A ','=0A=20',$sLine);
		}

		// finally split into softlines no longer than 76 chars,
		// for even more safeness one could encode x09,x20
		// at the very first character of the line
		// and after soft linebreaks, as well,
		// but this wouldn't be caught by such an easy RegExp
		preg_match_all( '/.{1,73}([^=]{0,2})?/', $sLine, $aMatch );
		$sLine = implode( '=' . chr(13).chr(10), $aMatch[0] ); // add soft crlf's
	  }

	  // join lines into text
	  return implode(chr(13).chr(10),$aLines);
	}
}
