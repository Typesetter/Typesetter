<?php

/**
 * local filesystem driver
 *
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 **/
class FinderVolumeLocalFileSystem extends FinderVolumeDriver {

	/**
	 * Driver id
	 * Must be started from letter and contains [a-z0-9]
	 * Used as part of volume id
	 *
	 * @var string
	 **/
	protected $driverId = 'l';

	/**
	 * Required to count total archive files size
	 *
	 * @var int
	 **/
	protected $archiveSize = 0;

	/**
	 * Constructor
	 * Extend options with required fields
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct() {
		$this->options['alias']    = '';              // alias to replace root dir name
		$this->options['dirMode']  = 0755;            // new dirs mode
		$this->options['fileMode'] = 0644;            // new files mode
		$this->options['quarantine'] = '.quarantine';  // quarantine folder name - required to check archive (must be hidden)
		$this->options['maxArcFilesSize'] = 0;        // max allowed archive files size (0 - no limit)
	}

	/*********************************************************************/
	/*                        INIT AND CONFIGURE                         */
	/*********************************************************************/

	/**
	 * Configure after successfull mount.
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function configure() {
		$this->aroot = realpath($this->root);
		$root = $this->stat($this->root);

		if ($this->options['quarantine']) {
			$this->attributes[] = array(
				'pattern' => '~^'.preg_quote($this->separator.$this->options['quarantine']).'$~',
				'read'    => false,
				'write'   => false,
				'locked'  => true,
				'hidden'  => true
			);
		}

		// check thumbnails path
		if( $this->options['tmbPath']
			&& ($test_path = $this->_separator($this->options['tmbPath']))
			&& (strpos($test_path,$this->separator) === false)
			){
				$this->options['tmbPath'] = $this->_joinPath( $this->root, $this->options['tmbPath']);
				$this->options['tmbPath'] = $this->_normpath($this->options['tmbPath']);
		}

		parent::configure();

		// if no thumbnails url - try detect it
		if ($root['read'] && !$this->tmbURL && $this->URL) {
			if (strpos($this->tmbPath, $this->root) === 0) {
				$temp = substr($this->tmbPath, strlen($this->root)+1);
				$this->tmbURL = $this->URL . str_replace($this->separator, '/', $temp );
				if (preg_match("|[^/?&=]$|", $this->tmbURL)) {
					$this->tmbURL .= '/';
				}
			}
		}

		// check quarantine dir
		if (!empty($this->options['quarantine'])) {
			$this->quarantine = $this->_joinPath( $this->root, $this->options['quarantine'] );
			if ((!is_dir($this->quarantine) && !$this->_mkdir($this->root, $this->options['quarantine'])) || !is_writable($this->quarantine)) {
				$this->archivers['extract'] = array();
				$this->disabled[] = 'extract';
			}
		} else {
			$this->archivers['extract'] = array();
			$this->disabled[] = 'extract';
		}

	}

	/*********************************************************************/
	/*                               FS API                              */
	/*********************************************************************/


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
	 **/
	protected function _stat($path) {
		$stat = array();

		if (!file_exists($path)) {
			return $stat;
		}

		if ($path != $this->root && is_link($path)) {
			if (($target = $this->readlink($path)) == false
			|| $target == $path) {
				$stat['mime']  = 'symlink-broken';
				$stat['read']  = false;
				$stat['write'] = false;
				$stat['size']  = 0;
				return $stat;
			}
			$stat['alias']  = $this->_path($target);
			$stat['target'] = $target;
			$path  = $target;
			$lstat = lstat($path);
			$size  = $lstat['size'];
		} else {
			$size = @filesize($path);
		}

		$dir = is_dir($path);

		$stat['mime']  = $dir ? 'directory' : $this->mimetype($path);
		$stat['ts']    = filemtime($path);
		$stat['read']  = is_readable($path);
		$stat['write'] = is_writable($path);
		if ($stat['read']) {
			$stat['size'] = $dir ? 0 : $size;
		}

		return $stat;
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
		clearstatcache();
		return strpos($mime, 'image') === 0 && ($s = @getimagesize($path)) !== false
			? $s[0].'x'.$s[1]
			: false;
	}
	/******************** file/dir content *********************/

	/**
	 * Return symlink target file
	 *
	 * @param  string  $path  link path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function readlink($path) {
		$target = @readlink($path);
		if( !$target ){
			return false;
		}

		$target = $this->_separator($target);

		if( $target[0] != $this->separator ){
			$target = $this->_joinPath( dirname($path), $target );
		}

		$atarget = realpath($target);

		if( !$atarget ){
			return false;
		}

		if( $this->_inpath($atarget, $this->aroot) ){
			$arelative = substr( $atarget, strlen($this->aroot) );
			return $this->_joinPath($this->root, $arelative );
		}

		return false;
	}

	/**
	 * Get stat for folder content and put in cache
	 *
	 * @param  string  $path
	 * @return void
	 */
	protected function cacheDir($path){
		$this->dirsCache[$path] = array();

		$list = scandir($path);
		foreach($list as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			$p = $this->_joinPath( $path, $file );
			$this->dirsCache[$path][] = $p;
		}
	}


	/**
	 * Open file and return file pointer
	 *
	 * @param  string  $path  file path
	 * @param  bool    $write open file for writing
	 * @return resource|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fopen($path, $mode='rb') {
		return @fopen($path, 'r');
	}

	/**
	 * Close opened file
	 *
	 * @param  resource  $fp  file pointer
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fclose($fp, $path='') {
		return @fclose($fp);
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
		$path = $this->_joinPath( $path, $name);

		if (@mkdir($path)) {
			@chmod($path, $this->options['dirMode']);
			return $path;
		}

		return false;
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
		$path = $this->_joinPath( $path, $name);

		if (($fp = @fopen($path, 'w'))) {
			@fclose($fp);
			@chmod($path, $this->options['fileMode']);
			return $path;
		}
		return false;
	}

	/**
	 * Create symlink
	 *
	 * @param  string  $source     file to link to
	 * @param  string  $targetDir  folder to create link in
	 * @param  string  $name       symlink name
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _symlink($source, $targetDir, $name) {
		return @symlink($source, $this->_joinPath( $targetDir, $name) );
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
		return copy($source, $this->_joinPath( $targetDir, $name) );
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
		$target = $this->_joinPath( $targetDir, $name);
		return @rename($source, $target) ? $target : false;
	}

	/**
	 * Remove file
	 *
	 * @param  string  $path  file path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _unlink($path) {
		return @unlink($path);
	}

	/**
	 * Remove dir
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _rmdir($path) {
		return @rmdir($path);
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
		$path = $this->_joinPath( $dir, $name );

		if (!($target = @fopen($path, 'wb'))) {
			return false;
		}

		while (!feof($fp)) {
			fwrite($target, fread($fp, 8192));
		}
		fclose($target);
		@chmod( $path, $this->options['fileMode'] );
		clearstatcache();
		return $path;
	}

	/**
	 * Get file contents
	 *
	 * @param  string  $path  file path
	 * @return string|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _getContents($path) {
		return file_get_contents($path);
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
		if (@file_put_contents($path, $content, LOCK_EX) !== false) {
			clearstatcache();
			return true;
		}
		return false;
	}

	/**
	 * Detect available archivers
	 *
	 * @return void
	 */
	protected function _checkArchivers() {

		$arcs = array(
			'create'  => array(),
			'extract' => array()
			);


		//.tar
		$arcs['create']['application/x-tar']  = array( 'function'=>'PhpCompress', 'ext'=> 'tar' );
		$arcs['extract']['application/x-tar'] = array( 'function'=>'PhpExtract', 'ext'=> 'tar' );


	    if( function_exists('gzopen') ){

			//.zip
			$arcs['create']['application/zip']  = array( 'function'=>'PhpCompress', 'ext'=> 'zip' );
			$arcs['extract']['application/zip'] = array( 'function'=>'PhpExtract', 'ext'=> 'zip' );


			// .tar.gz
			$arcs['create']['application/x-gzip']  = array( 'function'=>'PhpCompress', 'ext'=>'tgz' );
			$arcs['extract']['application/x-gzip'] = array( 'function'=>'PhpExtract', 'ext'=> 'tgz' );

		}

		// .tar.bz
		if( function_exists('bzopen') ){
			$arcs['create']['application/x-bzip2']  = array( 'function'=>'PhpCompress', 'ext'=>'tbz' );
			$arcs['extract']['application/x-bzip2'] = array( 'function'=>'PhpExtract', 'ext'=> 'tbz' );
		}

		if (!function_exists('exec')) {
			$this->archivers = $arcs;
			// $this->options['archivers'] = $this->options['archive'] = array();
			return;
		}

		// 7z supports multiple types, but isn't the ideal either
		// these setting will be overwritten if a better option is found
		$this->procExec('7za --help', $c);
		if ($c == 0) {
			$arcs['create']['application/x-7z-compressed']  = array('cmd' => '7za', 'argc' => 'a', 'ext' => '7z');
			$arcs['extract']['application/x-7z-compressed'] = array('cmd' => '7za', 'argc' => 'e -y', 'ext' => '7z');

			$arcs['create']['application/x-gzip'] = array('cmd' => '7za', 'argc' => 'a -tgzip', 'ext' => 'tar.gz');
			$arcs['extract']['application/x-gzip'] = array('cmd' => '7za', 'argc' => 'e -tgzip -y', 'ext' => 'tar.gz');

			$arcs['create']['application/x-bzip2'] = array('cmd' => '7za', 'argc' => 'a -tbzip2', 'ext' => 'tar.bz');
			$arcs['extract']['application/x-bzip2'] = array('cmd' => '7za', 'argc' => 'a -tbzip2 -y', 'ext' => 'tar.bz');

			$arcs['create']['application/zip'] = array('cmd' => '7za', 'argc' => 'a -tzip -l', 'ext' => 'zip');
			$arcs['extract']['application/zip'] = array('cmd' => '7za', 'argc' => 'e -tzip -y', 'ext' => 'zip');

			$arcs['create']['application/x-tar'] = array('cmd' => '7za', 'argc' => 'a -ttar -l', 'ext' => 'tar');
			$arcs['extract']['application/x-tar'] = array('cmd' => '7za', 'argc' => 'e -ttar -y', 'ext' => 'tar');
		}



		// native support
		$this->procExec('tar --version', $ctar);
		if ($ctar == 0) {
			$arcs['create']['application/x-tar']  = array('cmd' => 'tar', 'argc' => '-cf', 'ext' => 'tar');
			$arcs['extract']['application/x-tar'] = array('cmd' => 'tar', 'argc' => '-xf', 'ext' => 'tar');

			$test = $this->procExec('gzip --version', $c);
			if ($c == 0) {
				$arcs['create']['application/x-gzip']  = array('cmd' => 'tar', 'argc' => '-czf', 'ext' => 'tgz');
				$arcs['extract']['application/x-gzip'] = array('cmd' => 'tar', 'argc' => '-xzf', 'ext' => 'tgz');
			}

			$test = $this->procExec('bzip2 --version', $c);
			if ($c == 0) {
				$arcs['create']['application/x-bzip2']  = array('cmd' => 'tar', 'argc' => '-cjf', 'ext' => 'tbz');
				$arcs['extract']['application/x-bzip2'] = array('cmd' => 'tar', 'argc' => '-xjf', 'ext' => 'tbz');
			}
		}
		$this->procExec('zip -v', $c);
		if ($c == 0) {
			$arcs['create']['application/zip']  = array('cmd' => 'zip', 'argc' => '-r9', 'ext' => 'zip');
		}

		$this->procExec('unzip --help', $c);
		if ($c == 0) {
			$arcs['extract']['application/zip'] = array('cmd' => 'unzip', 'argc' => '',  'ext' => 'zip');
		}

		$this->procExec('rar --version', $c);
		if ($c == 0 || $c == 7) {
			$arcs['create']['application/x-rar']  = array('cmd' => 'rar', 'argc' => 'a -inul', 'ext' => 'rar');
			$arcs['extract']['application/x-rar'] = array('cmd' => 'rar', 'argc' => 'x -y',    'ext' => 'rar');
		} else {
			$test = $this->procExec('unrar', $c);
			if ($c==0 || $c == 7) {
				$arcs['extract']['application/x-rar'] = array('cmd' => 'unrar', 'argc' => 'x -y', 'ext' => 'rar');
			}
		}



		$this->archivers = $arcs;
	}

	/**
	 * Unpack archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function _unpack($path, $arc) {
		$cwd = getcwd();
		$dir = $this->_dirname($path);
		chdir($dir);
		$cmd = $arc['cmd'].' '.$arc['argc'].' '.escapeshellarg($this->_basename($path));
		$this->procExec($cmd, $c);
		chdir($cwd);
	}

	/**
	 * Recursive symlinks search
	 *
	 * @param  string  $path  file/dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _findSymlinks($path) {
		if (is_link($path)) {
			return true;
		}

		if (is_dir($path)) {
			foreach (scandir($path) as $name) {
				if ($name != '.' && $name != '..') {
					$p = $this->_joinPath( $path, $name );
					if (is_link($p) || !$this->nameAccepted($name)) {
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
	 * Extract files from archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _extract($path, $arc){

		if ($this->quarantine) {
			$dir     = $this->_joinPath( $this->quarantine, str_replace(' ', '_', microtime()).basename($path) );
			$archive = $this->_joinPath( $dir, basename($path) );

			if (!@mkdir($dir)) {
				return false;
			}

			chmod($dir, 0777);

			// copy in quarantine
			if (!copy($path, $archive)) {
				return false;
			}

			// extract in quarantine
			$this->_unpack($archive, $arc);
			unlink($archive);

			// get files list
			$ls = array();
			foreach (scandir($dir) as $i => $name) {
				if ($name != '.' && $name != '..') {
					$ls[] = $name;
				}
			}

			// no files - extract error ?
			if (empty($ls)) {
				return false;
			}

			$this->archiveSize = 0;

			// find symlinks
			$symlinks = $this->_findSymlinks($dir);
			// remove arc copy
			$this->remove($dir);

			if ($symlinks) {
				return $this->setError('errArcSymlinks');
			}

			// check max files size
			if ($this->options['maxArcFilesSize'] > 0 && $this->options['maxArcFilesSize'] < $this->archiveSize) {
				return $this->setError('errArcMaxSize');
			}



			// archive contains one item - extract in archive dir
			if (count($ls) == 1) {
				$this->_unpack($path, $arc);
				$result = $this->_joinPath( dirname($path), $ls[0]);


			} else {
				// for several files - create new directory
				// create unique name for directory
				$name = basename($path);
				if (preg_match('/\.((tar\.(gz|bz|bz2|z|lzo))|cpio\.gz|ps\.gz|xcf\.(gz|bz2)|[a-z0-9]{1,4})$/i', $name, $m)) {
					$name = substr($name, 0,  strlen($name)-strlen($m[0]));
				}
				$test = $this->_joinPath( dirname($path), $name );
				if (file_exists($test) || is_link($test)) {
					$name = $this->uniqueName(dirname($path), $name, '-', false);
				}

				$result  = $this->_joinPath( dirname($path), $name);
				$archive = $this->_joinPath( $result, basename($path) );

				if (!$this->_mkdir(dirname($path), $name) || !copy($path, $archive)) {
					return false;
				}

				$this->_unpack($archive, $arc);
				@unlink($archive);
			}

			return file_exists($result) ? $result : false;
		}
	}

	/**
	 * Extract files from an archive using pclzip.lib.php or Archive_Tar.php
	 *
	 * @param string  $path  archive path
	 * @param array $archiver
	 * @return bool
	 */
	protected function PhpExtract( $path, $archiver ){

		// create archive object
		@ini_set('memory_limit', '256M');
		switch( $archiver['ext'] ){
			case 'zip':
				include('pclzip.lib.php');
				$archive = new PclZip($path);
			break;

			case 'tbz':
			case 'tgz':
			case 'tar':
				include('Archive_Tar.php');
				$archive = new Archive_Tar( $path );
			break;
			default:
			return $this->setError('Unknown archive type');
		}

		$list = $archive->listContent();
		if( !count($list) ){
			return $this->setError('Empty Archive');
		}

		// destination path .. determine if we need to create a folder for the files in the archive
		$root_names = $this->ArchiveRoots($list);
		$extract_args = array();
		$remove_path = '';
		if( count($root_names) > 1 ){
			$dest = $this->ArchiveDestination( $path );
		}elseif( count($list) == 1 ){
			//$dest = dirname($path);
			$dest = $this->ArchiveDestination( $path );//not ideal, but the listing is updated this way
		}else{
			$name = array_shift($root_names);
			$remove_path = $name;
			$dest = $this->IncrementName( dirname($path), $name );
		}


		// extract
		switch( $archiver['ext'] ){
			case 'zip':
				if( !$archive->extract( $dest, $remove_path ) ){
					return $this->setError('Extract Failed');
				}
			break;

			case 'tbz':
			case 'tgz':
			case 'tar':
				if( !$archive->extractModify( $dest, $remove_path ) ){
					return $this->setError('Extract Failed');
				}
			break;
		}

		return $dest;
	}


	/**
	 * Return a list of root paths from an archive list
	 * Helpful in determining if we need to create a folder for the files in the archive
	 * @param array $list List of files in archive
	 *
	 */
	protected function ArchiveRoots( &$list ){
		$root_names = array();
		foreach($list as $file){
			$filename = ltrim( str_replace('\\','/',$file['filename']) ,' 	/' );
			$parts = explode('/',$filename);
			$root_names[] = array_shift($parts);
		}
		return array_unique($root_names);
	}


	/**
	 * Return the path an archive can be extracted to
	 * @param string $path
	 */
	protected function ArchiveDestination( $path ){

		$name = basename($path);
		$parts = explode('.',$name);
		$extension = array_pop($parts);
		$name = implode('.',$parts);

		return $this->IncrementName( dirname($path), $name );
	}

	/**
	 * Create a unique filename by incrementing if needed
	 * @param string $dir
	 * @param string $name
	 * @param string $ext
	 */
	function IncrementName( $dir, $name, $ext = false ){
		if( $ext ){
			$ext = ltrim($ext,'.').'.';
		}

		$dest = $this->_joinPath( $dir, $name.$ext);
		if( !file_exists($dest) && !is_link($dest) ){
			return $dest;
		}

		$i = 0;
		do{
			$dest = $this->_joinPath( $dir, $name.'-'.$i.$ext);
			$i++;
		}while( file_exists($dest) || is_link($dest) );

		return $dest;
	}


	/**
	 * Create archive using php function and return the path
	 *
	 * @param  string  $dir    target dir
	 * @param  array   $files  files names list
	 * @param  string  $name   archive name
	 * @param  array   $arc    archiver options
	 * @return string|bool
	 */
	protected function PhpCompress($dir, $files, $name, $archiver ){

		@ini_set('memory_limit', '256M');
		$path = $this->_joinPath( $dir, $name );

		//format the list
		$list = array();
		foreach($files as $file){
			$list[] = $this->_joinPath( $dir, $file );
		}

		// create archive object
		switch( $archiver['ext'] ){
			case 'zip':
				include('pclzip.lib.php');
				$archive = new PclZip($path);
				if( !$archive->Create($list,'',$dir) ){
					return $this->SetError('errArchive');
				}
			break;

			case 'tgz':
			case 'tbz':
			case 'tar':
				include('Archive_Tar.php');
				$archive = new Archive_Tar( $path );
				if( !$archive->createModify($list, '', $dir) ){
					return $this->SetError('errArchive');
				}
			break;
		}


		return $path;

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
		$cwd = getcwd();
		chdir($dir);

		$files = array_map('escapeshellarg', $files);

		$cmd = $arc['cmd'].' '.$arc['argc'].' '.escapeshellarg($name).' '.implode(' ', $files);
		$this->procExec($cmd, $c);
		chdir($cwd);

		$path = $this->_joinPath( $dir, $name );
		return file_exists($path) ? $path : false;
	}

} // END class
