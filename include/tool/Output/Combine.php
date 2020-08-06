<?php

namespace gp\tool\Output;

defined('is_running') or die('Not an entry point...');

includeFile('thirdparty/JShrink/autoload.php');

class Combine{


	public static $scripts = [

		//cms
		'gp-main' => [
			'file'			=> '/include/js/main.js'
		],

		'gp-admin' => [
			'file'			=> '/include/js/admin.js',
			'requires'		=> 'gp-main',
		],

		'gp-admin-css'=> [
			'file'			=> '/include/css/admin.scss',
			'type'			=> 'css',
			'requires'		=> 'ui-theme',
		],

		'gp-admin-toolbar' => [
			'file'			=> '/include/css/admin_toolbar.scss',
			'type'			=> 'css',
		],

		'gp-additional'=> [
			'file'			=> '/include/css/additional.css',
			'type'			=> 'css',
		],

		'gp-theme-css'=> [
			'file'			=> '/include/css/theme_content.scss',
			'type'			=> 'css',
			'requires'		=> 'ui-theme',
		],

		//colorbox
		'colorbox' => [
			'file'			=> '/include/thirdparty/colorbox/colorbox/jquery.colorbox.js',
			'requires'		=> 'gp-main,colorbox-css',
		],

		'colorbox-css' => [
			'file'			=> '/include/thirdparty/colorbox/$config[colorbox_style]/colorbox.css',
			'type'			=> 'css',
		],

		//jquery
		'jquery' => [
			'file'			=> '/include/thirdparty/js/jquery.js',
			'package'		=> 'jquery',
			'label'			=> 'jQuery',
			'cdn'		 => [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js',
				'Google'		=> '//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js',
			],
		],

		//jquery ui core
		'ui-theme' => [
			'file'			=> '/include/thirdparty/jquery_ui/jquery-ui.min.css',
			'type'			=> 'css',
			'package'		=> 'jquery_ui',
			'cdn'		 => [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css',
				'Google'		=> '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.min.css',
			],
		],

		'ui-core' => [
			'file'			=> '/include/thirdparty/jquery_ui/core.js',
			'package'		=> 'jquery_ui',
			'label'			=> 'jQuery UI',
			'cdn'		 => [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
				'Google'		=> '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
			],
		],

		'mouse' => [
			'file'			=> '/include/thirdparty/jquery_ui/mouse.js',
			'requires'		=> 'ui-core,widget',
			'package' 		=> 'jquery_ui',
		],

		'position' => [
			'file'			=> '/include/thirdparty/jquery_ui/position.js',
			'package'		=> 'jquery_ui',
		],

		'widget' => [
			'file'			=> '/include/thirdparty/jquery_ui/widget.js',
			'package'		=> 'jquery_ui',
		],

		//jquery ui interactions
		'draggable' => [
			'file'			=> '/include/thirdparty/jquery_ui/draggable.js',
			'requires'		=> 'ui-core,widget,mouse',
			'package'		=> 'jquery_ui',
		],

		'droppable' => [
			'file'			=> '/include/thirdparty/jquery_ui/droppable.js',
			'requires'		=> 'ui-core,widget,mouse,draggable',
			'package'		=> 'jquery_ui',
		],

		'resizable' => [
			'file'			=> '/include/thirdparty/jquery_ui/resizable.js',
			'requires'		=> 'ui-core,widget,mouse,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'selectable' => [
			'file'			=> '/include/thirdparty/jquery_ui/selectable.js',
			'requires'		=> 'ui-core,widget,mouse,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'selectmenu' => [
			'file'			=> '/include/thirdparty/jquery_ui/selectmenu.js',
			'requires'		=> 'ui-core,widget,position,menu,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'sortable' => [
			'file'			=> '/include/thirdparty/jquery_ui/sortable.js',
			'requires'		=> 'ui-core,widget,mouse',
			'package'		=> 'jquery_ui',
		],

		//jquery ui widgets
		'accordion' => [
			'file'			=> '/include/thirdparty/jquery_ui/accordion.js',
			'requires'		=> 'ui-core,widget,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'autocomplete' => [
			'file'			=> '/include/thirdparty/jquery_ui/autocomplete.js',
			'requires'		=> 'ui-core,widget,position,menu,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'button' => [
			'file'			=> '/include/thirdparty/jquery_ui/button.js',
			'requires'		=> 'ui-core,widget,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'datepicker' => [
			'file'			=> '/include/thirdparty/jquery_ui/datepicker.js',
			'requires'		=> 'ui-core,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'dialog' => [
			'file'			=> '/include/thirdparty/jquery_ui/dialog.js',
			'requires'		=> 'ui-core,widget,button,position,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'menu' => [
			'file'			=> '/include/thirdparty/jquery_ui/menu.js',
			'requires'		=> 'ui-core,widget,position,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'progressbar' => [
			'file'			=> '/include/thirdparty/jquery_ui/progressbar.js',
			'requires'		=> 'ui-core,widget,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'slider' => [
			'file'			=> '/include/thirdparty/jquery_ui/slider.js',
			'requires'		=> 'ui-core,widget,mouse,ui-theme',
			'package'		=> 'jquery_ui',
		],

		'tabs' => [
			'file'			=> '/include/thirdparty/jquery_ui/tabs.js',
			'requires'		=> 'ui-core,widget,ui-theme',
			'package'		=> 'jquery_ui',
		],

		//jquery ui effects
		'effects-core' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect.js',
			'package'		=> 'jquery_ui',
		],

		'blind' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-blind.js',
			'requires'		=> 'effects-core',
			'package'		=> 'jquery_ui',
		],

		'bounce' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-bounce.js',
			'requires'		=> 'effects-core',
			'package'		=> 'jquery_ui',
		],

		'clip' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-clip.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'drop' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-drop.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'explode' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-explode.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'fade' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-fade.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'fold' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-fold.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'highlight' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-highlight.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'puff' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-puff.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'pulsate' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-pulsate.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'scale' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-scale.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'shake' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-shake.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'size' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-size.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'slide' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-slide.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		'transfer' => [
			'file'			=> '/include/thirdparty/jquery_ui/effect-transfer.js',
			'requires'	 => ['effects-core'],
			'package'		=> 'jquery_ui',
		],

		//html5shiv
		'html5shiv' => [
			'file'			=> '/include/thirdparty/js/shiv/html5shiv.js',
			'label'			=> 'Html5Shiv',
			'cdn'			=> [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js',
			],
		],

		'printshiv' => [
			'file'			=> '/include/thirdparty/js/shiv/html5shiv-printshiv.js',
			'label'			=> 'PrintShiv',
			'cdn'			=> [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv-printshiv.min.js',
			],
		],

		//respond.js
		'respondjs' => [
			'file'			=> '/include/thirdparty/js/respond.min.js',
			'label'			=> 'Respond.js',
			'cdn'			=> [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js',
			],
		],

		//bootstrap 2.3.2 css (deprecated)
		'bootstrap-css' => [
			'file'			=> '/include/thirdparty/Bootstrap/css/bootstrap.min.css',
		],

		'bootstrap-responsive-css' => [
			'file'			=> '/include/thirdparty/Bootstrap/css/bootstrap-responsive.min.css',
			'requires'		=> 'bootstrap-css',
		],

		//bootstrap 2.3.2 js (deprecated)
		'bootstrap-alert' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-alert.js',
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-transition',
		],

		'bootstrap-button' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-button.js',
			'package'		=> 'bootstrap',
		],

		'bootstrap-carousel' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-carousel.js',
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-transition',
		],

		'bootstrap-collapse' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-collapse.js',
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-transition',
		],

		'bootstrap-dropdown' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-dropdown.js',
			'package'		=> 'bootstrap',
		],

		'bootstrap-modal' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-modal.js',
			'package'		=> 'bootstrap',
		],

		'bootstrap-popover' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-popover.js',
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-tooltip',
		],

		'bootstrap-scrollspy' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-scrollspy.js',
			'package'		=> 'bootstrap',
		],

		'bootstrap-tab' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-tab.js',
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-transition',
		],

		'bootstrap-tooltip' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-tooltip.js',
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-transition',
		],

		'bootstrap-transition' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-transition.js',
			'package'		=> 'bootstrap',
		],

		'bootstrap-typeahead' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap-typeahead.js',
			'package'		=> 'bootstrap',
		],

		'bootstrap-js' => [
			'file'			=> '/include/thirdparty/Bootstrap/js/bootstrap.min.js',
			'package'		=> 'bootstrap',
			'exclude'		=> 'bootstrap-alert,bootstrap-button,bootstrap-carousel,bootstrap-collapse,' .
								'bootstrap-dropdown,bootstrap-modal,bootstrap-popover,bootstrap-scrollspy,' .
								'bootstrap-tab,bootstrap-tooltip,bootstrap-transition,bootstrap-typeahead',
		],

		'bootstrap-all' => [
			'package'		=> 'bootstrap',
			'requires'		=> 'bootstrap-responsive-css,bootstrap-js',
		],

		// Bootstrap3
		'bootstrap3-js' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/bootstrap.min.js',
			'package'		=> 'bootstrap3',
			'exclude'		=> 'bootstrap3-affix,bootstrap3-alert,bootstrap3-button,bootstrap3-carousel,' .
								'bootstrap3-collapse,bootstrap3-dropdown,bootstrap3-modal,bootstrap3-popover,' .
								'bootstrap3-scrollspy,bootstrap3-tab,bootstrap3-tooltip,bootstrap3-transition',
		],

		'bootstrap3-affix' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/affix.js',
			'package'		=> 'bootstrap3',
		],

		'bootstrap3-alert' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/alert.js',
			'package'		=> 'bootstrap3',
			'requires'		=> 'bootstrap3-transition',
		],

		'bootstrap3-button' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/button.js',
			'package'		=> 'bootstrap3',
		],

		'bootstrap3-carousel' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/carousel.js',
			'package'		=> 'bootstrap3',
			'requires'		=> 'bootstrap3-transition',
		],

		'bootstrap3-collapse' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/collapse.js',
			'package'		=> 'bootstrap3',
			'requires'		=> 'bootstrap3-transition',
		],

		'bootstrap3-dropdown' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/dropdown.js',
			'package'		=> 'bootstrap3',
		],

		'bootstrap3-modal' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/modal.js',
			'package'		=> 'bootstrap3',
		],

		'bootstrap3-popover' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/popover.js',
			'package'		=> 'bootstrap3',
			'requires'		=> 'bootstrap3-tooltip',
		],

		'bootstrap3-scrollspy' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/scrollspy.js',
			'package'		=> 'bootstrap3',
		],

		'bootstrap3-tab' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/tab.js',
			'package'		=> 'bootstrap3',
			'requires'		=> 'bootstrap3-transition',
		],

		'bootstrap3-tooltip' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/tooltip.js',
			'package'		=> 'bootstrap3',
			'requires'		=> 'bootstrap3-transition',
		],

		'bootstrap3-transition' => [
			'file'			=> '/include/thirdparty/Bootstrap3/js/transition.js',
			'package'		=> 'bootstrap3',
		],

		// Bootstrap4
		'bootstrap4-js' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/bootstrap.bundle.min.js',
			'package'		=> 'bootstrap4',
			'exclude'		=> 'popper,bootstrap4-alert,bootstrap4-button,bootstrap4-carousel,bootstrap4-collapse,' .
								'bootstrap4-dropdown,bootstrap4-modal,bootstrap4-popover,bootstrap4-scrollspy,' .
								'bootstrap4-tab,bootstrap4-toast,bootstrap4-tooltip,bootstrap4-util',
		],

		'bootstrap4-alert' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/alert.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-button' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/button.min.js',
			'package'		=> 'bootstrap4',
		],

		'bootstrap4-carousel' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/carousel.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-collapse' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/collapse.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-dropdown' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/dropdown.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'popper,bootstrap4-util',
		],

		'bootstrap4-modal' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/modal.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-popover' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/popover.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-tooltip',
		],

		'bootstrap4-scrollspy' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/scrollspy.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-tab' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/tab.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-toast' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/toast.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'bootstrap4-util',
		],

		'bootstrap4-tooltip' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/tooltip.min.js',
			'package'		=> 'bootstrap4',
			'requires'		=> 'popper,bootstrap4-util',
		],

		'bootstrap4-util' => [
			'file'			=> '/include/thirdparty/Bootstrap4/js/util.min.js',
			'package'		=> 'bootstrap4',
		],


		// FontAwesome (4.7)
		'fontawesome'		=> [
			'file'			=> '/include/thirdparty/fontawesome/css/font-awesome.min.css',
			'label'			=> 'Font Awesome',
			'cdn'			=> [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
			],
		],

		// Colorbox (1.6.3)
		'colorbox' => [
			'file'			=> '/include/thirdparty/colorbox/colorbox/jquery.colorbox.js',
			'requires'		=> 'gp-main,colorbox-css',
			'label'			=> 'Colorbox JS',
			'cdn'			=> [
				'CloudFlare'	=> '//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.6.3/jquery.colorbox-min.js',
			],
		],

		'colorbox-css' => [
			'file'			=> '/include/thirdparty/colorbox/$config[colorbox_style]/colorbox.css',
			'type'			=> 'css',
		],

		// jQuery.dotdotdot (multi-line text truncation)
		'dotdotdot' => [
			'file'			=> '/include/thirdparty/dotdotdot/jquery.dotdotdot.js',
		],

		// Popper.js
		'popper' => [
			'file'			=> '/include/thirdparty/Popper.js/popper.min.js',
		],

		// Bootstrap colorpicker (v2.3.6, bootstrap deps removed)
		'colorpicker' => [
			'file'			=> '/include/thirdparty/bootstrap-colorpicker/bootstrap-colorpicker.min.js',
			'requires'		=> 'colorpicker-css',
		],
		'colorpicker-css' => [
			'file'			=> '/include/thirdparty/bootstrap-colorpicker/bootstrap-colorpicker.min.css',
		],

	];


	/**
	 * Generate a file with all of the combined content
	 *
	 */
	public static function GenerateFile($files,$type){

		$obj = new self( $files, $type);
		return $obj->Generate();
	}


	private $type;
	private $files;
	private $cache_relative;
	private $modified			= 0;
	private $content_len		= 0;
	private $had_imports		= false;
	private $import_data		= [];


	public function __construct($files, $type){

		$this->files	= $files;
		$this->type		= $type;

	}


	public function Generate(){
		global $dataDir;

		//get etag
		$full_paths = [];
		foreach($this->files as $file){
			$full_path = self::CheckFile($file);
			if( $full_path === false ){
				continue;
			}
			$full_paths[$file] = $full_path;
		}

		$this->FileStats($full_paths);

		//check css imports
		if( $this->type == 'css' ){
			$this->import_data	= \gp\tool\Files::Get('_cache/import_info', 'import_data');

			foreach($full_paths as $file => $full_path){
				if( !isset($this->import_data[$full_path]) ){
					continue;
				}
				$this->had_imported = true;
				$this->FileStats($this->import_data[$full_path]);
				unset($this->import_data[$full_path]);
			}
		}

		//check to see if file exists
		$etag				= \gp\tool::GenEtag( json_encode($this->files), $this->modified, $this->content_len );
		$cache_relative		= '/data/_cache/combined_' . $etag . '.' . $this->type;
		$cache_file			= $dataDir . $cache_relative;
		if( file_exists($cache_file) ){

			// change modified time to extend cache
			if( (time() - filemtime($cache_file)) > 604800 ){
				touch($cache_file);
			}

			return $cache_relative;
		}

		//create file
		if( $this->type == 'js' ){
			$combined_content = $this->CombineJS($full_paths);

		}else{
			$combined_content = $this->CombineCSS($full_paths);
		}

		if( !\gp\tool\Files::Save($cache_file,$combined_content) ){
			return false;
		}

		\gp\admin\Tools::CleanCache();

		return $cache_relative;
	}


	/**
	 * Combine CSS files
	 */
	public function CombineCSS($full_paths){

		$imports			= '';
		$combined_content	= '';
		$new_imported		= [];

		foreach($full_paths as $file => $full_path){
			$temp = new \gp\tool\Output\CombineCss($file);

			$combined_content .= "\n/* " . $file . " */\n";
			$combined_content .= $temp->content;
			$imports .= $temp->imports;
			if( count($temp->imported) ){
				$new_imported[$full_path] = $temp->imported;
			}
		}
		$combined_content = $imports . $combined_content;

		//save imported data
		if( count($new_imported) || $this->had_imports ){
			if( count($new_imported) ){
				$this->import_data = $new_imported + $this->import_data;
			}
			\gp\tool\Files::SaveData('_cache/import_info', 'import_data', $this->import_data);
		}

		return $combined_content;
	}


	/**
	 * Combine JS files
	 *
	 */
	public function CombineJS($full_paths){
		global $config;

		ob_start();
		\gp\tool::jsStart();

		foreach($full_paths as $full_path){
			readfile($full_path);
			echo ";\n";
		}
		$combined_content = ob_get_clean();

		//minify js
		if( $config['minifyjs'] ){

			$minify_stats = [
				'date'		=> date('Y-m-d H:i'),
				'errors'	=> 'none',
			];

			$minify_stats['mem_before'] = memory_get_peak_usage(true);
			$minify_stats['size_before'] = strlen($combined_content);

			try{
				$combined_content = \JShrink\Minifier::minify(
					$combined_content,
					['flaggedComments'=>false]
				);
			}catch( Exception $e ){
				$minify_stats['errors'] = $e->getMessage();
			}

			$minify_stats['mem_after'] = memory_get_peak_usage(true);
			$minify_stats['size_after'] = strlen($combined_content);

			$minify_stats['compression_rate'] = (
				round(
					(1 - $minify_stats['size_after'] / $minify_stats['size_before']) * 1000) / 10
				) .
			'%';

			$minify_stats['size_before'] = \gp\admin\Tools::FormatBytes($minify_stats['size_before']);
			$minify_stats['size_after'] = \gp\admin\Tools::FormatBytes($minify_stats['size_after']);

			$minify_stats['allocated_memory'] = \gp\admin\Tools::FormatBytes(
				$minify_stats['mem_after'] - $minify_stats['mem_before']
			);

			unset($minify_stats['mem_before'], $minify_stats['mem_after']);

			$combined_content = 'var minify_js_stats = ' . json_encode($minify_stats) . ';' . "\n\n" . $combined_content;
		}

		return $combined_content;
	}


	/**
	 * Make sure the file is a css or js file and that it exists
	 *
	 */
	public static function CheckFile(&$file){
		global $dataDir, $dirPrefix;
		$comment = "\n<!-- %s -->\n";

		$file = self::TrimQuery($file);

		if( empty($file) ){
			return false;
		}

		//translate addon paths
		$pos = strpos($file, '/data/_addoncode/');
		if( $pos !== false ){
			$file_parts		= substr($file, $pos + 17);
			$file_parts		= explode('/', $file_parts);
			$addon_key		= array_shift($file_parts);
			$addon_config	= \gp\tool\Plugins::GetAddonConfig($addon_key);
			if( $addon_config ){
				$file		= $addon_config['code_folder_rel'] . '/' . implode('/', $file_parts);
			}
		}

		//remove null charachters
		$file = \gp\tool\Files::NoNull($file);

		//require .js, or .css/.less/.scss
		$ext	= \gp\tool::Ext($file);
		if( $ext !== 'js' && $ext !== 'css' && $ext !== 'less' && $ext !== 'scss' ){
			echo sprintf($comment, 'File Not css, less, scss or js ' . $file);
			return false;
		}

		//paths that have been urlencoded
		if( strpos($file, '%') !== false ){
			$decoded_file = rawurldecode($file);
			if( $full_path = self::FixFilePath($decoded_file) ){
				$file		= $decoded_file;
				return $full_path;
			}
		}

		//paths that have not been encoded
		if( $full_path = self::FixFilePath($file) ){
			return $full_path;
		}

		echo sprintf($comment, 'File Not Found: '. $file);
		return false;
	}


	/**
	 * Change an incomplete path to a resolveable absolute file path
	 *
	 */
	public static function FixFilePath(&$file){
		global $dataDir, $dirPrefix;

		//realpath returns false if file does not exist
		$full_path = $dataDir . $file;
		if( file_exists($full_path) ){
			return realpath($full_path);
		}

		//check for paths that have already included $dirPrefix
		if( empty($dirPrefix) ){
			return false;
		}

		// remove $dirPrefix from the file path before adding $dataDir
		if( strpos($file,$dirPrefix) === 0 ){
			$fixed = substr($file, strlen($dirPrefix));
			$full_path = $dataDir . $fixed;
			if( file_exists($full_path) ){
				$file = $fixed;
				return realpath($full_path);
			}
		}

		return false;
	}


	/**
	 * Get the max modified time and sum of the content length of all the files
	 *
	 */
	public function FileStats( $files ){

		foreach($files as $file_path){
			$this->content_len += @filesize($file_path);
			if( strpos($file_path, '/data/_cache/') === false ){
				$this->modified = max($this->modified, @filemtime($file_path));
			}
		}
	}


	/**
	 * Remove the query off a file path
	 *
	 */
	public static function TrimQuery($file){
		$pos = mb_strpos($file, '?');
		if( $pos > 0 ){
			$file = mb_substr($file, 0, $pos);
		}
		return trim($file);
	}


	public static function ScriptInfo($components, $dependencies=true){
		global $config;
		static $root_call = true;
		if( is_string($components) ){
			$components = explode(',', strtolower($components));
			$components = array_unique($components);
		}

		self::$scripts['colorbox-css']['file'] = '/include/thirdparty/colorbox/' .
			$config['colorbox_style'] . '/colorbox.css';

		$all_scripts = [];

		//get all scripts
		foreach($components as $component){
			if( !array_key_exists($component, self::$scripts) ){
				$all_scripts[$component] = false;
				continue;
			}
			$script_info = self::$scripts[$component];
			if( $dependencies && isset($script_info['requires']) ){
				$is_root_call	= $root_call;
				$root_call		= false;
				$all_scripts	+= self::ScriptInfo($script_info['requires']);
				$root_call		= $is_root_call;
			}
			$all_scripts[$component] = self::$scripts[$component];
		}

		if( !$root_call ){
			return $all_scripts;
		}

		$all_scripts	= array_filter($all_scripts);
		$first_scripts	= [];

		//make sure jquery is the first
		if( array_key_exists('jquery', $all_scripts) ){
			$first_scripts['jquery'] = $all_scripts['jquery'];
		}

		// move any bootstrap components to front to prevent conflicts
		// hack for conflict between jquery ui button and bootstrap button
		foreach($all_scripts as $key => $script){
			if( !array_key_exists('package', $script) ){
				continue;
			}

			if( substr($script['package'], 0, 8) == 'bootstrap' ){
				$first_scripts[$key] = $script;
			}
		}

		$all_scripts	= $first_scripts + $all_scripts;

		$all_scripts	= self::RemoveExcludes($all_scripts);

		return self::OrganizeByType($all_scripts);
	}


	/**
	 * Remove Excludes
	 *
	 */
	public static function RemoveExcludes($scripts){

		$excludes = [];
		foreach($scripts as $key => $script){
			if( empty($script['exclude']) ){
				continue;
			}
			if( !is_array($script['exclude']) ){
				$script['exclude'] = explode(',', $script['exclude']);
			}
			$excludes = array_merge($excludes, $script['exclude']);
		}

		return array_diff_key($scripts, array_flip($excludes));
	}


	/**
	 * Organize $scripts by type
	 *
	 */
	public static function OrganizeByType($scripts){

		$return = ['js' => [], 'css' => []];

		foreach($scripts as $key => $script){
			if( empty($script['file']) ){
				continue;
			}

			if( gpdebug && !empty($script['dev']) ){
				$script['file'] = $script['dev'];
			}

			if( empty($script['type']) ){
				$script['type'] = \gp\tool::Ext($script['file']);
			}

			if( $script['type'] == 'less' || $script['type'] == 'scss' ){
				$script['type'] = 'css';
			}

			$return[$script['type']][$key] = $script;
		}

		return $return;
	}

}
