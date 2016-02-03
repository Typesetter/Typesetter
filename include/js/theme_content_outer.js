
$(function(){

	/**
	 * Seamless iframe
	 *
	 */
	var iframe	= document.getElementById('gp_layout_iframe');
	if( iframe ){
		var $wrap	= $('#gp_iframe_wrap');
		window.setInterval(function(){

			var iframe		= document.getElementById('gp_layout_iframe');
			var body		= iframe.contentWindow.document.body;
			if( body ){

				//shrink down to body size
				var html		= iframe.contentWindow.document.documentElement;
				height			= Math.max( body.scrollHeight, body.offsetHeight );
				$wrap.height( height );

				//increase back up if needed
				height			= Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight, $gp.$win.height() );
				$wrap.height( height );
			}


		},300);
	}


	/**
	 * Resizeable editor
	 *
	 */
	var width		= Math.min(gpui.thw, $gp.$win.width() - 50);
	$('#gp_iframe_wrap').css( 'margin-right', width );
	$('#theme_editor').css( 'width', width );

	$('#theme_editor').resizable({
		handles : 'w',
		minWidth : 172,
		resize : function(event, ui) {
			$('#gp_iframe_wrap').css( 'margin-right', ui.size.width+1 );

			gpui.thw = ui.size.width;
			$gp.SaveGPUI();
		}
	});


	/**
	 * Update New Layout button
	 *
	 */
	$gp.links.SetPreviewTheme = function(){
		var href = this.href+'&cmd=newlayout';
		$('.add_layout').attr('href',href);
	}

	/**
	 *
	 */
	var $available_wrap = $('#available_wrap');
	if( $available_wrap.length ){

		$gp.$win.resize(function(){
			var top		= $available_wrap.offset().top;
			var win_h	= $gp.$win.height();
			$available_wrap.css('max-height', win_h -top);
			console.log(top,win_h,win_h-top);
		}).resize();

	}

});

