
//
$(function(){

	$(document).delegate('span.rating a',
		{
			'mouseenter':function(){
				var $this = $(this);
				ResetStars( $this, $this.data('rating') );
			},
			'mouseleave':function(){
				ResetStars($(this));
			},
			'click':function(){
				var $this = $(this);
				var rating = $this.data('rating');
				ResetStars( $this, rating, rating );
			}
		});

	function ResetStars($link, show_value, set_value ){

		//$this.closest('span.rating').find('input[name=rating]').val( rating );
		var $span = $link.closest('span.rating');
		var $input = $span.find('input[name=rating]');

		if( set_value ){
			$input.val(set_value);
		}
		show_value = show_value || $input.val();

		var b = $span.find('a:eq('+(show_value-1)+')');

		b.nextAll().css({'background-position':'0 -16px'});
		b.css({'background-position':'0 0'});
		b.prevAll().css({'background-position':'0 0'});
	}

	ResetStars();

});


