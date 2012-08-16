<?php

//tell the output preparation functions GetAllGadgets() is not being used in this template
//$GP_GETALLGADGETS = false;

/*
 * True WYSIWYG
 * 	This theme is configured to use True WYSIWYG editing in gpEasy
 * 	If you modify the HTML or CSS for this theme, you may need to
 *  look at how the $GP_STYLES variable below affects editing
 * 	See: http://docs.gpeasy.org/index.php/Main/True_WYSIWYG
 *
 */
$GP_STYLES = array();
$GP_STYLES[] = '#container';
$GP_STYLES[] = '#footercontainer';
$GP_STYLES[] = '#header3';


gpOutput::Area('link_label','<div class="links">%s</div>');


