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
		<div class="navbar navbar-default navbar-fixed-top gp-fixed-adjust">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<?php
					global $config;
					echo common::Link('',$config['title'],'','class="navbar-brand"');
					?>
				</div>
				<div class="collapse navbar-collapse">
					<?php
					$GP_ARRANGE = false;
					$GP_MENU_CLASSES = array(
							'menu_top'			=> 'nav navbar-nav',
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

					gpOutput::Get('TopTwoMenu'); //top two levels
					?>

					<?php
					//search form
					global $langmessage;
					$_GET += array('q'=>'');
					?>
					<form class="navbar-form navbar-right" action="<?php echo common::GetUrl( 'special_gpsearch') ?>" method="get">
						<div class="form-group">
							<input name="q" type="text" class="form-control" value="<?php echo htmlspecialchars($_GET['q']) ?>" placeholder="<?php echo $langmessage['Search'] ?>">
						</div>
					</form>

				</div><!--/.nav-collapse -->
			</div>
		</div>


		<?php /* Main jumbotron for a primary marketing message or call to action */ ?>
		<div class="jumbotron">
			<div class="container">
			<?php
			$page->GetContent();
			?>
			</div>
		</div>



		<div class="container">
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
	</body>
</html>

