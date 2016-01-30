
$(function(){

	/**
	 * Seamless iframe
	 *
	 */
	var iframe	= document.getElementById('gp_layout_iframe');
	console.log('iframe height',iframe.contentWindow.document.body.offsetHeight);
	if( iframe ){
		var $wrap	= $('#gp_iframe_wrap');
		window.setInterval(function(){

			var iframe	= document.getElementById('gp_layout_iframe');
			height		= Math.max( iframe.contentWindow.document.body.offsetHeight, $gp.$win.height() );
			$wrap.height( height );

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

});

