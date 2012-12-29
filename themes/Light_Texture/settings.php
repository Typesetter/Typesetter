<?php

$GP_GETALLGADGETS = true;

$link = common::Link('','%s');
gpOutput::Area('header','<h1>'.$link.'</h1>');



/*
 * Settings for True WYSIWYG
 * No longer needed with ckeditor 4
 * @deprecated 3.6
 */

$GP_STYLES = array();
$GP_STYLES[] = '#content';
$GP_STYLES[] = '.footarea';
