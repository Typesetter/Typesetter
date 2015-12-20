<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');

class LayoutEdit extends Layout{

	protected $layout_request = true;

	public function __construct(){
		global $page, $gpLayouts;

		parent::__construct();


		//layout request
		$parts = explode('/',$page->requested);
		if( isset($parts[2]) && isset($gpLayouts[$parts[2]]) ){
			$this->EditLayout($parts[2]);
			return;
		}

		//default layout
		if( empty($parts[2]) ){
			$this->EditLayout($this->curr_layout);
			return;
		}

		//redirect
		$url = \common::GetUrl('Admin_Theme_Content','',false);
		\common::Redirect($url,302);
	}



	/**
	 * Edit layout properties
	 * 		Layout Identification
	 * 		Content Arrangement
	 * 		Gadget Visibility
	 *
	 */
	public function EditLayout($layout){
		global $page,$gpLayouts,$langmessage,$config;

		$cmd = \common::GetCommand();

		$GLOBALS['GP_ARRANGE_CONTENT'] = true;
		$page->head_js[] = '/include/js/inline_edit/inline_editing.js';


		$this->curr_layout = $layout;
		$this->SetLayoutArray();
		$page->SetTheme($layout);

		$this->LoremIpsum();

		\gpOutput::TemplateSettings();

		\gpPlugin::Action('edit_layout_cmd',array($layout));

		switch($cmd){

			/**
			 * Inline image editing
			 *
			 */
			case 'inlineedit':
				$this->InlineEdit();
			return;
			case 'gallery_folder':
			case 'gallery_images':
				$this->GalleryImages();
			return;
			case 'image_editor':
				includeFile('tool/editing.php');
				\gp_edit::ImageEditor($this->curr_layout);
			return;
			case 'save_inline':
				$this->SaveHeaderImage();
			return;

			case 'theme_images':
				$this->ShowThemeImages();
			return;

			case 'drag_area':
				$this->Drag();
			break;

			//insert
			case 'insert':
				$this->SelectContent();
			return;

			case 'addcontent':
				$this->AddContent();
			break;

			//remove
			case 'rm_area':
				$this->RemoveArea();
			break;

		}

		if( $this->LayoutCommands($cmd) ){
			return;
		}


		//control what is displayed
		switch( $cmd ){

			//show the layout (displayed within an iframe)
			case 'save_css':
			case 'preview_css':
			case 'addcontent':
			case 'rm_area':
			case 'drag_area':
			case 'in_iframe':
				$this->ShowInIframe($cmd);
			return;
		}



		$layout_info = \common::LayoutInfo($layout,false);
		$handlers_count = 0;
		if( isset($layout_info['handlers']) && is_array($layout_info['handlers']) ){
			foreach($layout_info['handlers'] as $val){
				$int = count($val);
				if( $int === 0){
					$handlers_count++;
				}
				$handlers_count += $int;
			}
		}

		$page->label = $langmessage['layouts'] . ' » '.$layout_info['label'];

		$_REQUEST += array('gpreq' => 'body'); //force showing only the body as a complete html document
		$page->get_theme_css = false;


		ob_start();
		$this->LayoutEditor($layout, $layout_info );
		$page->admin_html = ob_get_clean();
	}


}