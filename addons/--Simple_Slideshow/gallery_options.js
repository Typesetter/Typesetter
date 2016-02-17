
/*
var gp_gallery_options = {
	sortable_area_sel	: '.gp_slideshow',
	img_name			: 'gp_slideshow',
	img_rel				: '',
	auto_start			:	true,
	intervalSpeed		: function(){},
	updateCaption		: function(current_image, caption){
		$(current_image).find('a').attr('title',caption);
	}
};
*/

$.extend(gp_editor,{

	sortable_area_sel	: '.gp_slideshow',
	img_name			: 'gp_slideshow',
	img_rel				: '',
	auto_start			:	true,
	intervalSpeed		: function(){},
	updateCaption		: function(current_image, caption){
		$(current_image).find('a').attr('title',caption);
	}

});

