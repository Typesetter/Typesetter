
$(function(){
	LayoutSetup();

	$('.expand_row').on('mouseenter',function(){
		$(this).addClass('hover');
	}).on('mouseleave',function(){
		$(this).removeClass('hover');
	});

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
		panel.find('input.cancel').off('click').on('click',function(){
			LayoutClose();
		});

		$('body').on('click.layout_id',function(){
			LayoutClose();
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

		//prevent click on panel from closing it
		panel.on('click.layout_id',function(evt){
			evt.stopPropagation();
		});


	};

	//this has to be 'live'
	$('.draggable_element').live('mouseenter',function(){
		$this = $(this);
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

	}).live('mouseleave',function(){

		$this.removeClass('hover');

		$this = $(this);

		$this.stop(true).fadeTo('slow',.5);
		$this.find('div').stop(true,true).fadeOut();
		var h = $this.data('ph');
		if( parseInt(h) > 0 ){
			$this.height(h);
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
 * Prepare the page for editing a layout by setting up drag-n-drop areas
 *
 */
function LayoutSetup(){
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
	$('body').addClass('edit_layout');
	var drag_area = $('<div class="draggable_droparea" id="theme_content_drop"></div>').appendTo('#gp_admin_html');


	//create a draggable box for each output_area
	$('.output_area').each(function(i,b){
		var loc, lnks, c;

		c = $(b);

		//var id_number = c.attr('id');
		loc = $gp.Coords(c);

		//var lnks = c.find('.output_area_link');
		lnks = c.find('.gplinks');

		if( lnks.length > 0 ){


			$('<div class="draggable_element" style="position:absolute;height:5px;width:5px;"></div>')
			.appendTo(drag_area)
			.append(lnks) //.output_area_link
			.fadeTo('fast',.5)
			.height(loc.h-3).width(loc.w-3)
			//.animate({'height':loc.h-3,'width':loc.w-3},500)
			.on('gp_position',function(){

				var loc = $gp.Coords(c);

				//make sure there's at least a small box to work with
				if( loc.h < 20 ){
					c.height(20);
					loc.h = 20;
				}
				if( loc.w < 20 ){
					c.width(20);
					loc.w = 20;
				}

				//
				$(this).css({'top':loc.top,'left':loc.left})
				//$(this).animate({'top':loc.top,'left':loc.left})

			});
		}

		//var d = $('<div style="border:2px dashed red;overflow:hidden;" class="draggable_element"></div>')
		//.height(h-4).width(w-4);
		//c.wrap(d);
		//d.prepend(b.firstChild);

	});

	var drag_elements = $('.draggable_element');
	drag_elements.trigger('gp_position');
	window.setInterval(function(){
		drag_elements.trigger('gp_position');
	},2000);
}


/**
 * Prepare the page for editing css
 * CSS edits will be applied to the page every second
 *
 */
$gp.response.EditCSS = function(){
	var textarea = $('#gp_layout_css');
	var style_area = $('#gp_layout_style');
	var start_value = $('#gp_layout_style').html();
	var interval = window.setInterval(function(){
		if( textarea.is(':visible') ){
			style_area.html(textarea.val());
		}else{
			style_area.html(start_value);
			window.clearInterval(interval);
		}

	},1000);
};

