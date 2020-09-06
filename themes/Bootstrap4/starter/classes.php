<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'footer' layout
 * 'Available Classes' definition
 */

defined('is_running') or die('Not an entry point...');

$classes = [

	[
		'names' => 'text-left text-center text-right text-justify',
		'desc' => 'BS4: text alignment (mobile first)',
	],

	[
		'names' => 'text-sm-left text-sm-center text-sm-right text-sm-justify',
		'desc' => 'BS4: text alignment on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'text-md-left text-md-center text-md-right text-md-justify',
		'desc' => 'BS4: text alignment on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'text-lg-left text-lg-center text-lg-right text-lg-justify',
		'desc' => 'BS4: text alignment on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'text-xl-left text-xl-center text-xl-right text-xl-justify',
		'desc' => 'BS4: text alignment on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'text-primary text-secondary text-success text-danger text-warning text-info text-light text-dark text-white text-body text-muted text-black-50 text-white-50 text-reset',
		'desc' => 'BS4 text utils: colors the entire text in the section (unless otherwise specified)',
	],

	[
		'names' => 'font-weight-normal font-weight-bold font-weight-bolder font-weight-light font-weight-lighter',
		'desc' => 'BS4 text utils: apply different font weights',
	],

	[
		'names' => 'font-italic',
		'desc' => 'BS4 text utils: use italic font style',
	],

	[
		'names' => 'text-monospace',
		'desc' => 'BS4 text utils: use monospace font (stack) defined in variables.scss',
	],

	[
		'names' => 'lead',
		'desc' => 'BS4 text utils: makes paragraphs <p> inside the section stand out. Does not influence headings and other elements with defined font sizes',
	],

	[
		'names' => 'small',
		'desc' => 'BS4 text utils: makes text inside the section smaller. Does not influence headings and other elements with defined font sizes',
	],

	[
		'names' => 'text-lowercase text-uppercase text-capitalize',
		'desc' => 'BS4 text utils: use text-transform to change case',
	],

	[
		'names' => 'text-nowrap text-truncate',
		'desc' => 'BS4 text utils: prevent text from wrapping or truncate it',
	],

	[
		'names' => 'text-break',
		'desc' => 'BS4 text utils: prevent long text from breaking layout',
	],

	[
		'names' => 'bg-primary bg-secondary bg-success bg-danger bg-warning bg-info bg-light bg-dark bg-transparent ' .
					'bg-blue bg-indigo bg-purple bg-pink bg-red bg-orange bg-yellow bg-green bg-teal bg-cyan ' .
					'bg-white bg-gray-100 bg-gray-200 bg-gray-300 bg-gray-400 bg-gray-500 bg-gray-600 bg-gray-700 bg-gray-800 bg-gray-900 bg-black',
		'desc' => 'BS4 background colors: darker backgrounds will also need e.g. text-white',
	],

	[
		'names' => 'row container container-fluid',
		'desc' => 'BS4 layout/grid: to be used with wrapper sections',
	],

	[
		'names' => 'row-cols-1 row-cols-2 row-cols-3 row-cols-4 row-cols-5 row-cols-6 row-cols-7 row-cols-8 row-cols-9 row-cols-10 row-cols-11 row-cols-12',
		'desc' => 'BS4 grid: use together with ‘row’ to control how many col child sections appear next to each other (mobile first)',
	],

	[
		'names' => 'row-cols-sm-1 row-cols-sm-2 row-cols-sm-3 row-cols-sm-4 row-cols-sm-5 row-cols-sm-6 row-cols-sm-7 row-cols-sm-8 row-cols-sm-9 row-cols-sm-10 row-cols-sm-11 row-cols-sm-12',
		'desc' => 'BS4 grid: use together with ‘row’ to control how many col child sections appear next to each other on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'row-cols-md-1 row-cols-md-2 row-cols-md-3 row-cols-md-4 row-cols-md-5 row-cols-md-6 row-cols-md-7 row-cols-md-8 row-cols-md-9 row-cols-md-10 row-cols-md-11 row-cols-md-12',
		'desc' => 'BS4 grid: use together with ‘row’ to control how many col child sections appear next to each other on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'row-cols-lg-1 row-cols-lg-2 row-cols-lg-3 row-cols-lg-4 row-cols-lg-5 row-cols-lg-6 row-cols-lg-7 row-cols-lg-8 row-cols-lg-9 row-cols-lg-10 row-cols-lg-11 row-cols-lg-12',
		'desc' => 'BS4 grid: use together with ‘row’ to control how many col child sections appear next to each other on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'row-cols-xl-1 row-cols-xl-2 row-cols-xl-3 row-cols-xl-4 row-cols-xl-5 row-cols-xl-6 row-cols-xl-7 row-cols-xl-8 row-cols-xl-9 row-cols-xl-10 row-cols-xl-11 row-cols-xl-12',
		'desc' => 'BS4 grid: use together with ‘row’ to control how many col child sections appear next to each other on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'no-gutters',
		'desc' => 'BS4 grid: use together with ‘row’ to remove its negative margins and the horizontal padding from all immediate child cols',
	],

	[
		'names' => 'col col-1 col-2 col-3 col-4 col-5 col-6 col-7 col-8 col-9 col-10 col-11 col-12 col-auto',
		'desc' => 'BS4 grid: column width (in twelfths) (mobile first)',
	],

	[
		'names' => 'col-sm col-sm-1 col-sm-2 col-sm-3 col-sm-4 col-sm-5 col-sm-6 col-sm-7 col-sm-8 col-sm-9 col-sm-10 col-sm-11 col-sm-12 col-sm-auto',
		'desc' => 'BS4 grid: column width (in twelfths) on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'col-md col-md-1 col-md-2 col-md-3 col-md-4 col-md-5 col-md-6 col-md-7 col-md-8 col-md-9 col-md-10 col-md-11 col-md-12 col-md-auto',
		'desc' => 'BS4 grid: column width (in twelfths) on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'col-lg col-lg-1 col-lg-2 col-lg-3 col-lg-4 col-lg-5 col-lg-6 col-lg-7 col-lg-8 col-lg-9 col-lg-10 col-lg-11 col-lg-12 col-lg-auto',
		'desc' => 'BS4 grid: column width (in twelfths) on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'col-xl col-xl-1 col-xl-2 col-xl-3 col-xl-4 col-xl-5 col-xl-6 col-xl-7 col-xl-8 col-xl-9 col-xl-10 col-xl-11 col-xl-12 col-xl-auto',
		'desc' => 'BS4 grid: column width (in twelfths) on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'offset-1 offset-2 offset-3 offset-4 offset-5 offset-6 offset-7 offset-8 offset-9 offset-10 offset-11',
		'desc' => 'BS4 grid: offset a colum to the right (in twelfths) (mobile first)',
	],

	[
		'names' => 'offset-sm-0 offset-sm-1 offset-sm-2 offset-sm-3 offset-sm-4 offset-sm-5 offset-sm-6 offset-sm-7 offset-sm-8 offset-sm-9 offset-sm-10 offset-sm-11',
		'desc' => 'BS4 grid: offset a colum to the right (in twelfths) on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'offset-md-0 offset-md-1 offset-md-2 offset-md-3 offset-md-4 offset-md-5 offset-md-6 offset-md-7 offset-md-8 offset-md-9 offset-md-10 offset-md-11',
		'desc' => 'BS4 grid: offset a colum to the right (in twelfths) on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'offset-lg-0 offset-lg-1 offset-lg-2 offset-lg-3 offset-lg-4 offset-lg-5 offset-lg-6 offset-lg-7 offset-lg-8 offset-lg-9 offset-lg-10 offset-lg-11',
		'desc' => 'BS4 grid: offset a colum to the right (in twelfths) on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'offset-xl-0 offset-xl-1 offset-xl-2 offset-xl-3 offset-xl-4 offset-xl-5 offset-xl-6 offset-xl-7 offset-xl-8 offset-xl-9 offset-xl-10 offset-xl-11',
		'desc' => 'BS4 grid: offset a colum to the right (in twelfths) on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'd-none d-flex d-inline-flex d-block d-inline d-inline-block d-table d-table-cell d-table-row',
		'desc' => 'BS4 display utils: e.g. use d-none to hide an element (mobile first)',
	],

	[
		'names' => 'd-sm-none d-sm-flex d-sm-inline-flex d-sm-block d-sm-inline d-sm-inline-block d-sm-table d-sm-table-cell d-sm-table-row',
		'desc' => 'BS4 display utils: e.g. use d-none to hide an element on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'd-md-none d-md-flex d-md-inline-flex d-md-block d-md-inline d-md-inline-block d-md-table d-md-table-cell d-md-table-row',
		'desc' => 'BS4 display utils: e.g. use d-none to hide an element on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'd-lg-none d-lg-flex d-lg-inline-flex d-lg-block d-lg-inline d-lg-inline-block d-lg-table d-lg-table-cell d-lg-table-row',
		'desc' => 'BS4 display utils: e.g. use d-none to hide an element on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'd-xl-none d-xl-flex d-xl-inline-flex d-xl-block d-xl-inline d-xl-inline-block d-xl-table d-xl-table-cell d-xl-table-row',
		'desc' => 'BS4 display utils: e.g. use d-none to hide an element on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'flex-row flex-column flex-row-reverse flex-column-reverse',
		'desc' => 'BS4 flex utils: direction of flex items in a flex container (mobile first)',
	],

	[
		'names' => 'flex-sm-row flex-sm-column flex-sm-row-reverse flex-sm-column-reverse',
		'desc' => 'BS4 flex utils: direction of flex items in a flex container on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'flex-md-row flex-md-column flex-md-row-reverse flex-md-column-reverse',
		'desc' => 'BS4 flex utils: direction of flex items in a flex container on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'flex-lg-row flex-lg-column flex-lg-row-reverse flex-lg-column-reverse',
		'desc' => 'BS4 flex utils: direction of flex items in a flex container on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'flex-xl-row flex-xl-column flex-xl-row-reverse flex-xl-column-reverse',
		'desc' => 'BS4 flex utils: direction of flex items in a flex container on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'flex-wrap flex-nowrap flex-wrap-reverse',
		'desc' => 'BS4 flex utils: change how flex items wrap in a flex container (mobile first)',
	],

	[
		'names' => 'flex-sm-wrap flex-sm-nowrap flex-sm-wrap-reverse',
		'desc' => 'BS4 flex utils: change how flex items wrap in a flex container on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'flex-md-wrap flex-md-nowrap flex-md-wrap-reverse',
		'desc' => 'BS4 flex utils: change how flex items wrap in a flex container on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'flex-lg-wrap flex-lg-nowrap flex-lg-wrap-reverse',
		'desc' => 'BS4 flex utils: change how flex items wrap in a flex container on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'flex-xl-wrap flex-xl-nowrap flex-xl-wrap-reverse',
		'desc' => 'BS4 flex utils: change how flex items wrap in a flex container on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'justify-content-start justify-content-end justify-content-center justify-content-between justify-content-around',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical) (mobile first)',
	],

	[
		'names' => 'justify-content-sm-start justify-content-sm-end justify-content-sm-center justify-content-sm-between justify-content-sm-around',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical) on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'justify-content-md-start justify-content-md-end justify-content-md-center justify-content-md-between justify-content-md-around',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical) on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'justify-content-lg-start justify-content-lg-end justify-content-lg-center justify-content-lg-between justify-content-lg-around',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical) on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'justify-content-xl-start justify-content-xl-end justify-content-xl-center justify-content-xl-between justify-content-xl-around',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the main axis (flex-row=horizontal, flex-column=vertical) on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'align-content-start align-content-end align-content-center align-content-around align-content-stretch',
		'desc' => 'BS4 flex utils: changes how flex items align together on the cross axis (flex-row=vertical, flex-column=horizontal) (mobile first)',
	],

	[
		'names' => 'align-content-sm-start align-content-sm-end align-content-sm-center align-content-sm-around align-content-sm-stretch',
		'desc' => 'BS4 flex utils: changes how flex items align together on the cross axis (flex-row=vertical, flex-column=horizontal) on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'align-content-md-start align-content-md-end align-content-md-center align-content-md-around align-content-md-stretch',
		'desc' => 'BS4 flex utils: changes how flex items align together on the cross axis (flex-row=vertical, flex-column=horizontal) on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'align-content-lg-start align-content-lg-end align-content-lg-center align-content-lg-around align-content-lg-stretch',
		'desc' => 'BS4 flex utils: changes how flex items align together on the cross axis (flex-row=vertical, flex-column=horizontal) on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'align-content-xl-start align-content-xl-end align-content-xl-center align-content-xl-around align-content-xl-stretch',
		'desc' => 'BS4 flex utils: changes how flex items align together on the cross axis (flex-row=vertical, flex-column=horizontal) on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'align-items-start align-items-end align-items-center align-items-baseline align-items-stretch',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the cross axis (flex-row=vertical, flex-column=horizontal) (mobile first)',
	],

	[
		'names' => 'align-items-sm-start align-items-sm-end align-items-sm-center align-items-sm-baseline align-items-sm-stretch',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the cross axis (flex-row=vertical, flex-column=horizontal) on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'align-items-md-start align-items-md-end align-items-md-center align-items-md-baseline align-items-md-stretch',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the cross axis (flex-row=vertical, flex-column=horizontal) on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'align-items-lg-start align-items-lg-end align-items-lg-center align-items-lg-baseline align-items-lg-stretch',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the cross axis (flex-row=vertical, flex-column=horizontal) on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'align-items-xl-start align-items-xl-end align-items-xl-center align-items-xl-baseline align-items-xl-stretch',
		'desc' => 'BS4 flex utils: change the alignment of flex items on the cross axis (flex-row=vertical, flex-column=horizontal) on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'align-self-start align-self-end align-self-center align-self-baseline align-self-stretch',
		'desc' => 'BS4 flex utils: use on flexbox items to individually change their alignment on the cross axis (mobile first)',
	],

	[
		'names' => 'align-self-sm-start align-self-sm-end align-self-sm-center align-self-sm-baseline align-self-sm-stretch',
		'desc' => 'BS4 flex utils: use on flexbox items to individually change their alignment on the cross axis on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'align-self-md-start align-self-md-end align-self-md-center align-self-md-baseline align-self-md-stretch',
		'desc' => 'BS4 flex utils: use on flexbox items to individually change their alignment on the cross axis on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'align-self-lg-start align-self-lg-end align-self-lg-center align-self-lg-baseline align-self-lg-stretch',
		'desc' => 'BS4 flex utils: use on flexbox items to individually change their alignment on the cross axis on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'align-self-xl-start align-self-xl-end align-self-xl-center align-self-xl-baseline align-self-xl-stretch',
		'desc' => 'BS4 flex utils: use on flexbox items to individually change their alignment on the cross axis on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'flex-fill',
		'desc' => 'BS4 flex utils: use on series of sibling elements to force them into widths equal to their content (similar to table cells) (mobile first)',
	],

	[
		'names' => 'flex-sm-fill',
		'desc' => 'BS4 flex utils: use on series of sibling elements to force them into widths equal to their content (similar to table cells) on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'flex-md-fill',
		'desc' => 'BS4 flex utils: use on series of sibling elements to force them into widths equal to their content (similar to table cells) on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'flex-lg-fill',
		'desc' => 'BS4 flex utils: use on series of sibling elements to force them into widths equal to their content (similar to table cells) on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'flex-xl-fill',
		'desc' => 'BS4 flex utils: use on series of sibling elements to force them into widths equal to their content (similar to table cells) on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'flex-grow-0 flex-grow-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to grow to fill available space (mobile first)',
	],

	[
		'names' => 'flex-sm-grow-0 flex-sm-grow-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to grow to fill available space on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'flex-md-grow-0 flex-md-grow-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to grow to fill available space on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'flex-lg-grow-0 flex-lg-grow-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to grow to fill available space on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'flex-xl-grow-0 flex-xl-grow-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to grow to fill available space on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'flex-shrink-0 flex-shrink-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to shrink if necessary (mobile first)',
	],

	[
		'names' => 'flex-sm-shrink-0 flex-sm-shrink-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to shrink if necessary on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'flex-md-shrink-0 flex-md-shrink-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to shrink if necessary on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'flex-lg-shrink-0 flex-lg-shrink-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to shrink if necessary on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'flex-xl-shrink-0 flex-xl-shrink-1',
		'desc' => 'BS4 flex utils: toggle a flex item’s ability to shrink if necessary on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'card-columns card-deck card-group',
		'desc' => 'BS4 card layout wrappers: use for wrapper sections that contain ‘card’ sections. card-columns: a pinterest-like masonry, card-deck: grid of cards of equal height and width, card-group: similar to grid but without gutters',
	],

	[
		'names' => 'card',
		'desc' => 'BS4 card element: use this class on wrapper sections',
	],

	[
		'names' => 'card-header card-body card-footer',
		'desc' => 'BS4 card content: use for child sections inside wrapper sections with the ‘card’ class',
	],

	[
		'names' => 'card-img card-img-top card-img-bottom',
		'desc' => 'BS4 card content: use for child image sections inside wrapper sections with the ‘card’ class',
	],

	[
		'names' => 'card-img-overlay',
		'desc' => 'BS4 card content: use for child sections inside wrapper sections with the ‘card’ class. The section must follow a ‘card-image’ section so its content can overlay the image',
	],

	[
		'names' => 'card-title card-subtitle card-text',
		'desc' => 'BS4 card content: use for child sections inside wrapper sections with the ‘card-header -body or -footer’ classes',
	],

	[
		'names' => 'alert',
		'desc' => 'BS4 alert: a message box style content type',
	],

	[
		'names' => 'alert-primary alert-secondary alert-success alert-danger alert-warning alert-info alert-light alert-dark',
		'desc' => 'BS4 alerts: box color styles, use torgether with the ‘alert’ class',
	],

	[
		'names' => 'overflow-auto overflow-hidden',
		'desc' => 'BS4 utils: determines how content overflows the section',
	],

	[
		'names' => 'position-relative position-absolute position-fixed position-sticky position-static fixed-top fixed-bottom sticky-top',
		'desc' => 'BS4 utils: determines the positioning of the section',
	],

	[
		'names' => 'p-0 p-1 p-2 p-3 p-4 p-5',
		'desc' => 'BS4 sizing utils: set padding on all 4 sides (mobile first)',
	],

	[
		'names' => 'p-sm-0 p-sm-1 p-sm-2 p-sm-3 p-sm-4 p-sm-5',
		'desc' => 'BS4 sizing utils: set padding on all 4 sides on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'p-md-0 p-md-1 p-md-2 p-md-3 p-md-4 p-md-5',
		'desc' => 'BS4 sizing utils: set padding on all 4 sides on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'p-lg-0 p-lg-1 p-lg-2 p-lg-3 p-lg-4 p-lg-5',
		'desc' => 'BS4 sizing utils: set padding on all 4 sides on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'p-xl-0 p-xl-1 p-xl-2 p-xl-3 p-xl-4 p-xl-5',
		'desc' => 'BS4 sizing utils: set padding on all 4 sides on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'px-0 px-1 px-2 px-3 px-4 px-5',
		'desc' => 'BS4 sizing utils: set both padding-left and padding-right (mobile first)',
	],

	[
		'names' => 'px-sm-0 px-sm-1 px-sm-2 px-sm-3 px-sm-4 px-sm-5',
		'desc' => 'BS4 sizing utils: set both padding-left and padding-right on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'px-md-0 px-md-1 px-md-2 px-md-3 px-md-4 px-md-5',
		'desc' => 'BS4 sizing utils: set both padding-left and padding-right on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'px-lg-0 px-lg-1 px-lg-2 px-lg-3 px-lg-4 px-lg-5',
		'desc' => 'BS4 sizing utils: set both padding-left and padding-right on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'px-xl-0 px-xl-1 px-xl-2 px-xl-3 px-xl-4 px-xl-5',
		'desc' => 'BS4 sizing utils: set both padding-left and padding-right on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'pl-0 pl-1 pl-2 pl-3 pl-4 pl-5',
		'desc' => 'BS4 sizing utils: set padding-left (mobile first)',
	],

	[
		'names' => 'pl-sm-0 pl-sm-1 pl-sm-2 pl-sm-3 pl-sm-4 pl-sm-5',
		'desc' => 'BS4 sizing utils: set padding-left on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'pl-md-0 pl-md-1 pl-md-2 pl-md-3 pl-md-4 pl-md-5',
		'desc' => 'BS4 sizing utils: set padding-left on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'pl-lg-0 pl-lg-1 pl-lg-2 pl-lg-3 pl-lg-4 pl-lg-5',
		'desc' => 'BS4 sizing utils: set padding-left on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'pl-xl-0 pl-xl-1 pl-xl-2 pl-xl-3 pl-xl-4 pl-xl-5',
		'desc' => 'BS4 sizing utils: set padding-left on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'pr-0 pr-1 pr-2 pr-3 pr-4 pr-5',
		'desc' => 'BS4 sizing utils: set padding-right (mobile first)',
	],

	[
		'names' => 'pr-sm-0 pr-sm-1 pr-sm-2 pr-sm-3 pr-sm-4 pr-sm-5',
		'desc' => 'BS4 sizing utils: set padding-right on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'pr-md-0 pr-md-1 pr-md-2 pr-md-3 pr-md-4 pr-md-5',
		'desc' => 'BS4 sizing utils: set padding-right on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'pr-lg-0 pr-lg-1 pr-lg-2 pr-lg-3 pr-lg-4 pr-lg-5',
		'desc' => 'BS4 sizing utils: set padding-right on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'pr-xl-0 pr-xl-1 pr-xl-2 pr-xl-3 pr-xl-4 pr-xl-5',
		'desc' => 'BS4 sizing utils: set padding-right on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'py-0 py-1 py-2 py-3 py-4 py-5',
		'desc' => 'BS4 sizing utils: set both padding-top and padding-bottom (mobile first)',
	],

	[
		'names' => 'py-sm-0 py-sm-1 py-sm-2 py-sm-3 py-sm-4 py-sm-5',
		'desc' => 'BS4 sizing utils: set both padding-top and padding-bottom on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'py-md-0 py-md-1 py-md-2 py-md-3 py-md-4 py-md-5',
		'desc' => 'BS4 sizing utils: set both padding-top and padding-bottom on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'py-lg-0 py-lg-1 py-lg-2 py-lg-3 py-lg-4 py-lg-5',
		'desc' => 'BS4 sizing utils: set both padding-top and padding-bottom on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'py-xl-0 py-xl-1 py-xl-2 py-xl-3 py-xl-4 py-xl-5',
		'desc' => 'BS4 sizing utils: set both padding-top and padding-bottom on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'pt-0 pt-1 pt-2 pt-3 pt-4 pt-5',
		'desc' => 'BS4 sizing utils: set padding-top (mobile first)',
	],

	[
		'names' => 'pt-sm-0 pt-sm-1 pt-sm-2 pt-sm-3 pt-sm-4 pt-sm-5',
		'desc' => 'BS4 sizing utils: set padding-top on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'pt-md-0 pt-md-1 pt-md-2 pt-md-3 pt-md-4 pt-md-5',
		'desc' => 'BS4 sizing utils: set padding-top on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'pt-lg-0 pt-lg-1 pt-lg-2 pt-lg-3 pt-lg-4 pt-lg-5',
		'desc' => 'BS4 sizing utils: set padding-top on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'pt-xl-0 pt-xl-1 pt-xl-2 pt-xl-3 pt-xl-4 pt-xl-5',
		'desc' => 'BS4 sizing utils: set padding-top on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'pb-0 pb-1 pb-2 pb-3 pb-4 pb-5',
		'desc' => 'BS4 sizing utils: set padding-bottom (mobile first)',
	],

	[
		'names' => 'pb-sm-0 pb-sm-1 pb-sm-2 pb-sm-3 pb-sm-4 pb-sm-5',
		'desc' => 'BS4 sizing utils: set padding-bottom on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'pb-md-0 pb-md-1 pb-md-2 pb-md-3 pb-md-4 pb-md-5',
		'desc' => 'BS4 sizing utils: set padding-bottom on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'pb-lg-0 pb-lg-1 pb-lg-2 pb-lg-3 pb-lg-4 pb-lg-5',
		'desc' => 'BS4 sizing utils: set padding-bottom on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'pb-xl-0 pb-xl-1 pb-xl-2 pb-xl-3 pb-xl-4 pb-xl-5',
		'desc' => 'BS4 sizing utils: set padding-bottom on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'm-0 m-1 m-2 m-3 m-4 m-5 m-auto m-n1 m-n2 m-n3 m-n4 m-n5',
		'desc' => 'BS4 sizing utils: set margin on all 4 sides (mobile first)',
	],

	[
		'names' => 'm-sm-0 m-sm-1 m-sm-2 m-sm-3 m-sm-4 m-sm-5 m-sm-auto m-sm-n1 m-sm-n2 m-sm-n3 m-sm-n4 m-sm-n5',
		'desc' => 'BS4 sizing utils: set margin on all 4 sides on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'm-md-0 m-md-1 m-md-2 m-md-3 m-md-4 m-md-5 m-md-auto m-md-n1 m-md-n2 m-md-n3 m-md-n4 m-md-n5',
		'desc' => 'BS4 sizing utils: set margin on all 4 sides on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'm-lg-0 m-lg-1 m-lg-2 m-lg-3 m-lg-4 m-lg-5 m-lg-auto m-lg-n1 m-lg-n2 m-lg-n3 m-lg-n4 m-lg-n5',
		'desc' => 'BS4 sizing utils: set margin on all 4 sides on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'm-xl-0 m-xl-1 m-xl-2 m-xl-3 m-xl-4 m-xl-5 m-xl-auto m-xl-n1 m-xl-n2 m-xl-n3 m-xl-n4 m-xl-n5',
		'desc' => 'BS4 sizing utils: set margin on all 4 sides on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'mx-0 mx-1 mx-2 mx-3 mx-4 mx-5 mx-auto mx-n1 mx-n2 mx-n3 mx-n4 mx-n5',
		'desc' => 'BS4 sizing utils: set both margin-left and margin-right (mobile first)',
	],

	[
		'names' => 'mx-sm-0 mx-sm-1 mx-sm-2 mx-sm-3 mx-sm-4 mx-sm-5 mx-sm-auto mx-sm-n1 mx-sm-n2 mx-sm-n3 mx-sm-n4 mx-sm-n5',
		'desc' => 'BS4 sizing utils: set both margin-left and margin-right on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'mx-md-0 mx-md-1 mx-md-2 mx-md-3 mx-md-4 mx-md-5 mx-md-auto mx-md-n1 mx-md-n2 mx-md-n3 mx-md-n4 mx-md-n5',
		'desc' => 'BS4 sizing utils: set both margin-left and margin-right on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'mx-lg-0 mx-lg-1 mx-lg-2 mx-lg-3 mx-lg-4 mx-lg-5 mx-lg-auto mx-lg-n1 mx-lg-n2 mx-lg-n3 mx-lg-n4 mx-lg-n5',
		'desc' => 'BS4 sizing utils: set both margin-left and margin-right on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'mx-xl-0 mx-xl-1 mx-xl-2 mx-xl-3 mx-xl-4 mx-xl-5 mx-xl-auto mx-xl-n1 mx-xl-n2 mx-xl-n3 mx-xl-n4 mx-xl-n5',
		'desc' => 'BS4 sizing utils: set both margin-left and margin-right on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'ml-0 ml-1 ml-2 ml-3 ml-4 ml-5 ml-auto ml-n1 ml-n2 ml-n3 ml-n4 ml-n5',
		'desc' => 'BS4 sizing utils: set margin-left (mobile first)',
	],

	[
		'names' => 'ml-sm-0 ml-sm-1 ml-sm-2 ml-sm-3 ml-sm-4 ml-sm-5 ml-sm-auto ml-sm-n1 ml-sm-n2 ml-sm-n3 ml-sm-n4 ml-sm-n5',
		'desc' => 'BS4 sizing utils: set margin-left on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'ml-md-0 ml-md-1 ml-md-2 ml-md-3 ml-md-4 ml-md-5 ml-md-auto ml-md-n1 ml-md-n2 ml-md-n3 ml-md-n4 ml-md-n5',
		'desc' => 'BS4 sizing utils: set margin-left on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'ml-lg-0 ml-lg-1 ml-lg-2 ml-lg-3 ml-lg-4 ml-lg-5 ml-lg-auto ml-lg-n1 ml-lg-n2 ml-lg-n3 ml-lg-n4 ml-lg-n5',
		'desc' => 'BS4 sizing utils: set margin-left on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'ml-xl-0 ml-xl-1 ml-xl-2 ml-xl-3 ml-xl-4 ml-xl-5 ml-xl-auto ml-xl-n1 ml-xl-n2 ml-xl-n3 ml-xl-n4 ml-xl-n5',
		'desc' => 'BS4 sizing utils: set margin-left on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'mr-0 mr-1 mr-2 mr-3 mr-4 mr-5 mr-auto mr-n1 mr-n2 mr-n3 mr-n4 mr-n5',
		'desc' => 'BS4 sizing utils: set margin-right (mobile first)',
	],

	[
		'names' => 'mr-sm-0 mr-sm-1 mr-sm-2 mr-sm-3 mr-sm-4 mr-sm-5 mr-sm-auto mr-sm-n1 mr-sm-n2 mr-sm-n3 mr-sm-n4 mr-sm-n5',
		'desc' => 'BS4 sizing utils: set margin-right on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'mr-md-0 mr-md-1 mr-md-2 mr-md-3 mr-md-4 mr-md-5 mr-md-auto mr-md-n1 mr-md-n2 mr-md-n3 mr-md-n4 mr-md-n5',
		'desc' => 'BS4 sizing utils: set margin-right on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'mr-lg-0 mr-lg-1 mr-lg-2 mr-lg-3 mr-lg-4 mr-lg-5 mr-lg-auto mr-lg-n1 mr-lg-n2 mr-lg-n3 mr-lg-n4 mr-lg-n5',
		'desc' => 'BS4 sizing utils: set margin-right on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'mr-xl-0 mr-xl-1 mr-xl-2 mr-xl-3 mr-xl-4 mr-xl-5 mr-xl-auto mr-xl-n1 mr-xl-n2 mr-xl-n3 mr-xl-n4 mr-xl-n5',
		'desc' => 'BS4 sizing utils: set margin-right on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'my-0 my-1 my-2 my-3 my-4 my-5 my-auto my-n1 my-n2 my-n3 my-n4 my-n5',
		'desc' => 'BS4 sizing utils: set both margin-top and margin-bottom (mobile first)',
	],

	[
		'names' => 'my-sm-0 my-sm-1 my-sm-2 my-sm-3 my-sm-4 my-sm-5 my-sm-auto my-sm-n1 my-sm-n2 my-sm-n3 my-sm-n4 my-sm-n5',
		'desc' => 'BS4 sizing utils: set both margin-top and margin-bottom on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'my-md-0 my-md-1 my-md-2 my-md-3 my-md-4 my-md-5 my-md-auto my-md-n1 my-md-n2 my-md-n3 my-md-n4 my-md-n5',
		'desc' => 'BS4 sizing utils: set both margin-top and margin-bottom on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'my-lg-0 my-lg-1 my-lg-2 my-lg-3 my-lg-4 my-lg-5 my-lg-auto my-lg-n1 my-lg-n2 my-lg-n3 my-lg-n4 my-lg-n5',
		'desc' => 'BS4 sizing utils: set both margin-top and margin-bottom on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'my-xl-0 my-xl-1 my-xl-2 my-xl-3 my-xl-4 my-xl-5 my-xl-auto my-xl-n1 my-xl-n2 my-xl-n3 my-xl-n4 my-xl-n5',
		'desc' => 'BS4 sizing utils: set both margin-top and margin-bottom on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'mt-0 mt-1 mt-2 mt-3 mt-4 mt-5 mt-auto mt-n1 mt-n2 mt-n3 mt-n4 mt-n5',
		'desc' => 'BS4 sizing utils: set margin-top (mobile first)',
	],

	[
		'names' => 'mt-sm-0 mt-sm-1 mt-sm-2 mt-sm-3 mt-sm-4 mt-sm-5 mt-sm-auto mt-sm-n1 mt-sm-n2 mt-sm-n3 mt-sm-n4 mt-sm-n5',
		'desc' => 'BS4 sizing utils: set margin-top on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'mt-md-0 mt-md-1 mt-md-2 mt-md-3 mt-md-4 mt-md-5 mt-md-auto mt-md-n1 mt-md-n2 mt-md-n3 mt-md-n4 mt-md-n5',
		'desc' => 'BS4 sizing utils: set margin-top on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'mt-lg-0 mt-lg-1 mt-lg-2 mt-lg-3 mt-lg-4 mt-lg-5 mt-lg-auto mt-lg-n1 mt-lg-n2 mt-lg-n3 mt-lg-n4 mt-lg-n5',
		'desc' => 'BS4 sizing utils: set margin-top on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'mt-xl-0 mt-xl-1 mt-xl-2 mt-xl-3 mt-xl-4 mt-xl-5 mt-xl-auto mt-xl-n1 mt-xl-n2 mt-xl-n3 mt-xl-n4 mt-xl-n5',
		'desc' => 'BS4 sizing utils: set margin-top on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'mb-0 mb-1 mb-2 mb-3 mb-4 mb-5 mb-auto mb-n1 mb-n2 mb-n3 mb-n4 mb-n5',
		'desc' => 'BS4 sizing utils: set margin-bottom (mobile first)',
	],

	[
		'names' => 'mb-sm-0 mb-sm-1 mb-sm-2 mb-sm-3 mb-sm-4 mb-sm-5 mb-sm-auto mb-sm-n1 mb-sm-n2 mb-sm-n3 mb-sm-n4 mb-sm-n5',
		'desc' => 'BS4 sizing utils: set margin-bottom on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'mb-md-0 mb-md-1 mb-md-2 mb-md-3 mb-md-4 mb-md-5 mb-md-auto mb-md-n1 mb-md-n2 mb-md-n3 mb-md-n4 mb-md-n5',
		'desc' => 'BS4 sizing utils: set margin-bottom on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'mb-lg-0 mb-lg-1 mb-lg-2 mb-lg-3 mb-lg-4 mb-lg-5 mb-lg-auto mb-lg-n1 mb-lg-n2 mb-lg-n3 mb-lg-n4 mb-lg-n5',
		'desc' => 'BS4 sizing utils: set margin-bottom on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'mb-xl-0 mb-xl-1 mb-xl-2 mb-xl-3 mb-xl-4 mb-xl-5 mb-xl-auto mb-xl-n1 mb-xl-n2 mb-xl-n3 mb-xl-n4 mb-xl-n5',
		'desc' => 'BS4 sizing utils: set margin-bottom on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'w-25 w-50 w-75 w-100 w-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s width (mobile first)',
	],

	[
		'names' => 'w-sm-25 w-sm-50 w-sm-75 w-sm-100 w-sm-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s width on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'w-md-25 w-md-50 w-md-75 w-md-100 w-md-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s width on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'w-lg-25 w-lg-50 w-lg-75 w-lg-100 w-lg-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s width on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'w-xl-25 w-xl-50 w-xl-75 w-xl-100 w-xl-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s width on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'w-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-width (mobile first)',
	],

	[
		'names' => 'w-sm-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-width on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'w-md-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-width on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'w-lg-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-width on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'w-xl-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-width on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'h-25 h-50 h-75 h-100 h-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s height (mobile first)',
	],

	[
		'names' => 'h-sm-25 h-sm-50 h-sm-75 h-sm-100 h-sm-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s height on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'h-md-25 h-md-50 h-md-75 h-md-100 h-md-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s height on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'h-lg-25 h-lg-50 h-lg-75 h-lg-100 h-lg-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s height on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'h-xl-25 h-xl-50 h-xl-75 h-xl-100 h-xl-auto',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s height on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'h-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-height (mobile first)',
	],

	[
		'names' => 'h-sm-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-height on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'h-md-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-height on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'h-lg-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-height on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'h-xl-100',
		'desc' => 'BS4 sizing utils: quickly define or override an element’s max-height on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'order-order-first order-order-last order-0 order-1 order-2 order-3 order-4 order-5 order-6 order-7 order-8 order-9 order-10 order-11 order-12',
		'desc' => 'BS4 order utils: change the visual order of the section inside its wrapper (mobile first)',
	],

	[
		'names' => 'order-sm-order-first order-sm-order-last order-sm-0 order-sm-1 order-sm-2 order-sm-3 order-sm-4 order-sm-5 order-sm-6 order-sm-7 order-sm-8 order-sm-9 order-sm-10 order-sm-11 order-sm-12',
		'desc' => 'BS4 order utils: change the visual order of the section inside its wrapper on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'order-md-order-first order-md-order-last order-md-0 order-md-1 order-md-2 order-md-3 order-md-4 order-md-5 order-md-6 order-md-7 order-md-8 order-md-9 order-md-10 order-md-11 order-md-12',
		'desc' => 'BS4 order utils: change the visual order of the section inside its wrapper on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'order-lg-order-first order-lg-order-last order-lg-0 order-lg-1 order-lg-2 order-lg-3 order-lg-4 order-lg-5 order-lg-6 order-lg-7 order-lg-8 order-lg-9 order-lg-10 order-lg-11 order-lg-12',
		'desc' => 'BS4 order utils: change the visual order of the section inside its wrapper on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'order-xl-order-first order-xl-order-last order-xl-0 order-xl-1 order-xl-2 order-xl-3 order-xl-4 order-xl-5 order-xl-6 order-xl-7 order-xl-8 order-xl-9 order-xl-10 order-xl-11 order-xl-12',
		'desc' => 'BS4 order utils: change the visual order of the section inside its wrapper on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'border border-top border-right border-bottom border-left',
		'desc' => 'BS4 border utils: add borders to an element',
	],

	[
		'names' => 'border-0 border-top-0 border-right-0 border-bottom-0 border-left-0',
		'desc' => 'BS4 border utils: subtract an element’s borders',
	],

	[
		'names' => 'border-primary border-secondary border-success border-danger border-warning border-info border-light border-dark',
		'desc' => 'BS4 border utils: change the border color',
	],

	[
		'names' => 'rounded rounded-top rounded-right rounded-bottom rounded-left rounded-circle rounded-pill rounded-0',
		'desc' => 'BS4 border utils: easily round an element’s corners',
	],

	[
		'names' => 'rounded-sm rounded-lg',
		'desc' => 'BS4 border utils: use for larger or smaller border-radius',
	],

	[
		'names' => 'jumbotron',
		'desc' => 'BS4: everything big for calling extra attention to some special content',
	],

	[
		'names' => 'jumbotron-fluid',
		'desc' => 'BS4: combine with jumbotron for full-width sections without rounded corners',
	],

	[
		'names' => 'float-left float-right float-none',
		'desc' => 'BS4 float utils: toggle floats on the section (mobile first)',
	],

	[
		'names' => 'float-sm-left float-sm-right float-sm-none',
		'desc' => 'BS4 float utils: toggle floats on the section on large smartphones (screen width ≥ 576px)',
	],

	[
		'names' => 'float-md-left float-md-right float-md-none',
		'desc' => 'BS4 float utils: toggle floats on the section on tablets (screen width ≥ 786px)',
	],

	[
		'names' => 'float-lg-left float-lg-right float-lg-none',
		'desc' => 'BS4 float utils: toggle floats on the section on laptops (screen width ≥ 992px)',
	],

	[
		'names' => 'float-xl-left float-xl-right float-xl-none',
		'desc' => 'BS4 float utils: toggle floats on the section on desktops (screen width ≥ 1200px)',
	],

	[
		'names' => 'clearfix',
		'desc' => 'BS clearfix: use for wrapper sections that contain floated child sections',
	],

	[
		'names' => 'visible invisible',
		'desc' => 'BS4 visibility: control the visibility without modifying the display. Invisible elements will still take up space in the page',
	],

	[
		'names' => 'sr-only',
		'desc' => 'BS4 screen reader utils: hide elements on all devices except screen readers',
	],

	[
		'names' => 'sr-only-focusable',
		'desc' => 'BS4 screen reader utils: combine with sr-only to show the element again when it’s focused (e.g. via keyboard)',
	],

	[
		'names' => 'd-print-none d-print-inline d-print-inline-block d-print-block d-print-table d-print-table-row d-print-table-cell d-print-flex d-print-inline-flex',
		'desc' => 'BS4 print utils: change the display value of elements when printing',
	],
];
