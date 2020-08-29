<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'footer' layout customizer definition
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
				'default_value'	=> true,
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


	'content'	=> [
		'label'			=> 'Page Content Area',
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


	'footer'	=> [
		'label'			=> 'Page Footer Area',
		'collapsed'		=> true,
		'items'			=> [

			'footer_use_container'	=>	[
				'default_value'	=> true,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Constrain Footer Content Width',
					'description'		=> 'use a Bootstrap container ' .
						'to pad the footer\s content left and right',

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
					'description'		=> 'pick a background color for the footer',
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
					'description'		=> 'pick a text color for the footer',
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
					'description'		=> 'pick a color for headings in the footer',
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
					'description'		=> 'pick a color for hyperlinks in the footer',
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


	'typography'	=> [
		'label'			=> 'Typography',
		'collapsed'		=> true,
		'items'			=> [

			'sans_web_font'	=>	[
				'default_value'	=> '',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Sans-Serif Font Family',
					'description'		=> 'load a custom sans-serif web font family from Google Fonts',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default'			=> '',
											'Source Sans Pro'	=> 'source_sans_pro',
											'Open Sans'			=> 'open_sans',
											'Roboto'			=> 'roboto',
											'Fira Sans'			=> 'fira_sans',
											'Alegreya Sans'		=> 'alegreya_sans',
											'Inter'				=> 'inter',
											'Ubuntu'			=> 'ubuntu',
											'Montserrat'		=> 'montserrat',
											'Raleway'			=> 'raleway',
											'Work Sans'			=> 'work_sans',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],


			'serif_web_font'	=>	[
				'default_value'	=> '',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Serif Font Family',
					'description'		=> 'load a custom serif web font family from Google Fonts',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default'			=> '',
											'Bitter'			=> 'bitter',
											'Merriweather'		=> 'merriweather',
											'Playfair Display'	=> 'playfair_display',
											'Lora'				=> 'lora',
											'EB Garamond'		=> 'eb_garamond',
											'Alegreya Serif'	=> 'alegreya',
											'Zilla Slab'		=> 'zilla_slab',
											'Roboto Slab'		=> 'roboto_slab',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],


			'text_use_font'	=>	[
				'default_value'	=> '',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Text Font',
					'description'		=> 'select which font family to use for regular text',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default'					=> '',
											'Sans-Serif Font Family'	=> 'sans_web_font',
											'Serif Font Family'			=> 'serif_web_font',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],


			'headings_use_font'	=>	[
				'default_value'	=> '',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Headings Font',
					'description'		=> 'select which font family to use for headings',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default'					=> '',
											'Sans-Serif Font Family'	=> 'sans_web_font',
											'Serif Font Family'			=> 'serif_web_font',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],


			/*
			// THIS WILL OVERWITE THE BOOSTRAP VARIABLE!
			// discarded
			//
			'font-size-base'	=>	[ 
				'default_value'	=> '1rem',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Base Font Size',
					'description'		=> 'The initial font size of your website, ' .
						'from which all other font sizes are derived. For larger sizes ' .
						'the header heights may need to be adjusted accordingly.',

					'type'				=> 'select',
					'possible_values'	=> [
											'Small'			=> '0.875rem',		// ~14px
											'Default'		=> '1rem',			// ~16px 
											'Large'			=> '1.125rem',		// ~18px
											'Extra Large'	=> '1.25rem',		// ~20px
											'Very Large'	=> '1.5rem',		// ~24px
											],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],
			*/


		], // end of items
	], // end of collapsible UI area

];

