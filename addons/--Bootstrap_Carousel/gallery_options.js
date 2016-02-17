

$.extend(gp_editor,{

	sortable_area_sel:	'.carousel-indicators',
	img_name:			'',
	img_rel:			'',
	edit_links_target:	'.carousel-inner .item',

	make_sortable:		false,
	auto_start:			true,


	/**
	 * Listen for an image being added
	 *
	 */
	addedImage: function($li){
		var src			= $li.find('a:first').attr('href');
		var carousel	= $li.closest('.gp_twitter_carousel');
		var $inner		= carousel.find('.carousel-inner');
		var blank		= $('.gp_blank_img').data('src') || '';
		var $item		= $('<div class="item"><img src="'+blank+'" style="background-image:url('+src+')"><div class="caption carousel-caption no_caption"></div></div>').appendTo($inner);

		$li.attr('data-target','#'+carousel.attr('id')).attr('data-slide-to',$li.siblings().length);

		if( !$item.siblings().length ){
			$item.addClass('active');
			$li.addClass('active');
		}

	},


	/**
	 * Update the caption if the current image is the active image
	 *
	 */
	updateCaption : function(current_image,text){
		var caption_div = $(current_image).find('.caption');
		test = text.replace(/^\s+/,'');
		if( text == '' ){
			caption_div.addClass('no_caption');
		}else{
			caption_div.removeClass('no_caption');
		}
	},



	/**
	 * Remove an image
	 * Reset data-slide-to
	 */
	removeImage : function(current_image){

		var index				= $(current_image).index();
		var $indicator_area		= $('.gp_editing .carousel-indicators');

		//remove indicator
		$indicator_area.children().eq(index).remove();

		$('.gp_editing').carousel('next');

		//reorder indicatorss
		$indicator_area.children().each(function(i){
			$(this).attr('data-slide-to',i).data('slide-to',i);
		});
	},


	/**
	 * Size Changes
	 *
	 */
	heightChanged : function(){
		$('.gp_editing .gp_twitter_carousel').stop(true,true).delay(800).animate({'padding-bottom':this.value});
	},


	/**
	 * Interval Speed
	 *
	 */
	intervalSpeed : function(){
	},


	/**
	 * Move Back/Forward
	 * @todo Remove, not used since Typesetter 5.0-rc3
	 *
	 */
	moveBack : function(){
		var $curr = $('.gp_editing .carousel-inner .active');
		var $prev = $curr.prev();
		if( !$prev.length ) return;

		// swap images
		var prev_html = $prev.html();
		var curr_html = $curr.html();
		$prev.html(curr_html);
		$curr.html(prev_html);

		//swap icons
		var $indicators = $('.gp_editing .carousel-indicators li');
		$prev = $indicators.eq( $prev.index() );
		$curr = $indicators.eq( $curr.index() );
		prev_html = $prev.html();
		curr_html = $curr.html();
		$prev.html(curr_html);
		$curr.html(prev_html);

		//move to previous image
		$('.gp_editing').carousel('prev');

	},

	moveForward : function(){

		var $curr = $('.gp_editing .carousel-inner .active');
		var $next = $curr.next();
		if( !$next.length ) return;


		// swap images
		var next_html = $next.html();
		var curr_html = $curr.html();
		$next.html(curr_html);
		$curr.html(next_html);

		//swap icons
		var $indicators = $('.gp_editing .carousel-indicators li');
		$next = $indicators.eq( $next.index() );
		$curr = $indicators.eq( $curr.index() );
		next_html = $next.html();
		curr_html = $curr.html();
		$next.html(curr_html);
		$curr.html(next_html);

		$('.gp_editing').carousel('next');
	}

});

