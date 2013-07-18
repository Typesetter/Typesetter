
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

		b.nextAll().addClass('unset');
		b.removeClass('unset');
		b.prevAll().removeClass('unset');
	}

});



/**
 * Remote Browse
 * @param object evt Event object
 *
 */
$gp.links.remote = function(evt){
	evt.preventDefault();
	var src = $gp.jPrep(this.href,'gpreq=body');

	//can remote install
	if( gpRem ){
		var pathArray = window.location.href.split( '/' );
		var url = pathArray[0] + '//' + pathArray[2]+gpBase;
		if( window.location.href.indexOf('index.php') > 0 ){
			url += '/index.php';
		}

		src += '&inUrl='+encodeURIComponent(url)
			+ '&gpRem='+encodeURIComponent(gpRem);
	}

	//40px margin + 17px*2 border + 20px padding + 10 (extra padding) = approx 130
	var height = $(window).height() - 130;

	var opts = {context:'iframe',width:780};

	var iframe = '<iframe src="'+src+'" style="height:'+height+'px;" frameborder="0" />';
	$gp.AdminBoxC(iframe,opts);
};

