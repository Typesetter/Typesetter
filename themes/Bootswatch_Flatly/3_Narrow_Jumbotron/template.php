<?php

global $page;
$path = $page->theme_dir.'/drop_down_menu.php';
include_once($path);


?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<?php
		common::LoadComponents( 'bootstrap3-js' );
		gpOutput::GetHead();
		?>

		<!--[if lt IE 9]><?php
		// HTML5 shim, for IE6-8 support of HTML5 elements
		gpOutput::GetComponents( 'html5shiv' );
		gpOutput::GetComponents( 'respondjs' );
		?><![endif]-->
	</head>


	<body>
		<div class="container">
			<div class="header">
				<?php
				$GP_ARRANGE = false;
				$GP_MENU_CLASSES = array(
						'menu_top'			=> 'nav nav-pills pull-right',
						'selected'			=> '',
						'selected_li'		=> 'active',
						'childselected'		=> '',
						'childselected_li'	=> '',
						'li_'				=> '',
						'li_title'			=> '',
						'haschildren'		=> 'dropdown-toggle',
						'haschildren_li'	=> 'dropdown',
						'child_ul'			=> 'dropdown-menu',
						);

				gpOutput::Get('Menu');
				?>

				<h3 class="text-muted">
				<?php
				global $config;
				echo common::Link('',$config['title']);
				?>
				</h3>

			</div>


			<?php /* Main jumbotron for a primary marketing message or call to action */ ?>
			<div class="jumbotron">
				<?php
				$page->GetContent();
				?>
			</div>


			<!-- Example row of columns -->
			<div class="row">
				<div class="col-lg-4">
					<?php
					gpOutput::Get('Extra','Side_Menu');
					?>
				</div>
				<div class="col-lg-4">
					<?php
					gpOutput::Get('Extra','Footer');
					?>
				</div>
				<div class="col-lg-4">
					<?php
					gpOutput::Get('Extra','Lorem');
					?>
				</div>
			</div>

			<hr>

			<footer><p>
			<?php
			gpOutput::GetAdminLink();
			?>
			</p></footer>
		</div> <!-- /container -->

		</div> <!-- /container -->
	</body>
</html>

