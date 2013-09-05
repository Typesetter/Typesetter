
$(function(){
	LayoutSetup();
	CssSetup();

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




});


/**
 * Prepare a layout for css editing
 * Watch for changes to the css/less textarea to apply changes to the page as they're made
 *
 */
function CssSetup(){

	// get the textarea
	var textarea = $('#gp_layout_css');
	if( !textarea.length ){
		return;
	}


	// get the css area that contains the custom css
	var style_area = $('#gp_layout_iframe').contents().find('#gp_layout_style');
	if( !style_area.length ){
		setTimeout(function(){CssSetup()},400);
		return;
	}



	//Watch for changes
	var start_value = style_area.html();
	var prev_value = start_value;
	//var less = document.getElementById('gp_layout_iframe').contentWindow.less;

	var interval = window.setInterval(function(){

		var new_value = textarea.val();

		//don't call less.refresh more than needed
		if( new_value == prev_value ){
			return;
		}
		prev_value = new_value;


		//apply new value
		style_area.html(new_value);

		//refresh with less
		//less.refresh();
		//less.modifyVars({});
		//less.refreshStyles();

	},1000);
	*/

}


/**
 * Prepare the page for editing a layout by setting up drag-n-drop areas
 *
 */
function LayoutSetup(){

	if( $('#theme_toolbar').length ){
		$('body').addClass('edit_layout');
	}

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

	/*
	var drag_note = $('#gp_drag_n_drop')
					.show()
					.css({'top':pos.top,'left':(pos.left+w-300)})
					.appendTo('#gp_admin_html');
	SimpleDrag('#gp_drag_n_drop',drag_note,'absolute',function(){});
	*/



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




