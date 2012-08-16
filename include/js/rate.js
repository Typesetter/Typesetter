
//
$(function(){
	
	$('span.rating a').hover(function(){
		
		ResetStars(this.rel);
		
	},function(){
		
		ResetStars();
		
	}).click(function(e){
		
		$('span.rating input[name=rating]').val(this.rel);
		ResetStars(this.rel);
		
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
		
		
