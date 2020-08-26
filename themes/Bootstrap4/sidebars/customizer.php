<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'sidebars' layout customizer definition
 */

defined('is_running') or die('Not an entry point...');

$customizer = [

	'main_header'	=> [														// * unique key for collapsible UI section, will not be displayed in the UI
		'label'			=> 'Main Header and Navbar',							// * keep it short
		'collapsed'		=> false,												//   initial state of this area
		'items'			=> [

			'header_height'	=>	[												// * the name of the variable. allowed are only alphanumeric ASCII characters and underscores.
																				//    └─ no blanks, no dollar signs, may not start with a digit
				'default_value'	=> '72',										// * the default value for this setting
				'default_units'	=> 'px',										// * the default units for this value, can be omitted if empty

				'control' => [
					'label'			=> 'Header Height',							// * plain text label for the customizer item. UTF-8, no HTML tags
					'description'	=> 'set the main header\'s height',			//   plain text description for the item. UTF-8, no HTML tags
					'placeholder'	=> '',										//   placeholder value for text, number and url inputs

					'type'				=> 'number',							// * may be 'text' | 'number' | 'url' | 'select' | 'checkbox' | 'radio' | 'colorpicker' | 'colors' | 'image' | 'file'
					'min'				=> false,								//   optional minimum value for number inputs
					'max'				=> false,								//   optional maximum value for number inputs
					'possible_values'	=> [],									// * possible values are required for select options, radio buttons or color swatches, can be empty or ommited otherwise
					'used_in'			=> ['scssless', 'css'],					// * context(s) where the variable will be needed
					'units'				=> ['px', 'rem'],						//   units the user can choose from, to be appended to numerical values in css
					'pattern'			=> 'number',							//   may be empty | 'number' | 'integer' | 'onoff' | 'color' | 'url' | a regular expression the value will be tested against upon saving
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

			'header_brand_logo'	=>	[
				'default_value'	=> '',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Logo',
					'description'		=> 'upload / select an image. Leave blank to omit the logo',
					'placeholder'		=> 'logo image URL',

					'type'				=> 'image',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'url',
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

			'header_sticky'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Keep Header Visible',
					'description'		=> 'keep the header visible by ' .
						'sticking it to the top of the viewport',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'scc', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'navbar_expand_breakpoint'	=>	[
				'default_value'	=> 'lg',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Expand Hamburger Menu',
					'description'		=> 'select the Bootstrap breakpoint ' . 
						'(viewport width) from which the hambuger menu will expand. ' .
						'Think small-to-large, we\'re mobile first!',

					'type'				=> 'select', // could also be 'radio'
					'possible_values'	=> [
												// use
												// value, value, value, ...
												// OR
												// label => value, label => value, ...
												'small (sm)'			=> 'sm',
												'medium (md)'			=> 'md',
												'large (lg)'			=> 'lg',
												'x-large (xl)'			=> 'xl',
												'hamburger forever!'	=> 'never',
											],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> '/(sm|md|lg|xl|never)/',
				],
			],

		], // end of items
	], // end of collapsible UI area


	'complementary_header'	=> [
		'label'			=> 'Complementary Header',
		'collapsed'		=> true,
		'items'			=> [

			'complementary_header_show'	=>	[
				'default_value'	=> 'never',
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
												'always show'		=> 'on',
												'small (sm)'		=> 'sm',
												'medium (md)'		=> 'md',
												'large (lg)'		=> 'lg',
												'x-large (xl)'		=> 'xl',
												'never show'		=> 'off',
											],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> '/(on|sm|md|lg|xl|off)/',
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
					'used_in'			=> ['scssless', 'scc', 'php', 'js'],
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

		], // end of items
	], // end of collapsible UI area


	'content'	=> [
		'label'			=> 'Page Content Area',
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

			'show_breadcrumb_nav'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Show Breadcrumb Navigation',
					'description'		=> 'Adds a Home » Heading Page » Child Page ' .
						'type navigation at the top of the main content',

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
					'description'		=> 'pick a text color for the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'content_headings_color'	=>	[
				'default_value'	=> '#212529', // = $gray-900
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Content Headings Color',
					'description'		=> 'pick a color for headings in the content area',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
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

];
