
$(function(){

	//disable editable areas, there could be conflicts with the layout toolbar and content toolbars
	$('a.ExtraEditLink').detach();
	$('.editable_area').removeClass('editable_area');



	LayoutSetup();




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
	 * Prepare the page for editing a layout by setting up drag-n-drop areas
	 *
	 */
	function LayoutSetup(){

		$('html').addClass('edit_layout'); //for .gp-fixed-adjust

		if( typeof(gpLayouts) == 'undefined' ){
			return;
		}


		//prepare the drag area
		var drag_area = $('<div class="draggable_droparea" id="theme_content_drop"></div>').appendTo('#gp_admin_html');


		//create a draggable box for each output_area
		var $inner_links = $('.gp_inner_links');
		$('.gp_output_area').each(function(i){

			var $this	= $(this);
			var lnks	= $inner_links.eq(i);

			if( lnks.length > 0 ){


				$('<div class="draggable_element" style="position:absolute;min-height:20px;min-width:20px;"></div>')
				.appendTo(drag_area)
				.append(lnks) //.output_area_link
				.append('<div class="decrease_z_index" title="' + gplang.MoveBehind + '"><i class="fa fa-minus-square"></i></div>')
				.on('gp_position',function(){

					var loc = $gp.Coords($this);

					//make sure there's at least a small box to work with
					loc.h = Math.max(20,loc.h);

					$(this).css({'top':loc.top,'left':loc.left,'width':loc.w,'height':loc.h});
				})
				.on("hover", function(){ $(this).css("z-index", "+=500"); }, function(){ $(this).css("z-index", "-=500"); })
				.find(".decrease_z_index")
					.on("click", function(){
						$(this).closest(".draggable_element").trigger("mouseleave").css("z-index", "-=1");
					});

			}
		});

		var drag_elements = $('#theme_content_drop .draggable_element');
		drag_elements.trigger('gp_position');
		window.setInterval(function(){
			drag_elements.trigger('gp_position');
		},2000);

		$gp.$win.resize(function(){
			drag_elements.trigger('gp_position');
		});
	}



	/**
	 * Hide loading image after iframe has loaded
	 *
	 */
	$gp.iframeloaded = function(){
		$gp.loaded();
	}

	/**
	 * Trigger parent iframe handling
	 * this function itself is called from the parent @domready
	 */
	$gp.iframe_ready_triggered = false;

	$gp.iframe_ready = function(){
		if( !$gp.iframe_ready_triggered && typeof(parent.$gp.editor_ready) != 'undefined' ){
			parent.$gp.handle_iframe();
			$gp.iframe_ready_triggered = true;
		}
	}

	$gp.iframe_ready();

});
