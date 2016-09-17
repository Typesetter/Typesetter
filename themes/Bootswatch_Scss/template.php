<?php

global $page;
$path = $page->theme_dir.'/drop_down_menu.php';
include_once($path);


?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

	<?php
	common::LoadComponents( 'bootstrap3-js' );
	gpOutput::GetHead();
	?>

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <!-- <link href="../../assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet"> -->


	<!--[if lt IE 9]><?php
	// HTML5 shim, for IE6-8 support of HTML5 elements
	gpOutput::GetComponents( 'html5shiv' );
	gpOutput::GetComponents( 'respondjs' );
	?><![endif]-->


  </head>
  <body>

	<nav class="navbar navbar-default navbar-static-top"><!--  navbar-fixed-top gp-fixed-adjust -->
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse" aria-expanded="false" aria-controls="navbar">
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
						'menu_top'			=> 'nav navbar-nav navbar-right',
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
		</div>
	</nav>


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
	</div>

  </body>
</html>

