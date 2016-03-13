
/**
 * Jumbotron
 *
 */
$(function(){

	$('.gp_twitter_carousel').each(function(){
		var $carousel = $(this);
		var speed = $carousel.data('speed') || 5000;

		$carousel
			.carousel({interval:speed})
			.swiperight(function() {
				$carousel.carousel('prev');
			}).swipeleft(function() {
				$carousel.carousel('next');
			});

		//autostart if not editing
		if( typeof(gp_editing) == 'undefined' ){
			$carousel
				.filter('.start_paused')
				.carousel('pause');
		}

		if ( $carousel.find(".item").length < 2 ){
      		$carousel.find(".carousel-indicators, .carousel-control").hide();
    	}

	});

});


