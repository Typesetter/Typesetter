<?php
defined('is_running') or die('Not an entry point...');


$query = '';
if( isset($_GET['q']) ){
	$query = $_GET['q'];
}


echo '<h3>';
echo gpOutput::GetAddonText('Search');
echo '</h3>';
echo '<form action="'.common::GetUrl('Special_Search').'" method="get">';
echo '<div>';
echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($query).'"/>';
echo '<input type="hidden" name="src" value="gadget" />';

$html = '<input type="submit" class="submit" name="" value="%s" />';
echo gpOutput::GetAddonText('Search',$html);

echo '</div>';
echo '</form>';
