<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'sidebars' layout template
 */

// debug('$page = ' . pre(get_object_vars($page)) ); // TODO remove
// debug('$layout_config = ' . pre($layout_config) ); // TODO remove

// Include current layout functions.php
include_once($page->theme_dir . '/' . $page->theme_color . '/functions.php');

?><!DOCTYPE html>
<html lang="<?php echo $page->lang; ?>" 
	class="bootstrap-4 <?php gpOutput::GetPageInfoClasses(); echo $html_classes; ?>">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

		<?php
			gpOutput::GetHead(); // get head content
		?>

	</head>


	<body class="d-flex flex-column">

		<!--[if lte IE 9]>
			<div class="alert alert-warning">
				<h3>Please consider to use a modern web browser</h3>
				<p>We&rsquo;re sorry but Internet Explorer &lt; 10 support
					was dropped as of Bootstrap version 4</p>
			</div>
		<![endif]-->

		<a class="sr-only" href="#content">Skip to main content</a>

		<?php
			// render the complementary header?
			if( isset($layout_config['complementary_header_show']['value']) &&
				$layout_config['complementary_header_show']['value'] !== false
			):
		?>
			<header role="complementary"
				class="complementary-header <?php echo $complementary_header_classes; ?>">
				<nav class="complementary-nav navbar navbar-dark">
					<div class="no-container d-flex justify-content-between">
						<?php 
							gpOutput::Get('Extra', 'Header_Contact');
							gpOutput::Get('Extra', 'Header_SocialMedia');
							// you can use Layout Editor to rearrange, remove or add areas
						?>
					</div>
				</nav>
			</header>
		<?php endif; ?>

		<header class="main-header bg-dark">
			<nav class="main-nav navbar navbar-dark<?php echo $navbar_classes; ?>">
				<div class="no-container d-flex justify-content-between">

					<?php
						global $config;
						echo '<a class="navbar-brand d-flex align-items-center" href="' . common::GetUrl('') . '" >';
						echo $brand_logo_img;
						gpOutput::GetArea('Site-Name', $config['title']); // as defined in theme settings.php
						echo '</a>';
					?>

					<button class="navbar-toggler" type="button"
						data-toggle="collapse" data-target="#navbarResponsive"
						aria-controls="navbarResponsive" aria-expanded="false"
						aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
					</button>

					<div class="collapse navbar-collapse" id="navbarResponsive">

						<div class="<?php echo $menu_alignment_class; ?>"><!--menu alignment: ml-auto | mx-auto | mr-auto -->
							<?php
								// $GP_ARRANGE = false; // prevent deleting the menu via Layout Manager
								$GP_MENU_ELEMENTS = 'MainNavElements'; // menu formatting function name, see functions.php
								$GP_MENU_CLASSES = [
									'menu_top'			=> 'navbar-nav',
									'a'					=> '',
									'selected'			=> 'active',
									'selected_li'		=> '',
									'childselected'		=> 'active',
									'childselected_li'	=> 'active', // use '' if you do not want 1st-level nav items to indicate that a child item is active
									'li_'				=> 'nav-item nav-item-',
									'li_title'			=> '',
									'haschildren'		=> 'dropdown-toggle',
									'haschildren_li'	=> 'dropdown',
									'child_ul'			=> $menu_dropdown_alignment_class, // 'dropdown-menu' | 'dropdown-menu dropdown-menu-right'
								];
								// gpOutput::Get('TopTwoMenu'); // Top two level menu
								gpOutput::Get('Menu'); // Only top level menu
							?>
						</div><!--/.ml-auto -->

					</div><!--/.collapse -->

				</div><!--/.container -->
			</nav><!--/.main-nav -->
		</header><!--/.main-header -->


		<div class="main-body position-relative flex-grow-1 d-flex">

			<main role="main" class="main-content flex-grow-1" id="content">
				<div class="no-container">
					<?php
						$GP_MENU_ELEMENTS = ''; // menu formatting function name
						$GP_MENU_CLASSES = [
							'menu_top'			=> 'breadcrumb',
							'a'					=> '',
							'selected'			=> '',
							'selected_li'		=> 'active',
							'childselected'		=> '',
							'childselected_li'	=> '',
							'li_'				=> 'breadcrumb-item',
							'li_title'			=> '',
							'haschildren'		=> '',
							'haschildren_li'	=> '',
							'child_ul'			=> '',
						];

						gpOutput::Get(); // empty 'area slot' e.g. to add a breadcrumb nav via Layout Editor

						if( !empty($layout_config['show_breadcrumb_nav']['value']) ){
							// gpOutput::BreadcrumbNav();
							gpOutput::Get('Breadcrumbs');
						}

						$page->GetContent(); // get the page content
					?>
				</div><!-- /.container-->
			</main><!-- /.main-content -->

			<?php
				// prepare sidebar menu rendering
				$GP_MENU_ELEMENTS = '';
				$GP_MENU_CLASSES = [
					'menu_top'			=> 'nav nav-stacked nav-stacked-first-level',
					'selected'			=> '',
					'selected_li'		=> 'active expanded',
					'childselected'		=> '',
					'childselected_li'	=> 'expanded',
					'li_'				=> '',
					'li_title'			=> '',
					'haschildren'		=> '',
					'haschildren_li'	=> 'expandable',
					'child_ul'			=> 'nav nav-stacked',
				];
			?>


			<?php if( !empty($layout_config['show_left_sidebar']['value']) ): ?>
				<aside class="sidebar sidebar-left<?php echo $sidebar_left_class; ?>">
					<div class="sidebar-container">
						<?php
							gpOutput::GetArea('Search-Gadget', ''); // as defined in settings.php
							gpOutput::Get('CustomMenu', 1, 4, 1, 1); // 2nd-level+ links

							// Simple Blog Gadgets, if installed
							if( gpOutput::GadgetExists('Simple_Blog_Categories') ){
								gpOutput::GetArea('Simple-Blog-Categories-Gadget', ''); // as defined in settings.php
							}
							if( gpOutput::GadgetExists('Simple_Blog_Archives') ){
								gpOutput::GetArea('Simple-Blog-Archives-Gadget', ''); // as defined in settings.php
							}

							gpOutput::GetArea('Admin-Link-Area', ''); // as defined in settings.php
						?>
					</div><!-- /.sidebar-container -->
				</aside><!-- /.sidebar-left -->
			<?php endif; ?>


			<?php if( !empty($layout_config['show_right_sidebar']['value']) ): ?>
				<aside class="sidebar sidebar-right<?php echo $sidebar_right_class; ?>">
					<div class="sidebar-container">
						<?php
							gpOutput::Get('Extra', 'Side_Menu');

							// Simple Blog Main Gadges, if installed
							if( gpOutput::GadgetExists('Simple_Blog') ){
								gpOutput::GetArea('Simple-Blog-Gadget', ''); // as defined in settings.php
							}
						?>
					</div><!-- /.sidebar-container -->
				</aside><!-- /.sidebar-right -->
			<?php endif; ?>

		</div><!-- /.main-body -->

		<?php
			// we call GetMessages here because
			// flex-order, which we use for the sidebars, messes up the 'natural' z-index layering of the DOM
			// therefore we prevent message output in the 'Admin-Link-Area' defined in theme settings.php
			echo GetMessages();
		?>

		<div id="detect-bootstrap-breakpoints">
			<div class="breakpoint-xs d-block d-sm-none d-md-none d-lg-none d-xl-none" data-breakpoint="xs"></div>
			<div class="breakpoint-sm d-none d-sm-block d-md-none d-lg-none d-xl-none" data-breakpoint="sm"></div>
			<div class="breakpoint-md d-none d-sm-none d-md-block d-lg-none d-xl-none" data-breakpoint="md"></div>
			<div class="breakpoint-lg d-none d-sm-none d-md-none d-lg-block d-xl-none" data-breakpoint="lg"></div>
			<div class="breakpoint-xl d-none d-sm-none d-md-none d-lg-none d-xl-block" data-breakpoint="xl"></div>
		</div>
		<div id="breakpoint-navbar-expanded" class="d-none d-<?php echo $navbar_expand_breakpoint; ?>-block"></div>

</body>
</html>
