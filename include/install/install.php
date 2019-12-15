<?php
defined('is_running') or die('Not an entry point...');

?>

<!DOCTYPE html>
<html>
<head>
<title>Typesetter Installation</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta name="robots" content="noindex,nofollow"/>
<script type="text/javascript">

function toggleOptions(){
	var options = document.getElementById('config_options').style;
	if( options.display == '' ){
		options.display = 'none';
	}else{
		options.display = '';
	}
}

</script>

<?php
\gp\install\Tools::AddCSs();
?>

</head>
<body>
<div class="wrapper">

<?php

$installer = new \gp\install\Installer();
$installer->Run();
echo \gp\tool::ErrorBuffer(false);
?>

</div>
</body></html>
