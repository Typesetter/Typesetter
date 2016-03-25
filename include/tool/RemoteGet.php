<?php


namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	class RemoteGet{

		public static $redirected;
		public static $maxlength = -1;	// The maximum bytes to read. eg: stream_get_contents($handle, $maxlength)
		public static $debug;
		public static $methods	= array('stream','curl','fopen','fsockopen');


		protected $url_array = array();
		protected $body = '';
		protected $headers = '';
		protected $bytes_written_total = 0;



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

			foreach(self::$methods as $method){
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

			switch($method){

				case 'fsockopen':
				return function_exists('fsockopen');

				case 'curl';
				return function_exists('curl_init') && function_exists('curl_exec');

			}

			//stream and fopen
			return \gp\tool::IniGet('allow_url_fopen');
		}


		/**
		 * Return response only if successful, otherwise return false
		 *
		 */
		public static function Get_Successful($url,$args=array()){

			$getter	= new \gp\tool\RemoteGet();
			$result = $getter->Get($url,$args);

			if( (int)$result['response']['code'] >= 200 && (int)$result['response']['code'] < 300 ){
				return $result['body'];
			}

			return false;
		}


		/**
		 * Attempt to get the resource at $url
		 * Loop through all potential methods until successful
		 */
		public function Get($url,$args=array()){

			self::$debug					= array();
			self::$debug['Redir']			= 0;
			self::$debug['FailedMethods']	= '';
			self::$debug['NotSupported']	= '';
			self::$redirected				= null;

			return $this->_get($url,$args);
		}

		protected function _get($url, $args = array()){

			$url				= rawurldecode($url);
			$url				= str_replace(' ','%20',$url); //spaces in the url can make the request fail
			$url				= self::FixScheme($url);
			$this->url_array	= self::ParseUrl($url);

			if( $this->url_array === false ){
				return false;
			}


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


			foreach(self::$methods as $method){

				if( !self::Supported($method) ){
					self::$debug['NotSupported']	.= $method.',';
					continue;
				}

				$result = self::GetMethod($method,$url,$args);
				if( $result === false ){
					self::$debug['FailedMethods'] .= $method.',';
					return false;
				}

				self::$debug['Method']	= $method;
				self::$debug['Len']		= strlen($result['body']);

				return $result;
			}

			return false;
		}


		public function GetMethod($method,$url,$args=array()){
			global $langmessage;

			msg('method: '.$method);

			//decide how to get
			switch($method){

				case 'stream':
				return $this->stream_request($url,$args);

				case 'fopen':
				return $this->fopen_request($url,$args);

				case 'fsockopen':
				return $this->fsockopen_request($url,$args);

				case 'curl':
				return $this->curl_request($url,$args);

				default:
				return false;

			}

		}


		/**
		 * Fetch a url using php's stream_get_contents() function
		 *
		 */
		public function stream_request($url,$r){

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

			$handle = fopen($url, 'r', false, $context);

			if( !$handle ){
				self::$debug['stream']	= 'no handle';
				return false;
			}

			self::stream_timeout($handle,$r['timeout']);

			$strResponse = stream_get_contents($handle, self::$maxlength);
			$theHeaders = self::StreamHeaders($handle);
			fclose($handle);

			$processedHeaders = self::processHeaders($theHeaders);

			// If location is found, then assume redirect and redirect to location.
			if( isset($processedHeaders['headers']['location']) ){
				return $this->Redirect($processedHeaders,$r);
			}

			$strResponse = self::chunkTransferDecode($strResponse,$processedHeaders);

			return array('headers' => $processedHeaders['headers'], 'body' => $strResponse, 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}

		/**
		 * Fetch a url using php's fopen() function
		 *
		 */
		public function fopen_request($url,$r){

			$handle		= fopen($url, 'r');

			if( !$handle ){
				self::$debug['fopen']	= 'no handle';
				return false;
			}

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
				return $this->Redirect($processedHeaders,$r);
			}

			$strResponse = self::chunkTransferDecode($strResponse,$processedHeaders);

			return array('headers' => $processedHeaders['headers'], 'body' => $strResponse, 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}


		/**
		 *  Parse a URL and return its components
		 *
		 */
		public static function ParseUrl($url){

			$arrURL = parse_url($url);

			if( !isset($arrURL['port']) ){
				$arrURL['port'] = 80;
			}
			$arrURL += array('path'=>'');

			return $arrURL;
		}


		/**
		 * Make sure the url has an http or https scheme
		 *
		 */
		public static function FixScheme($url){

			preg_match('#^[a-z]+:#',$url,$match);

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


		/**
		 * Fetch a url using php's fsockopen() function
		 *
		 */
		public function fsockopen_request($url,$r){

			$fsockopen_host		= $this->url_array['host'];

			//fsockopen has issues with 'localhost' with IPv6 with certain versions of PHP, It attempts to connect to ::1,
			// which fails when the server is not setup for it. For compatibility, always connect to the IPv4 address.
			if ( 'localhost' == strtolower($fsockopen_host) )
				$fsockopen_host = '127.0.0.1';

			$iError = null; // Store error number
			$strError = null; // Store error string

			$handle = fsockopen( $fsockopen_host, $this->url_array['port'], $iError, $strError, $r['timeout'] );

			if( $handle === false ){
				self::$debug['fsock']	= 'no handle';
				return false;
			}
			self::stream_timeout($handle,$r['timeout']);

			$requestPath = $this->url_array['path'] . ( isset($this->url_array['query']) ? '?' . $this->url_array['query'] : '' );
			if ( empty($requestPath) )
				$requestPath .= '/';

			$strHeaders = strtoupper($r['method']) . ' ' . $requestPath . ' HTTP/' . $r['httpversion'] . "\r\n";
			$strHeaders .= 'Host: ' . $this->url_array['host'] . "\r\n";

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
				return $this->Redirect($processedHeaders,$r);
			}

			$strResponse = self::chunkTransferDecode($strResponse,$processedHeaders);

			return array('headers' => $processedHeaders['headers'], 'body' => $process['body'], 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}


		/**
		 * Fetch a url using php's curl library
		 *
		 */
		protected function curl_request($url, $r){

			$handle = curl_init();

			/*
			 * CURLOPT_TIMEOUT and CURLOPT_CONNECTTIMEOUT expect integers. Have to use ceil since.
			 * a value of 0 will allow an unlimited timeout.
			 */
			$timeout = (int) ceil( $r['timeout'] );
			curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, $timeout );
			curl_setopt( $handle, CURLOPT_TIMEOUT, $timeout );

			curl_setopt( $handle, CURLOPT_URL, $url);
			curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
			//curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, ( $ssl_verify === true ) ? 2 : false );
			//curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, $ssl_verify );

			//if ( $ssl_verify ) {
			//	curl_setopt( $handle, CURLOPT_CAINFO, $r['sslcertificates'] );
			//}

			curl_setopt( $handle, CURLOPT_USERAGENT, $r['user-agent'] );

			/*
			 * The option doesn't work with safe mode or when open_basedir is set, and there's
			 * a bug #17490 with redirected POST requests, so handle redirections outside Curl.
			 */
			curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, false );
			if ( defined( 'CURLOPT_PROTOCOLS' ) ) // PHP 5.2.10 / cURL 7.19.4
				curl_setopt( $handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS );

			curl_setopt( $handle, CURLOPT_CUSTOMREQUEST, $r['method'] );
			curl_setopt( $handle, CURLOPT_HEADERFUNCTION, array( $this, 'curl_headers' ) );
			curl_setopt( $handle, CURLOPT_WRITEFUNCTION, array( $this, 'curl_body' ) );
			curl_setopt( $handle, CURLOPT_HEADER, 0 );

			if( $r['httpversion'] == '1.0' ){
				curl_setopt( $handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
			}else{
				curl_setopt( $handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
			}

			curl_exec( $handle );

			if( curl_errno( $handle ) ){
				self::$debug['curl_error'] = curl_error( $handle );
				curl_close( $handle );
				return false;
			}
			curl_close( $handle );


			$processedHeaders = self::processHeaders($this->headers);

			// If location is found, then assume redirect and redirect to location.
			if( isset($processedHeaders['headers']['location']) ){
				return $this->Redirect($processedHeaders,$r);
			}

			return array('headers' => $processedHeaders['headers'], 'body' => $this->body, 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}


		/**
		 * Grab the headers of the cURL request
		 *
		 * Each header is sent individually to this callback, so we append to the $header property for temporary storage
		 *
		 * @since 3.2.0
		 * @access private
		 * @return int
		 */
		private function curl_headers( $handle, $headers ) {
			$this->headers .= $headers;
			return strlen( $headers );
		}


		/**
		 * Grab the body of the cURL request
		 *
		 * The contents of the document are passed in chunks, so we append to the $body property for temporary storage.
		 * Returning a length shorter than the length of $data passed in will cause cURL to abort the request with CURLE_WRITE_ERROR
		 *
		 * @since 3.6.0
		 * @access private
		 * @return int
		 */
		private function curl_body( $handle, $data ) {
			$data_length = strlen( $data );

			$this->body .= $data;

			$this->bytes_written_total += $data_length;

			// Upon event of this function returning less than strlen( $data ) curl will error with CURLE_WRITE_ERROR.
			return $data_length;
		}


		/**
		 * Set the stream timeout
		 *
		 */
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
		public function Redirect($headers,$r){

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
				$location = $this->url_array['scheme'].'://'.$this->url_array['host'].$location;
			}

			self::$redirected		= $location;
			self::$debug['Redir']	= 1;

			return $this->_get($location, $r);
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
		public static function chunkTransferDecode($body,$headers){

			if( !self::IsChunked($body,$headers) ){
				return $body;
			}

			$parsed_body = '';

			// We'll be altering $body, so need a backup in case of error.
			$body_original = $body;

			while ( true ) {
				$has_chunk = (bool) preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i', $body, $match );
				if ( ! $has_chunk || empty( $match[1] ) )
					return $body_original;

				$length = hexdec( $match[1] );
				$chunk_length = strlen( $match[0] );

				// Parse out the chunk of data.
				$parsed_body .= substr( $body, $chunk_length, $length );

				// Remove the chunk from the raw data.
				$body = substr( $body, $length + $chunk_length );

				// End of the document.
				if ( '0' === trim( $body ) )
					return $parsed_body;
			}
		}


		/**
		 * Return true if the response body is chunked
		 *
		 */
		public static function IsChunked($body, $headers){

			$body = trim($body);

			if( empty($body) ){
				return false;
			}
			if( !isset( $headers['headers']['transfer-encoding'] ) || 'chunked' != $headers['headers']['transfer-encoding'] ){
				return false;
			}


			// The body is not chunked encoded or is malformed.
			if( ! preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i',$body) ){
				return false;
			}

			return true;
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