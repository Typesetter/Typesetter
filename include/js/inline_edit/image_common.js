
	/*
	 * drop down menu
	 *
	 */
	gplinks.gp_show_select = function(rel,evt){
		evt.preventDefault();
		var $this = $(this);
		var $options = $this.siblings('.gp_edit_select_options');

		if( $options.is(':visible') ){
			$options.hide();
		}else{
			$options.show();

			$this.parent().unbind('mouseleave').bind('mouseleave',function(){
				$options.hide();
			});
		}

	}

	gplinks.gp_gallery_folder = function(rel,evt){
		evt.preventDefault();
		LoadImages(rel);
	}

	function LoadImages(directory){
		var edit_path = strip_from(gp_editor.save_path,'?');
		edit_path += '?cmd=gallery_images';
		if( directory ){
			edit_path += '&dir='+encodeURIComponent(directory);
		}
		$gp.jGoTo(edit_path);
	}



	/**
	 * Remove an image from the list of available images
	 *
	 */
	gpresponse.img_deleted_id = function(){
		$('#'+this.CONTENT).remove();
	}

