<?php


namespace gp\tool{

	defined('is_running') or die('Not an entry point...');

	class RemoteGet{

		public static $redirected;
		public static $maxlength =			-1;	// The maximum bytes to read. eg: stream_get_contents($handle, $maxlength)
		public static $debug;
		public static $methods =			array('stream','curl','fopen','fsockopen');


		protected $url_array =				array();
		protected $body =					'';
		protected $headers =				'';
		protected $bytes_written_total =	0;



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

			foreach(static::$methods as $method){
				if( static::Supported($method) ){
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

			static::$debug					= array();
			static::$debug['Redir']			= 0;
			static::$debug['FailedMethods']	= '';
			static::$debug['NotSupported']	= '';
			static::$redirected				= null;

			return $this->_get($url,$args);
		}

		protected function _get($url, $args = array()){

			//reset body, headers, bytes_written. Important for redirection, multiple requests using same object
			$this->body =					'';
			$this->headers =				'';
			$this->bytes_written_total =	0;


			//$url				= rawurldecode($url);
			$url				= str_replace(' ','%20',$url); //spaces in the url can make the request fail
			$url				= static::FixScheme($url);
			$this->url_array	= static::ParseUrl($url);

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
				'ignore_errors'		=> false,


				//could be added
				//'blocking' => true,
				//'headers' => array(),
				//'body' => null,
				//'cookies' => array(),
			);

			$args += $defaults;


			foreach(static::$methods as $method){

				if( !static::Supported($method) ){
					static::$debug['NotSupported']	.= $method.',';
					continue;
				}

				$result = static::GetMethod($method,$url,$args);
				if( $result === false ){
					static::$debug['FailedMethods'] .= $method.',';
					return false;
				}

				static::$debug['Method']	= $method;
				static::$debug['Len']		= strlen($result['body']);

				return $result;
			}

			return false;
		}


		public function GetMethod($method,$url,$args=array()){

			$func = $method.'_request';

			if( method_exists($this,$func) ){
				return $this->$func( $url, $args );
			}

			return false;
		}


		/**
		 * Fetch a url using php's stream_get_contents() function
		 *
		 */
		public function stream_request($url,$r){

			$arrContext =	$this->stream_context($url,$r);

			$context =		stream_context_create($arrContext);

			$handle =		fopen($url, 'r', false, $context);

			if( !$handle ){
				static::$debug['stream']	= 'no handle';
				return false;
			}

			static::stream_timeout($handle,$r['timeout']);

			$strResponse = stream_get_contents($handle, static::$maxlength);
			$theHeaders = static::StreamHeaders($handle);
			fclose($handle);

			$processedHeaders = static::processHeaders($theHeaders);

			$this->body = static::chunkTransferDecode($strResponse,$processedHeaders);

			return $this->ReturnRequest( $url, $r, $processedHeaders );
		}


		/**
		 * Create context array
		 *
		 */
		public function stream_context($url,$r){

			//create context
			$arrContext = array();
			$arrContext['http'] = array(
					'method'			=> 'GET',
					'user_agent'		=> $r['user-agent'],
					'max_redirects'		=> $r['redirection'],
					'protocol_version'	=> (float) $r['httpversion'],
					'timeout'			=> $r['timeout'],
					'ignore_errors'		=> $r['ignore_errors'],
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

			return $arrContext;
		}


		/**
		 * Fetch a url using php's fopen() function
		 *
		 */
		public function fopen_request($url,$r){

			$handle		= fopen($url, 'r');

			if( !$handle ){
				static::$debug['fopen']	= 'no handle';
				return false;
			}

			static::stream_timeout($handle,$r['timeout']);

			$strResponse	= $this->ReadHandle($handle);
			$theHeaders		= static::StreamHeaders($handle);

			fclose($handle);

			$processedHeaders = static::processHeaders($theHeaders);

			$this->body = static::chunkTransferDecode($strResponse,$processedHeaders);

			return $this->ReturnRequest( $url, $r, $processedHeaders );
		}


		/**
		 *  Parse a URL and return its components
		 *
		 */
		public static function ParseUrl($url){

			$arr_url = parse_url($url);
			if( is_array($arr_url) ){



				$arr_url += array('path'=>'');
			}elseif( \gp\tool::LoggedIn() ){
				trigger_error('invalid url: '.$url.' '.pre($url));
			}


			return $arr_url;
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

			$port = 80;
			if( !empty($this->url_array['port']) ){
				$port = 80;
			}
			$handle = fsockopen( $fsockopen_host, $port, $iError, $strError, $r['timeout'] );

			if( $handle === false ){
				static::$debug['fsock']	= 'no handle';
				return false;
			}
			static::stream_timeout($handle,$r['timeout']);


			$strHeaders = $this->ReqHeader($r);

			fwrite($handle, $strHeaders);

			$strResponse = $this->ReadHandle($handle);

			fclose($handle);

			$process =				static::processResponse($strResponse);
			$processedHeaders =		static::processHeaders($process['headers']);
			$this->body =			static::chunkTransferDecode($process['body'],$processedHeaders);

			return $this->ReturnRequest( $url, $r, $processedHeaders );
		}


		/**
		 * Return request header string
		 *
		 */
		protected function ReqHeader($r){

			$requestPath = $this->url_array['path'] . ( isset($this->url_array['query']) ? '?' . $this->url_array['query'] : '' );
			if ( empty($requestPath) )
				$requestPath .= '/';

			$strHeaders = strtoupper($r['method']) . ' ' . $requestPath . ' HTTP/' . $r['httpversion'] . "\r\n";
			$strHeaders .= 'Host: ' . $this->url_array['host'] . "\r\n";

			if ( isset($r['user-agent']) )
				$strHeaders .= 'User-agent: ' . $r['user-agent'] . "\r\n";

			$strHeaders .= "\r\n";

			return $strHeaders;
		}


		/**
		 * Read all content from the handle
		 *
		 */
		protected function ReadHandle($handle){
			$response = '';
			while( !feof($handle) ){
				$response .= fread($handle, 4096);

				if( strlen($response) > static::$maxlength ){
					break;
				}
			}

			return $response;
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


			if( !$r['ignore_errors'] && curl_errno( $handle ) ){
				static::$debug['curl_error'] = curl_error( $handle );
				curl_close( $handle );
				return false;
			}

			curl_close( $handle );


			$processedHeaders = static::processHeaders($this->headers);

			return $this->ReturnRequest( $url, $r, $processedHeaders );
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
		 * Return the response info or redirection
		 *
		 */
		public function ReturnRequest( $url, $r, $processedHeaders ){

			// If location is found, then assume redirect and redirect to location.
			$redir_location = $this->RedirectLocation($processedHeaders);
			if( $redir_location !== false ){
				if( $redir_location == $url ){
					// redirecting to the same url
					// need cookies
					if( \gp\tool::LoggedIn() ){
						msg('infinite redirection: '.$redir_location);
					}
					return false;
				}
				return $this->Redirect($redir_location,$r);
			}

			return array('headers' => $processedHeaders['headers'], 'body' => $this->body, 'response' => $processedHeaders['response'], 'cookies' => $processedHeaders['cookies']);
		}


		/**
		 * Handle a redirect response
		 *
		 */
		public function Redirect($location,$r){

			if( $r['redirection']-- < 0 ){
				trigger_error('Too many redirects');
				return false;
			}

			static::$redirected		= $location;
			static::$debug['Redir']	= 1;

			return $this->_get($location, $r);
		}

		/**
		 * Get the redirect location
		 *
		 */
		public function RedirectLocation($headers){

			if( empty($headers['headers']['location']) ){
				return false;
			}

			//check location for releative value
			$location = $headers['headers']['location'];
			if( is_array($headers['headers']['location']) ){
				do{
					$location =		array_pop($headers['headers']['location']);
					$location =		trim($location);

				}while( count($headers['headers']['location']) && empty($location) );
			}

			$location = trim($location);

			if( empty($location) ){
				return false;
			}


			//	//www.example.com
			if( substr($location,0,2) == '//' ){
				$location = $this->url_array['scheme'].':'.$location;

			// ?page=test
			}elseif( $location[0] == '?' ){
				$location = $this->url_array['scheme'].'://'.rtrim($this->url_array['host'],'/').'/'.ltrim($this->url_array['path'],'/').$location;

			// /page
			}elseif( $location[0] == '/' ){
				$location = $this->url_array['scheme'].'://'.rtrim($this->url_array['host'],'/').$location;

			// http://www.example.com
			}elseif( preg_match('#^[a-z]+:#i',$location) ){
				// do nothing

			// otherwise relative path
			}else{
				$urla = $this->url_array;
				unset($urla['query'], $urla['fragment']);

				if( empty($urla['path']) || $urla['path'] == '/' ){
					$urla['path'] = $location;

				}elseif( substr($urla['path'],-1) != '/' ){
					$urla['path'] .= ltrim($location,'/');

				}else{
					$urla['path'] = rtrim(dirname($urla['path']),'/'). '/' . ltrim($location,'/');
				}

				$location = $this->unparse_url($urla);
			}

			return $location;
		}

		function unparse_url($parsed_url) {
			$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
			$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
			$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
			$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
			$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
			$pass     = ($user || $pass) ? "$pass@" : '';
			$path     = isset($parsed_url['path']) ? '/'.ltrim($parsed_url['path'],'/') : '';
			$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
			$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
			return "$scheme$user$pass$host$port$path$query$fragment";
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

			if( !static::IsChunked($body,$headers) ){
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

			$headers		= static::HeadersArray($headers);
			$response		= array('code' => 0, 'message' => '');
			$cookies		= array();
			$newheaders		= array();

			foreach( $headers as $tempheader ){

				if( false === strpos($tempheader, ':') ){
					$stack = explode(' ', $tempheader, 3);
					$stack[] = '';
					list( , $response['code'], $response['message']) = $stack;
					continue;
				}

				list($key, $value)	= explode(':', $tempheader, 2);
				$key				= strtolower( $key );
				$value				= trim( $value );

				if( isset( $newheaders[$key] ) ){
					if( !is_array( $newheaders[$key] ) ){
						$newheaders[$key] = array( $newheaders[$key] );
					}
					$newheaders[$key][] = $value;
				}else{
					$newheaders[$key] = $value;
				}
			}


			static::$debug['Headers'] = count($newheaders);

			return array('response' => $response, 'headers' => $newheaders, 'cookies' => $cookies);
		}


		/**
		 * Split header header string into array if needed
		 *
		 */
		protected static function HeadersArray($headers){
			// split headers, one per array element
			if ( is_string($headers) ) {
				// tolerate line terminator: CRLF = LF (RFC 2616 19.3)
				$headers = str_replace("\r\n", "\n", $headers);
				// unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>, <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2)
				$headers = preg_replace('/\n[ \t]/', ' ', $headers);
				// create the headers array
				$headers = explode("\n", $headers);
			}
			$headers		= (array)$headers;
			$headers		= array_filter($headers);

			return $headers;
		}


		/**
		 * Output debug info about the most recent request
		 *
		 */
		public static function Debug($lang_key, $debug = array()){

			$debug	= array_merge(static::$debug,$debug);

			return \gp\tool::Debug($lang_key, $debug);
		}


	}
}

namespace {
	class gpRemoteGet extends \gp\tool\RemoteGet{}
}