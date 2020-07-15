<?php

namespace gp\admin\Settings;

defined('is_running') or die('Not an entry point...');

class Classes extends \gp\special\Base{

	var $admin_link;

	function __construct($args){

		parent::__construct($args);

		$this->admin_link = \gp\tool::GetUrl('Admin/Classes');

		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'SaveClasses':
				$this->SaveClasses();
			break;
		}
		$this->ClassesForm();
	}


	/**
	 * Get the current classes
	 *
	 */
	public static function GetClasses(){

		$classes		= \gp\tool\Files::Get('_config/classes');
		if( $classes ){
			array_walk_recursive($classes, function($value){
				return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
			});
			return $classes;
		}

		//defaults
		return self::Defaults();
	}



	public static function Defaults(){
		return array(
			array(
				'names'		=> 'gpRow',
				'desc'		=> \CMS_NAME.' Grid Row - for wrapper sections',
			),
			array(
				'names'		=> 'gpCol-1 gpCol-2 gpCol-3 gpCol-4 gpCol-5 gpCol-6 gpCol-7 gpCol-8 gpCol-9 gpCol-10 gpCol-11 gpCol-12',
				'desc'		=> \CMS_NAME.' Grid Columns - for content sections',
			),
		);
	}



	public static function Bootstrap3(){
		return array (
			array (
				'names'		=> 'jumbotron',
				'desc'		=> 'Bootstrap: everything big for calling extra attention to some special content',
			),
			array (
				'names'		=> 'text-left text-center text-right text-justify',
				'desc'		=> 'Bootstrap: section text alignment',
			),
			array (
				'names'		=> 'text-muted text-primary text-success text-info text-warning text-danger',
				'desc'		=> 'Bootstrap text color classes: will color the entire text in the section (unless otherwise specified)',
			),
			array (
				'names'		=> 'bg-primary bg-success bg-info bg-warning bg-danger',
				'desc'		=> 'Bootstrap background color classes: darker backgrounds will also need e.g. text-white',
			),
			array (
				'names'		=> 'row container container-fluid',
				'desc'		=> 'Bootstrap Grid: use with Wrapper Sections',
			),
			array (
				'names'		=> 'col-xs-1 col-xs-2 col-xs-3 col-xs-4 col-xs-5 col-xs-6 col-xs-7 col-xs-8 col-xs-9 col-xs-10 col-xs-11 col-xs-12',
				'desc'		=> 'Bootstrap Grid: column width (mobile first)',
			),
			array (
				'names'		=> 'col-sm-1 col-sm-2 col-sm-3 col-sm-4 col-sm-5 col-sm-6 col-sm-7 col-sm-8 col-sm-9 col-sm-10 col-sm-11 col-sm-12',
				'desc'		=> 'Bootstrap Grid: column width on tablets (screen width ≥ 768px)',
			),
			array (
				'names'		=> 'col-md-1 col-md-2 col-md-3 col-md-4 col-md-5 col-md-6 col-md-7 col-md-8 col-md-9 col-md-10 col-md-11 col-md-12',
				'desc'		=> 'Bootstrap Grid: column width on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=> 'col-lg-1 col-lg-2 col-lg-3 col-lg-4 col-lg-5 col-lg-6 col-lg-7 col-lg-8 col-lg-9 col-lg-10 col-lg-11 col-lg-12',
				'desc'		=> 'Bootstrap Grid: column width on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=> 'col-xs-push-1 col-xs-push-2 col-xs-push-3 col-xs-push-4 col-xs-push-5 col-xs-push-6 col-xs-push-7 col-xs-push-8 col-xs-push-9 col-xs-push-10 col-xs-push-11',
				'desc'		=> 'Bootstrap Grid: push colum to the right (mobile first)',
			),
			array (
				'names'		=> 'col-sm-push-0 col-sm-push-1 col-sm-push-2 col-sm-push-3 col-sm-push-4 col-sm-push-5 col-sm-push-6 col-sm-push-7 col-sm-push-8 col-sm-push-9 col-sm-push-10 col-sm-push-11',
				'desc'		=> 'Bootstrap Grid: push colum to the right on tablets (screen width ≥ 768px)',
			),
			array (
				'names'		=> 'col-md-push-0 col-md-push-1 col-md-push-2 col-md-push-3 col-md-push-4 col-md-push-5 col-md-push-6 col-md-push-7 col-md-push-8 col-md-push-9 col-md-push-10 col-md-push-11',
				'desc'		=> 'Bootstrap Grid: push colum to the right on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=> 'col-lg-push-0 col-lg-push-1 col-lg-push-2 col-lg-push-3 col-lg-push-4 col-lg-push-5 col-lg-push-6 col-lg-push-7 col-lg-push-8 col-lg-push-9 col-lg-push-10 col-lg-push-11',
				'desc'		=> 'Bootstrap Grid: push colum to the right on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=> 'col-xs-pull-1 col-xs-pull-2 col-xs-pull-3 col-xs-pull-4 col-xs-pull-5 col-xs-pull-6 col-xs-pull-7 col-xs-pull-8 col-xs-pull-9 col-xs-pull-10 col-xs-pull-11',
				'desc'		=> 'Bootstrap Grid: pull colum to the left (mobile first)',
			),
			array (
				'names'		=> 'col-sm-pull-0 col-sm-pull-1 col-sm-pull-2 col-sm-pull-3 col-sm-pull-4 col-sm-pull-5 col-sm-pull-6 col-sm-pull-7 col-sm-pull-8 col-sm-pull-9 col-sm-pull-10 col-sm-pull-11',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on tablets (screen width ≥ 768px)',
			),
			array (
				'names'		=> 'col-md-pull-0 col-md-pull-1 col-md-pull-2 col-md-pull-3 col-md-pull-4 col-md-pull-5 col-md-pull-6 col-md-pull-7 col-md-pull-8 col-md-pull-9 col-md-pull-10 col-md-pull-11',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=> 'col-lg-pull-0 col-lg-pull-1 col-lg-pull-2 col-lg-pull-3 col-lg-pull-4 col-lg-pull-5 col-lg-pull-6 col-lg-pull-7 col-lg-pull-8 col-lg-pull-9 col-lg-pull-10 col-lg-pull-11',
				'desc'		=> 'Bootstrap Grid: pull colum to the left on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=> 'col-xs-offset-1 col-xs-offset-2 col-xs-offset-3 col-xs-offset-4 col-xs-offset-5 col-xs-offset-6 col-xs-offset-7 col-xs-offset-8 col-xs-offset-9 col-xs-offset-10 col-xs-offset-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right (mobile first)',
			),
			array (
				'names'		=> 'col-sm-offset-0 col-sm-offset-1 col-sm-offset-2 col-sm-offset-3 col-sm-offset-4 col-sm-offset-5 col-sm-offset-6 col-sm-offset-7 col-sm-offset-8 col-sm-offset-9 col-sm-offset-10 col-sm-offset-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on tablets (screen width ≥ 768px)',
			),
			array (
				'names'		=> 'col-md-offset-0 col-md-offset-1 col-md-offset-2 col-md-offset-3 col-md-offset-4 col-md-offset-5 col-md-offset-6 col-md-offset-7 col-md-offset-8 col-md-offset-9 col-md-offset-10 col-md-offset-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=> 'col-lg-offset-0 col-lg-offset-1 col-lg-offset-2 col-lg-offset-3 col-lg-offset-4 col-lg-offset-5 col-lg-offset-6 col-lg-offset-7 col-lg-offset-8 col-lg-offset-9 col-lg-offset-10 col-lg-offset-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=> 'visible-xs-block visible-sm-block visible-md-block visible-lg-block',
				'desc'		=> 'Bootstrap Visibility: using this classes will make the section visible <strong>only</strong> on the specified breakpoint / screen width (xs, sm, md or lg)',
			),
			array (
				'names'		=> 'hidden-xs hidden-sm hidden-md hidden-lg',
				'desc'		=> 'Bootstrap Visibility: using this classes will hide the section <strong>only</strong> on the specified breakpoint / screen width (xs, sm, md or lg)',
			),
		);
	}


	public static function Bootstrap4(){

		$cols_count = 12;
		$breakpoints = [ 'xs', 'sm', 'md', 'lg', 'xl' ];
		$colors		= [ 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark' ];
		$spacers	= range(0, 5);
		$margins	= array_merge(range(0, 5), ['auto', 'n1', 'n2', 'n3', 'n4', 'n5']);
		$cols		= array_merge([''], range(1, $cols_count), ['auto']);
		$offsets	= range(0, $cols_count-1);

		$bs4 = []; // array that will be returned

		function addSet(&$bs4, $desc, $name, $bps, $vals){ // $bs4 is passed by reference!
			$breakpoint_descs = [
				'xs' => '(mobile first)',
				'sm' => 'on large smartphones (screen width ≥ 576px)',
				'md' => 'on tablets (screen width ≥ 786px)',
				'lg' => 'on laptops (screen width ≥ 992px)',
				'xl' => 'on desktops (screen width ≥ 1200px)',
			];
			foreach( $bps as $i => $bp ){
				$names = [];
				$d = $desc;
				$bpn = $bp == 'xs' || $bp == '' ? '' : '-' . $bp;
				foreach( $vals as $val ){
					if( $bp == 'xs' && $name == 'offset' && $val == 0 ){
						continue;
					}
					$names[] = $name . $bpn . ($val !== '' ? '-' : '') . $val;
				}
				if( !empty($bp) && !empty($breakpoint_descs[$bp]) ){
					$d .= ' ' . $breakpoint_descs[$bp];
				}
				$bs4[] = [
					'names'	=> implode(' ', $names),
					'desc'	=> $d,
				];
			}
		}

		// text
		addSet(
			$bs4,
			'BS4: text alignment',
			'text',
			$breakpoints,
			['left', 'center', 'right', 'justify']
		);
		$bs4[] = [
			'names'	=> 'text-primary text-secondary text-success text-danger text-warning ' .
						 'text-info text-light text-dark text-white text-body text-muted ' .
						 'text-black-50 text-white-50 text-reset',
			'desc'	=> 'BS4 text utils: colors the entire text in the section (unless otherwise specified)',
		];
		$bs4[] = [
			'names'	=> 'font-weight-normal font-weight-bold font-weight-bolder font-weight-light font-weight-lighter',
			'desc'	=> 'BS4 text utils: apply different font weights',
		];
		$bs4[] = [
			'names'	=> 'font-italic',
			'desc'	=> 'BS4 text utils: use italic font style',
		];
		$bs4[] = [
			'names'	=> 'text-monospace',
			'desc'	=> 'BS4 text utils: use monospace font (stack) defined in variables.scss',
		];
		$bs4[] = [
			'names'	=> 'lead',
			'desc'	=> 'BS4 text utils: makes paragraphs <p> inside the section stand out. Does not influence headings and other elements with defined font sizes',
		];
		$bs4[] = [
			'names'	=> 'small',
			'desc'	=> 'BS4 text utils: makes text inside the section smaller. Does not influence headings and other elements with defined font sizes',
		];
		$bs4[] = [
			'names'	=> 'text-lowercase text-uppercase text-capitalize',
			'desc'	=> 'BS4 text utils: use text-transform to change case',
		];
		$bs4[] = [
			'names'	=> 'text-nowrap text-truncate',
			'desc'	=> 'BS4 text utils: prevent text from wrapping or truncate it',
		];
		$bs4[] = [
			'names'	=> 'text-break',
			'desc'	=> 'BS4 text utils: force long words to break at the section boundaries',
		];

		// background colors
		$bs4[] = [
			'names'	=> 'bg-primary bg-secondary bg-success bg-danger bg-warning bg-info bg-light bg-dark bg-white bg-transparent',
			'desc'	=> 'BS4 background colors: darker backgrounds will also need e.g. text-white',
		];

		/*
		// background gradients (disabled by default)
		$bs4[] = [
			'names'	=> 'bg-gradient-primary bg-gradient-secondary bg-gradient-success bg-gradient-danger bg-gradient-warning bg-gradient-info bg-gradient-light bg-gradient-dark',
			'desc'	=> 'BS4 background gradients: only works with $enable-gradients: true; in variables.scss'
		];
		*/

		// containers + rows
		$bs4[] = [
			'names'	=> 'row container container-fluid',
			'desc'	=> 'BS4 layout/grid: to be used with wrapper sections',
		];

		// row-cols
		addSet(
			$bs4,
			'BS4 grid: use together with ‘row’ to control how many col child sections appear next to each other',
			'row-cols',
			$breakpoints,
			range(1, $cols_count)
		);

		// no-gutters
		$bs4[] = [
			'names'	=> 'no-gutters',
			'desc'	=> 'BS4 grid: use together with ‘row’ to remove its negative margins ' .
						 'and the horizontal padding from all immediate child cols',
		];

		// columns
		addSet(
			$bs4,
			'BS4 grid: column width (in twelfths)',
			'col',
			$breakpoints,
			$cols
		);

		// offsets
		addSet(
			$bs4,
			'BS4 grid: offset a colum to the right (in twelfths)',
			'offset',
			$breakpoints,
			$offsets
		);

		// display
		addSet(
			$bs4,
			'BS4 display utils: e.g. use d-none to hide an element',
			'd',
			$breakpoints,
			['none', 'flex', 'inline-flex', 'block', 'inline', 'inline-block', 'table', 'table-cell', 'table-row']
		);

		/*
		// vertical-align
		// Disabled for being potentially misleading with sections,
		// which are very unlinkely to be inline level or table cells
		$bs4[] = [
			'names'	=> 'align-baseline align-top align-middle align-bottom align-text-bottom  align-text-top',
			'desc'	=> 'BS4 alignment utils: change vertical alignment of a section. ' .
						'Only works with d-inline, d-inline-block, d-inline-table or table-cell',
		];
		*/

		// flex
		addSet(
			$bs4,
			'BS4 flex utils: direction of flex items in a flex container',
			'flex',
			$breakpoints,
			['row', 'column', 'row-reverse', 'column-reverse']
		);
		addSet(
			$bs4,
			'BS4 flex utils: change how flex items wrap in a flex container',
			'flex',
			$breakpoints,
			['wrap', 'nowrap', 'wrap-reverse']
		);
		addSet(
			$bs4,
			'BS4 flex utils: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical)',
			'justify-content',
			$breakpoints,
			['start', 'end', 'center', 'between', 'around']
		);
		addSet(
			$bs4,
			'BS4 flex utils: changes how flex items align together on the cross axis (flex-row=vertical, flex-column=horizontal)',
			'align-content',
			$breakpoints,
			['start', 'end', 'center', 'around', 'stretch']
		);
		addSet(
			$bs4,
			'BS4 flex utils: change the alignment of flex items on the cross axis (flex-row=vertical, flex-column=horizontal)',
			'align-items',
			$breakpoints,
			['start', 'end', 'center', 'baseline', 'stretch']
		);
		addSet(
			$bs4,
			'BS4 flex utils: use on flexbox items to individually change their alignment on the cross axis',
			'align-self',
			$breakpoints,
			['start', 'end', 'center', 'baseline', 'stretch']
		);
		addSet(
			$bs4,
			'BS4 flex utils: use on series of sibling elements to force them into widths equal to their content (similar to table cells)',
			'flex',
			$breakpoints,
			['fill']
		);
		addSet(
			$bs4,
			'BS4 flex utils: toggle a flex item’s ability to grow to fill available space',
			'flex',
			$breakpoints,
			['grow-0', 'grow-1']
		);
		addSet(
			$bs4,
			'BS4 flex utils: toggle a flex item’s ability to shrink if necessary',
			'flex',
			$breakpoints,
			['shrink-0', 'shrink-1']
		);

		// cards
		$bs4[] = [
			'names'	=> 'card-columns card-deck card-group',
			'desc'	=> 'BS4 card layout wrappers: use for wrapper sections that contain ‘card’ sections. ' .
						'card-columns: a pinterest-like masonry, ' .
						'card-deck: grid of cards of equal height and width, ' .
						'card-group: similar to grid but without gutters',
		];
		$bs4[] = [
			'names'	=> 'card',
			'desc'	=> 'BS4 card element: use this class on wrapper sections',
		];
		$bs4[] = [
			'names'	=> 'card-header card-body card-footer',
			'desc'	=> 'BS4 card content: use for child sections inside wrapper sections with the ‘card’ class',
		];
		$bs4[] = [
			'names'	=> 'card-img card-img-top card-img-bottom',
			'desc'	=> 'BS4 card content: use for child image sections inside wrapper sections with the ‘card’ class',
		];
		$bs4[] = [
			'names'	=> 'card-img-overlay',
			'desc'	=> 'BS4 card content: use for child sections inside wrapper sections with the ‘card’ class. The section must follow a ‘card-image’ section so its content can overlay the image',
		];
		$bs4[] = [
			'names'	=> 'card-title card-subtitle card-text',
			'desc'	=> 'BS4 card content: use for child sections inside wrapper sections with the ‘card-header -body or -footer’ classes',
		];


		// alerts
		$bs4[] = [
			'names'	=> 'alert',
			'desc'	=> 'BS4 alert: a message-box-style section. Use together with alert-color classes',
		];
		addSet(
			$bs4,
			'BS4 alerts: color classes to be used together with the ‘alert’ class',
			'alert',
			[''],
			$colors
		);


		// overflow
		$bs4[] = [
			'names'	=> 'overflow-auto overflow-hidden',
			'desc'	=> 'BS4 utils: determines how content overflows the section',
		];

		// position
		$bs4[] = [
			'names'	=> 'position-relative position-absolute position-fixed position-sticky position-static fixed-top fixed-bottom sticky-top',
			'desc'	=> 'BS4 utils: determines the positioning of the section',
		];

		//paddings
		addSet(
			$bs4,
			'BS4 sizing utils: set padding on all 4 sides',
			'p',
			$breakpoints,
			$spacers
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set both padding-left and padding-right',
			'px',
			$breakpoints,
			$spacers
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set padding-left',
			'pl',
			$breakpoints,
			$spacers
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set padding-right',
			'pr',
			$breakpoints,
			$spacers
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set both padding-top and padding-bottom',
			'py',
			$breakpoints,
			$spacers
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set padding-top',
			'pt',
			$breakpoints,
			$spacers
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set padding-bottom',
			'pb',
			$breakpoints,
			$spacers
		);

		// margins
		addSet(
			$bs4,
			'BS4 sizing utils: set margin on all 4 sides',
			'm',
			$breakpoints,
			$margins
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set both margin-left and margin-right',
			'mx',
			$breakpoints,
			$margins
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set margin-left',
			'ml',
			$breakpoints,
			$margins
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set margin-right',
			'mr',
			$breakpoints,
			$margins
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set both margin-top and margin-bottom',
			'my',
			$breakpoints,
			$margins
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set margin-top',
			'mt',
			$breakpoints,
			$margins
		);
		addSet(
			$bs4,
			'BS4 sizing utils: set margin-bottom',
			'mb',
			$breakpoints,
			$margins
		);

		// width
		addSet(
			$bs4,
			'BS4 sizing utils: quickly define or override an element’s width',
			'w',
			$breakpoints,
			['25', '50', '75', '100', 'auto']
		);

		// max-width
		addSet(
			$bs4,
			'BS4 sizing utils: quickly define or override an element’s max-width',
			'w',
			$breakpoints,
			['100']
		);

		// height
		addSet(
			$bs4,
			'BS4 sizing utils: quickly define or override an element’s height',
			'h',
			$breakpoints,
			['25', '50', '75', '100', 'auto']
		);

		// max-height
		addSet(
			$bs4,
			'BS4 sizing utils: quickly define or override an element’s max-height',
			'h',
			$breakpoints,
			['100']
		);

		// order
		addSet(
			$bs4,
			'BS4 order utils: change the visual order of the section inside its wrapper',
			'order',
			$breakpoints,
			array_merge(['order-first', 'order-last'], range(0, 12))
		);

		// border
		$bs4[] = [
			'names'	=> 'border border-top border-right border-bottom border-left',
			'desc'	=> 'BS4 border utils: add borders to an element',
		];
		$bs4[] = [
			'names'	=> 'border-0 border-top-0 border-right-0 border-bottom-0 border-left-0',
			'desc'	=> 'BS4 border utils: subtract an element’s borders',
		];
		addSet(
			$bs4,
			'BS4 border utils: change the border color',
			'border',
			[''], // not responsive but we need to pass an array
			$colors
		);

		// border-radius
		$bs4[] = [
			'names'	=> 'rounded rounded-top rounded-right rounded-bottom rounded-left rounded-circle rounded-pill rounded-0',
			'desc'	=> 'BS4 border utils: easily round an element’s corners',
		];
		$bs4[] = [
			'names'	=> 'rounded-sm rounded-lg',
			'desc'	=> 'BS4 border utils: use for larger or smaller border-radius',
		];

		/*
		// shadows (disabled by default)
		$bs4[] = [
			'names'	=> 'shadow shadow-none shadow-sm shadow-lg',
			'desc'	=> 'BS4: change shadow display and size added via box-shadow utility classes. Requires $enable-shadows: true; in variables.scss',
		];
		*/

		// jumbotron
		$bs4[] = [
			'names'	=> 'jumbotron',
			'desc'	=> 'BS4: everything big for calling extra attention to some special content',
		];
		$bs4[] = [
			'names'	=> 'jumbotron-fluid',
			'desc'	=> 'BS4: combine with jumbotron for full-width sections without rounded corners',
		];

		// float
		addSet(
			$bs4,
			'BS4 float utils: toggle floats on the section',
			'float',
			$breakpoints,
			['left', 'right', 'none']
		);

		// clearfix
		$bs4[] = [
			'names'	=> 'clearfix',
			'desc'	=> 'BS clearfix: use for wrapper sections that contain floated child sections',
		];

		// visibility
		$bs4[] = [
			'names'	=> 'visible invisible',
			'desc'	=> 'BS4 visibility: control the visibility without modifying the display. Invisible elements will still take up space in the page',
		];

		// screen readers only
		$bs4[] = [
			'names'	=> 'sr-only',
			'desc'	=> 'BS4 screen reader utils: hide elements on all devices except screen readers',
		];
		$bs4[] = [
			'names'	=> 'sr-only-focusable',
			'desc'	=> 'BS4 screen reader utils: combine with sr-only to show the element again when it’s focused (e.g. via keyboard)',
		];

		// print
		$bs4[] = [
			'names'	=> 'd-print-none d-print-inline d-print-inline-block d-print-block d-print-table d-print-table-row d-print-table-cell d-print-flex d-print-inline-flex',
			'desc'	=> 'BS4 print utils: change the display value of elements when printing',
		];

		return $bs4;
	}



	/**
	 * Display form for selecting classes
	 *
	 */
	private function ClassesForm(){
		global $dataDir, $langmessage;

		echo '<h2 class="hmargin">' . $langmessage['Manage Classes'] . '</h2>';

		$cmd = \gp\tool::GetCommand();
		switch($cmd){
			case 'LoadDefault':
				$loaded_classes = self::Defaults();
			break;

			case 'LoadBootstrap3':
				$loaded_classes = self::Bootstrap3();
			break;

			case 'LoadBootstrap4':
				$loaded_classes = self::Bootstrap4();
			break;

			default:
				$loaded_classes = self::GetClasses();
			break;
		}

		$classes = self::GetClasses();

		$processing = !empty($_REQUEST['process']) ? $_REQUEST['process'] : 'load';

		switch($processing){
			case 'prepend':
				$classes = array_unique(array_merge($loaded_classes, $classes), SORT_REGULAR);
			break;

			case 'append':
				$classes = array_unique(array_merge($classes, $loaded_classes), SORT_REGULAR);
			break;

			case 'remove':
				$classes = array_udiff($classes, $loaded_classes, function($a, $b){
					return strcmp($a['names'], $b['names']);
				});
			break;

			case 'load':
				$classes = $loaded_classes;
			break;
		}

		// the following is not beautiful ;)
		if( $cmd && $cmd != 'SaveClasses'){
			msg('OK. <a style="cursor:pointer;" '
				. 'onclick="$(\'button[value=SaveClasses]\').trigger(\'click\')">'
				. $langmessage['save'] . '</a> (?)');
		}

		$classes[] = array('names'=>'','desc'=>'');


		$this->page->jQueryCode .= '$(".sortable_table").sortable({items : "tr",handle: "td"});';

		// FORM
		echo '<form action="' . $this->admin_link . '" method="post">';
		echo '<table class="bordered full_width sortable_table manage_classes_table">';
		echo '<thead><tr><th style="width:50%;">' . $langmessage['Classes'] . '</th><th>' . $langmessage['description'] . '</th></tr></thead>';
		echo '<tbody>';

		foreach( $classes as $key => $classArray ){
			echo '<tr><td class="manage_class_name">';
			echo '<img alt="" src="'.\gp\tool::GetDir('/include/imgs/drag_handle.gif').'" /> &nbsp; ';
			echo '<input size="32" class="gpinput" type="text" name="class_names[]" value="' . htmlspecialchars($classArray['names'],ENT_COMPAT,'UTF-8') . '"/>';
			echo '</td><td class="manage_class_desc">';
			echo '<input size="64" class="gpinput" type="text" name="class_desc[]" value="' . htmlspecialchars($classArray['desc'],ENT_COMPAT,'UTF-8') . '"/> ';
			echo '<a class="gpbutton rm_table_row" title="Remove Item" data-cmd="rm_table_row"><i class="fa fa-trash"></i></a>';
			echo '</td></tr>';
		}

		echo '<tr><td colspan="3">';
		echo '<a data-cmd="add_table_row">' . $langmessage['add'] . '</a>';
		echo '</td></tr>';
		echo '</tbody>';
		echo '</table>';

		// SAVE / CANCEL BUTTONS
		echo '<br/>';
		echo '<button type="submit" name="cmd" value="SaveClasses" class="gpsubmit">'.$langmessage['save'].'</button>';
		echo '<button type="submit" name="cmd" value="" class="gpcancel">'.$langmessage['cancel'].'</button>';

		echo '</form>';

		echo '<div class="classes-load-presets well">';
		echo $langmessage['Manage Classes Description'];
		echo '<form action="' . $this->admin_link . '" method="get">';

		echo	'<h4>' . $langmessage['Load'] . ', ' . $langmessage['Merge'] . ', ' . $langmessage['remove'] . '</h4>';

		echo	'<select class="gpselect" name="cmd">';
		echo		'<option value="LoadDefault">'		. $langmessage['The Default Preset'] . '</option> ';
		echo		'<option value="LoadBootstrap3">'	. sprintf($langmessage['The Bootstrap Preset'], '3') . '</option> ';
		echo		'<option value="LoadBootstrap4">'	. sprintf($langmessage['The Bootstrap Preset'], '4') . '</option> ';
		echo	'</select>';

		echo	'<button type="submit" name="process" value="load" class="gpsubmit">' . $langmessage['Load'] . '</button>';
		echo	'<button type="submit" name="process" value="prepend" class="gpsubmit">' . $langmessage['Prepend'] . '</button>';
		echo	'<button type="submit" name="process" value="append" class="gpsubmit">'  . $langmessage['Append'] . '</button>';
		echo	'<button type="submit" name="process" value="remove" class="gpsubmit">'  . $langmessage['remove'] . '</button>';

		echo '</form>';
		echo '</div>';

	}


	/**
	 * Save the posted data
	 *
	 */
	public function SaveClasses(){
		global $langmessage;

		$classes = array();
		foreach($_POST['class_names'] as $i => $class_names){

			$class_names = trim($class_names);
			if( empty($class_names) ){
				continue;
			}

			$classes[] = array(
				'names'		=> $class_names,
				'desc' 		=> $_POST['class_desc'][$i],
			);
		}


		if( \gp\tool\Files::SaveData('_config/classes','classes',$classes) ){
			msg($langmessage['SAVED']);
		}else{
			msg($langmessage['OOPS'].' (Not Saved)');
		}
	}


}
