<?php

global $page;
$path = $page->theme_dir.'/../drop_down_menu.php';
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

		<?php
		/**
		 * NAVBAR
		 *
		 */?>
		<div class="navbar-wrapper">
			<!-- Wrap the .navbar in .container to center it within the absolutely positioned parent. -->
			<div class="container">

				<div class="navbar navbar-inverse">
					<div class="navbar-inner">
						<!-- Responsive Navbar Part 1: Button for triggering responsive navbar (not covered in tutorial). Include responsive CSS to utilize. -->
						<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>

						<?php
						global $config;
						echo common::Link('',$config['title'],'',' class="brand"');
						?>

						<!-- Responsive Navbar Part 2: Place all navbar contents you want collapsed withing .navbar-collapse.collapse. -->
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
								<!-- Read about Bootstrap dropdowns at http://twitter.github.com/bootstrap/javascript.html#dropdowns -->
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
					</div><!-- /.navbar-inner -->
				</div><!-- /.navbar -->

			</div> <!-- /.container -->
		</div><!-- /.navbar-wrapper -->



		<!-- Carousel
		================================================== -->
		<div id="myCarousel" class="carousel slide">
			<div class="carousel-inner">


				<div class="item active">
					<img src="../assets/img/examples/slide-01.jpg" alt="">
					<div class="container">
						<div class="carousel-caption">
							<h1>Example headline.</h1>
							<p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
							<a class="btn btn-large btn-primary" href="#">Sign up today</a>
						</div>
					</div>
				</div>


				<div class="item">
					<img src="../assets/img/examples/slide-02.jpg" alt="">
					<div class="container">
						<div class="carousel-caption">
							<h1>Another example headline.</h1>
							<p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
							<a class="btn btn-large btn-primary" href="#">Learn more</a>
						</div>
					</div>
				</div>


				<div class="item">
					<img src="../assets/img/examples/slide-03.jpg" alt="">
					<div class="container">
						<div class="carousel-caption">
							<h1>One more for good measure.</h1>
							<p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
							<a class="btn btn-large btn-primary" href="#">Browse gallery</a>
						</div>
					</div>
				</div>


			</div>
			<a class="left carousel-control" href="#myCarousel" data-slide="prev">&lsaquo;</a>
			<a class="right carousel-control" href="#myCarousel" data-slide="next">&rsaquo;</a>
		</div><!-- /.carousel -->



		<!-- Marketing messaging and featurettes
		================================================== -->
		<!-- Wrap the rest of the page in another container to center all the content. -->

		<div class="container marketing">

			<!-- Three columns of text below the carousel -->
			<div class="row">
				<div class="span4">
					<img class="img-circle" data-src="holder.js/140x140">
					<h2>Heading</h2>
					<p>Donec sed odio dui. Etiam porta sem malesuada magna mollis euismod. Nullam id dolor id nibh ultricies vehicula ut id elit. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Praesent commodo cursus magna, vel scelerisque nisl consectetur et.</p>
					<p><a class="btn" href="#">View details &raquo;</a></p>
				</div><!-- /.span4 -->
				<div class="span4">
					<img class="img-circle" data-src="holder.js/140x140">
					<h2>Heading</h2>
					<p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Cras mattis consectetur purus sit amet fermentum. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
					<p><a class="btn" href="#">View details &raquo;</a></p>
				</div><!-- /.span4 -->
				<div class="span4">
					<img class="img-circle" data-src="holder.js/140x140">
					<h2>Heading</h2>
					<p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
					<p><a class="btn" href="#">View details &raquo;</a></p>
				</div><!-- /.span4 -->
			</div><!-- /.row -->


			<!-- START THE FEATURETTES -->

			<hr class="featurette-divider">

			<div class="featurette">
				<img class="featurette-image pull-right" src="../assets/img/examples/browser-icon-chrome.png">
				<h2 class="featurette-heading">First featurette headling. <span class="muted">It'll blow your mind.</span></h2>
				<p class="lead">Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>
			</div>

			<hr class="featurette-divider">

			<div class="featurette">
				<img class="featurette-image pull-left" src="../assets/img/examples/browser-icon-firefox.png">
				<h2 class="featurette-heading">Oh yeah, it's that good. <span class="muted">See for yourself.</span></h2>
				<p class="lead">Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>
			</div>

			<hr class="featurette-divider">

			<div class="featurette">
				<img class="featurette-image pull-right" src="../assets/img/examples/browser-icon-safari.png">
				<h2 class="featurette-heading">And lastly, this one. <span class="muted">Checkmate.</span></h2>
				<p class="lead">Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>
			</div>

			<hr class="featurette-divider">

			<!-- /END THE FEATURETTES -->


			<!-- FOOTER -->
			<footer>
				<p class="pull-right"><a href="#">Back to top</a></p>
				<p>
				<?php
				gpOutput::GetAdminLink();
				?>
				</p>
			</footer>

		</div><!-- /.container -->


		<script>
				$(function(){
					// carousel demo
					$('#myCarousel').carousel()
				});
		</script>
	</body>
</html>
