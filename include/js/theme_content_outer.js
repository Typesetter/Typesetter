$gp.handle_iframe = function(){
	var iframe	= document.getElementById('gp_layout_iframe');
	if( iframe ){
		if ( iframe.contentWindow.document.getElementById('gp_layout_iframe') ){
			// prevent nested iframes
			inner_iframe_src = iframe.contentWindow.document.getElementById('gp_layout_iframe').getAttribute("src");
			iframe.src = inner_iframe_src;
		}
		var iframe_body = iframe.contentWindow.document.body;
		if( iframe_body ){
			$(iframe_body).append('<div id="layout_appended_spacer">'); // 90px = grace extra space for near bottom areas
			// console.log("grace space appended to ", iframe_body);
		}
	}
}

$gp.check_iframe_ready = function(){
	var iframe	= document.getElementById('gp_layout_iframe');

	/*
	console.log("iframe = " + typeof(iframe));
	console.log("iframe.contentWindow = " + typeof(iframe.contentWindow));
	console.log("iframe.contentWindow.document = " + typeof(iframe.contentWindow.document));
	console.log("iframe.contentWindow.document.$gp = " + typeof(iframe.contentWindow.$gp));
	console.log("iframe.contentWindow.document.$gp.iframe_ready = " + typeof(iframe.contentWindow.$gp.iframe_ready));
	*/

	if( iframe.contentWindow.document
		&& typeof(iframe.contentWindow.$gp) == 'object'
		&& typeof(iframe.contentWindow.$gp.iframe_ready) == 'function' ){
		iframe.contentWindow.$gp.iframe_ready();
		$gp.loaded();
	}else{
		setTimeout($gp.check_iframe_ready, 150);
	}
}

$(function(){

	/**
	 * Layout preview iframe
	 *
	 */
	$gp.check_iframe_ready();

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
		resize: function(event,ui){
			$('#gp_iframe_wrap').css( 'margin-right', ui.size.width+1 );
		},
		stop : function(event, ui) {
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

		$gp.$win.on('resize', function(){
			var top		= $available_wrap.offset().top;
			var win_h	= $gp.$win.height();
			$available_wrap.css('max-height', win_h -top);
			console.log(top,win_h,win_h-top);
		}).trigger('resize');

	}



	/**
	 * Watch for changes to the custom css area
	 *
	 */
	function CssSetup(){

		// get the textarea
		var $textarea = $('#gp_layout_css');
		if( !$textarea.length ){
			return;
		}

		var codeMirrorConfig = {
				mode: 'text/x-less',
				lineWrapping:false
			};

		var mode = $textarea.data('mode');
		if( mode == 'scss' ){
			codeMirrorConfig.mode = 'text/x-scss';
		}

		var editor = CodeMirror.fromTextArea($textarea.get(0),codeMirrorConfig);

		var prev_value = editor.getValue();

		// events may change in future codemirror versions(!)
		editor.on("change", function(evt){
			var current_value = evt.getValue();
			var cm_isDirty = prev_value != current_value;
			$textarea.toggleClass('edited', cm_isDirty);
			$('button[data-cmd="preview_css"], button[data-cmd="save_css"], input[type="reset"]')
				.toggleClass('gpdisabled', !cm_isDirty)
				.prop("disabled", !cm_isDirty);
		});

		$(window).on('resize', function(){
			var parent = $textarea.parent();
			editor.setSize(225, 100); //shrink the editor so we can get the container size
			editor.setSize(225, parent.height()-5);
		}).trigger('resize');

		// preview button
		$gp.inputs.preview_css = function(evt){
			$gp.loading();
		};

		// save button
		$gp.inputs.save_css = function(evt){
			$textarea.removeClass('edited');
			prev_value = $textarea.val();
			setTimeout(function(){
				$('button[data-cmd="preview_css"], button[data-cmd="save_css"], input[type="reset"]')
					.addClass('gpdisabled')
					.prop("disabled", true);
			},150);
			$gp.loading();
		};

		// reset button
		$gp.inputs.reset_css = function(evt){
			editor.setValue(prev_value);
			editor.clearHistory();
			if( $textarea.hasClass('edited') ){
				$gp.inputs.save_css();
			}
		}

		$(window).on("beforeunload", function(evt) {
			if( $textarea.hasClass('edited') ){
				return 'Warning: There are unsaved changes. Proceed anyway?';
			}
		});

	}


	CssSetup();

	$gp.editor_ready = true;

});
