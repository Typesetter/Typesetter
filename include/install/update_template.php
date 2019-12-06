<?php
defined('is_running') or die('Not an entry point...');
global $page;

?><!DOCTYPE html>
<html class="admin_body">
<head>
<meta name="robots" content="noindex,nofollow"/>


<?php
\gp\tool\Output::getHead();
?>

</head>

<?php
\gp\install\Tools::AddCSs();
?>

<body>

<div class="wrapper">

<h1>Typesetter Updater</h1>

<?php $page->GetContent(); ?>

</div>
</body>

</html>
