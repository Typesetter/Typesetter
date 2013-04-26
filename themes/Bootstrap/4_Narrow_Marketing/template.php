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

		<div class="container-narrow">

			<div class="masthead">
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
						);
				gpOutput::Get('Menu'); //top level links
				?>


				<h3>
				<?php
				global $config;
				echo common::Link('',$config['title'],'',' class="muted"');
				?>
				</h3>
			</div>

			<hr>

			<div class="jumbotron">
				<?php
				$page->GetContent();
				/*
				<h1>Super awesome marketing speak!</h1>
				<p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
				<a class="btn btn-large btn-success" href="#">Sign up today</a>
				*/
				?>
			</div>

			<hr>

			<div class="row-fluid marketing">
				<div class="span6">
					<?php
					gpOutput::Get('Extra','Side_Menu');

					/*
					<h4>Subheading</h4>
					<p>Donec id elit non mi porta gravida at eget metus. Maecenas faucibus mollis interdum.</p>

					<h4>Subheading</h4>
					<p>Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cras mattis consectetur purus sit amet fermentum.</p>

					<h4>Subheading</h4>
					<p>Maecenas sed diam eget risus varius blandit sit amet non magna.</p>
					*/
					?>
				</div>

				<div class="span6">
					<?php
					gpOutput::Get('Extra','Footer');

					/*
					<h4>Subheading</h4>
					<p>Donec id elit non mi porta gravida at eget metus. Maecenas faucibus mollis interdum.</p>

					<h4>Subheading</h4>
					<p>Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cras mattis consectetur purus sit amet fermentum.</p>

					<h4>Subheading</h4>
					<p>Maecenas sed diam eget risus varius blandit sit amet non magna.</p>
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
