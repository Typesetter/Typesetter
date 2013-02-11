<?php
defined('is_running') or die('Not an entry point...');

global $gp_mailer;
//includeFile('tool/email.php');
includeFile('thirdparty/PHPMailer/class.phpmailer.php');


/**
 * An extension of phpmailer for usage with gpeasy
 *
 * @since 1.7
 *
 */
class gp_phpmailer extends PHPMailer{

	function gp_phpmailer(){
		global $dataDir,$config;

		$this->Reset();
		$this->PluginDir = $dataDir.'/include/thirdparty/PHPMailer/';
		$this->CharSet = 'utf-8';
		$this->ContentType = 'text/html';

		$mail_method = $this->Mail_Method();
		switch($mail_method){

			//smtp & smtpauth
			case 'smtpauth':
				$this->SMTPAuth = true;
				$this->Username = common::ConfigValue('smtp_user','');
				$this->Password = common::ConfigValue('smtp_pass','');
			case 'smtp';
				$this->IsSMTP();
				$this->Host = common::ConfigValue('smtp_hosts','');
			break;

			//sendmail
			case 'sendmail':
				$this->IsSendmail();
				$this->Sendmail = $this->Sendmail_Path();
			break;

			//mail
			default:
				$this->IsMail();
				$this->Mailer = 'mail';
			break;
		}


	}

	// Empty out the values that may be set
	function Reset(){
		$this->ClearAddresses();
		$this->ClearAllRecipients();
		$this->ClearAttachments();
		$this->ClearBCCs();
		$this->ClearCCs();
		$this->ClearCustomHeaders();
		$this->ClearReplyTos();

		$this->From = $this->From_Address();
		$this->FromName = $this->From_Name();
	}


	/**
	 *
	 * @param string|array $to Array or comma-separated list of email addresses to send message.
	 * @param string $subject Email subject
	 * @param string $message Message contents
	 * @return bool Whether the email contents were sent successfully.
	 */
	function SendEmail($to,$subject,$message){
	//function SendEmail($to,$subject,$message,$headers=array()){
		global $config;

		// Set destination addresses
		foreach( (array)$to as $recipient){
			$recipient = $this->SplitNameAddress($recipient);
			$this->AddAddress( $recipient['address'], $recipient['name'] );
		}

		// Set mail's subject and body
		$this->Subject = $subject;
		$this->Body    = $message;


		// Send!
		$result = @$this->Send();

		$this->Reset();

		return $result;
	}


	/**
	 * Clean and prepare variables before calling phpMailer::Send();
	 *
	 */
	function Send(){

		$this->CleanAddresses( 'to' );
		$this->CleanAddresses( 'cc' );
		$this->CleanAddresses( 'bcc' );
		$this->CleanAddresses( 'ReplyTo' );
		$this->Subject = $this->CleanSubject( $this->Subject );
		$this->Body = $this->CleanText( $this->Body );
		$this->AltBody = $this->CleanText( $this->AltBody );

		return parent::Send();
	}




  /**
   * Overrides the default from address for the current email. Similar to phpmailer's AddAddress() method
   * @param string $address
   * @param string $name
   * @return void
   */
	public function SetFrom($address, $name = '', $auto = 1) {
		$this->From = $this->CleanLine( $address );
		$this->FromName = $this->CleanLine( $name );
	}


	function SplitNameAddress($address){

		$address_name = '';
		if ( strpos($address, '<' ) !== false ) {
			$address_name = substr( $address, 0, strpos( $address, '<' ) - 1 );
			$address_name = str_replace( '"', '', $address_name );
			$address_name = trim( $address_name );

			$address = substr( $address, strpos( $address, '<' ) + 1 );
			$address = str_replace( '>', '', $address );
			$address = trim( $address );
		} else {
			$address = trim( $address );
		}

		return array('name'=>$address_name,'address'=>$address);
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
		}elseif( isset($_SERVER['SERVER_NAME']) ){
			$server = $_SERVER['SERVER_NAME'];
		}else{
			$server = 'localhost';
		}

		$pos = strpos($server,':');
		if( $pos > 0 ){
			$port = substr($server,$pos+1);
			if( is_numeric($port) ){
				$server = substr($server,0,$pos);
			}
		}


		if( substr( $server, 0, 4 ) == 'www.' ){
			$server = substr( $server, 4 );
		}
		return 'AutomatedSender@'.$server;
	}

	function From_Name(){
		return common::ConfigValue('from_name','Automated Sender');
	}

	function Mail_Method(){
		return common::ConfigValue('mail_method','mail');
	}

	function Sendmail_Path(){

		//get value set in php.ini, remove arguments
		$default = ini_get('sendmail_path');
		$pos = strpos($default,' ');
		if( $pos !== false ){
			$default = substr($default,0,$pos);
		}
		if( empty($default) ){
			$default = '/usr/sbin/sendmail';
		}
		return common::ConfigValue('sendmail_path',$default);
	}



	/**
	 * Clean all addresses by removing newlines
	 *
	 */
	function CleanAddresses($array){

		$temp =& $this->$array;
		foreach($temp as $i => $name_addr){
			$temp[$i][0] = $this->CleanLine( $temp[$i][0] );
			$temp[$i][1] = $this->CleanLine( $temp[$i][1] );
		}
	}

	/**
	 * Cleans multi-line inputs.
	 * From Joomla
	 * @param   string  $value	Multi-line string to be cleaned.
	 * @return  string  Cleaned multi-line string.
	 */
	function CleanText($value){
		return trim(preg_replace('/(%0A|%0D|\n+|\r+)(content-type:|to:|cc:|bcc:)/i', '', $value));
	}


	/**
	 * Cleans single line inputs.
	 * From Joomla
	 * @param   string  $value  String to be cleaned.
	 * @return  string  Cleaned string.
	 */
	function CleanLine($value){
		return trim(preg_replace('/(%0A|%0D|\n+|\r+)/i', '', $value));
	}


	/**
	 * Cleans any injected headers from the subject string.
	 * From Joomla
	 * @param   string  $subject  email subject string.
	 * @return  string  Cleaned email subject string.
	 */
	function CleanSubject($subject){
		return preg_replace("/((From:|To:|Cc:|Bcc:|Content-type:) ([\S]+))/", "", $subject);
	}


}

// (Re)create it, if it's gone missing
if ( !is_object( $gp_mailer ) || !is_a( $gp_mailer, 'gp_phpmailer' ) ) {
	$gp_mailer = new gp_phpmailer();
}


