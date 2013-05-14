
$(function(){

	$(document).on('keyup',function(evt){

		switch(evt.which){
			case 39:
				var $slideshow = GetSlideshow();
				NextImg( $slideshow );
			break;
			case 37:
				var $slideshow = GetSlideshow();
				PrevImg( $slideshow );
			break;
		}
	});

	function GetSlideshow(){
		var $slideshows = $('.slideshowb_wrap');
		if( $slideshows.length == 1 ){
			return $slideshows.eq(0);
		}
		var $window = $(window);
		var w_top = $window.scrollTop();
		var w_bottom = $window.height() + w_top;


		var max_overlap = 0, slideshow_index = 0;
		$.each($slideshows,function(i,j){
			var $slideshow = $(j);
			var s_top = $slideshow.offset().top;
			var s_bottom = $slideshow.height() + s_top;

			if( s_top > w_top ){
				overlap = w_bottom - s_top;
			}else{
				overlap = s_bottom - w_top;
			}
			if( overlap > max_overlap ){
				max_overlap = overlap;
				slideshow_index = i;
			}
		});
		return $slideshows.eq(slideshow_index);
	}

	//var $images = $('.slideshowb_images');

	$gp.links.slideshowb_img = function(evt){
		evt.preventDefault();
		var $this = $(this);
		var $slideshow = $this.closest('.slideshowb_wrap');
		LoadImg( $slideshow, $this, true );
	}


	$gp.links.slideshowb_next = function(evt){
		var $slideshow = $(this).closest('.slideshowb_wrap');
		evt.preventDefault();


		//previous or next
		var half = ($slideshow.width()/2) + $slideshow.offset().left;
		if( evt.pageX > half ){
			NextImg($slideshow);
		}else{
			PrevImg($slideshow);
		}
	}

	function NextImg($slideshow){
		var next = $slideshow.find('.slideshowb_icons li:eq(1) a');
		LoadImg( $slideshow, next, true );
	}

	function PrevImg($slideshow){
		var prev = $slideshow.find('.slideshowb_icons li:last a');
		LoadImg( $slideshow, prev, false );
	}

	function LoadImg( $slideshow, lnk, next ){

		if( !lnk.length ){
			return;
		}

		var curr = $slideshow.find('.slideshowb_icons a:first');
		var $images = $slideshow.find('.slideshowb_images');
		var left = $images.outerWidth();

		var hash = Hash( lnk );
		if( curr.length ){
			var curr_hash = Hash( curr );
			if( curr_hash == hash ){
				return;
			}
		}

		var new_span = ImgSpan( $images, lnk );
		var curr_span = ImgSpan( $images, curr );

		//scroll current image
		if( !next ){
			left *= -1;
		}
		//new_span.css('left',left).animate({'left':0});
		new_span.animate({'left':left},{duration:0}).animate({'left':0});
		curr_span.animate(
			{'left':-(left*2)}
		);


		//scroll icons
		var icon = $slideshow.find('a.slideshowb_icon_'+hash).parent();
		var icon_wrap = $slideshow.find('.slideshowb_icons > ul');


		if( next ){
			var pos = icon.position();
			icon_wrap.animate(
				{'top': -(pos.top)}
				,{
					complete: function(){

						icon_wrap.animate({'top':0},{duration:0,complete:function(){
								var prev = icon.prevUntil().detach().get().reverse();
								$.each(prev,function(){
									$(this).hide().appendTo( icon_wrap ).fadeIn();
								});
							}
						});
					}
				}
			);
		}else{
			icon.prependTo( icon_wrap );
			icon_wrap.animate({'top':-80},{duration:0});
			icon_wrap.animate({'top':0});
		}



		$slideshow.find('.slideshowb_caption').html(lnk.attr('title')+'&nbsp;');

		//load next image
		var next = $slideshow.find('.slideshowb_icons li:eq(1) a');
		if( next.length != 0 ){
			ImgSpan( $images, next );
		}
	}

	function ImgSpan( $images, lnk ){

		var hash = Hash( lnk );
		var new_span = $images.find('a.slideshowb_img_'+hash);

		if( new_span.length == 0 ){

			var href = lnk.attr('href');
			var new_span = $('<a href="'+href+'" data-cmd="slideshowb_next" class="slideshowb_img_'+hash+'" data-hash="'+hash+'">').appendTo( $images );

			var $img = $('<img>').load(function(){
				$(this).parent().addClass('loaded');
			}).attr('src',href).appendTo(new_span);

		}

		return new_span;
	}

	function Hash( lnk ){
		var hash = lnk.data('hash');
		if( hash ){
			return hash;
		}

		do{
			hash = Math.round(Math.random()*100);
			classname = 'slideshowb_icon_'+hash;
		}while( $('.'+classname).length > 0 );

		lnk.data('hash',hash);
		lnk.addClass(classname);
		return hash;
	}

	//auto start
	$('.slideshowb_wrap.start').each(function(){
		var $slideshow = $(this);
		var speed = $slideshow.data('speed') || 5000;

		window.setInterval(function(){
			if( !$slideshow.hasClass('hover') ){
				NextImg( $slideshow );
			}
		},speed);

		//cancel on mouseover
		$slideshow.on('mouseenter',function(){
			$slideshow.addClass('hover');
		}).on('mouseleave',function(){
			$slideshow.removeClass('hover');
		});


	});



});


