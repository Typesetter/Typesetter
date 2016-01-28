
$(function(){
	LayoutSetup();
	CssSetup();


	var width		= Math.min(gpui.thw, $gp.$win.width() - 50);
	console.log('width',width);
	$('#gp_iframe_wrap').css( 'left', width );
	$('#theme_editor form').css( 'width', width );

	/**
	 * Resizeable editor
	 *
	 */
	$('#theme_editor form').resizable({
		handles : 'e',
		minWidth : 172,
		resize : function(event, ui) {
			$('#gp_iframe_wrap').css( 'left', ui.size.width );

			gpui.thw = ui.size.width;
			$gp.SaveGPUI();
		}
	});


	/**
	 * Adjust link targets to point at parent unless they're layout links
	 *
	 */
	if( window.self !== window.top ){
		$('a').each(function(){
			if( this.href.indexOf('Admin_Theme_Content') > 0 ){
				return;
			}
			this.target = '_parent';
		});
	}


	/**
	 * Show the layout color and label editor
	 *
	 */
	$gp.links.layout_id = function(evt,color){

		evt.preventDefault();
		var $this = $(this);
		var startc = this.value;
		var pos = $this.offset();
		var a = this.title;


		$('#current_color').css('background-color',color);
		var panel = $('#layout_ident').show().css({'left':(pos.left+20),'top':pos.top}).appendTo('#gp_admin_html');

		//assign values to the form based on hidden input elements
		var c = panel.find('form').get(0);
		if( c ){
			$(this).find('input').each(function(i,j){
				if( c[j.name] ){
					c[j.name].value = j.value;
				}
			});
		}

		panel.find('a.color').off('click').on('click',function(){
			//$this.css('background-color',this.title);
			var color = $(this).data('arg');
			$('#current_color').css('background-color',color);
			c['color'].value = color;
		});


		//closing the panel
		panel.find('input.close_color_dialog').off('click').on('click',function(){
			LayoutClose();
		});

		$('body').on('click.layout_id',function(evt){

			//prevent click on panel from closing it
			if( !$(evt.target).closest('#layout_ident').length ){
				LayoutClose();
			}
		})
		//close with esc key
		.on('keydown.layout_id', function (evt) {
			if (evt.keyCode === 27) {
				evt.preventDefault();
				LayoutClose();
			}
		});

		function LayoutClose(){
			panel.hide();
			$('body').off('.layout_id');
		}

	};

	//this has to be 'live'
	$(document).delegate('.draggable_element',{
		'mouseenter': function(){
			var $this = $(this);
			if( $this.hasClass('target') ){
				return;
			}

			$this.addClass('hover');


			$this.find('div').stop(true,true).fadeIn();
			$this.stop(true).fadeTo('200',1);

			//set height
			var h = $this.height();
			$this.data('ph',h);
			var h2 = $this.height('auto').height();
			if( h2 < h ){
				$this.height(h);
			}
		},
		'mouseleave': function(){
			var $this = $(this)
						.removeClass('hover')
						.stop(true)
						.fadeTo('slow',.5);

			$this.find('div').stop(true,true).fadeOut();

			var h = $this.data('ph');
			if( parseInt(h) > 0 ){
				$this.height(h);
			}
		}
	});


	//layout and theme select
	$('.theme_select select').change(function(){
		if( this.value == '' ) return;
		if( this.name == 'layout' ){
			window.location = this.form.action+'/'+this.value;
		}else{
			window.location = this.form.action+'?cmd=preview&theme='+this.value;
		}
	});





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



	/**
	 * Prepare the page for editing a layout by setting up drag-n-drop areas
	 *
	 */
	function LayoutSetup(){

		$('html').addClass('edit_layout'); //for .gp-fixed-adjust

		if( typeof(gpLayouts) == 'undefined' ){
			return;
		}


		//disable editable areas, there could be conflicts with the layout toolbar and content toolbars
		$('a.ExtraEditLink').detach();
		$('.editable_area').removeClass('editable_area');

		//show drag-n-drop message
		var $content = $('.filetype-text');
		var pos = $content.offset();
		var w = $content.width();


		//prepare the drag area
		var drag_area = $('<div class="draggable_droparea" id="theme_content_drop"></div>').appendTo('#gp_admin_html');


		//create a draggable box for each output_area
		var $inner_links = $('.gp_inner_links');
		$('.gp_output_area').each(function(i,b){
			var loc, lnks, $this = $(b);

			loc = $gp.Coords($this);

			lnks = $inner_links.eq(i);

			if( lnks.length > 0 ){


				$('<div class="draggable_element" style="position:absolute;height:5px;width:5px;"></div>')
				.appendTo(drag_area)
				.append(lnks) //.output_area_link
				.fadeTo('fast',.5)
				.height(loc.h-3).width(loc.w-3)
				.on('gp_position',function(){

					var loc = $gp.Coords($this);

					//make sure there's at least a small box to work with
					if( loc.h < 20 ){
						$this.height(20);
						loc.h = 20;
					}
					if( loc.w < 20 ){
						$this.width(20);
						loc.w = 20;
					}

					$(this).css({'top':loc.top,'left':loc.left})
				});
			}
		});

		var drag_elements = $('.draggable_element');
		drag_elements.trigger('gp_position');
		window.setInterval(function(){
			drag_elements.trigger('gp_position');
		},2000);
	}



	/**
	 * Hide loading image after iframe has loaded
	 *
	 */
	$gp.iframeloaded = function(){
		$gp.loaded();
	}


});










