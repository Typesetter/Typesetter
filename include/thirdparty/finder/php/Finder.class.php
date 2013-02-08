<?php


require('FinderVolumeDriver.class.php');


/**
 * Core class.
 *
 * @package Finder
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 * @author Alexey Sukhotin
 **/
class Finder {

	/**
	 * API version number
	 *
	 * @var string
	 **/
	protected $version = '2.2.1';

	/**
	 * Storages (root dirs)
	 *
	 * @var array
	 **/
	protected $volumes = array();

	public static $netDrivers = array('ftp'=>'FTP');

	/**
	 * Mounted volumes count
	 * Required to create unique volume id
	 *
	 * @var int
	 **/
	public static $volumesCnt = 1;

	/**
	 * Default root (storage)
	 *
	 * @var FinderStorageDriver
	 **/
	protected $default = null;

	/**
	 * Commands and required arguments list
	 *
	 * @var array
	 **/
	protected $commands = array(
		'open'      => array('target' => false, 'tree' => false, 'init' => false, 'mimes' => false),
		'ls'        => array('target' => true, 'mimes' => false),
		'tree'      => array('target' => true),
		'parents'   => array('target' => true),
		'tmb'       => array('targets' => true),
		'file'      => array('target' => true, 'download' => false),
		'size'      => array('targets' => true),
		'mkdir'     => array('target' => true, 'name' => true),
		'mkfile'    => array('target' => true, 'name' => true, 'mimes' => false),
		'rm'        => array('targets' => true),
		'rename'    => array('target' => true, 'name' => true, 'mimes' => false),
		'duplicate' => array('targets' => true, 'suffix' => false),
		'paste'     => array('dst' => true, 'targets' => true, 'cut' => false, 'mimes' => false),
		'upload'    => array('target' => true, 'FILES' => true, 'mimes' => false, 'html' => false, 'upload' => false, 'name' => false),
		'get'       => array('target' => true),
		'put'       => array('target' => true, 'content' => '', 'mimes' => false),
		'archive'   => array('targets' => true, 'type' => true, 'mimes' => false),
		'extract'   => array('target' => true, 'mimes' => false),
		'search'    => array('q' => true, 'mimes' => false),
		'info'      => array('targets' => true),
		'dim'       => array('target' => true),
		'resize'    => array('target' => true, 'width' => true, 'height' => true, 'mode' => false, 'x' => false, 'y' => false, 'degree' => false),
		'netmount'  => array('protocol' => true, 'host' => true, 'path' => false, 'port' => false, 'user' => true, 'pass' => true, 'alias' => false, 'options' => false),
		'unmount'   => array('target' => true)
	);

	/**
	 * Commands listeners
	 *
	 * @var array
	 **/
	protected $listeners = array();

	/**
	 * script work time for debug
	 *
	 * @var string
	 **/
	protected $time = 0;
	/**
	 * Is Finder init correctly?
	 *
	 * @var bool
	 **/
	protected $loaded = false;
	/**
	 * Send debug to client?
	 *
	 * @var string
	 **/
	protected $debug = false;

	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	protected $header = 'Content-Type: application/json';


	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	protected $uploadDebug = '';

	/**
	 * Errors from not mounted volumes
	 *
	 * @var array
	 */
	public $mountErrors = array();

	/**
	 * Array of user data
	 *
	 * @var array
	 */
	public $userData = array();

	/**
	 * Callback for saving user data
	 *
	 * @var callback
	 */
	public $saveDataMethod = false;


	/**
	 * Errors message constants
	 * @deprecated
	 *
	 */
	const ERROR_PERM_DENIED       = 'errPerm';
	const ERROR_LOCKED            = 'errLocked';        // '"$1" is locked and can not be renamed, moved or removed.'
	const ERROR_EXISTS            = 'errExists';        // 'File named "$1" already exists.'
	const ERROR_INVALID_NAME      = 'errInvName';       // 'Invalid file name.'
	const ERROR_MKDIR             = 'errMkdir';
	const ERROR_MKFILE            = 'errMkfile';
	const ERROR_RENAME            = 'errRename';
	const ERROR_COPY              = 'errCopy';
	const ERROR_COPY_ITSELF       = 'errCopyInItself';
	const ERROR_MOVE              = 'errMove';
	const ERROR_REPLACE           = 'errReplace';          // 'Unable to replace "$1".'
	const ERROR_RM                = 'errRm';               // 'Unable to remove "$1".'
	const ERROR_RM_SRC            = 'errRmSrc';            // 'Unable remove source file(s)'
	const ERROR_UPLOAD            = 'errUpload';           // 'Upload error.'
	const ERROR_UPLOAD_FILE       = 'errUploadFile';       // 'Unable to upload "$1".'
	const ERROR_UPLOAD_NO_FILES   = 'errUploadNoFiles';    // 'No files found for upload.'
	const ERROR_UPLOAD_TOTAL_SIZE = 'errUploadTotalSize';  // 'Data exceeds the maximum allowed size.'
	const ERROR_UPLOAD_FILE_SIZE  = 'errUploadFileSize';   // 'File exceeds maximum allowed size.'
	const ERROR_UPLOAD_FILE_MIME  = 'errUploadMime';       // 'File type not allowed.'
	const ERROR_UPLOAD_TRANSFER   = 'errUploadTransfer';   // '"$1" transfer error.'
	// const ERROR_ACCESS_DENIED     = 'errAccess';
	const ERROR_NOT_REPLACE       = 'errNotReplace';       // Object "$1" already exists at this location and can not be replaced with object of another type.
	const ERROR_SAVE              = 'errSave';
	const ERROR_EXTRACT           = 'errExtract';
	const ERROR_ARCHIVE_TYPE      = 'errArcType';
	const ERROR_ARCHIVE           = 'errArchive';
	const ERROR_NOT_ARCHIVE       = 'errNoArchive';
	const ERROR_ARC_SYMLINKS      = 'errArcSymlinks';
	const ERROR_ARC_MAXSIZE       = 'errArcMaxSize';
	const ERROR_RESIZE            = 'errResize';
	const ERROR_UNSUPPORT_TYPE    = 'errUsupportType';
	const ERROR_NOT_UTF8_CONTENT  = 'errNotUTF8Content';
	const ERROR_NETMOUNT          = 'errNetMount';
	const ERROR_NETMOUNT_NO_DRIVER = 'errNetMountNoDriver';

	//not used ... may still be used in javascript
	//const ERROR_NETMOUNT_FAILED       = 'errNetMountFailed';
	//const ERROR_COPY_FROM         = 'errCopyFrom';
	//const ERROR_COPY_TO           = 'errCopyTo';


	/**
	 * Constructor
	 *
	 * @param  array  Finder and roots configurations
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct($opts) {

		$this->time  = $this->utime();
		$this->debug = (isset($opts['debug']) && $opts['debug'] ? true : false);
		if( $this->debug ){
			$this->header = 'Content-Type: text/html; charset=utf-8';
		}
		setlocale(LC_ALL, !empty($opts['locale']) ? $opts['locale'] : 'en_US.UTF-8');

		// bind events listeners
		if( !empty($opts['bind']) && is_array($opts['bind']) ){
			foreach( $opts['bind'] as $cmd => $listener ){
				$this->bind($cmd, $listener);
			}
		}

		// get data
		if( !empty($opts['returnData']) && !empty($opts['saveData'])
			&& is_callable($opts['returnData']) && is_callable($opts['saveData']) ){
			$this->userData = call_user_func( $opts['returnData'] );
			$this->saveDataMethod = $opts['saveData'];
		}


		// roots
		if( !isset($opts['roots']) || !is_array($opts['roots']) ){
			$opts['roots'] = array();
		}

		// check for net volumes stored in userData
		foreach ($this->getNetVolumes() as $root) {
			$opts['roots'][] = $root;
		}

		// "mount" volumes
		foreach( $opts['roots'] as $i => $o ){
			$driver = isset($o['driver']) ? $o['driver'] : '';
			if( $this->MountVolume( $volume, $driver, $o ) ){

				// unique volume id (ends on "_") - used as prefix to files hash
				$id = $volume->id();

				$this->volumes[$id] = $volume;
				if( !$this->default && $volume->isReadable() ){
					$this->default = $this->volumes[$id];
				}
			}

		}

		// if at least one redable volume - ii desu >_<
		$this->loaded = !empty($this->default);
	}


	/**
	 * Execute Finder command and output result
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function run() {
		$isPost = $_SERVER["REQUEST_METHOD"] == 'POST';
		$src    = $_SERVER["REQUEST_METHOD"] == 'POST' ? $_POST : $_GET;
		$cmd    = isset($src['cmd']) ? $src['cmd'] : '';
		$args   = array();

		if (!function_exists('json_encode')) {
			$error = $this->error('errConf', 'errJSON');
			$this->output(array('error' => '{"error":["'.implode('","', $error).'"]}', 'raw' => true));
		}

		if (!$this->loaded()) {
			$this->output(array('error' => $this->error('errConf', 'errNoVolumes'), 'debug' => $this->mountErrors));
		}

		// telepat_mode: on
		if (!$cmd && $isPost) {
			$this->output(array('error' => $this->error('errUpload', 'errUploadTotalSize'), 'header' => 'Content-Type: text/html'));
		}
		// telepat_mode: off

		if (!$this->commandExists($cmd)) {
			$this->output(array('error' => $this->error('errUnknownCmd')));
		}

		// collect required arguments to exec command
		foreach ($this->commandArgsList($cmd) as $name => $req) {
			$arg = $name == 'FILES'
				? $_FILES
				: (isset($src[$name]) ? $src[$name] : '');

			if (!is_array($arg)) {
				$arg = trim($arg);
			}
			if ($req && (!isset($arg) || $arg === '')) {
				$this->output(array('error' => $this->error('errCmdParams', $cmd)));
			}
			$args[$name] = $arg;
		}

		$args['debug'] = isset($src['debug']) ? !!$src['debug'] : false;

		$this->output($this->exec($cmd, $args));
	}

	/**
	 * Output json
	 *
	 * @param  array  data to output
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function output(array $data) {

		if( isset($data['pointer']) ){
			$this->ClearBuffer();
			$this->Headers($data);

			rewind($data['pointer']);
			fpassthru($data['pointer']);
			if( !empty($data['volume']) ){
				$data['volume']->close($data['pointer'], $data['info']['hash']);
			}
			exit();
		}

		$this->Headers($data);
		if (!empty($data['raw']) && !empty($data['error'])) {
			exit($data['error']);
		}

		exit(json_encode($data));
	}

	/**
	 * Set response headers
	 *
	 */
	function Headers($data){
		$header = isset($data['header']) ? $data['header'] : $this->header;
		unset($data['header']);
		if( $header ){
			if( is_array($header) ){
				foreach( $header as $h ){
					header($h);
				}
			}else{
				header($header);
			}
		}
	}

	/**
	 * Close all open output buffers
	 *
	 */
	function ClearBuffer(){
		$level = ob_get_level();
		while( $level > 0 ){
			ob_end_clean();
			$level--;
		}
		if( function_exists('header_remove') ){
			header_remove();
		}
	}


	/**
	 * Return true if fm init correctly
	 *
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function loaded() {
		return $this->loaded;
	}

	/**
	 * Return version (api) number
	 *
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	public function version() {
		return $this->version;
	}

	/**
	 * Add lister to Finder command
	 *
	 * @param  string  command name
	 * @param  callback  callback name or array(object, method)
	 * @return Finder
	 *
	 */
	public function bind($cmd, $listener) {
		$cmds = explode(' ', $cmd);
		foreach($cmds as $cmd){
			$cmd = trim($cmd);
			if( empty($cmd) ){
				continue;
			}
			if( !isset($this->listeners[$cmd]) ){
				$this->listeners[$cmd] = array();
			}

			if( is_callable($listener) ){
				$this->listeners[$cmd][] = $listener;
			}
		}

		return $this;
	}

	/**
	 * Remove event (command exec) listener
	 *
	 * @param  string $event event name
	 * @param  callback  callback name or array(object, method)
	 * @return Finder
	 *
	 */
	public function unbind($event, $listener){
		$events = explode(' ', $event);
		foreach($events as $event){
			$event = trim($event);
			if( empty($event) ){
				continue;
			}

			if( empty($this->listeners[$event]) ){
				continue;
			}

			foreach( $this->listeners[$event] as $i => $h ){
				if( $h === $listener ){
					unset($this->listeners[$event][$i]);
				}
			}
		}
		return $this;
	}

	/**
	 * Return a list of listeners for the current event
	 * @param string $event
	 * @return array
	 */
	public function GetListeners( $event, $state = false ){
		$listeners = array();

		if( $state ){
			$state = '-'.trim($state,'-');
			$event .= $state;
		}

		if( !empty($this->listeners[$event]) ){
			$listeners = $this->listeners[$event];
		}
		if( !empty($this->listeners['*'.$state]) ){
			$listeners = array_merge($listeners,$this->listeners['*'.$state]);
		}
		return $listeners;
	}



	/**
	 * Return true if command exists
	 *
	 * @param  string  command name
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function commandExists($cmd) {
		return $this->loaded && isset($this->commands[$cmd]) && method_exists($this, $cmd);
	}

	/**
	 * Return command required arguments info
	 *
	 * @param  string  command name
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function commandArgsList($cmd) {
		return $this->commandExists($cmd) ? $this->commands[$cmd] : array();
	}

	/**
	 * Exec command and return result
	 *
	 * @param  string  $cmd  command name
	 * @param  array   $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function exec($cmd, $args) {

		if( !$this->loaded ){
			return array('error' => $this->error('errConf', 'errNoVolumes'));
		}

		if( !$this->commandExists($cmd) ){
			return array('error' => $this->error('errUnknownCmd'));
		}

		if( !empty($args['mimes']) && is_array($args['mimes']) ){
			foreach ($this->volumes as $id => $v) {
				$this->volumes[$id]->setMimesFilter($args['mimes']);
			}
		}

		// call "*-before" events
		$listeners = $this->GetListeners($cmd,'before');
		foreach( $listeners as $listener ){
			$args = call_user_func($listener,$cmd.'-before',$args,$this);
		}
		if( !is_array($args) ){
			return array('error' => $this->error('errPerm'));
		}

		//call the command
		$result = call_user_func( array($this,$cmd), $args );


		//get result information from volumes
		$result_lists = array( 'changed'=>false, 'removed'=>true, 'added'=>false );
		foreach($result_lists as $index => $hashes_only){
			if( !array_key_exists($index,$result) ){
				continue;
			}

			$list =& $result[$index];
			foreach( $this->volumes as $volume ){
				$list = array_merge($list, $volume->result($index));
			}
		}

		// call listeners for this command
		$listeners = $this->GetListeners($cmd);
		foreach( $listeners as $listener ){
			// listener return true to force sync client after command completed
			if( call_user_func($listener,$cmd,$result,$args,$this) ){
				$result['sync'] = true;
			}
		}


		// remove hidden files and filter files by mimetypes
		// replace removed files info with removed files hashes
		foreach($result_lists as $index => $hashes_only ){
			if( !array_key_exists($index,$result) ){
				continue;
			}
			$result[$index] = $this->filter($result[$index], $hashes_only);
		}

		if ($this->debug || !empty($args['debug'])) {
			$result['debug'] = array(
				'connector' => 'php',
				'phpver'    => PHP_VERSION,
				'time'      => $this->utime() - $this->time,
				'memory'    => (function_exists('memory_get_peak_usage') ? ceil(memory_get_peak_usage()/1024).'Kb / ' : '').ceil(memory_get_usage()/1024).'Kb / '.ini_get('memory_limit'),
				'upload'    => $this->uploadDebug,
				'volumes'   => array(),
				'mountErrors' => $this->mountErrors
				);

			foreach( $this->volumes as $id => $volume ){
				$result['debug']['volumes'][] = $volume->debug();
			}
		}

		foreach ($this->volumes as $volume) {
			$volume->reset();
			$volume->umount();
		}

		return $result;
	}

	/**
	 * Return file real path
	 *
	 * @param  string  $hash  file hash
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	public function realpath($hash)	{
		if (($volume = $this->volume($hash)) == false) {
			return false;
		}
		return $volume->realpath($hash);
	}

	/**
	 * Return network volumes config.
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 */
	protected function getNetVolumes(){
		if( isset($this->userData['FinderNetVolumes']) && is_array($this->userData['FinderNetVolumes']) ){
			return $this->userData['FinderNetVolumes'];
		}
		return array();
	}

	/**
	 * Save network volumes config.
	 *
	 * @param  array  $volumes  volumes config
	 * @return void
	 * @author Dmitry (dio) Levashov
	 */
	protected function saveNetVolumes($volumes){
		if( !$this->saveDataMethod ){
			return false;
		}
		$this->userData['FinderNetVolumes'] = $volumes;
		return call_user_func( $this->saveDataMethod, $this->userData);
	}

	/***************************************************************************/
	/*                                 commands                                */
	/***************************************************************************/

	/**
	 * Normalize error messages
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function error() {
		$errors = array();

		foreach (func_get_args() as $msg) {
			if (is_array($msg)) {
				$errors = array_merge($errors, $msg);
			} else {
				$errors[] = $msg;
			}
		}

		return count($errors) ? $errors : array('errUnknown');
	}

	/**
	 * Delete a net mounted volume
	 *
	 */
	protected function unmount($args){

		// get volume info
		$netVolumes	= $this->getNetVolumes();
		$hash_parts = explode('_',$args['target']);
		$volume_id = array_shift($hash_parts).'_';

		// remove from array
		$removed = false;
		foreach($netVolumes as $id => $v){
			if( $id == $volume_id ){
				unset($netVolumes[$volume_id]);
				unset($this->volumes[$volume_id]);
				$removed = true;
			}
		}
		$this->saveNetVolumes($netVolumes);

		if( $removed ){
			return array('unmount'=>true);
		}

		return array('error' => $this->error('errNetMount', 'Not Found'));
	}

	protected function netmount($args) {
		$options  = array();
		$protocol = $args['protocol'];
		$driver   = isset(self::$netDrivers[$protocol]) ? self::$netDrivers[$protocol] : '';
		$netVolumes = $this->getNetVolumes();


		if( !$driver ){
			return array('error' => $this->error('errNetMount', $args['host'], 'errNetMountNoDriver'));
		}

		if( !$args['path'] ){
			$args['path'] = '/';
		}

		foreach($args as $k => $v){
			if ($k != 'options' && $k != 'protocol' && $v) {
				$options[$k] = $v;
			}
		}

		if( is_array($args['options']) ){
			foreach($args['options'] as $key => $value){
				$options[$key] = $value;
			}
		}

		//generate an id
		$options['id'] = base_convert(time(),10,36);
		$options['driver'] = $driver;
		$options['netmount'] = true;


		if( !$this->MountVolume( $volume, $driver, $options ) ){
			return array('error' => $this->error('errNetMount', $args['host'], implode(' ', $volume->error())));
		}

		if( !$volume->connect() ){
			return array('error' => $this->error('errNetMount', $args['host'], implode(' ', $volume->error())));
		}

		//add to list of volumes
		$id = $volume->id();
		$this->volumes[$id] = $volume;


		//save net volumes
		$netVolumes[$id]     = $options;
		$netVolumes        = array_unique($netVolumes);
		$this->saveNetVolumes($netVolumes);




		// simulate open request send open data
		// data : {cmd : 'open', init : 1, target : cwd, tree : this.ui.tree ? 1 : 0},
		$args = array();
		$args['cmd'] = 'open';
		$args['init'] = 1;
		$args['target'] = $volume->defaultPath(); //$id;
		$args['tree'] = 1;
		return $this->open($args);
	}

	/**
	 * Create a new volume object for the specified driver
	 * Include the file if the class doesn't exist
	 *
	 */
	function MountVolume( &$volume, $driver, $options ){
		$class = 'FinderVolume'.$driver;
		if( !class_exists($class) ){
			$file = 'FinderVolume'.$driver.'.class.php';
			include_once($file);
		}
		if( !class_exists($class) ){
			$this->mountErrors[] = 'Driver "'.$driver.'" does not exists';
		}
		$volume = new $class();

		if( !$volume->mount($options) ){
			$this->mountErrors[] = 'Driver "'.$driver.'" : '.implode(' ', $volume->error());
			return false;
		}

		return true;
	}

	/**
	 * "Open" directory
	 * Return array with following elements
	 *  - cwd          - opened dir info
	 *  - files        - opened dir content [and dirs tree if $args[tree]]
	 *  - api          - api version (if $args[init])
	 *  - uplMaxSize   - if $args[init]
	 *  - error        - on failed
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function open($args) {
		$target = $args['target'];
		$init   = !empty($args['init']);
		$tree   = !empty($args['tree']);
		$volume = $this->volume($target);
		$cwd    = $volume ? $volume->dir($target, true) : false;
		$hash   = $init ? 'default folder' : '#'.$target;

		// on init request we can get invalid dir hash -
		// dir which can not be opened now, but remembered by client,
		// so open default dir
		if ((!$cwd || !$cwd['read']) && $init) {
			$volume = $this->default;
			$cwd    = $volume->dir($volume->defaultPath(), true);
		}

		if (!$cwd) {
			return array('error' => $this->error('errOpen', $hash, 'errFolderNotFound'));
		}
		if (!$cwd['read']) {
			return array('error' => $this->error('errOpen', $hash, 'errPerm'));
		}

		$files = array($cwd);

		// get folders trees
		if( $args['tree'] ){
			foreach($this->volumes as $id => $vol){
				$vol->connect();
				$tree = $vol->tree('', 0, $cwd['hash']);
				if( $tree !== false ){
					$files = array_merge($files, $tree);
				}
			}
		}

		// get current working directory files list and add to $files if not exists in it
		$ls = $volume->scandir($cwd['hash']);
		if( $ls === false) {
			return array('error' => $this->error('errOpen', $cwd['name'], $volume->error()));
		}

		foreach( $ls as $file ){
			if( !in_array($file, $files) ){
				$files[] = $file;
			}
		}

		$result = array(
			'cwd'     => $cwd,
			'options' => $volume->options($cwd['hash']),
			'files'   => $files
		);

		if (!empty($args['init'])) {
			$result['uplMaxSize'] = ini_get('upload_max_filesize');
			$result['netDrivers'] = array_keys(self::$netDrivers);
		}

		return $result;
	}

	/**
	 * Return dir files names list
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function ls($args) {
		$target = $args['target'];
		$volume = $this->volume($target);
		if( $volume == false ){
			return array('error' => $this->error('errOpen', '#'.$target));
		}

		$list = $volume->ls($target);
		if( $list == false ){
			return array('error' => $this->error('errOpen', '#'.$target));
		}

		return array('list' => $list);
	}

	/**
	 * Return subdirs for required directory
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function tree($args) {
		$target = $args['target'];

		$volume = $this->volume($target);
		if( $volume == false ){
			return array('error' => $this->error('errOpen', '#'.$target));
		}

		$tree = $volume->tree($target);
		if( $tree == false ){
			return array('error' => $this->error('errOpen', '#'.$target));
		}

		return array('tree' => $tree);
	}

	/**
	 * Return parents dir for required directory
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function parents($args) {
		$target = $args['target'];

		$volume = $this->volume($target);
		if( $volume == false ){
			return array('error' => $this->error('errOpen', '#'.$target));
		}

		$tree = $volume->parents($target);
		if( $tree == false ){
			return array('error' => $this->error('errOpen', '#'.$target));
		}

		return array('tree' => $tree);
	}

	/**
	 * Return new created thumbnails list
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function tmb($args) {

		$result  = array('images' => array());
		$targets = $args['targets'];

		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) != false
			&& (($tmb = $volume->tmb($target)) != false)) {
				$result['images'][$target] = $tmb;
			}
		}
		return $result;
	}

	/**
	 * Required to output file in browser when volume URL is not set
	 * Return array contains opened file pointer, root itself and required headers
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function file($args) {
		$target   = $args['target'];
		$download = !empty($args['download']);
		$h403     = 'HTTP/1.x 403 Access Denied';
		$h404     = 'HTTP/1.x 404 Not Found';

		if (($volume = $this->volume($target)) == false) {
			return array('error' => 'File not found', 'header' => $h404, 'raw' => true);
		}

		if (($file = $volume->file($target)) == false) {
			return array('error' => 'File not found', 'header' => $h404, 'raw' => true);
		}

		if (!$file['read']) {
			return array('error' => 'Access denied', 'header' => $h403, 'raw' => true);
		}

		if (($fp = $volume->open($target)) == false) {
			return array('error' => 'File not found', 'header' => $h404, 'raw' => true);
		}

		if ($download) {
			$disp = 'attachment';
			$mime = 'application/octet-stream';
		} else {
			$disp  = preg_match('/^(image|text)/i', $file['mime']) || $file['mime'] == 'application/x-shockwave-flash'
					? 'inline'
					: 'attachment';
			$mime = $file['mime'];
		}

		$filenameEncoded = rawurlencode($file['name']);
		if (strpos($filenameEncoded, '%') === false) { // ASCII only
			$filename = 'filename="'.$file['name'].'"';
		} else {
			$ua = $_SERVER["HTTP_USER_AGENT"];
			if (preg_match('/MSIE [4-8]/', $ua)) { // IE < 9 do not support RFC 6266 (RFC 2231/RFC 5987)
				$filename = 'filename="'.$filenameEncoded.'"';
			} elseif (strpos($ua, 'Chrome') === false && strpos($ua, 'Safari') !== false) { // Safari
				$filename = 'filename="'.str_replace('"', '', $file['name']).'"';
			} else { // RFC 6266 (RFC 2231/RFC 5987)
				$filename = 'filename*=UTF-8\'\''.$filenameEncoded;
			}
		}

		$result = array(
			'volume'  => $volume,
			'pointer' => $fp,
			'info'    => $file,
			'header'  => array(
				'Content-Type: '.$mime,
				'Content-Disposition: '.$disp.'; '.$filename,
				'Content-Location: '.$file['name'],
				'Content-Transfer-Encoding: binary',
				'Content-Length: '.$file['size'],
				'Connection: close'
			)
		);
		return $result;
	}

	/**
	 * Count total files size
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function size($args) {
		$size = 0;

		foreach ($args['targets'] as $target) {
			if (($volume = $this->volume($target)) == false
			|| ($file = $volume->file($target)) == false
			|| !$file['read']) {
				return array('error' => $this->error('errOpen', '#'.$target));
			}

			$size += $volume->size($target);
		}
		return array('size' => $size);
	}

	/**
	 * Create directory
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function mkdir($args) {
		$target = $args['target'];
		$name   = $args['name'];

		if (($volume = $this->volume($target)) == false) {
			return array('error' => $this->error('errMkdir', $name, 'errTrgFolderNotFound', '#'.$target));
		}

		return ($dir = $volume->mkdir($target, $name)) == false
			? array('error' => $this->error('errMkdir', $name, $volume->error()))
			: array('added' => array($dir));
	}

	/**
	 * Create empty file
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function mkfile($args) {
		$target = $args['target'];
		$name   = $args['name'];

		if (($volume = $this->volume($target)) == false) {
			return array('error' => $this->error('errMkfile', $name, 'errTrgFolderNotFound', '#'.$target));
		}

		return ($file = $volume->mkfile($target, $args['name'])) == false
			? array('error' => $this->error('errMkfile', $name, $volume->error()))
			: array('added' => array($file));
	}

	/**
	 * Rename file
	 *
	 * @param  array  $args
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function rename($args) {
		$target = $args['target'];
		$name   = $args['name'];

		if (($volume = $this->volume($target)) == false
		||  ($rm  = $volume->file($target)) == false) {
			return array('error' => $this->error('errRename', '#'.$target, 'errFileNotFound'));
		}
		$rm['realpath'] = $volume->realpath($target);

		return ($file = $volume->rename($target, $name)) == false
			? array('error' => $this->error('errRename', $rm['name'], $volume->error()))
			: array('added' => array($file), 'removed' => array($rm));
	}

	/**
	 * Duplicate file - create copy with "copy %d" suffix
	 *
	 * @param array  $args  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function duplicate($args) {
		$targets = is_array($args['targets']) ? $args['targets'] : array();
		$result  = array('added' => array());
		$suffix  = empty($args['suffix']) ? 'copy' : $args['suffix'];

		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) == false
			|| ($src = $volume->file($target)) == false) {
				$result['warning'] = $this->error('errCopy', '#'.$target, 'errFileNotFound');
				break;
			}

			if (($file = $volume->duplicate($target, $suffix)) == false) {
				$result['warning'] = $this->error($volume->error());
				break;
			}

			$result['added'][] = $file;
		}

		return $result;
	}

	/**
	 * Remove dirs/files
	 *
	 * @param array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function rm($args) {
		$targets = is_array($args['targets']) ? $args['targets'] : array();
		$result  = array('removed' => array());

		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) == false) {
				$result['warning'] = $this->error('errRm', '#'.$target, 'errFileNotFound');
				return $result;
			}
			if (!$volume->rm($target)) {
				$result['warning'] = $this->error($volume->error());
				return $result;
			}
		}

		return $result;
	}


	/**
	 * Save uploaded files
	 *
	 * @param  array
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function upload($args) {

		if( empty($args['FILES']['upload']) ){
			return $this->UploadExternal($args);
		}


		$target = $args['target'];
		$volume = $this->volume($target);
		$files =& $args['FILES']['upload'];
		$result = array('added' => array(), 'header' => empty($args['html']) ? false : 'Content-Type: text/html; charset=utf-8');

		if( !$volume ){
			return array('error' => $this->error('errUpload', 'errTrgFolderNotFound', '#'.$target));
		}

		if( !is_array($files) || empty($files) ){
			return array('error' => $this->error('errUpload', 'errUploadNoFiles','1'));
		}

		// check and upload content in $files array
		foreach( $files['name'] as $i => $name ){
			$error = $files['error'][$i];
			if( $error > 0 ){
				$result['warning'] = $this->error('errUploadFile', $name, $error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE ? 'errUploadFileSize' : 'errUploadTransfer');
				$this->uploadDebug = 'Upload error code: '.$error;
				break;
			}

			$tmpname = $files['tmp_name'][$i];

			//make sure it's an uploaded file
			if( !is_uploaded_file($tmpname) ){
				$result['warning'] = $this->error('errUploadFile', $name, 'errUploadTransfer');
				$this->uploadDebug = 'Upload error: not an uploaded file';
				break;
			}

			$fp = fopen($tmpname, 'rb');
			if( $fp === false ){
				$result['warning'] = $this->error('errUploadFile', $name, 'errUploadTransfer');
				$this->uploadDebug = 'Upload error: unable open tmp file';
				break;
			}

			$file = $volume->upload($fp, $target, $name, $tmpname);
			fclose($fp);

			if( $file === false ){
				$result['warning'] = $this->error('errUploadFile', $name, $volume->error());
				break;
			}

			$result['added'][] = $file;
		}

		return $result;
	}

	/**
	 * Create a $files array similar to $_FILES from drag/drop data
	 * @param	array $args command arguments
	 * @return	array
	 *
	 */
	protected function UploadExternal($args){
		$target = $args['target'];
		$volume = $this->volume($target);
		$result = array('added' => array(), 'header' => empty($args['html']) ? false : 'Content-Type: text/html; charset=utf-8');

		if( !isset($args['upload']) || !is_array($args['upload'])) {
			return array('error' => $this->error('errUpload', 'errUploadNoFiles','2'));
		}

		// values may be "http://file_path" or "<img src='http://file_path' ...>"
		$urls = array();
		foreach($args['upload'] as $i => $string){
			$pat = "#(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))#";
			if( preg_match_all($pat,$string,$matches) ){
				$urls = array_merge($urls,$matches[0]);
			}
		}

		if( empty($urls) ){
			return array('error' => $this->error('errUpload', 'errUploadNoFiles','3'));
		}

		$urls = array_unique($urls);
		foreach($urls as $url){

			$name = preg_replace('~^.*?([^/#?]+)(?:\?.*)?(?:#.*)?$~', '$1', rawurldecode($url));

			if( empty($name) ){
				continue;
			}

			$data = $this->get_remote_contents($url);
			if( !$data ){
				continue;
			}

			$tmpfname = tempnam(sys_get_temp_dir(),'');
			$fp = fopen($tmpfname, 'w+b');
			if( !$fp ){
				continue;
			}

			if( !fwrite($fp, $data) ){
				unlink($tmpfname);
				continue;
			}

			rewind($fp);

			$file = $volume->upload($fp, $target, $name, $tmpfname);
			fclose($fp);

			if( $file === false ){
				$result['warning'] = $this->error('errUploadFile', $name, $volume->error());
				unlink($tmpfname);
				break;
			}

			$result['added'][] = $file;

		}

		return $result;
	}



	/**
	* Get remote contents
	*
	* @param  string  $url     target url
	* @param  int     $timeout timeout (sec)
	* @param  int     $redirect_max redirect max count
	* @param  string  $ua
	* @return string or bool(false)
	* @retval string contents
	* @retval false  error
	* @author Naoki Sawada
	**/
	protected function get_remote_contents( $url, $timeout = 30, $redirect_max = 5, $ua = 'Mozilla/5.0' ) {
		$method = function_exists('curl_exec')? 'curl_get_contents' : 'fsock_get_contents';
		return $this->$method( $url, $timeout, $redirect_max, $ua );
	}

	/**
	 * Get remote contents with cURL
	 *
	 * @param  string  $url     target url
	 * @param  int     $timeout timeout (sec)
	 * @param  int     $redirect_max redirect max count
	 * @param  string  $ua
	 * @return string or bool(false)
	 * @retval string contents
	 * @retval false  error
	 * @author Naoki Sawada
	 **/
	 protected function curl_get_contents( $url, $timeout, $redirect_max, $ua ){
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_MAXREDIRS, $redirect_max);
		curl_setopt( $ch, CURLOPT_USERAGENT, $ua);
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}

	/**
	 * Get remote contents with fsockopen()
	 *
	 * @param  string  $url          url
	 * @param  int     $timeout      timeout (sec)
	 * @param  int     $redirect_max redirect max count
	 * @param  string  $ua
	 * @return string or bool(false)
	 * @retval string contents
	 * @retval false  error
	 * @author Naoki Sawada
	 */
	protected function fsock_get_contents( $url, $timeout, $redirect_max, $ua ) {

		$connect_timeout = 3;
		$connect_try = 3;
		$method = 'GET';
		$readsize = 4096;

		$getSize = null;
		$headers = '';

		$arr = parse_url($url);
		if (!$arr){
			// Bad request
			return false;
		}

		// query
		$arr['query'] = isset($arr['query']) ? '?'.$arr['query'] : '';
		// port
		$arr['port'] = isset($arr['port']) ? $arr['port'] : (!empty($arr['https'])? 443 : 80);

		$url_base = $arr['scheme'].'://'.$arr['host'].':'.$arr['port'];
		$url_path = isset($arr['path']) ? $arr['path'] : '/';
		$uri = $url_path.$arr['query'];

		$query = $method.' '.$uri." HTTP/1.0\r\n";
		$query .= "Host: ".$arr['host']."\r\n";
		if (!empty($ua)) $query .= "User-Agent: ".$ua."\r\n";
		if (!is_null($getSize)) $query .= 'Range: bytes=0-' . ($getSize - 1) . "\r\n";

		$query .= $headers;

		$query .= "\r\n";

		$fp = $connect_try_count = 0;
		while( !$fp && $connect_try_count < $connect_try ) {

			$errno = 0;
			$errstr = "";
			$fp = @ fsockopen(
			$arr['https'].$arr['host'],
			$arr['port'],
			$errno,$errstr,$connect_timeout);
			if ($fp) break;
			$connect_try_count++;
			if (connection_aborted()) {
				exit();
			}
			sleep(1); // wait 1sec
		}

		$fwrite = 0;
		for ($written = 0; $written < strlen($query); $written += $fwrite) {
			$fwrite = fwrite($fp, substr($query, $written));
			if (!$fwrite) {
				break;
			}
		}

		$response = '';

		if ($timeout) {
			socket_set_timeout($fp, $timeout);
		}

		$_response = true;
		while ($_response && (is_null($getSize) || strlen($response) < $getSize)) {
			if (connection_aborted()) {
				exit();
			}
			if ($_response = fread($fp, $readsize)) {
				$response .= $_response;
			}
		}

		if ($timeout) {
			$_status = socket_get_status($fp);
			if ($_status['timed_out']) {
				fclose($fp);
				return false; // Request Time-out
			}
		}

		fclose($fp);

		$resp = array_pad(explode("\r\n\r\n",$response,2), 2, '');
		$rccd = array_pad(explode(' ',$resp[0],3), 3, ''); // array('HTTP/1.1','200','OK\r\n...')
		$rc = (integer)$rccd[1];

		// Redirect
		switch ($rc) {
			case 307: // Temporary Redirect
			case 303: // See Other
			case 302: // Moved Temporarily
			case 301: // Moved Permanently
				$matches = array();
				if (preg_match('/^Location: (.+?)(#.+)?$/im',$resp[0],$matches) && --$redirect_max > 0) {
					$url = trim($matches[1]);
					$hash = isset($matches[2])? trim($matches[2]) : '';
					if (!preg_match('/^https?:\//',$url)) { // no scheme
						if ($url{0} != '/') { // Relative path
							// to Absolute path
							$url = substr($url_path,0,strrpos($url_path,'/')).'/'.$url;
						}
						// add sheme,host
						$url = $url_base.$url;
					}
					return $this->fsock_get_contents( $url, $timeout, $redirect_max, $ua );
				}
		}

		return $resp[1]; // Data
	}


	/**
	 * Copy/move files into new destination
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function paste($args) {
		$dst     = $args['dst'];
		$targets = is_array($args['targets']) ? $args['targets'] : array();
		$cut     = !empty($args['cut']);
		$error   = $cut ? 'errMove' : 'errCopy';
		$result  = array('added' => array(), 'removed' => array());

		if (($dstVolume = $this->volume($dst)) == false) {
			return array('error' => $this->error($error, '#'.$targets[0], 'errTrgFolderNotFound', '#'.$dst));
		}

		foreach ($targets as $target) {
			if (($srcVolume = $this->volume($target)) == false) {
				$result['warning'] = $this->error($error, '#'.$target, 'errFileNotFound');
				break;
			}

			$file = $dstVolume->paste($srcVolume, $target, $dst, $cut);
			if( !$file ){
				$result['warning'] = $this->error($dstVolume->error());
				break;
			}

		}
		return $result;
	}

	/**
	 * Return file content
	 *
	 * @param  array  $args  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function get($args) {
		$target = $args['target'];
		$volume = $this->volume($target);

		if (!$volume || ($file = $volume->file($target)) == false) {
			return array('error' => $this->error('errOpen', '#'.$target, 'errFileNotFound'));
		}

		if (($content = $volume->getContents($target)) === false) {
			return array('error' => $this->error('errOpen', $volume->path($target), $volume->error()));
		}

		$json = json_encode($content);

		if ($json == 'null' && strlen($json) < strlen($content)) {
			return array('error' => $this->error('errNotUTF8Content', $volume->path($target)));
		}

		return array('content' => $content);
	}

	/**
	 * Save content into text file
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function put($args) {
		$target = $args['target'];

		if (($volume = $this->volume($target)) == false
		|| ($file = $volume->file($target)) == false) {
			return array('error' => $this->error('errSave', '#'.$target, 'errFileNotFound'));
		}

		if (($file = $volume->putContents($target, $args['content'])) == false) {
			return array('error' => $this->error('errSave', $volume->path($target), $volume->error()));
		}

		return array('changed' => array($file));
	}

	/**
	 * Extract files from archive
	 *
	 * @param  array  $args  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function extract($args) {
		$target = $args['target'];
		$mimes  = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : array();
		$error  = array('errExtract', '#'.$target);

		if (($volume = $this->volume($target)) == false
		|| ($file = $volume->file($target)) == false) {
			return array('error' => $this->error('errExtract', '#'.$target, 'errFileNotFound'));
		}

		return ($file = $volume->extract($target))
			? array('added' => array($file))
			: array('error' => $this->error('errExtract', $volume->path($target), $volume->error()));
	}

	/**
	 * Create archive
	 *
	 * @param  array  $args  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function archive($args) {
		$type    = $args['type'];
		$targets = isset($args['targets']) && is_array($args['targets']) ? $args['targets'] : array();

		if (($volume = $this->volume($targets[0])) == false) {
			return $this->error('errArchive', 'errTrgFolderNotFound');
		}

		return ($file = $volume->archive($targets, $args['type']))
			? array('added' => array($file))
			: array('error' => $this->error('errArchive', $volume->error()));
	}

	/**
	 * Search files
	 *
	 * @param  array  $args  command arguments
	 * @return array
	 * @author Dmitry Levashov
	 **/
	protected function search($args) {
		$q      = trim($args['q']);
		$mimes  = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : array();
		$result = array();

		foreach ($this->volumes as $volume) {
			$volume->connect();
			$result = array_merge($result, $volume->search($q, $mimes));
		}

		return array('files' => $result);
	}

	/**
	 * Return file info (used by client "places" ui)
	 *
	 * @param  array  $args  command arguments
	 * @return array
	 * @author Dmitry Levashov
	 **/
	protected function info($args) {
		$files = array();

		foreach ($args['targets'] as $hash) {
			if (($volume = $this->volume($hash)) != false
			&& ($info = $volume->file($hash)) != false) {
				$files[] = $info;
			}
		}

		return array('files' => $files);
	}

	/**
	 * Return image dimmensions
	 *
	 * @param  array  $args  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function dim($args) {
		$target = $args['target'];

		if (($volume = $this->volume($target)) != false) {
			$dim = $volume->dimensions($target);
			return $dim ? array('dim' => $dim) : array();
		}
		return array();
	}

	/**
	 * Resize image
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function resize($args) {
		$target = $args['target'];
		$width  = $args['width'];
		$height = $args['height'];
		$x      = (int)$args['x'];
		$y      = (int)$args['y'];
		$mode   = $args['mode'];
		$bg     = null;
		$degree = (int)$args['degree'];

		if (($volume = $this->volume($target)) == false
		|| ($file = $volume->file($target)) == false) {
			return array('error' => $this->error('errResize', '#'.$target, 'errFileNotFound'));
		}

		return ($file = $volume->resize($target, $width, $height, $x, $y, $mode, $bg, $degree))
			? array('changed' => array($file))
			: array('error' => $this->error('errResize', $volume->path($target), $volume->error()));
	}

	/***************************************************************************/
	/*                                   utils                                 */
	/***************************************************************************/

	/**
	 * Return root - file's owner
	 *
	 * @param  string  file hash
	 * @return FinderStorageDriver
	 * @author Dmitry (dio) Levashov
	 **/
	protected function volume($hash){

		$hash_parts = explode('_',$hash);
		$volume_id = array_shift($hash_parts).'_';
		if( !isset($this->volumes[$volume_id]) ){
			return false;
		}

		if( !$this->volumes[$volume_id]->connect() ){
			return false;
		}

		return $this->volumes[$volume_id];
	}

	/**
	 * Return files info array
	 *
	 * @param  array  $data  one file info or files info
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function toArray($data) {
		return isset($data['hash']) || !is_array($data) ? array($data) : $data;
	}

	/**
	 * Return fils hashes list
	 *
	 * @param  array  $files  files info
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function hashes($files) {
		$ret = array();
		foreach ($files as $file) {
			$ret[] = $file['hash'];
		}
		return $ret;
	}

	/**
	 * Remove from files list hidden files and files with required mime types
	 *
	 * @param  array  $files  files info
	 * @return array Array of file hashes
	 * @author Dmitry (dio) Levashov
	 **/
	protected function filter($files, $hashes_only = true){
		$hashes = array();
		foreach( $files as $i => $file ){
			if( empty($file['hidden']) && $this->default->mimeAccepted($file['mime']) ){
				$hashes[$file['hash']] = $file;
			}
		}
		if( $hashes_only ){
			return array_keys($hashes);
		}
		return array_values($hashes);;
	}

	protected function utime() {
		$time = explode(" ", microtime());
		return (double)$time[1] + (double)$time[0];
	}


} // END class
