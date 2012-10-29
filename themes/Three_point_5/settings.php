<?php

/*
 * True WYSIWYG
 * 	This theme is configured to use True WYSIWYG editing in gpEasy
 * 	If you modify the HTML or CSS for this theme, you may need to
 *  look at how the $GP_STYLES variable below affects editing
 * 	See: http://docs.gpeasy.org/index.php/Main/True_WYSIWYG
 *
 */

$GP_GETALLGADGETS = true;
$GP_STYLES = array();
$GP_STYLES[] = '#content2';
$GP_STYLES[] = '.right_content';
$GP_STYLES[] = '.footer_col';

$link = common::Link('','%s');
gpOutput::Area('header','<h1>'.$link.'</h1>');
gpOutput::Area('link_label','<h3>%s</h3>');