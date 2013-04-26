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

		<div class="container">

			<div class="masthead">
				<h3>
				<?php
				global $config;
				echo common::Link('',$config['title'],'',' class="muted"');
				?>
				</h3>


				<div class="navbar">
					<div class="navbar-inner">
						<div class="container">
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
						</div>
					</div>
				</div><!-- /.navbar -->
			</div>

			<!-- Jumbotron -->
			<div class="jumbotron">
				<?php
				$page->GetContent();
				/*
				<h1>Marketing stuff!</h1>
				<p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
				<a class="btn btn-large btn-success" href="#">Get started today</a>
				*/
				?>
			</div>

			<hr>

			<!-- Example row of columns -->
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
				</div>
				<div class="span4">
					<?php
					gpOutput::Get('Extra','Footer');

					/*
					<h2>Heading</h2>
					<p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
					<p><a class="btn" href="#">View details &raquo;</a></p>
					*/
					?>
			 </div>
				<div class="span4">
					<?php
					gpOutput::Get('Extra','Lorem');

					/*
					<h2>Heading</h2>
					<p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa.</p>
					<p><a class="btn" href="#">View details &raquo;</a></p>
					*/
					?>
				</div>
			</div>

			<hr>

			<div class="footer">
			<?php
			gpOutput::GetAdminLink();
			?>
			</div>

		</div> <!-- /container -->

	</body>
</html>
