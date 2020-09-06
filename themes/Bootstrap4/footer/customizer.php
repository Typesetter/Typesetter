<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'footer' layout customizer definition
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'description'		=> 'Adjust the height of the logo. ' .
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'header_brand_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Brand Font Weight',
					'description'		=> 'choose the weight for the brand / site title',

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
											'Heavy / Black' => '900',
											],
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

			'main_menu_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Main Menu Font Family',
					'description'		=> 'choose a font family for the main menu',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'description'		=> 'make the main menu items all uppercase',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
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
					'possible_values'	=> [
											'Thin'			=> '100',
											'Extra Light'	=> '200',
											'Light'			=> '300',
											'Regular'		=> '400',
											'Medium'		=> '500',
											'Semi Bold'		=> '600',
											'Bold'			=> '700',
											'Extra Bold'	=> '800',
											'Heavy / Black' => '900',
											],
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
						'to pad the footer\'s content left and right',

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

			'footer_border_top_width'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'px',

				'control' => [
					'label'				=> 'Footer Top Border Width',
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

			'footer_border_top_color'	=>	[
				'default_value'	=> 'rgba(127,127,127,0.25)',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Top Border Color',
					'description'		=> 'pick a border color for the footer',
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

			'footer_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Font Family',
					'description'		=> 'choose a font family for footer text',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],
			
			'footer_font_size'	=>	[
				'default_value'	=> '1',
				'default_units'	=> 'rem',

				'control' => [
					'label'				=> 'Footer Font Size',
					'description'		=> 'the font size of text in the footer',

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

			'footer_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Font Weight',
					'description'		=> 'choose a font weight for footer text',

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
											'Heavy / Black' => '900',
											],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
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

			'footer_headings_font_family'	=>	[
				'default_value'	=> 'default_sans',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Headings Font Family',
					'description'		=> 'choose a font family for headings in the footer',

					'type'				=> 'select',
					'possible_values'	=> [
											'Default Sans-Serif'	=> 'default_sans',
											'Default Serif'			=> 'default_serif',
											'Alegreya Sans'			=> 'alegreya_sans',
											'Alegreya Serif'		=> 'alegreya',
											'Bitter'				=> 'bitter',
											'EB Garamond'			=> 'eb_garamond',
											'Fira Sans'				=> 'fira_sans',
											'Inter'					=> 'inter',
											'Lora'					=> 'lora',
											'Merriweather'			=> 'merriweather',
											'Montserrat'			=> 'montserrat',
											'Nunito'				=> 'nunito',
											'Open Sans'				=> 'open_sans',
											'Playfair Display'		=> 'playfair_display',
											'Raleway'				=> 'raleway',
											'Roboto'				=> 'roboto',
											'Roboto Slab'			=> 'roboto_slab',
											'Source Sans Pro'		=> 'source_sans_pro',
											'Ubuntu'				=> 'ubuntu',
											'Work Sans'				=> 'work_sans',
											'Zilla Slab'			=> 'zilla_slab',
											],
					'used_in'			=> ['scssless'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'footer_headings_font_weight'	=>	[
				'default_value'	=> '400',
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Headings Font Weight',
					'description'		=> 'choose a font weight for headings in the footer',

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
											'Heavy / Black' => '900',
											],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> '',
				],
			],

			'footer_headings_uppercase'	=>	[
				'default_value'	=> false,
				'default_units'	=> '',

				'control' => [
					'label'				=> 'Footer Headings Uppercase',
					'description'		=> 'make all headings in the footer uppercase',

					'type'				=> 'checkbox',
					'possible_values'	=> [],
					'used_in'			=> ['scssless', 'css'],
					'units'				=> [],
					'pattern'			=> 'onoff',
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
