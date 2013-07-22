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
		common::LoadComponents( 'bootstrap-all' );
		gpOutput::GetHead();
		?>

		<!--[if lt IE 9]><?php
		// HTML5 shim, for IE6-8 support of HTML5 elements
		gpOutput::GetComponents( 'html5shiv' );
		?><![endif]-->
	</head>

	<body>

		<!-- Part 1: Wrap all page content here -->
		<div id="wrap">


			<!-- Fixed navbar -->
			<div class="navbar navbar-fixed-top">
				<div class="navbar-inner">
					<div class="container">
						<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>

						<?php
						global $config;
						echo common::Link('',$config['title'],'',' class="brand"');
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
									'haschildren'		=> 'dropdown-toggle',
									'haschildren_li'	=> 'dropdown',
									'child_ul'			=> 'dropdown-menu',
									);

							gpOutput::Get('TopTwoMenu'); //top two levels

							/*
							<ul class="nav">
								<li class="active"><a href="#">Home</a></li>
								<li><a href="#about">About</a></li>
								<li><a href="#contact">Contact</a></li>
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <b class="caret"></b></a>
									<ul class="dropdown-menu">
										<li><a href="#">Action</a></li>
										<li><a href="#">Another action</a></li>
										<li><a href="#">Something else here</a></li>
										<li class="divider"></li>
										<li class="nav-header">Nav header</li>
										<li><a href="#">Separated link</a></li>
										<li><a href="#">One more separated link</a></li>
									</ul>
								</li>
							</ul>
							*/

							?>
						</div><!--/.nav-collapse -->
					</div>
				</div>
			</div><!--/.navbar -->


			<!-- Begin page content -->
			<div class="container">
				<?php
				$page->GetContent();
				/*

				<div class="page-header">
					<h1>Sticky footer with fixed navbar</h1>
				</div>
				<p class="lead">Pin a fixed-height footer to the bottom of the viewport in desktop browsers with this custom HTML and CSS. A fixed navbar has been added within <code>#wrap</code> with <code>padding-top: 60px;</code> on the <code>.container</code>.</p>
				<p>Back to <a href="./sticky-footer.html">the sticky footer</a> minus the navbar.</p>
				*/
				?>
			</div>

			<div id="push"></div>
		</div>

		<div id="footer">
			<div class="container">
			<p class="muted credit">
			<?php
			gpOutput::GetAdminLink();
			?>
			</p>
			</div>
		</div>


	</body>
</html>
