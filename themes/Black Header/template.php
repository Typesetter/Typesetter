<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php gpOutput::GetHead(); ?>
</head>
<body>

	<div id="header"><div id="header2"><div id="header3">
			<?php gpOutput::Get('Extra','Header'); ?>
		<div id="menu">
			<?php
			$GP_ARRANGE = false;
			gpOutput::Get('Menu');
			?>
		</div>
		<div class="clear"></div>
	</div></div></div>

	<div id="container1">
	<div id="container">
		<div id="columncontainer">

			<div id="maincontent" class="column">
				<?php $page->GetContent(); ?>
			</div>

			<div id="left" class="column">
				<div class="leftnav">

				<?php
				gpOutput::GetArea('link_label','Links');
				gpOutput::Get('FullMenu');
				?>
				</div>
			</div>

			<div id="right" class="column">
				<div class="leftnav rightnav">
				<?php gpOutput::Get('Extra','Side_Menu'); ?>
				<?php gpOutput::GetAllGadgets(); ?>
				</div>
			</div>

		</div>
		<div class="clear"></div>
	</div>
	</div>

	<div id="footer-wrapper">
		<div id="footer">
		<div id="footercontainer">
			<?php gpOutput::Get('Extra','Footer'); ?>
			<?php gpOutput::GetAdminLink(); ?>
		</div>
		</div>
	</div>

</body>

</html>
