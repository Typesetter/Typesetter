<?php
/**
 * Bootstrap 4 - Typesetter CMS theme
 * 'starter' layout template
 */

// debug('$page = ' . pre(get_object_vars($page)) );
// debug('$layout_config = ' . pre($layout_config) );

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


	<body>

		<!--[if lte IE 9]>
			<div class="alert alert-warning">
				<h3>Please consider to use a modern web browser</h3>
				<p>We&rsquo;re sorry but Internet Explorer &lt; 10 support
					was dropped as of Bootstrap version 4</p>
			</div>
		<![endif]-->

		<!-- Link for screen readers -->
		<a class="sr-only" href="#content">Skip to main content</a>

		<?php
			// render the complementary header?
			if( !empty($layout_config['complementary_header_show']['value']) &&
				$layout_config['complementary_header_show']['value'] != 'off'
			):
		?>
			<header role="complementary"
				class="complementary-header bg-dark <?php echo $complementary_header_classes; ?>">
				<nav class="complementary-nav navbar navbar-dark">
					<div class="<?php echo $complementary_header_container_class; ?> d-flex justify-content-between">
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
				<div class="<?php echo $header_container_class; ?> d-flex justify-content-between">

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

						<div class="ml-auto"><!--navbar alignment: ml-auto | mx-auto | mr-auto -->
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
									'child_ul'			=> 'dropdown-menu dropdown-menu-right', // add 'dropdown-menu-right' with right-aligned nav (.navbar-collapse > div.ml-auto)
								];

								gpOutput::Get('TopTwoMenu'); // Top two level menu
							?>
						</div><!--/.ml-auto -->

						<?php
							// gpOutput::Get(); // empty 'area slot' to add extra content via Layout Editor
							gpOutput::GetArea('Search-Gadget','');
						?>

					</div><!--/.collapse -->

				</div><!--/.container -->
			</nav><!--/.main-nav -->
		</header><!--/.main-header -->

		<div class="main-body position-relative">
			<main role="main" class="main-content" id="content">
				<div class="<?php echo $content_container_class; ?>">
					<?php
						gpOutput::Get(); // empty 'area slot' e.g. to add a breadcrumb nav via Layout Editor
						$page->GetContent(); // get the page content
						// #gpAfterContent area outputs here

						// Simple Blog Gadgets, if installed
						if( gpOutput::GadgetExists('Simple_Blog') ){
							gpOutput::GetArea('Simple-Blog-Gadget', ''); // as defined in settings.php
						}

						if( gpOutput::GadgetExists('Simple_Blog_Categories') ){
							gpOutput::GetArea('Simple-Blog-Categories-Gadget', ''); // as defined in settings.php
						}

						if( gpOutput::GadgetExists('Simple_Blog_Archives') ){
							gpOutput::GetArea('Simple-Blog-Archives-Gadget', ''); // as defined in settings.php
						}
					?>
				</div><!-- /.container-->
			</main><!-- /.main-content -->
		</div><!-- /.main-body -->


		<footer class="main-footer mt-2 mt-md-4 pb-2">

			<?php
				// assign footer nav classes for possible footer menus (e.g. added via Layout Editor)
				$GP_MENU_ELEMENTS = ''; // define menu formatting function name
				$GP_MENU_CLASSES = [
					'menu_top'			=> 'footer-nav mb-1',
					'selected'			=> 'active',
					'selected_li'		=> '',
					'childselected'		=> '',
					'childselected_li'	=> '',
					'li_'				=> 'li-',
					'li_title'			=> '',
					'haschildren'		=> 'subitem-link',
					'haschildren_li'	=> 'subitem',
					'child_ul'			=> 'subitem-menu',
				]; // assign footer nav classes
			?>

			<div class="<?php echo $footer_container_class; ?>">

				<hr/>

				<div class="row">

					<div class="col-12 col-sm text-center text-sm-left">
						<?php
							gpOutput::Get('Extra', 'Copyright_Notice');
						?>
					</div>

					<div class="col-12 col-sm-auto text-center">
						<?php
							gpOutput::Get(); // empty 'area slot' for Layout Editor
						?>
					</div>

					<div class="col-12 col-sm text-center text-sm-right">
						<?php
							gpOutput::GetArea('Admin-Link-Area', ''); // as defined in theme settings.php
						?>
					</div>

				</div><!-- /.row -->

			</div><!-- /.container -->
		</footer><!-- /.main-footer -->

		<?php
			// we call GetMessages here because
			// flex-order can mess up the 'natural' z-index layering of the DOM
			// therefore we prevent message output in the 'Admin-Link-Area' defined in theme settings.php
			echo GetMessages();
		?>

	</body>
</html>
