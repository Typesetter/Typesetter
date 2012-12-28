
//
$(function(){

	$('span.rating a').hover(function(){
		ResetStars( $(this).data('rating') );

	},function(){

		ResetStars();

	}).click(function(e){
		var rating = $(this).data('rating');

		$('span.rating input[name=rating]').val( rating );
		ResetStars( rating );

	});


	function ResetStars(a){
		a = a || $('span.rating input[name=rating]').val();
		var b = $('span.rating a:eq('+(a-1)+')');

		b.nextAll().css({'background-position':'0 -16px'});
		b.css({'background-position':'0 0'});
		b.prevAll().css({'background-position':'0 0'});

	}

	ResetStars();

});


