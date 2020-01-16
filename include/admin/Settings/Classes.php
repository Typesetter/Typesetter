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
		return array (
			array (
				'names'		=>'text-left text-center text-right text-justify',
				'desc'		=> 'Bootstrap: text alignment (mobile first)',
			),
			array (
				'names'		=>'text-sm-left text-sm-center text-sm-right',
				'desc'		=> 'Bootstrap 4: responsive text alignment on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'text-md-left text-md-center text-md-right',
				'desc'		=> 'Bootstrap 4: responsive text alignment on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'text-lg-left text-lg-center text-lg-right',
				'desc'		=> 'Bootstrap 4: responsive text alignment on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'text-xl-left text-xl-center text-xl-right',
				'desc'		=> 'Bootstrap 4: responsive text alignment on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'text-primary text-secondary text-success text-danger text-warning text-info text-light text-dark text-body text-muted text-white text-black-50 text-white-50 text-reset',
				'desc'		=> 'Bootstrap text color classes: will colors the entire text in the section (unless otherwise specified)',
			),
			array (
				'names'		=>'bg-primary bg-secondary bg-success bg-danger bg-warning bg-info bg-light bg-dark bg-white bg-transparent',
				'desc'		=> 'Bootstrap background color classes: darker backgrounds will also need e.g. text-white',
			),
			array (
				'names'		=>'row container container-fluid',
				'desc'		=> 'Bootstrap Grid Layout: containers and rows (use with Wrapper Sections)',
			),
			array (
				'names'		=>'no-gutters',
				'desc'		=> 'Bootstrap 4 Grid: use this class together with the row class to remove the negative margins from the roew and the horizontal padding from all immediate children columns',
			),
			array (
				'names'		=>'col col-1 col-2 col-3 col-4 col-5 col-6 col-7 col-8 col-9 col-10 col-11 col-12 col-auto',
				'desc'		=> 'Bootstrap 4 Grid: column width (mobile first)',
			),
			array (
				'names'		=>'col-sm col-sm-1 col-sm-2 col-sm-3 col-sm-4 col-sm-5 col-sm-6 col-sm-7 col-sm-8 col-sm-9 col-sm-10 col-sm-11 col-sm-12 col-sm-auto',
				'desc'		=> 'Bootstrap Grid: column width on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'col-md col-md-1 col-md-2 col-md-3 col-md-4 col-md-5 col-md-6 col-md-7 col-md-8 col-md-9 col-md-10 col-md-11 col-md-12 col-md-auto',
				'desc'		=> 'Bootstrap Grid: column width on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'col-lg col-lg-1 col-lg-2 col-lg-3 col-lg-4 col-lg-5 col-lg-6 col-lg-7 col-lg-8 col-lg-9 col-lg-10 col-lg-11 col-lg-12 col-lg-auto',
				'desc'		=> 'Bootstrap Grid: column width on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'col-xl col-xl-1 col-xl-2 col-xl-3 col-xl-4 col-xl-5 col-xl-6 col-xl-7 col-xl-8 col-xl-9 col-xl-10 col-xl-11 col-xl-12 col-xl-auto',
				'desc'		=> 'Bootstrap 4 Grid: column width on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'offset-1 offset-2 offset-3 offset-4 offset-5 offset-6 offset-7 offset-8 offset-9 offset-10 offset-11 offset-12',
				'desc'		=> 'Bootstrap Grid: offset a colum to the right (mobile first)',
			),
			array (
				'names'		=>'offset-sm-0 offset-sm-1 offset-sm-2 offset-sm-3 offset-sm-4 offset-sm-5 offset-sm-6 offset-sm-7 offset-sm-8 offset-sm-9 offset-sm-10 offset-sm-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'offset-md-0 offset-md-1 offset-md-2 offset-md-3 offset-md-4 offset-md-5 offset-md-6 offset-md-7 offset-md-8 offset-md-9 offset-md-10 offset-md-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'offset-lg-0 offset-lg-1 offset-lg-2 offset-lg-3 offset-lg-4 offset-lg-5 offset-lg-6 offset-lg-7 offset-lg-8 offset-lg-9 offset-lg-10 offset-lg-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'offset-xl-0 offset-xl-1 offset-xl-2 offset-xl-3 offset-xl-4 offset-xl-5 offset-xl-6 offset-xl-7 offset-xl-8 offset-xl-9 offset-xl-10 offset-xl-11',
				'desc'		=> 'Bootstrap Grid: offset colum to the right on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'d-none d-flex d-inline-flex d-block d-inline d-inline-block d-table d-table-cell d-table-row',
				'desc'		=> 'Bootstrap 4 display utility classes: e.g. use d-none <strong>to hide</strong> an element (mobile first)',
			),
			array (
				'names'		=>'d-sm-none d-sm-flex d-sm-inline-flex d-sm-block d-sm-inline d-sm-inline-block d-sm-table d-sm-table-cell d-sm-table-row',
				'desc'		=> 'Bootstrap 4 display utility classes: display value on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'d-md-none d-md-flex d-md-inline-flex d-md-block d-md-inline d-md-inline-block d-md-table d-md-table-cell d-md-table-row',
				'desc'		=> 'Bootstrap 4 display utility classes: display value on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'d-lg-none d-lg-flex d-lg-inline-flex d-lg-block d-lg-inline d-lg-inline-block d-lg-table d-lg-table-cell d-lg-table-row',
				'desc'		=> 'Bootstrap 4 display utility classes: display value on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'d-xl-none d-xl-flex d-xl-inline-flex d-xl-block d-xl-inline d-xl-inline-block d-xl-table d-xl-table-cell d-xl-table-row',
				'desc'		=> 'Bootstrap 4 display utility classes: display value on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'flex-row flex-column flex-row-reverse flex-column-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: direction of flex items in a flex container (mobile-first)',
			),
			array (
				'names'		=>'flex-sm-row flex-sm-column flex-sm-row-reverse flex-sm-column-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: direction of flex items in a flex container on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'flex-md-row flex-md-column flex-md-row-reverse flex-md-column-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: direction of flex items in a flex container on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'flex-lg-row flex-lg-column flex-lg-row-reverse flex-lg-column-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: direction of flex items in a flex container on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'flex-xl-row flex-xl-column flex-xl-row-reverse flex-xl-column-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: direction of flex items in a flex container on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'flex-wrap flex-nowrap flex-wrap-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items wrap in a flex container (mobile first)',
			),
			array (
				'names'		=>'flex-sm-wrap flex-sm-nowrap flex-sm-wrap-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items wrap in a flex container on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'flex-md-wrap flex-md-nowrap flex-md-wrap-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items wrap in a flex container on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'flex-lg-wrap flex-lg-nowrap flex-lg-wrap-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items wrap in a flex container on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'flex-xl-wrap flex-xl-nowrap flex-xl-wrap-reverse',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items wrap in a flex container on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'justify-content-start justify-content-end justify-content-center justify-content-between justify-content-around',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical) (mobile first)',
			),
			array (
				'names'		=>'justify-content-sm-start justify-content-sm-end justify-content-sm-center justify-content-sm-between justify-content-sm-around',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the main axis on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'justify-content-md-start justify-content-md-end justify-content-md-center justify-content-md-between justify-content-md-around',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the main axis on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'justify-content-lg-start justify-content-lg-end justify-content-lg-center justify-content-lg-between justify-content-lg-around',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the main axis on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'justify-content-xl-start justify-content-xl-end justify-content-xl-center justify-content-xl-between justify-content-xl-around',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the main axis on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'align-items-start align-items-end align-items-center align-items-baseline align-items-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the cross axis (cross axis: flex-row=vertical, flex-column=horizontal) (mobile first)',
			),
			array (
				'names'		=>'align-items-sm-start align-items-sm-end align-items-sm-center align-items-sm-baseline align-items-sm-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the cross axis on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'align-items-md-start align-items-md-end align-items-md-center align-items-md-baseline align-items-md-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the cross axis on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'align-items-lg-start align-items-lg-end align-items-lg-center align-items-lg-baseline align-items-lg-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the cross axis on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'align-items-xl-start align-items-xl-end align-items-xl-center align-items-xl-baseline align-items-xl-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: change the alignment of flex items on the cross axis on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'align-content-start align-content-end align-content-center align-content-around align-content-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items <strong>align together on the cross axis</strong> (mobile first)',
			),
			array (
				'names'		=>'align-content-sm-start align-content-sm-end align-content-sm-center align-content-sm-around align-content-sm-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items align together (cross axis) on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'align-content-md-start align-content-md-end align-content-md-center align-content-md-around align-content-md-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items align together (cross axis) on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'align-content-lg-start align-content-lg-end align-content-lg-center align-content-lg-around align-content-lg-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items align together (cross axis) on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'align-content-xl-start align-content-xl-end align-content-xl-center align-content-xl-around align-content-xl-stretch',
				'desc'		=> 'Bootstrap 4 flex utility classes: changes how flex items align together (cross axis) on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'overflow-auto overflow-hidden',
				'desc'		=> 'Bootstrap 4 utility classes: determines how content overflows the section',
			),
			array (
				'names'		=>'position-relative fixed-top fixed-bottom sticky-top',
				'desc'		=> 'Bootstrap 4 utility classes: determines the positioning of the section',
			),
			array (
				'names'		=>'w-25 w-50 w-75 w-100 w-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s width (mobile first)',
			),
			array (
				'names'		=>'w-sm-25 w-sm-50 w-sm-75 w-sm-100 w-sm-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s width on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'w-md-25 w-md-50 w-md-75 w-md-100 w-md-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s width on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'w-lg-25 w-lg-50 w-lg-75 w-lg-100 w-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s width on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'w-xl-25 w-50 w-xl-75 w-xl-100 w-xl-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s width on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'h-25 h-50 h-75 h-100 h-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s height (mobile first)',
			),
			array (
				'names'		=>'h-sm-25 h-sm-50 h-sm-75 h-sm-100 h-sm-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s height large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'h-md-25 h-md-50 h-md-75 h-md-100 h-md-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s height on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'h-lg-25 h-lg-50 h-lg-75 h-lg-100 h-lg-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s height on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'h-xl-25 h-xl-50 h-xl-75 h-xl-100 h-xl-auto',
				'desc'		=> 'Bootstrap 4 sizing utility classes: quickly define or override an element\'s height on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'order-first order-last order-0 order-1 order-2 order-3 order-4 order-5 order-6 order-7 order-8 order-9 order-10 order-11 order-12',
				'desc'		=> 'Bootstrap 4 order utility classes: change the <em>visual</em> order of the section inside its wrapper (mobile first)',
			),
			array (
				'names'		=>'order-sm-first order-sm-last order-sm-0 order-sm-1 order-sm-2 order-sm-3 order-sm-4 order-sm-5 order-sm-6 order-sm-7 order-sm-8 order-sm-9 order-sm-10 order-sm-11 order-sm-12',
				'desc'		=> 'Bootstrap 4 order utility classes: change the <em>visual</em> order on large smartphones (screen width ≥ 576px)',
			),
			array (
				'names'		=>'order-md-first order-md-last order-md-0 order-md-1 order-md-2 order-md-3 order-md-4 order-md-5 order-md-6 order-md-7 order-md-8 order-md-9 order-md-10 order-md-11 order-md-12',
				'desc'		=> 'Bootstrap 4 order utility classes: change the <em>visual</em> order on tablets (screen width ≥ 786px)',
			),
			array (
				'names'		=>'order-lg-first order-lg-last order-lg-0 order-lg-1 order-lg-2 order-lg-3 order-lg-4 order-lg-5 order-lg-6 order-lg-7 order-lg-8 order-lg-9 order-lg-10 order-lg-11 order-lg-12',
				'desc'		=> 'Bootstrap 4 order utility classes: change the <em>visual</em> order on laptops (screen width ≥ 992px)',
			),
			array (
				'names'		=>'order-xl-first order-xl-last order-xl-0 order-xl-1 order-xl-2 order-xl-3 order-xl-4 order-xl-5 order-xl-6 order-xl-7 order-xl-8 order-xl-9 order-xl-10 order-xl-11 order-xl-12',
				'desc'		=> 'Bootstrap 4 order utility classes: change the <em>visual</em> order on desktops (screen width ≥ 1200px)',
			),
			array (
				'names'		=>'jumbotron jumbotron-fluid',
				'desc'		=> 'Bootstrap: everything big for calling extra attention to some special content',
			),
			array (
				'names'		=>'sr-only sr-only-focusable',
				'desc'		=> 'Bootstrap 4 screen reader utilities: hide elements on all devices except screen readers',
			),
			array (
				'names'		=>'float-left float-right float-none',
				'desc'		=> 'Bootstrap 4 float utility classes: toggle floats on the section',
			),
			array (
				'names'		=>'clearfix',
				'desc'		=> 'Bootstrap clearfix: clear floated content within a container',
			),
		);
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
				$classes = array_merge($loaded_classes, $classes);
			break;

			case 'append':
				$classes = array_merge($classes, $loaded_classes);
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

		echo '<br/>';

		// SAVE / CANCEL BUTTONS
		echo '<button type="submit" name="cmd" value="SaveClasses" class="gpsubmit">'.$langmessage['save'].'</button>';
		echo '<button type="submit" name="cmd" value="" class="gpcancel">'.$langmessage['cancel'].'</button>';
		echo '</form>';

		echo '<div class="classes-load-presets well">';
		echo $langmessage['Manage Classes Description'];
		echo '<form action="' . $this->admin_link . '" method="get">';

		echo	'<h4>' . $langmessage['Load'] . ', ' . $langmessage['Merge'] . ', ' . $langmessage['remove'] . '</h4>';

		echo	'<select class="gpselect" name="cmd">';
		echo		'<option value="LoadBootstrap3">'	. sprintf($langmessage['The Bootstrap Preset'], '3') . '</option> ';
		echo		'<option value="LoadBootstrap4">'	. sprintf($langmessage['The Bootstrap Preset'], '4') . '</option> ';
		echo		'<option value="LoadDefault">'		. $langmessage['The Default Preset'] . '</option> ';
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
