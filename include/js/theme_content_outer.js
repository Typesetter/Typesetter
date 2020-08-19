$gp.handle_iframe = function(){
	var iframe	= document.getElementById('gp_layout_iframe');
	if( iframe ){
		if ( iframe.contentWindow.document.getElementById('gp_layout_iframe') ){
			// prevent nested iframes
			inner_iframe_src = iframe.contentWindow.document
				.getElementById('gp_layout_iframe')
				.getAttribute("src");
			iframe.src = inner_iframe_src;
		}
		var iframe_body = iframe.contentWindow.document.body;
		if( iframe_body ){
			// 90px = grace extra space for near bottom areas
			$(iframe_body).append('<div id="layout_appended_spacer">');
		}
	}
};


$gp.check_iframe_ready = function(){
	var iframe	= document.getElementById('gp_layout_iframe');

	if( iframe.contentWindow.document &&
		typeof(iframe.contentWindow.$gp) == 'object' &&
		typeof(iframe.contentWindow.$gp.iframe_ready) == 'function'
	){
		iframe.contentWindow.$gp.iframe_ready();
		$gp.loaded();
	}else{
		setTimeout($gp.check_iframe_ready, 150);
	}
};


$(function(){

	// Layout preview iframe
	$gp.check_iframe_ready();

	// Resizeable editor
	var width = Math.min(gpui.thw, $gp.$win.width() - 50);
	$('#gp_iframe_wrap').css('margin-right', width);
	$('#theme_editor').css('width', width);

	$('#theme_editor').resizable({
		handles : 'w',
		minWidth : 250,
		resize: function(event,ui){
			$('#gp_iframe_wrap').css( 'margin-right', ui.size.width+1 );
		},
		stop : function(event, ui) {
			gpui.thw = ui.size.width;
			$gp.SaveGPUI();
		}
	});

	$gp.editor_ready = true;

});
