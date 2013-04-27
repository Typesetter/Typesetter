<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<link href='http://fonts.googleapis.com/css?family=Courgette' rel='stylesheet' type='text/css'>
<?php gpOutput::GetHead(); ?>

</head><body><div id="wrapper">

	<div>

		<div id="search">
		<?php $_GET += array('q'=>''); ?>
		<form action="<?php echo common::GetUrl( 'special_gpsearch') ?>" method="get">
		<div>
		<input type="text" class="query" name="q" value="<?php echo htmlspecialchars($_GET['q']) ?>" />
		<input type="submit" class="submit" value="" />
		</div>
		</form>
		</div>


		<div id="logo">
		<?php
		global $config;
		$default_value = $config['title'];
		$GP_ARRANGE = false;
		gpOutput::GetArea('header',$default_value);
		?>
		</div>


		<div id="menu">
			<?php
			gpOutput::Get('Menu');
			?>
		</div>

	</div>

	<div id="image"><div>
	<?php
	if( is_callable(array('gpOutput','GetImage')) ){
		gpOutput::GetImage('images/shore.jpg', array('width'=>1000,'height'=>230) );
	}else{
		global $page;
		$img_src = dirname($page->theme_path).'/images/shore.jpg';
		echo '<img src="'.$img_src.'" width="1000" height="230"/>';
	}
	?>
	</div></div>

	<div class="cf">

		<div id="content" class="cf"><div id="content2">
		<?php $page->GetContent(); ?>
		</div></div>

		<div id="right" class="cf"><div class="right_content">
		<?php
		//gpOutput::GetArea('link_label','Links');
		gpOutput::Get('TopTwoMenu');
		gpOutput::GetAllGadgets();
		?>
		</div></div>

	</div>

	<div id="footer_cols">

	<div class="footer_col">
	<?php gpOutput::Get('Extra','Side_Menu'); ?>
	</div>

	<div class="footer_col">
	<?php gpOutput::Get('Extra','Footer'); ?>
	</div>

	<div  class="footer_col last">
	<?php gpOutput::Get('Extra','Lorem'); ?>
	</div>

	</div>

	<div id="footer">
	<?php
	gpOutput::GetAdminLink();
	?>
	</div>

</div>
</body></html>