

$(function(){

	var gp_slideshow = {};
	gp_slideshow.ImgSelect = function(a){
		var container, lnk, file_container, $anchor, href;

		if( !a ) return;

		$anchor = $(a);
		file_container = $anchor.closest('.slideshow_area');
		container = file_container.find('.slideshow-container');
		container.removeClass('loaded');

		//change the thumbnail current class
		var thumb_li = $anchor.parent();
		thumb_li.siblings().removeClass('current');
		thumb_li.addClass('current');

		NewImg($anchor,true);

		function NewImg(source_link,onload){
			var new_link, img,hash

			if( !source_link.attr('href') ) return;


			var hash = source_link.data('hash');
			if( !hash ){
				hash = Hash();
				source_link.data('hash',hash);
			}

			var lnk = container.find('#'+hash);
			if( lnk.length > 0 ){
				if( onload ){
					window.setTimeout(function(){
						loaded(lnk);
					},100);
				}
				return;
			}

			new_link = $('<a href="#" name="gp_slideshow_next" class="slideshow_slide" id="'+hash+'"/>')
						.hide()
						.appendTo(container)
						.attr('title',source_link.attr('title'));
			img = $('<img />').appendTo(new_link);


			if( onload ){
				img.load(function(){
					//allow browser to get image size
					window.setTimeout(function(){
						loaded(new_link);
					},100);
				});
			}

			//setting the src value needs to be after onload function is added
			img.attr('src',source_link.attr('href'))
				.attr('title',source_link.attr('title'));

		}


		function loaded(lnk){

			container.addClass('loaded');

			var visible = container.find('a.slideshow_slide:visible');
			if( visible.attr('id') != lnk.attr('id') ){

				//fade the current image
				visible.stop(true,true).fadeOut();

				//adjust container height
				var h = lnk.outerHeight();
				container.stop(true,true).animate({'height':h});

				//show the new image
				lnk.css({'position':'absolute'}).stop(true,true).fadeIn();
			}

			//always make sure the caption is correct since the caption for the first image isn't automatic
			file_container.find('.caption-container').html(lnk.attr('title'));

			//preload load next
			var next = thumb_li.next().children(':first');
			if( next.length > 0 ) NewImg(next,false);
		}

	}

	function Hash(){
		do{
			var hash = Math.round(Math.random()*10000);
		}while( $('#'+hash).length > 0 );
		return hash;
	}



	gplinks.gp_slideshow_next = function(rel,evt){
		evt.preventDefault();
		Next( $(this) );
	}

	function Next( $this ){

		var thumbs = $this.closest('.slideshow_area').find('.gp_slideshow');
		if( thumbs.length == 0 ) return;

		var current = thumbs.children('li.current');
		var next_li = false;
		if( current.length > 0 ){
			next_li = current.next();
		}
		if( !next_li || next_li.length == 0 ){
			next_li = thumbs.children(':first');
		}
		gp_slideshow.ImgSelect(next_li.children(':first').get(0));
	}

	gplinks.gp_slideshow_prev = function(rel,evt){
		evt.preventDefault();
		var thumbs = $(this).closest('.slideshow_area').find('.gp_slideshow');
		if( thumbs.length == 0 ) return;

		var current = thumbs.children('li.current');
		var prev_li = false;
		if( current.length > 0 ){
			prev_li = current.prev();
		}
		if( !prev_li || prev_li.length == 0 ){
			prev_li = thumbs.children(':last');
		}
		gp_slideshow.ImgSelect(prev_li.children(':first').get(0));
	}

	gplinks.gp_slideshow_play = function(rel,evt){
		evt.preventDefault();

		var interval = false;
		var container = $(this).closest('.slideshow_area');
		var play_pause = container.find('.gp_slide_play_pause');
		if( play_pause.hasClass('gp_slide_play') ){
			//change to pause
			play_pause.removeClass('gp_slide_play');
		}else{
			//change to play
			play_pause.addClass('gp_slide_play');
			interval = window.setInterval(function(){
				PlayNext();
			},3000);
		}

		function PlayNext(){
			//no longer playing
			if( !play_pause.hasClass('gp_slide_play') ){
				window.clearInterval(interval);
				return;
			}
			Next( play_pause );
		}
	}


	gplinks.gp_slideshow = function(rel,evt){
		evt.preventDefault();
		gp_slideshow.ImgSelect(this);
	}


	$('ul.gp_slideshow').each(function(){
		var timeout = false;
		function clear(){
			if( timeout )
				window.clearTimeout(timeout);
		}

		//init containers
		var container = $(this).closest('.slideshow_area');
		var html = container.find('.gp_nosave');
		var cntrls = html.find('.gp_slide_cntrls');

		$(html).mousemove(function(){
			cntrls.stop(true,true).fadeIn();
			clear();
			timeout = window.setTimeout(function(){
				cntrls.stop(true,true).fadeOut('slow');
			},1500);
		}).mouseleave(function(){
			cntrls.stop(true,true).fadeOut();
		});


		//first image
		var hash = Hash();
		var first_img = $(this).find('li:first > a').data('hash',hash);
		container.find('.first_image').attr('id',hash);
		gp_slideshow.ImgSelect(first_img.get(0));

	});

		//// Set Navigation Key Bindings
		//$(document).bind('keydown.' + prefix, function (e) {
            //var key = e.keyCode;
			//if (open && settings.escKey && key === 27) {
				//e.preventDefault();
				//publicMethod.close();
			//}
			//if (open && settings.arrowKey && $related[1]) {
				//if (key === 37) {
					//e.preventDefault();
					//$prev.click();
				//} else if (key === 39) {
					//e.preventDefault();
					//$next.click();
				//}
			//}
		//});

});
