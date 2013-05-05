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
				<div class="container">
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
					gpOutput::Get('Menu');
					?>
					</div><!--/.nav-collapse -->
				</div>
			</div>
		</div>

		<div class="container">
		<?php
		$page->GetContent();
		?>
		<hr/>
		<footer><p>
		<?php
		gpOutput::GetAdminLink();
		?>
		</p></footer>

		</div> <!-- /container -->
	</body>
</html>
