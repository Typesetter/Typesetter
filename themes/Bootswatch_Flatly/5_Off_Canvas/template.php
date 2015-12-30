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
		global $page;
		$page->head_js[] = rawurldecode($page->theme_path).'/script.js';

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
		<div class="navbar navbar-default navbar-fixed-top gp-fixed-adjust" role="navigation">
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
				</div><!--/.nav-collapse -->
			</div><!-- /.container -->
		</div><!-- /.navbar -->

		<div class="container">

			<div class="row row-offcanvas row-offcanvas-right">
				<div class="col-xs-12 col-sm-9">
					<p class="pull-right visible-xs">
						<button type="button" class="btn btn-primary btn-xs" data-toggle="offcanvas">Toggle nav</button>
					</p>
					<div class="jumbotron">
						<?php
						$page->GetContent();
						?>
					</div>
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
					</div><!--/row-->
				</div><!--/span-->

				<div class="col-xs-6 col-sm-3 sidebar-offcanvas" id="sidebar" role="navigation">
					<div class="well sidebar-nav">
						<?php

						$GP_MENU_CLASSES = array(
								'menu_top'			=> 'nav',
								'selected'			=> '',
								'selected_li'		=> 'active',
								'childselected'		=> '',
								'childselected_li'	=> '',
								'li_'				=> '',
								'li_title'			=> '',
								);
						gpOutput::Get('CustomMenu','1,2,0,0');

						/*
						<ul class="nav">
							<li>Sidebar</li>
							<li class="active"><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li>Sidebar</li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li>Sidebar</li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
						</ul>
						*/
						?>
					</div><!--/.well -->
				</div><!--/span-->
			</div><!--/row-->

			<hr>

			<footer><p>
			<?php
			gpOutput::GetAdminLink();
			?>
			</p></footer>

		</div><!--/.container-->

	</body>
</html>

