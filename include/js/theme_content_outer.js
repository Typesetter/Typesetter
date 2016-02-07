
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

		$(window).resize(function(){
			var parent = $textarea.parent();
			editor.setSize(225,100);//shrink the editor so we can get the container size
			editor.setSize(225,parent.height()-5);
		}).resize();

		var prev_value = $textarea.val();

		// preview button
		$gp.inputs.preview_css = function(evt){
			$gp.loading();
		};

		// if save or reset are clicked, remove the edited class
		$gp.inputs.reset_css = function(evt){
			$textarea.removeClass('edited');
			prev_value = $textarea.val();

			$gp.loading();
		};




		// watch for changes
		window.setInterval(function(){

			if( $textarea.val() != prev_value ){
				$textarea.addClass('edited');
			}

		},1000);

	}


	CssSetup();

});

