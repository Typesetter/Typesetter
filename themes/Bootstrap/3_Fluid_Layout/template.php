<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<?php
		common::LoadComponents( 'bootstrap-all' );
		gpOutput::GetHead();
		?>

		<!--[if lt IE 9]><?php
		// HTML5 shim, for IE6-8 support of HTML5 elements
		gpOutput::GetComponents( 'html5shiv' );
		?><![endif]-->

	</head>

	<body>

		<div class="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container-fluid">
					<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<?php
					global $config;
					echo common::Link('',$config['title'],'','class="brand"');
					?>
					<div class="nav-collapse collapse">

						<?php
						//search form
						$_GET += array('q'=>''); ?>
						<form class="navbar-form pull-right" action="<?php echo common::GetUrl( 'special_gpsearch') ?>" method="get">
							<input class="span2" type="text"  name="q" value="<?php echo htmlspecialchars($_GET['q']) ?>" />
							<button class="btn" type="submit">Search</button>
						</form>


						<?php
						$GP_ARRANGE = false;
						$GP_MENU_CLASSES = array(
								'menu_top'			=> 'nav',
								'selected'			=> '',
								'selected_li'		=> 'active',
								'childselected'		=> '',
								'childselected_li'	=> '',
								'li_'				=> '',
								'li_title'			=> '',
								);
						gpOutput::Get('Menu'); //top level links
						?>

					</div><!--/.nav-collapse -->
				</div>
			</div>
		</div>

		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span3">
					<div class="well sidebar-nav">

						<?php

						$GP_MENU_CLASSES = array(
								'menu_top'			=> 'nav nav-list',
								'selected'			=> '',
								'selected_li'		=> 'active',
								'childselected'		=> '',
								'childselected_li'	=> '',
								'li_'				=> '',
								'li_title'			=> '',
								);
						gpOutput::Get('CustomMenu','1,2,0,0');

						/*
						<ul class="nav nav-list">
							<li class="nav-header">Sidebar</li>
							<li class="active"><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li class="nav-header">Sidebar</li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li class="nav-header">Sidebar</li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
						</ul>
						*/
						?>


					</div><!--/.well -->
				</div><!--/span-->
				<div class="span9">
					<div class="hero-unit">
						<?php
						$page->GetContent();
						/*
						<h1>Hello, world!</h1>
						<p>This is a template for a simple marketing or informational website. It includes a large callout called the hero unit and three supporting pieces of content. Use it as a starting point to create something more unique.</p>
						<p><a href="#" class="btn btn-primary btn-large">Learn more &raquo;</a></p>
						*/
						?>
					</div>

					<div class="row-fluid">
						<div class="span4">
							<?php
							gpOutput::Get('Extra','Side_Menu');
							/*
							<h2>Heading</h2>
							<p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
							<p><a class="btn" href="#">View details &raquo;</a></p>
							*/
							?>
						</div><!--/span-->
						<div class="span4">
							<?php
							gpOutput::Get('Extra','Footer');
							/*
							<h2>Heading</h2>
							<p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
							<p><a class="btn" href="#">View details &raquo;</a></p>
							*/
							?>
						</div><!--/span-->
						<div class="span4">
							<?php
							gpOutput::Get('Extra','Lorem');

							/*
							<h2>Heading</h2>
							<p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
							<p><a class="btn" href="#">View details &raquo;</a></p>
							*/
							?>
						</div><!--/span-->
					</div><!--/row-->
				</div><!--/span-->
			</div><!--/row-->

			<hr>

			<footer>
			<?php
			gpOutput::GetAdminLink();
			?>
			</footer>

		</div><!--/.fluid-container-->

	</body>
</html>
