<?php

function chmodnum($chmod) {
    $trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
    $chmod = substr(strtr($chmod, $trans), 1);
    $array = str_split($chmod, 3);
    return array_sum(str_split($array[0])) . array_sum(str_split($array[1])) . array_sum(str_split($array[2]));
}

/**
 * Simple FTP driver
 *
 * @author Dmitry (dio) Levashov
 * @author Cem (discofever)
 **/
class FinderVolumeFTP extends FinderVolumeDriver {

	/**
	 * Driver id
	 * Must be started from letter and contains [a-z0-9]
	 * Used as part of volume id
	 *
	 * @var string
	 **/
	protected $driverId = 'f';

	/**
	 * FTP Connection Instance
	 *
	 * @var ftp
	 **/
	protected $connect = null;

	/**
	 * Directory for tmp files
	 * If not set driver will try to use tmbDir as tmpDir
	 *
	 * @var string
	 **/
	protected $tmpPath = '';

	/**
	 * Last FTP error message
	 *
	 * @var string
	 **/
	protected $ftpError = '';

	/**
	 * FTP server output list as ftp on linux
	 *
	 * @var bool
	 **/
	protected $ftpOsUnix;

	/**
	 * Tmp folder path
	 *
	 * @var string
	 **/
	protected $tmp = '';


	/**
	 * Which methods can be used for mime detection
	 *
	 * @var array
	 */
	protected $mime_detection = array('internal');

	/**
	 * Constructor
	 * Extend options with required fields
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Cem (DiscoFever)
	 **/
	public function __construct() {
		$opts = array(
			'host'          => 'localhost',
			'user'          => '',
			'pass'          => '',
			'port'          => 21,
			'mode'        	=> 'passive',
			'path'			=> '/',
			'timeout'		=> 20,
			'owner'         => true,
			'tmbPath'       => '',
			'tmpPath'       => '',
			'dirMode'       => 0755,
			'fileMode'      => 0644
		);
		$this->options = array_merge($this->options, $opts);
	}

	/*********************************************************************/
	/*                        INIT AND CONFIGURE                         */
	/*********************************************************************/

	/**
	 * Prepare FTP connection
	 * Connect to remote server and check if credentials are correct, if so, store the connection id in $ftp_conn
	 *
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 * @author Cem (DiscoFever)
	 **/
	protected function init() {
		if (!$this->options['host']
		||  !$this->options['user']
		||  !$this->options['pass']
		||  !$this->options['port']) {
			return $this->setError('Required options undefined.');
		}

		if (!function_exists('ftp_connect')) {
			return $this->setError('FTP extension not loaded.');
		}

		// remove protocol from host
		$scheme = parse_url($this->options['host'], PHP_URL_SCHEME);

		if ($scheme) {
			$this->options['host'] = substr($this->options['host'], strlen($scheme)+3);
		}

		// normalize root path
		$this->root = $this->options['path'] = $this->_normpath($this->options['path']);

		if (empty($this->options['alias'])) {
			$this->options['alias'] = $this->options['user'].'@'.$this->options['host'];
		}

		$this->rootName = $this->options['alias'];
		$this->options['separator'] = '/';

		return true;
	}


	/**
	 * Configure after successfull mount.
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function configure() {
		parent::configure();

		if (!empty($this->options['tmpPath'])) {
			if ((is_dir($this->options['tmpPath']) || @mkdir($this->options['tmpPath'])) && is_writable($this->options['tmpPath'])) {
				$this->tmp = $this->options['tmpPath'];
			}
		}

		if (!$this->tmp && $this->tmbPath) {
			$this->tmp = $this->tmbPath;
		}
	}

	/**
	 * Connect to ftp server
	 *
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 */
	public function connect() {
		static $connected = false;

		if( $connected ){
			return true;
		}

		if (!($this->connect = ftp_connect($this->options['host'], $this->options['port'], $this->options['timeout']))) {
			return $this->setError('Unable to connect to FTP server '.$this->options['host']);
		}
		if (!ftp_login($this->connect, $this->options['user'], $this->options['pass'])) {
			$this->umount();
			return $this->setError('Unable to login into '.$this->options['host']);
		}

		// switch off extended passive mode - may be usefull for some servers
		@ftp_exec($this->connect, 'epsv4 off' );
		// enter passive mode if required
		ftp_pasv($this->connect, $this->options['mode'] == 'passive');

		// enter root folder
		if (!ftp_chdir($this->connect, $this->root)
		|| $this->root != ftp_pwd($this->connect)) {
			$this->umount();
			return $this->setError('Unable to open root folder.');
		}

		$connected = parent::connect();
		return $connected;
	}

	/*********************************************************************/
	/*                               FS API                              */
	/*********************************************************************/

	/**
	 * Close opened connection
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function umount() {
		$this->connect && @ftp_close($this->connect);
	}


	/**
	 * Parse line from ftp_rawlist() output and return file stat (array)
	 *
	 * @param  string  $raw  line from ftp_rawlist() output
	 * @return array
	 */
	function ParseRaw($line,$path){
		static $is_windows;
		if( is_null($is_windows) ){
			$is_windows = stripos( ftp_systype($this->connect), 'win') !== false;
		}
		$stat = array();

		//windows
		if( $is_windows && preg_match('/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)/', $line, $info) ){
			if( $info[3] < 70 ){
				$info[3] += 2000;
			}else{
				$info[3] += 1900; // 4digit year fix
			}

			$stat['ts'] = @mktime($info[4] + (strcasecmp($info[6], 'PM') == 0 ? 12 : 0), $info[5], 0, $info[1], $info[2], $info[3]);
			$stat['name'] = $info[8];
			if( $info[7] == '<DIR>' ){
				$stat['mime'] = 'directory';
				$stat['size'] = 0;
			}else{
				$stat['mime'] = $this->mimetype($stat['name']);
				$stat['size'] = $info[7];
			}

		//unix
		}elseif( !$is_windows && $info = preg_split('/[ ]/', $line, 9, PREG_SPLIT_NO_EMPTY) ){
			$lcount = count($info);
			if ( $lcount < 8 ){
				return false;
			}

			$stat['perm'] = substr($info[0], 1);
			$perm = $this->parsePermissions($info[0]);
			$stat['read']  = $perm['read'];
			$stat['write'] = $perm['write'];

			if( $lcount == 8 ){
				sscanf($info[5], '%d-%d-%d', $year, $month, $day);
				sscanf($info[6], '%d:%d', $hour, $minute);
				$stat['ts'] = @mktime($hour, $minute, 0, $month, $day, $year);
				$stat['name'] = $info[7];
			}else{
				$month = $info[5];
				$day = $info[6];
				if( preg_match('/([0-9]{2}):([0-9]{2})/', $info[7], $l2) ){
					$year = date("Y");
					$hour = $l2[1];
					$minute = $l2[2];
				}else{
					$year = $info[7];
					$hour = 0;
					$minute = 0;
				}
				$stat['ts'] = strtotime( sprintf('%d %s %d %02d:%02d', $day, $month, $year, $hour, $minute) );
				$stat['name'] = $info[8];
			}

			//directory
			if( $info[0]{0} === 'd' ){
				$stat['mime'] = 'directory';
				$stat['size'] = 0;
				return $stat;
			}


			//symlink
			if( $info[0]{0} === 'l' ){

				$name_parts = explode('->',$stat['name']);
				$stat['name'] = trim($name_parts[0]);
				$target = trim($name_parts[1]);
				if( $target[0] != $this->separator ){
					$target = $this->_joinPath($path,$target);
				}
				$target = $this->_normpath($target);

				if( !$this->_inpath($target, $this->root) ){
					$stat['mime']  = 'symlink-broken';
					$stat['read']  = false;
					$stat['write'] = false;
					$stat['size']  = 0;

				}elseif( $this->is_dir($target) ){
					$stat['mime']  = 'directory';
					$stat['size']  = 0;
					$stat['alias'] = $this->_relpath($target);

				}else{
					$stat['mime']  = $this->mimetype($target);
					$stat['size']  = $info[4];
					$stat['alias'] = $this->_relpath($target);
				}

				return $stat;
			}


			//file
			$stat['mime'] = $this->mimetype($stat['name']);
			$stat['size'] = $info[4];
		}

		return $stat;
	}

	/**
	 * Wrapper for ftp_rawlist
	 *
	 * @param string $path
	 * @return array The parsed results of ftp_rawlist( $path )
	 */
	protected function RawList($path){

		$pwd = ftp_pwd($this->connect);
		@ftp_chdir($this->connect, $path);
		$list = ftp_rawlist($this->connect, '.');
		if( $pwd ){
			ftp_chdir($this->connect, $pwd);
		}

		return $list;
	}


	/**
	 * Parse permissions string. Return array(read => true/false, write => true/false)
	 *
	 * @param  string  $perm  permissions string
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function parsePermissions($perm) {
		$res   = array();
		$parts = array();
		$owner = $this->options['owner'];
		for ($i = 0, $l = strlen($perm); $i < $l; $i++) {
			$parts[] = substr($perm, $i, 1);
		}

		$read = ($owner && $parts[0] == 'r') || $parts[4] == 'r' || $parts[7] == 'r';

		return array(
			'read'  => $parts[0] == 'd' ? $read && (($owner && $parts[3] == 'x') || $parts[6] == 'x' || $parts[9] == 'x') : $read,
			'write' => ($owner && $parts[2] == 'w') || $parts[5] == 'w' || $parts[8] == 'w'
		);
	}

	/**
	 * Cache dir contents
	 *
	 * @param  string  $path  dir path
	 * @return void
	 * @author Dmitry Levashov
	 **/
	protected function cacheDir($path) {
		$this->dirsCache[$path] = array();

		$list = $this->RawList($path);
		if( !$list ){
			return;
		}

		foreach($list as $raw){
			$stat = $this->parseRaw($raw,$path);
			if( $stat && empty($stat['hidden']) ){
				$p = $this->_joinPath($path,$stat['name']);
				$this->updateCache( $p, $stat );
				$this->dirsCache[$path][] = $p;
			}
		}
	}

	/**
	 * Return ftp transfer mode for file
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function ftpMode($path) {
		return strpos($this->mimetype($path), 'text/') === 0 ? FTP_ASCII : FTP_BINARY;
	}



	/***************** file stat ********************/
	/**
	 * Return stat for given path.
	 * Stat contains following fields:
	 * - (int)    size    file size in b. required
	 * - (int)    ts      file modification time in unix time. required
	 * - (string) mime    mimetype. required for folders, others - optionally
	 * - (bool)   read    read permissions. required
	 * - (bool)   write   write permissions. required
	 * - (bool)   locked  is object locked. optionally
	 * - (bool)   hidden  is object hidden. optionally
	 * - (string) alias   for symlinks - link target path relative to root path. optionally
	 * - (string) target  for symlinks - link target path. optionally
	 *
	 * If file does not exists - returns empty array or false.
	 *
	 * @param  string  $path    file path
	 * @return array|false
	 * @author Dmitry (dio) Levashov
	 */
	protected function _stat($path){

		//use MLST if available
		$stat = $this->MLST($path);
		if( $stat ){
			return $stat;
		}

		//directories
		$stat = array();
		if( $this->is_dir($path) ){
			$stat['mime'] = 'directory';
			$stat['size'] = 0;
			return $stat;
		}

		//files
		$size = ftp_size($this->connect, $path);
		if( $size < 0 ){
			return false;
		}

		$stat['mime'] = $this->mimetype($path);
		$stat['size'] = $size;
		$stat['ts'] = ftp_mdtm($this->connect, $path);

		return $stat;
	}

	function MLST($path){

		$raw = ftp_raw($this->connect, 'MLST '.$path);

		if( !is_array($raw) || count($raw) <= 1 || substr(trim($raw[0]), 0, 1) != 2 ){
			return false;
		}


		$parts = explode(';', trim($raw[1]));
		array_pop($parts);
		$parts = array_map('strtolower', $parts);
		$stat  = array();
		// debug($parts);
		foreach ($parts as $part) {

			list($key, $val) = explode('=', $part);

			switch ($key) {
				case 'type':
					$stat['mime'] = strpos($val, 'dir') !== false ? 'directory' : $this->mimetype($path);
					break;

				case 'size':
					$stat['size'] = $val;
					break;

				case 'modify':
					$ts = mktime(intval(substr($val, 8, 2)), intval(substr($val, 10, 2)), intval(substr($val, 12, 2)), intval(substr($val, 4, 2)), intval(substr($val, 6, 2)), substr($val, 0, 4));
					$stat['ts'] = $ts;
					// $stat['date'] = $this->formatDate($ts);
					break;

				case 'unix.mode':
					$stat['chmod'] = $val;
					break;

				case 'perm':
					$val = strtolower($val);
					$stat['read']  = (int)preg_match('/e|l|r/', $val);
					$stat['write'] = (int)preg_match('/w|m|c/', $val);
					if (!preg_match('/f|d/', $val)) {
						$stat['locked'] = 1;
					}
					break;
			}
		}
		if (empty($stat['mime'])) {
			return false;
		}
		if ($stat['mime'] == 'directory') {
			$stat['size'] = 0;
		}

		if (isset($stat['chmod'])) {
			$stat['perm'] = '';
			if ($stat['chmod'][0] == 0) {
				$stat['chmod'] = substr($stat['chmod'], 1);
			}

			for ($i = 0; $i <= 2; $i++) {
				$perm[$i] = array(false, false, false);
				$n = isset($stat['chmod'][$i]) ? $stat['chmod'][$i] : 0;

				if ($n - 4 >= 0) {
					$perm[$i][0] = true;
					$n = $n - 4;
					$stat['perm'] .= 'r';
				} else {
					$stat['perm'] .= '-';
				}

				if ($n - 2 >= 0) {
					$perm[$i][1] = true;
					$n = $n - 2;
					$stat['perm'] .= 'w';
				} else {
					$stat['perm'] .= '-';
				}

				if ($n - 1 == 0) {
					$perm[$i][2] = true;
					$stat['perm'] .= 'x';
				} else {
					$stat['perm'] .= '-';
				}

				$stat['perm'] .= ' ';
			}

			$stat['perm'] = trim($stat['perm']);

			$owner = $this->options['owner'];
			$read = ($owner && $perm[0][0]) || $perm[1][0] || $perm[2][0];

			$stat['read']  = $stat['mime'] == 'directory' ? $read && (($owner && $perm[0][2]) || $perm[1][2] || $perm[2][2]) : $read;
			$stat['write'] = ($owner && $perm[0][1]) || $perm[1][1] || $perm[2][1];
			unset($stat['chmod']);

		}

		return $stat;

	}

	/**
	 * Return true if the $path is a directory
	 *
	 */
	function is_dir($path){

		$pwd = @ftp_pwd($this->connect);
		if( $path == $pwd ){
			return true;
		}

		if( @ftp_chdir($this->connect, $path ) ){
			$new_pwd = @ftp_pwd($this->connect);
			if( $pwd != $new_pwd ){
				return true;
			}
		}
		return false;
	}


	/**
	 * Return object width and height
	 * Ususaly used for images, but can be realize for video etc...
	 *
	 * @param  string  $path  file path
	 * @param  string  $mime  file mime type
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _dimensions($path, $mime) {
		return false;
	}

	/******************** file/dir content *********************/

	/**
	 * Open file and return file pointer
	 *
	 * @param  string  $path  file path
	 * @param  bool    $write open file for writing
	 * @return resource|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fopen($path, $mode='rb') {

		$local = $this->tempname($path);

		if( ftp_get($this->connect, $local, $path, FTP_BINARY) ){
			return @fopen($local, $mode);
		}

		return false;
	}

	/**
	 * Close opened file
	 *
	 * @param  resource  $fp  file pointer
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fclose($fp, $path='') {
		@fclose($fp);
		if ($path) {
			$local = $this->tempname($path);
			@unlink( $local );
		}
	}

	/********************  file/dir manipulations *************************/

	/**
	 * Create dir and return created dir path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new directory name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _mkdir($path, $name) {
		$path = $this->_joinPath($path,$name);
		if (ftp_mkdir($this->connect, $path) === false) {
			return false;
		}

		$this->options['dirMode'] && @ftp_chmod($this->connect, $this->options['dirMode'], $path);
		return $path;
	}

	/**
	 * Create file and return it's path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new file name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _mkfile($path, $name) {
		$path = $this->_joinPath( $path, $name );
		$local = $this->tempname($path );
		$res = touch($local) && ftp_put($this->connect, $path, $local, FTP_ASCII);
		@unlink($local);
		return $res ? $path : false;
	}

	/**
	 * Create symlink. FTP driver does not support symlinks.
	 *
	 * @param  string  $target  link target
	 * @param  string  $path    symlink path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _symlink($target, $path, $name) {
		return false;
	}

	/**
	 * Copy file into another file
	 *
	 * @param  string  $source     source file path
	 * @param  string  $targetDir  target directory path
	 * @param  string  $name       new file name
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _copy($source, $targetDir, $name) {

		$local = $this->tempname($source);
		$target = $this->_joinPath( $targetDir, $name );

		if( !ftp_get($this->connect, $local, $source, FTP_BINARY) ){
			@unlink($local);
			return false;
		}

		if( !ftp_put($this->connect, $target, $local, $this->ftpMode($target)) ){
			@unlink($local);
			return false;
		}

		@unlink($local);
		return $target;
	}

	/**
	 * Move file into another parent dir.
	 * Return new file path or false.
	 *
	 * @param  string  $source  source file path
	 * @param  string  $target  target dir path
	 * @param  string  $name    file name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _move($source, $targetDir, $name) {
		$target = $this->_joinPath( $targetDir, $name );
		return ftp_rename($this->connect, $source, $target) ? $target : false;
	}

	/**
	 * Remove file
	 *
	 * @param  string  $path  file path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _unlink($path) {
		return ftp_delete($this->connect, $path);
	}

	/**
	 * Remove dir
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _rmdir($path) {
		return ftp_rmdir($this->connect, $path);
	}

	/**
	 * Create new file and write into it from file pointer.
	 * Return new file path or false on error.
	 *
	 * @param  resource  $fp   file pointer
	 * @param  string    $dir  target dir path
	 * @param  string    $name file name
	 * @param  array     $stat file stat (required by some virtual fs)
	 * @return bool|string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _save($fp, $dir, $name, $stat) {
		$path = $this->_joinPath( $dir,$name);
		return ftp_fput($this->connect, $path, $fp, $this->ftpMode($path))
			? $path
			: false;
	}

	/**
	 * Get file contents
	 *
	 * @param  string  $path  file path
	 * @return string|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _getContents($path) {
		$contents = '';
		if (($fp = $this->_fopen($path))) {
			while (!feof($fp)) {
			  $contents .= fread($fp, 8192);
			}
			$this->_fclose($fp, $path);
			return $contents;
		}
		return false;
	}

	/**
	 * Write a string to a file
	 *
	 * @param  string  $path     file path
	 * @param  string  $content  new file content
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _filePutContents($path, $content) {

		$local = $this->tempname($path);

		if( @file_put_contents($local, $content, LOCK_EX) === false ){
			return false;
		}
		$fp = @fopen($local, 'rb');
		if( !$fp ){
			unlink($local);
			return false;
		}

		$res = ftp_fput($this->connect, $path, $fp, $this->ftpMode($path));

		unlink($local);
		return true;
	}

	/**
	 * Detect available archivers
	 *
	 * @return void
	 **/
	protected function _checkArchivers() {
		// die('Not yet implemented. (_checkArchivers)');
		return array();
	}

	/**
	 * Unpack archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function _unpack($path, $arc) {
		die('Not yet implemented. (_unpack)');
		return false;
	}

	/**
	 * Recursive symlinks search
	 *
	 * @param  string  $path  file/dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _findSymlinks($path) {
		die('Not yet implemented. (_findSymlinks)');
		if (is_link($path)) {
			return true;
		}
		if (is_dir($path)) {
			foreach (scandir($path) as $name) {
				if ($name != '.' && $name != '..') {
					$p = $this->_joinPath( $path, $name );
					if (is_link($p)) {
						return true;
					}
					if (is_dir($p) && $this->_findSymlinks($p)) {
						return true;
					} elseif (is_file($p)) {
						$this->archiveSize += filesize($p);
					}
				}
			}
		} else {
			$this->archiveSize += filesize($path);
		}

		return false;
	}

	/**
	 * Return a temporary path name
	 *
	 */
	protected function tempname( $path ){
		static $paths = array();

		if( isset($paths[$path]) ){
			return $paths[$path];
		}

		if( !$this->tmp ){
			$dir = sys_get_temp_dir();
		}
		$temp = tempnam($dir,'');

		$paths[$path] = $temp;
		return $temp;
	}

	/**
	 * Extract files from archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _extract($path, $arc) {
		die('Not yet implemented. (_extract)');

	}

	/**
	 * Create archive and return its path
	 *
	 * @param  string  $dir    target dir
	 * @param  array   $files  files names list
	 * @param  string  $name   archive name
	 * @param  array   $arc    archiver options
	 * @return string|bool
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _archive($dir, $files, $name, $arc) {
		die('Not yet implemented. (_archive)');
		return false;
	}



} // END class
