
$.extend(gp_editor,{

	sortable_area_sel:	'.slideshowb_icons ul',
	img_name:			'slideshowb_img',
	img_rel:			'',


	/**
	 * Update the caption if the current image is the active image
	 *
	 */
	updateCaption: function(current_image,text){
		var $li = $(current_image);
		if( $li.index() === 0 ){
			$li.closest('.slideshowb_wrap').find('.slideshowb_caption').html(text+'&nbsp;');
		}
	},

	removeImage: function(image){
		var $li = $(image);
		if( $li.index() === 0 ){
			$li.closest('.slideshowb_wrap').find('.slideshowb_icons li').eq(1).find('a').click();
		}
	}

});

