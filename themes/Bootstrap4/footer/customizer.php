<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'footer' layout customizer definition
 */

defined('is_running') or die('Not an entry point...');

$customizer = [

	'header'	=> [															// * unique key for collapsible UI section, will not be displayed in the UI
		'label'			=> 'Page Header and Navbar',							// * keep it short
		'collapsed'		=> false,												//   initial state of this area
		'items'			=> [

			'header_height'	=>	[												// * the name of the variable. allowed are only alphanumeric ASCII characters and underscores.
																				//    └─ no blanks, no dollar signs, may not start with a digit
				'default_value'	=> '72',										// * the default value for this setting
				'default_units'	=> 'px',										// * the default units for this value, can be omitted if empty

				'control' => [
					'label'			=> 'Header Height',							// * plain text label for the customizer item. UTF-8, no HTML tags
					'description'	=> 'Height of the header / navbar',			//   plain text description for the item. UTF-8, no HTML tags
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
					'description'	=> '',
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
					'description'		=> '',
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
					'placeholder'		=> '',

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
					'description'		=> 'set the color of your site title',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'header_fixed'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Fixed Header',
					'description'		=> 'make the header stick to the top of the viewport',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'scc', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'header_use_container'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Header Content Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the header content left and right',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],

			'navbar_expand_breakpoint'	=>	[
				'default_value'	=> 'lg',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Hamburger Menu',
					'description'		=> 'select the Bootstrap breakpoint from ' .
						'which the hambuger menu will expand. ' .
						'Think small-to-large, we\'re mobile first!',

					'type'				=> 'select', // could also be 'radio'
					'possible_values'	=> [
											// use
											// value, value, value, ...
											// OR
											// label => value, label => value, ...
											'small (sm)'	=> 'sm',
											'medium (md)'	=> 'md',
											'large (lg)'	=> 'lg',
											'x-large (xl)'	=> 'xl',
											],
					'used_in'			=> ['scssless', 'css', 'php', 'js'],
					'units'				=> [],
					'pattern'			=> '/(sm|md|lg|xl)/',
				],
			],

		], // end of items
	], // end of collapsible UI area

	'content'	=> [
		'label'			=> 'Page Content Area',
		'collapsed'		=> true,
		'items'			=> [

			'content_use_container'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Content Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the main content left and right',

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
					'description'		=> '',
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
					'description'		=> '',
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
					'description'		=> '',
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
					'description'		=> '',
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


	'footer'	=> [
		'label'			=> 'Page Footer Area',
		'collapsed'		=> true,
		'items'			=> [

			'footer_use_container'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Content Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the footer content left and right',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['php'],
					'units'				=> [],
					'pattern'			=> 'onoff',
				],
			],


			'footer_bg'	=>	[
				'default_value'	=> '#e9ecef', // = $gray-200
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Background Color',
					'description'		=> '',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'footer_color'	=>	[
				'default_value'		=> '#212529', // = $gray-900
				'default_units'		=> '',

				'control' => [
					'label'				=> 'Footer Text Color',
					'description'		=> '',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'footer_headings_color'	=>	[
				'default_value'	=> '#343a40', // = $gray-800
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Headings Color',
					'description'		=> '',
					'placeholder'		=> '',

					'type'				=> 'colorpicker',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'color',
				],
			],

			'footer_link_color'	=>	[
				'default_value'	=> '#007bff', // = $blue
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Link Color',
					'description'		=> '',
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

