<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<?php gpOutput::GetHead(); ?>

</head>
<body>

	<div id="header"><div class="set_width">
			<?php
			gpOutput::Get('Extra','Header');
			?>

		<div id="menu">
			<?php
			$GP_ARRANGE = false;
			gpOutput::Get('Menu');
			?>
			<div class="clear"></div>
		</div>
	</div></div>

	<div id="submenu"><div class="set_width">

		<?php
		$GP_ARRANGE = false;
		gpOutput::Get('SubMenu');
		?>
		<div class="clear"></div>
	</div></div>

	<div id="bodywrapper" class="set_width">
		<div id="sidepanel">
			<div id="fullmenu">
				<?php gpOutput::Get('FullMenu'); ?>
			</div>
			<?php
			gpOutput::Get('Extra','Side_Menu');
			gpOutput::GetAllGadgets();
			?>
		</div>
		<div id="content">
			<?php
			$page->GetContent();
			?>
		</div>

		<div style="clear:both"></div>
	</div>

	<div id="footer"><div class="set_width">
		<?php
		gpOutput::Get('Extra','Footer');
		gpOutput::GetAdminLink();
		?>
	</div></div>

</body>
</html>
