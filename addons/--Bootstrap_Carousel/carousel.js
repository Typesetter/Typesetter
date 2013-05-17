
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
			.filter('.start_paused')
			.carousel('pause')
			.resize(function(){
				debug('resize');
			}).swiperight(function() {
				$carousel.carousel('prev');
			}).swipeleft(function() {
				$carousel.carousel('next');
			});
	});

});


