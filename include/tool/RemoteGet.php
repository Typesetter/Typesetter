<?php


namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	class RemoteGet{

		public static $redirected;
		public static $maxlength = -1;	// The maximum bytes to read. eg: stream_get_contents($handle, $maxlength)
		public static $debug;



		/* determine if the functions exist for fetching remote files,
		 * test is done in order of preference
		 *
		 * notes from wordpress http.php
		 * The order for the GET/HEAD requests are Streams, HTTP Extension, Fopen,
		 * and finally Fsockopen. fsockopen() is used last, because it has the most
		 * overhead in its implementation. There isn't any real way around it, since
		 * redirects have to be supported, much the same way the other transports
		 * also handle redirects.
		 *
		 * @return mixed string indicates the method, False indicates it's not compatible
		*/
		public static function Test(){

			$methods = array('stream','fopen','fsockopen');
			foreach($methods as $method){
				if( self::Supported($method) ){
					return $method;
				}
			}

			return false;
		}

		/**
		 * Determine if a specific method is supported
		 *
		 */
		public static function Supported($method){

			if( \gp\tool::IniGet('safe_mode') ){
				return false;
			}

			$url_fopen = \gp\tool::IniGet('allow_url_fopen');
			$php5 = version_compare(phpversion(), '5.0', '>=');

			switch($method){
				case 'stream':
				return $url_fopen && $php5;

				case 'fopen':
				return $url_fopen;

				case 'fsockopen':
				return function_exists('fsockopen');
			}
			return false;
		}


		/**
		 * Return response only if successful, otherwise return false
		 *
		 */
		public static function Get_Successful($url,$args=array()){
			$result = self::Get($url,$args);

			if( (int)$result['response']['code'] >= 200 && (int)$result['response']['code'] < 300 ){
				return $result['body'];
			}
			return false;
		}


		/**
		 * Attempt to get the resource at $url
		 * Loop through all potential methods until successful
		 */
		public static function Get($url,$args=array()){

			self::$debug			= array();
			self::$debug['Redir']	= 0;
			self::$redirected		= null;

			return self::_get($url,$args);
		}

		public static function _get($url, $args = array()){

			$url					= rawurldecode($url);
			$methods				= array('stream','fopen','fsockopen');

			foreach($methods as $method){
				if( !self::Supported($method) ){
					continue;
				}

				$result = self::GetMethod($method,$url,$args);
				if( $result === false ){
					return false;
				}

				self::$debug['Method']	= $method;
				self::$debug['Len']		= strlen($result['body']);

				return $result;
			}

			return false;
		}

		public static function GetMethod($method,$url,$args=array()){
			global $langmessage;


			//arguments
			$defaults = array(
				'method'			=> 'GET',
				'timeout'			=> 5,
				'redirection'		=> 5,
				'httpversion'		=> '1.0',
				'user-agent'		=> 'Mozilla/5.0 (Typesetter RemoteGet) ',
				//'user-agent'		=> 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0',

				//could be added
				//'blocking' => true,
				//'headers' => array(),
				//'body' => null,
				//'cookies' => array(),
			);

			$args += $defaults;


			//check url
			$url = str_replace(' ','%20',$url); //spaces in the url can make the request fail
			if( parse_url($url) === false ){
				return false;
			}

			//decide how to get
			switch($method){

	/*
				case 'http_request':
				return self::http_request($url,$args);
	*/

				case 'stream':
				return self::stream_request($url,$args);

				case 'fopen':
				return self::fopen_request($url,$args);

				case 'fsockopen':
				return self::fsockopen_request($url,$args);

				default:
					//message($langmessage['OOPS']);
				return false;

			}

		}


		public static function http_request($url,$r){



		}

		public static function stream_request($url,$r){

			$url		= self::FixScheme($url);
			$arrURL		= parse_url($url);

			//create context
			$arrContext = array();
			$arrContext['http'] = array(
					'method'			=> 'GET',
					'user_agent'		=> 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:36.0) Gecko/20100101 Firefox/36.0', //$r['user-agent'],
					'max_redirects'		=> $r['redirection'],
					'protocol_version'	=> (float) $r['httpversion'],
					'timeout'			=> $r['timeout'],
				);

			if( isset($r['http']) ){
				$arrContext['http'] = $r['http'] + $arrContext['http'];
			}

			if( isset($r['headers']) ){
				$arrContext['http']['header'] = '';
				foreach($r['headers'] as $hk => $hv){
					$arrContext['http']['header'] .= $hk.': '.$hv."\r\n";
				}
				$arrContext['http']['header'] = trim($arrContext['http']['header']);
			}

			$context = stream_context_create($arrContext);

			$handle = @fopen($url, 'r', false, $context);

			if( !$handle ){
				return false;
			}

			self::stream_timeout($handle,$r['timeout']);

			$strResponse = stream_get_contents($handle, self::$maxlength);
			$theHeaders = self::StreamHeaders($handle);
			fclose($handle);

			$processedHeaders = self::processHeaders($theHeaders);

			// If location is found, then assume redirect and redirect to location.
			if( isset($processedHeaders['headers']['location']) ){
				return self::Redirect($processedHeaders,$r,$arrURL);
			}

			$strResponse = self::chunkTransferDecode($strResponse,$processedHeaders);

			return array('headers' => $processedHeaders['headers'], 'body' => $strResponse, 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}


		public static function fopen_request($url,$r){

			$url		= self::FixScheme($url);
			$arrURL		= parse_url($url);
			$handle		= @fopen($url, 'r');

			if ( !$handle ) return false;

			self::stream_timeout($handle,$r['timeout']);

			$strResponse = '';
			while ( ! feof($handle) ){
				$strResponse .= fread($handle, 4096);
			}

			$theHeaders = self::StreamHeaders($handle);
			fclose($handle);

			$processedHeaders = self::processHeaders($theHeaders);

			// If location is found, then assume redirect and redirect to location.
			if( isset($processedHeaders['headers']['location']) ){
				return self::Redirect($processedHeaders,$r,$arrURL);
			}

			$strResponse = self::chunkTransferDecode($strResponse,$processedHeaders);

			return array('headers' => $processedHeaders['headers'], 'body' => $strResponse, 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}


		/**
		 * Make sure the url has an http or https scheme
		 *
		 */
		public static function FixScheme($url){

			$matched = preg_match('#^[a-z]+:#',$url,$match);

			if( empty($match) ){
				return 'http://'.$url;
			}

			$match[0] = strtolower($match[0]);
			if( $match[0] !== 'http:' && $match[0] !== 'https:' ){
				$url = substr($url,strlen($match[0]));
				$url = 'http://'.ltrim($url,'/');
			}

			return $url;
		}


		public static function fsockopen_request($url,$r){

			$arrURL = parse_url($url);
			$fsockopen_host = $arrURL['host'];
			if( !isset($arrURL['port']) ){
				$arrURL['port'] = 80;
			}
			$arrURL += array('path'=>''); //prevent notice

			//fsockopen has issues with 'localhost' with IPv6 with certain versions of PHP, It attempts to connect to ::1,
			// which fails when the server is not setup for it. For compatibility, always connect to the IPv4 address.
			if ( 'localhost' == strtolower($fsockopen_host) )
				$fsockopen_host = '127.0.0.1';

			$iError = null; // Store error number
			$strError = null; // Store error string

			$handle = @fsockopen( $fsockopen_host, $arrURL['port'], $iError, $strError, $r['timeout'] );

			if( $handle === false ){
				return false;
			}
			self::stream_timeout($handle,$r['timeout']);

			$requestPath = $arrURL['path'] . ( isset($arrURL['query']) ? '?' . $arrURL['query'] : '' );
			if ( empty($requestPath) )
				$requestPath .= '/';

			$strHeaders = strtoupper($r['method']) . ' ' . $requestPath . ' HTTP/' . $r['httpversion'] . "\r\n";
			$strHeaders .= 'Host: ' . $arrURL['host'] . "\r\n";

			if ( isset($r['user-agent']) )
				$strHeaders .= 'User-agent: ' . $r['user-agent'] . "\r\n";

			$strHeaders .= "\r\n";

			fwrite($handle, $strHeaders);

			$strResponse = '';
			while ( ! feof($handle) )
				$strResponse .= fread($handle, 4096);

			fclose($handle);

			$process = self::processResponse($strResponse);
			$processedHeaders = self::processHeaders($process['headers']);

			// If location is found, then assume redirect and redirect to location.
			if( isset($processedHeaders['headers']['location']) ){
				return self::Redirect($processedHeaders,$r,$arrURL);
			}

			$strResponse = self::chunkTransferDecode($strResponse,$processedHeaders);

			return array('headers' => $processedHeaders['headers'], 'body' => $process['body'], 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}

		public static function stream_timeout($handle,$time){

			if( !function_exists('stream_set_timeout') ){
				return;
			}

			$timeout = (int) floor( $time );
			$utimeout = $timeout == $time ? 0 : 1000000 * $time % 1000000;
			stream_set_timeout( $handle, $timeout, $utimeout );
		}

		/**
		 * Handle a redirect response
		 *
		 */
		public static function Redirect($headers,$r,$arrURL){
			if( $r['redirection']-- < 0 ){
				trigger_error('Too many redirects');
				return false;
			}

			//check location for releative value
			$location = $headers['headers']['location'];
			if( is_array($location) ){
				$location = array_pop($location);
			}
			if( $location{0} == '/' ){
				$location = $arrURL['scheme'].'://'.$arrURL['host'].$location;
			}

			self::$redirected		= $location;
			self::$debug['Redir']	= 1;

			return self::_get($location, $r);
		}



		/**
		 * Decodes chunk transfer-encoding, based off the HTTP 1.1 specification.
		 *
		 * Based off the HTTP http_encoding_dechunk function. Does not support UTF-8. Does not support
		 * returning footer headers. Shouldn't be too difficult to support it though.
		 *
		 * @todo Add support for footer chunked headers.
		 * @access public
		 * @since 1.7
		 * @static
		 *
		 * @param string $body Body content
		 * @return string Chunked decoded body on success or raw body on failure.
		 */
		public static function chunkTransferDecode($body,&$headers){

			if( empty($body) ){
				return $body;
			}
			if( !isset( $headers['headers']['transfer-encoding'] ) || 'chunked' != $headers['headers']['transfer-encoding'] ){
				return $body;
			}


			$body = str_replace(array("\r\n", "\r"), "\n", $body);
			// The body is not chunked encoding or is malformed.
			if ( ! preg_match( '/^[0-9a-f]+(\s|\n)+/mi', trim($body) ) )
				return $body;

			$parsedBody = '';
			//$parsedHeaders = array(); Unsupported

			while ( true ) {
				$hasChunk = (bool) preg_match( '/^([0-9a-f]+)(\s|\n)+/mi', $body, $match );

				if ( $hasChunk ) {
					if ( empty( $match[1] ) )
						return $body;

					$length = hexdec( $match[1] );
					$chunkLength = strlen( $match[0] );

					$strBody = substr($body, $chunkLength, $length);
					$parsedBody .= $strBody;

					$body = ltrim(str_replace(array($match[0], $strBody), '', $body), "\n");

					if ( "0" == trim($body) )
						return $parsedBody; // Ignore footer headers.
				} else {
					return $body;
				}
			}
		}

		/**
		 * Gets stream headers, return false otherwise
		 *
		 * @access public
		 * @static
		 * @since 1.7
		 *
		 * @param resource $handle stream handle
		 * @return array|false Array with unprocessed string headers.
		 */
		public static function StreamHeaders($handle){

			$debug['stream'] = 1;

			$meta = stream_get_meta_data($handle);
			if( !isset($meta['wrapper_data']) ){
				return $http_response_header; //$http_response_header is a PHP reserved variable which is set in the current-scope when using the HTTP Wrapper
			}

			$theHeaders = $meta['wrapper_data'];
			if( isset($meta['wrapper_data']['headers']) ){
				$theHeaders = $meta['wrapper_data']['headers'];
			}

			return $theHeaders;
		}


		/**
		 * Parses the responses and splits the parts into headers and body.
		 *
		 * @access public
		 * @static
		 * @since 1.7
		 *
		 * @param string $strResponse The full response string
		 * @return array Array with 'headers' and 'body' keys.
		 */
		public static function processResponse($strResponse) {
			list($theHeaders, $theBody) = explode("\r\n\r\n", $strResponse, 2);
			return array('headers' => $theHeaders, 'body' => $theBody);
		}

		/**
		 * Transform header string into an array.
		 *
		 * If an array is given then it is assumed to be raw header data with numeric keys with the
		 * headers as the values. No headers must be passed that were already processed.
		 *
		 * @access public
		 * @static
		 * @since 1.7
		 *
		 * @param string|array $headers
		 * @return array Processed string headers. If duplicate headers are encountered,
		 * 					Then a numbered array is returned as the value of that header-key.
		 */
		public static function processHeaders($headers) {
			// split headers, one per array element
			if ( is_string($headers) ) {
				// tolerate line terminator: CRLF = LF (RFC 2616 19.3)
				$headers = str_replace("\r\n", "\n", $headers);
				// unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>, <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2)
				$headers = preg_replace('/\n[ \t]/', ' ', $headers);
				// create the headers array
				$headers = explode("\n", $headers);
			}

			$response = array('code' => 0, 'message' => '');

			$cookies = array();
			$newheaders = array();
			if( is_array($headers) ){
				foreach ( $headers as $tempheader ) {
					if ( empty($tempheader) )
						continue;

					if ( false === strpos($tempheader, ':') ) {
						list( , $iResponseCode, $strResponseMsg) = explode(' ', $tempheader, 3);
						$response['code'] = $iResponseCode;
						$response['message'] = $strResponseMsg;
						continue;
					}

					list($key, $value) = explode(':', $tempheader, 2);

					if ( !empty( $value ) ) {
						$key = strtolower( $key );
						if ( isset( $newheaders[$key] ) ) {
							$newheaders[$key] = array( $newheaders[$key], trim( $value ) );
						} else {
							$newheaders[$key] = trim( $value );
						}
					}
				}
			}


			self::$debug['Headers'] = count($newheaders);

			return array('response' => $response, 'headers' => $newheaders, 'cookies' => $cookies);
		}


		/**
		 * Output debug info about the most recent request
		 *
		 */
		public static function Debug($lang_key, $debug = array()){

			$debug	= array_merge(self::$debug,$debug);

			return \gp\tool::Debug($lang_key, $debug);
		}


	}
}

namespace {
	class gpRemoteGet extends \gp\tool\RemoteGet{}
}