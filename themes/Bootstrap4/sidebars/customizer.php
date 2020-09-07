<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'sidebars' layout customizer definition
 */

defined('is_running') or die('Not an entry point...');

/**
 * Example customizer array key
 *
 *	'main_header'	=> [										// * unique key for collapsible customizer section, will not be displayed in the UI
 *		'label'			=> 'Main Header',						// * the visible label, keep it short
 *		'collapsed'		=> true,								// * initial collapsed/expanded state of this area
 *		'items'			=> [
 *
 *			'header_height'	=>	[								// * the name of the variable! allowed are only alphanumeric ASCII characters and underscores.
 *																//    └─ no blanks, no dollar signs, may not start with a digit
 *				'default_value'	=> '72',						// * the default value for this setting
 *				'default_units'	=> 'px',						// * the default unit for this value, may be empty if value doen't have a unit
 *
 *				'control' => [
 *					'label'			=> 'Header Height',			// * plain text label for the customizer item.
 *					'description'	=> 'set the main ' .		//   optional plain text description for the item.
 *						'header\'s height',
 *					'placeholder'	=> '',						//   optional placeholder value for text, number and url inputs
 *
 *					'type'				=> 'number',			// * may be 'text' | 'number' | 'url' | 'select' | 'checkbox' | 'radio' | 'colorpicker' | 'colors' | 'image' | 'file'
 *					'min'				=> false,				//   optional minimum value for number inputs
 *					'max'				=> false,				//   optional maximum value for number inputs
 *					'step'				=> false,				//   optional step value for number inputs
 *					'possible_values'	=> [],					// * possible values are required for select options, radio buttons or color swatches, ommit or leave empty for other control types
 *					'used_in'			=> ['scssless', 'css'],	// * context(s) where the variable will be needed. any combination of 'scssless', 'css', 'php' and 'js'. at least one is rewuired
 *					'units'				=> ['px', 'rem'],		//   units the user can choose from, to be appended to numerical values in Scss/LESS/css
 *					'pattern'			=> 'number',			//   may be empty | 'number' | 'integer' | 'onoff' | 'color' | 'url' or a regular expression the value will be tested against upon saving
 *				],												// end of the 'control' array
 *
 *			],													// end of the 'header_height' item array
 *
 *			// more items
 *
 *		],														// end of 'items' array
 *
 *	],															// end of 'main_header'
 *
 *	// more sections
 *
 */


$font_list = [
	'Default Sans-Serif'				=> 'default_sans',
	'Default Serif'						=> 'default_serif',
	'Alegreya Sans'						=> 'alegreya_sans',
	'Alegreya Serif'					=> 'alegreya',
	'Bitter'							=> 'bitter',
	'Cinzel (Latin only)'				=> 'cinzel',
	'Cinzel Decorative (Latin only)'	=> 'cinzel_decorative',
	'EB Garamond'						=> 'eb_garamond',
	'Fira Sans'							=> 'fira_sans',
	'Inter'								=> 'inter',
	'Lora'								=> 'lora',
	'Merriweather'						=> 'merriweather',
	'Montserrat'						=> 'montserrat',
	'Nunito'							=> 'nunito',
	'Open Sans'							=> 'open_sans',
	'Playfair Display'					=> 'playfair_display',
	'Raleway'							=> 'raleway',
	'Roboto'							=> 'roboto',
	'Roboto Slab'						=> 'roboto_slab',
	'Source Sans Pro'					=> 'source_sans_pro',
	'Ubuntu'							=> 'ubuntu',
	'Work Sans'							=> 'work_sans',
	'Zilla Slab'						=> 'zilla_slab',
];


$font_weights = [
	'Thin'			=> '100',
	'Extra Light'	=> '200',
	'Light'			=> '300',
	'Regular'		=> '400',
	'Medium'		=> '500',
	'Semi Bold'		=> '600',
	'Bold'			=> '700',
	'Extra Bold'	=> '800',
	'Heavy / Black' => '900',
];


$customizer = [

	'complementary_header'	=> [
		'label'			=> 'Complementary Header',
		'collapsed'		=> true,
		'items'			=> [

			'complementary_header_show'	=>	[
				'default_value'	=> 'md',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Show Complementary Header',
					'description'		=> 'an additional, smaller header ' .
						'at the very top of the page. Choose an ' .
						'option / Bootstrap breakpoint (viewport width) ' .
						'from which this header will show. ' .
						'Think small-to-large, we\'re mobile first!',

					'type'				=> 'select',
					'possible_values'	=> [
												'always show'				=> 'on',
												'small (sm, ≥ 576px)'		=> 'sm',
												'medium (md, ≥ 768px)'		=> 'md',
												'large (lg, ≥ 992px)'		=> 'lg',
												'x-large (xl, ≥ 1200px)'	=> 'xl',
												'never show'				=> 'off',
											],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> '', // '/(on|sm|md|lg|xl|off)/', not req for selects TODO remove comment
				],
			],

			'complementary_header_fixed'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Keep Complementary Header Visible',
					'description'		=> 'make the complementary header stick to the top of the viewport',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'complementary_header_use_container'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Constrain Complementary Header Content Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the complementary header\'s content left and right',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'complementary_header_height'	=>	[
				'default_value'	=> '46',
				'default_units'	=> 'px',

				'control' => [
					'label'			=> 'Complementary Header Height',
					'description'	=> 'set the height of the complementary header',
					'placeholder'	=> '',

					'type'				=> 'number',
					'min'				=> false,
					'max'				=> false,
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'complementary_header_bg'	=>	[
				'default_value'	=> '#212529', // $gray-900
				'default_units'	=> '',

				'control' => [
					'label'			=> 'Complementary Header Background Color',
					'description'	=> 'pick a background color for the complementary header',
					'placeholder'	=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'complementary_header_color'	=>	[
				'default_value'	=> '#ffffff',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Complementary Header Text Color',
					'description'		=> 'pick a text color for the complementary header',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'complementary_header_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Complementary Header Font Family',
					'description'		=> 'choose a font family for complementary header text',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'complementary_header_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Complementary Header Font Size',
					'description'		=> 'The font size of all elements, ' .
						'in the complementary header. At larger sizes ' .
						'adjust the header height accordingly',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'complementary_header_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Complementary Header Font Weight',
					'description'		=> 'choose a font weight for complementary header text',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'complementary_header_border_bottom_width'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'px',

				'control' => [
					'label'				=> 'Complementary Header Bottom Border Width',
					'description'		=> 'set a width for the bottom border of the complementary header, use 0 if you don\'t want a border',

					'type'				=> 'number',
					'min'				=> '0',
					'max'				=> false,
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'complementary_header_border_bottom_color'	=>	[
				'default_value'	=> 'rgba(0, 0, 0, 0)',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Complementary Header Border Bottom Color',
					'description'		=> 'pick a color for the complementary header\'s bottom border',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'main_header'	=> [
		'label'			=> 'Main Header',
		'collapsed'		=> true,
		'items'			=> [

			'header_height'	=>	[
				'default_value'	=> '72',
				'default_units'	=> 'px',

				'control' => [
					'label'			=> 'Header Height',
					'description'	=> 'set the main header\'s height',
					'placeholder'	=> '',

					'type'				=> 'number',
					'min'				=> '0',
					'max'				=> false,
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'header_bg'	=>	[
				'default_value'	=> '#343a40',
				'default_units'	=> '',

				'control' => [
					'label'			=> 'Header Background Color',
					'description'	=> 'pick a background color for the main header',
					'placeholder'	=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'header_color'	=>	[
				'default_value'	=> '#ffffff',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Header Text Color',
					'description'		=> 'pick a text color for the main header',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'header_border_bottom_width'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'px',

				'control' => [
					'label'				=> 'Header Bottom Border Width',
					'description'		=> 'set a width for the bottom border of the header, use 0 if you don\'t want a border',

					'type'				=> 'number',
					'min'				=> '0',
					'max'				=> false,
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'header_border_bottom_color'	=>	[
				'default_value'	=> 'rgba(0, 0, 0, 0)',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Header Border Bottom Color',
					'description'		=> 'pick a color for the header\'s bottom border',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'header_sticky'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Keep Header Visible',
					'description'		=> 'keep the header visible by ' .
						'sticking it to the top of the viewport',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'header_use_container'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Constrain Header Content Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the header content left and right',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'branding'	=> [
		'label'			=> 'Branding',
		'collapsed'		=> true,
		'items'			=> [

			'header_brand_logo'	=>	[
				'default_value'	=> '',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Logo',
					'description'		=> 'upload / select a logo image. Leave blank to omit the logo',
					'placeholder'		=> 'logo image URL',

					'type'				=> 'image',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'url',
				],
			],

			'header_brand_logo_height'	=>	[
				'default_value'	=> '100',
				'default_units'	=> '%',

				'control' => [
					'label'				=> 'Brand Logo Height',
					'description'		=> 'adjust the height of the logo. ' .
						'Use percent (of the available height), pixels or rem units.'.
						'The logo width will change accordingly - make sure it is ' .
						'no wider than ~250px so that it fits on phone screens',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['%', 'px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'header_brand_color'	=>	[
				'default_value'	=> '#ffffff',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Text Color',
					'description'		=> 'pick the color of your site title',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'header_brand_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Font Family',
					'description'		=> 'choose a font family for the brand / site title',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'header_brand_font_size'	=>	[
				'default_value'	=> '1.25',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Brand Font Size',
					'description'		=> 'set the font size of the brand / site title',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'header_brand_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Font Weight',
					'description'		=> 'choose the weight for the brand / site title',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'header_brand_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Text Uppercase',
					'description'		=> 'make the brand / site title all uppercase',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],


		], // end of items
	], // end of collapsible UI area


	'main_menu'	=> [
		'label'			=> 'Main Menu',
		'collapsed'		=> true,
		'items'			=> [

			'navbar_expand_breakpoint'	=>	[
				'default_value'	=> 'lg',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Expand Hamburger Menu',
					'description'		=> 'select the viewport width (Bootstrap breakpoint) ' .
						'from which the hambuger menu will expand. ' .
						'Think small-to-large, we\'re mobile first!',

					'type'				=> 'select', // alternatively you could also use 'radio'
					'possible_values'	=> [
												// use
												// value, value, value, ...
												// OR
												// label => value, label => value, ...
												'small (sm, ≥ 576px)'		=> 'sm',
												'medium (md, ≥ 768px)'		=> 'md',
												'large (lg, ≥ 992px)'		=> 'lg',
												'x-large (xl, ≥ 1200px)'	=> 'xl',
												'hamburger forever!'		=> 'never',
											],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> '', // not required for selects '/(sm|md|lg|xl|never)/', TODO remove comment
				],
			],

			'main_menu_color'	=>	[
				'default_value'	=> 'rgba(255,255,255,0.6)',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Color',
					'description'		=> 'pick the color for the main menu items',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],
			
			'main_menu_hover_color'	=>	[
				'default_value'	=> 'rgba(255,255,255,0.8)',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Hover Color',
					'description'		=> 'pick the color for the ' .
						'main menu items as you hover over them',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],
			
			'main_menu_active_color'	=>	[
				'default_value'	=> '#ffffff',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Active Color',
					'description'		=> 'pick the color for the active ' .
						'main menu item',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'main_menu_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Font Family',
					'description'		=> 'choose a font family for the main menu',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'main_menu_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Main Menu Font Size',
					'description'		=> 'set the main menu items\' font size',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'main_menu_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Font Weight',
					'description'		=> 'choose the main menu items\' font weight',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'main_menu_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Uppercase',
					'description'		=> 'make the main menu 1st level items all uppercase',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'main_menu_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Main Menu Letter Spacing',
					'description'		=> 'adjust the tracking (space between characters) ' .
						'of the main menu 1st level items',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],


		], // end of items
	], // end of collapsible UI area


	'search'	=> [
		'label'			=> 'Search Field',
		'collapsed'		=> true,
		'items'			=> [

			'search_field_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Search Field Font Family',
					'description'		=> 'choose a font family for the search field',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'search_field_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Search Field Font Size',
					'description'		=> 'set the font size for the search field',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'search_field_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Search Field Font Weight',
					'description'		=> 'choose the search field\'s font weight',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'search_field_bg'	=>	[
				'default_value'	=> '#ffffff',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Search Field Background Color',
					'description'		=> 'pick a background color for the search field',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'search_field_idle_opacity'	=>	[
				'default_value'	=> '1',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Search Field Idle Opacity',
					'description'		=> 'set the search field\'s opacity when it\'s inactive',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> '1',
					'step'				=> '0.05',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'search_field_active_opacity'	=>	[
				'default_value'	=> '1',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Search Field Active Opacity',
					'description'		=> 'set the search field\'s opacity when ' .
						'it\'s hovered or has the focus',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> '1',
					'step'				=> '0.05',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'search_field_border_color'	=>	[
				'default_value'	=> '#dee2e6',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Search Field Border Color',
					'description'		=> 'pick a border color for the search field. ' . 
						'Use \'transparent\' to hide the border',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'search_field_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Search Field Text Color',
					'description'		=> 'pick a text color for the search field',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'content'	=> [
		'label'			=> 'Main Content Area',
		'collapsed'		=> true,
		'items'			=> [

			'content_use_container'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Constrain Content Area Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the main content area left and right',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'content_bg'	=>	[
				'default_value'	=> '#ffffff', // = $white
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content Background Color',
					'description'		=> 'pick a background color for the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'content_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Content Text Color',
					'description'		=> 'pick a body text color for the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'content_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content Font Family',
					'description'		=> 'choose a font family for content body text',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],
			
			'content_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Content Font Size',
					'description'		=> 'the font size of content body text',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'content_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content Font Weight',
					'description'		=> 'choose a font weight for content body text',

					'type'				=> 'select',
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black'	=> '900',
											],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'content_link_color'	=>	[
				'default_value'	=> '#007bff', // = $blue
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content Link Color',
					'description'		=> 'pick a color for hyperlinks in the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'h1'	=> [
		'label'			=> '<H1> Headings',
		'collapsed'		=> true,
		'items'			=> [

			'h1_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H1> Font Family',
					'description'		=> 'load a custom web font family ' .
						'from Google Fonts for type 1 headings. ',
						'This settings only applies to the content area',
					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'h1_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H1> Headings Color',
					'description'		=> 'pick a color for type 1 headings. ' .
						'This settings only applies to the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'h1_font_size'	=>	[
				'default_value'	=> '2.5',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> '<H1> Headings Font Size',
					'description'		=> 'set the size of type 1 headings. ' .
						'This settings also applies to other areas (footer / sidebars) ' .
						'but will scale with the font sizes set for these areas',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'h1_font_weight'	=>	[
				'default_value'	=> '500',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H1> Weight',
					'description'		=> 'choose the weight for ' .
						'type 1 headings. If the selected weight is not ' .
						'available, the closest possible match is used. ' .
						'This settings only applies to the content area',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'h1_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H1> Uppercase',
					'description'		=> 'make type 1 headings uppercase. ' .
						'This settings only applies to the content area',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'h1_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Content <H1> Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of type 1 headings. ' .
						'This settings only applies to the content area',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'h2'	=> [
		'label'			=> '<H2> Headings',
		'collapsed'		=> true,
		'items'			=> [

			'h2_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H2> Font Family',
					'description'		=> 'load a custom web font family ' .
						'from Google Fonts for type 2 headings. ',
						'This settings only applies to the content area',
					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'h2_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H2> Headings Color',
					'description'		=> 'pick a color for type 2 headings. ' .
						'This settings only applies to the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'h2_font_size'	=>	[
				'default_value'	=> '2',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> '<H2> Headings Font Size',
					'description'		=> 'set the size of type 2 headings. ' .
						'This settings also applies to other areas (footer / sidebars) ' .
						'but will scale with the font sizes set for these areas',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'h2_font_weight'	=>	[
				'default_value'	=> '500',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H2> Weight',
					'description'		=> 'choose the weight for ' .
						'type 2 headings. If the selected weight is not ' .
						'available, the closest possible match is used. ' .
						'This settings only applies to the content area',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'h2_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H2> Uppercase',
					'description'		=> 'make type 2 headings uppercase. ' .
						'This settings only applies to the content area',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'h2_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Content <H2> Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of type 2 headings. ' .
						'This settings only applies to the content area',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'h3'	=> [
		'label'			=> '<H3> Headings',
		'collapsed'		=> true,
		'items'			=> [

			'h3_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H3> Font Family',
					'description'		=> 'load a custom web font family ' .
						'from Google Fonts for type 3 headings. ',
						'This settings only applies to the content area',
					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'h3_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H3> Headings Color',
					'description'		=> 'pick a color for type 3 headings. ' .
						'This settings only applies to the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'h3_font_size'	=>	[
				'default_value'	=> '1.75',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> '<H3> Headings Font Size',
					'description'		=> 'set the size of type 3 headings. ' .
						'This settings also applies to other areas (footer / sidebars) ' .
						'but will scale with the font sizes set for these areas',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'h3_font_weight'	=>	[
				'default_value'	=> '500',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H3> Weight',
					'description'		=> 'choose the weight for ' .
						'type 3 headings. If the selected weight is not ' .
						'available, the closest possible match is used. ' .
						'This settings only applies to the content area',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'h3_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H3> Uppercase',
					'description'		=> 'make type 3 headings uppercase. ' .
						'This settings only applies to the content area',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'h3_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Content <H3> Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of type 3 headings. ' .
						'This settings only applies to the content area',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'h4'	=> [
		'label'			=> '<H4> Headings',
		'collapsed'		=> true,
		'items'			=> [

			'h4_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H4> Font Family',
					'description'		=> 'load a custom web font family ' .
						'from Google Fonts for type 4 headings. ',
						'This settings only applies to the content area',
					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'h4_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H4> Headings Color',
					'description'		=> 'pick a color for type 4 headings. ' .
						'This settings only applies to the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'h4_font_size'	=>	[
				'default_value'	=> '1.5',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> '<H4> Headings Font Size',
					'description'		=> 'set the size of type 4 headings. ' .
						'This settings also applies to other areas (footer / sidebars) ' .
						'but will scale with the font sizes set for these areas',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'h4_font_weight'	=>	[
				'default_value'	=> '500',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H4> Weight',
					'description'		=> 'choose the weight for ' .
						'type 4 headings. If the selected weight is not ' .
						'available, the closest possible match is used. ' .
						'This settings only applies to the content area',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'h4_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H4> Uppercase',
					'description'		=> 'make type 4 headings uppercase. ' .
						'This settings only applies to the content area',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'h4_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Content <H4> Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of type 4 headings. ' .
						'This settings only applies to the content area',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'h5'	=> [
		'label'			=> '<H5> Headings',
		'collapsed'		=> true,
		'items'			=> [

			'h5_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H5> Font Family',
					'description'		=> 'load a custom web font family ' .
						'from Google Fonts for type 5 headings. ',
						'This settings only applies to the content area',
					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'h5_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H5> Headings Color',
					'description'		=> 'pick a color for type 5 headings. ' .
						'This settings only applies to the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'h5_font_size'	=>	[
				'default_value'	=> '1.25',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> '<H5> Headings Font Size',
					'description'		=> 'set the size of type 5 headings. ' .
						'This settings also applies to other areas (footer / sidebars) ' .
						'but will scale with the font sizes set for these areas',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'h5_font_weight'	=>	[
				'default_value'	=> '500',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H5> Weight',
					'description'		=> 'choose the weight for ' .
						'type 5 headings. If the selected weight is not ' .
						'available, the closest possible match is used. ' .
						'This settings only applies to the content area',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'h5_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H5> Uppercase',
					'description'		=> 'make type 5 headings uppercase. ' .
						'This settings only applies to the content area',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'h5_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Content <H5> Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of type 5 headings. ' .
						'This settings only applies to the content area',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'h6'	=> [
		'label'			=> '<H6> Headings',
		'collapsed'		=> true,
		'items'			=> [

			'h6_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H6> Font Family',
					'description'		=> 'load a custom web font family ' .
						'from Google Fonts for type 6 headings. ',
						'This settings only applies to the content area',
					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'h6_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H6> Headings Color',
					'description'		=> 'pick a color for type 6 headings. ' .
						'This settings only applies to the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'h6_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> '<H6> Headings Font Size',
					'description'		=> 'set the size of type 6 headings. ' .
						'This settings also applies to other areas (footer / sidebars) ' .
						'but will scale with the font sizes set for these areas',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'h6_font_weight'	=>	[
				'default_value'	=> '500',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H6> Weight',
					'description'		=> 'choose the weight for ' .
						'type 6 headings. If the selected weight is not ' .
						'available, the closest possible match is used. ' .
						'This settings only applies to the content area',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'number',
				],
			],

			'h6_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content <H6> Uppercase',
					'description'		=> 'make type 6 headings uppercase. ' .
						'This settings only applies to the content area',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'h6_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Content <H6> Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of type 6 headings. ' .
						'This settings only applies to the content area',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'sidebars_typography'	=> [
		'label'			=> 'Sidebar Typography',
		'collapsed'		=> true,
		'items'			=> [

			'sidebar_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Sidebar Font Family',
					'description'		=> 'choose a font family for sidebar text',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],
			
			'sidebar_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Sidebar Font Size',
					'description'		=> 'the font size of text in the sidebars',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['rem'],
					'pattern'			=> 'number',
				],
			],

			'sidebar_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Sidebar Font Weight',
					'description'		=> 'choose a font weight for sidebar text',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'sidebar_headings_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Sidebar Headings Font Family',
					'description'		=> 'choose a font family for headings in the sidebars',

					'type'				=> 'select',
					'possible_values'	=> $font_list,
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'sidebar_headings_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Sidebar Headings Font Weight',
					'description'		=> 'choose a font weight for headings in the sidebars',

					'type'				=> 'select',
					'possible_values'	=> $font_weights,
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'sidebar_headings_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Sidebar Headings Uppercase',
					'description'		=> 'make all headings in the sidebars uppercase',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'sidebar_headings_letter_spacing'	=>	[
				'default_value'	=> '0',
				'default_units'	=> 'em',

				'control' => [
					'label'				=> 'Sidebar Headings Letter Spacing',
					'description'		=> 'adjust the tracking (space ' .
						'between characters) of all headings ' .
						'in the sidebars',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> false,
					'max'				=> false,
					'step'				=> '0.005',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['em'],
					'pattern'			=> 'number',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'sidebar_left'	=> [
		'label'			=> 'Left Sidebar',
		'collapsed'		=> true,
		'items'			=> [

			'show_left_sidebar'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Show Left Sidebar',
					'description'		=> 'if you decide to hide it, ' .
						'move required areas elsewhere in the layout first',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'sidebar_left_sticky'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Left Sidebar Sticky',
					'description'		=> 'make the content of the left sidebar sticky',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'sidebar_left_width'	=>	[
				'default_value'	=> '250',
				'default_units'	=> 'px',

				'control' => [
					'label'				=> 'Left Sidebar Width',
					'description'		=> 'set the width of the left sidebar',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['%', 'px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'sidebar_left_bg'	=>	[
				'default_value'	=> '#f8f9fa',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Left Sidebar Background Color',
					'description'		=> 'pick a background color for the left sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_left_border_color'	=>	[
				'default_value'	=> '#dee2e6',	// $gray-300
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Left Sidebar Border Color',
					'description'		=> 'pick a border color for the left sidebar. ' . 
						'Use \'transparent\' to hide borders',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_left_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Left Sidebar Text Color',
					'description'		=> 'pick a text color for the left sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_left_link_color'	=>	[
				'default_value'		=> '#007bff', // = $blue
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Left Sidebar Link Color',
					'description'		=> 'pick a link color for the left sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_left_headings_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Left Sidebar Heading Color',
					'description'		=> 'pick a color for headings in the left sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'sidebar_right'	=> [
		'label'			=> 'Right Sidebar',
		'collapsed'		=> true,
		'items'			=> [

			'show_right_sidebar'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Show Right Sidebar',
					'description'		=> 'if you decide to hide it, ' .
						'move required areas elsewhere in the layout first',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'sidebar_right_sticky'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Right Sidebar Sticky',
					'description'		=> 'make the content of the right sidebar sticky',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],


			'sidebar_right_width'	=>	[
				'default_value'	=> '250',
				'default_units'	=> 'px',

				'control' => [
					'label'				=> 'Right Sidebar Width',
					'description'		=> 'set the width of the right sidebar',

					'type'				=> 'number',
					'possible_values'	=> [],
					'min'				=> '0',
					'used_in'			=> ['scssless', 'css'],
					'units'				=> ['%', 'px', 'rem'],
					'pattern'			=> 'number',
				],
			],

			'sidebar_right_bg'	=>	[
				'default_value'	=> '#ffffff',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Right Sidebar Background Color',
					'description'		=> 'pick a background color for the right sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_right_border_color'	=>	[
				'default_value'	=> '#dee2e6',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Right Sidebar Border Color',
					'description'		=> 'pick a border color for the left sidebar. ' . 
						'Use \'transparent\' to hide borders',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_right_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Right Sidebar Text Color',
					'description'		=> 'pick a text color for the right sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_right_link_color'	=>	[
				'default_value'		=> '#007bff', // = $blue
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Right Sidebar Link Color',
					'description'		=> 'pick a link color for the right sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'sidebar_right_headings_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Right Sidebar Heading Color',
					'description'		=> 'pick a color for headings in the right sidebar',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'settings'	=> [
		'label'			=> 'Settings',
		'collapsed'		=> true,
		'items'			=> [

			'use_avail_classes'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Use Optimized Classes',
					'description'		=> 'when editing pages using this layout, ' .
						'the settings specified under Admin Menu → Settings → Manage Classes ' .
						'are replaced by a set optimized for this theme',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],
		],
	], // end of collapsible UI area

];
