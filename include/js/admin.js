/*global $gp:false, gpui:false, gplinks:false, gpinputs:false isadmin:false, gpBase:false, strip_from:false, gpRem:false, gpBLink:false */
/*jshint forin:true, noarg:true, noempty:true, eqeqeq:true, bitwise:true, strict:true, undef:true, unused:true, curly:true, browser:true, jquery:true, indent:4, maxerr:100, newcap:false, white:false*/
//"use strict";

var gp_editor		= false;

$gp.curr_edit_id	= null;
$gp.interface		= [];		// storage for editing interfaces
$gp.cached			= {};
$gp.defaults		= {};
$gp.editors			= [];		// storage for editing objects


/**
 * Get the coordinates for positioning editable area overlays
 *
 */
$gp.Coords = function(area){
	if( area.hasClass('inner_size') ){
		area = area.children(':first');
	}
	var loc	= area.offset();
	loc.w	= area.outerWidth();
	loc.h	= area.outerHeight();
	return loc;
};


/**
 * Create a <div> in the #gp_admin_html space
 *
 */
$gp.div = function(id){
	var div = $('#'+id);
	if( div.length === 0 ){
		div = $('<div id="'+id+'"></div>').appendTo('#gp_admin_html');
	}
	return div;
};


/**
 * Dynamically load inline editing
 *
 */
$gp.links.inline_edit_generic = function(evt,arg){

	evt.preventDefault();

	var area_id		= $gp.AreaId( $(this) );

	$gp.LoadEditor(this.href, area_id, arg);
}

$gp.LoadEditor = function(href, area_id, arg){

	area_id = area_id || 0;

	if( area_id === $gp.curr_edit_id ){
		return;
	}


	//first time editing, get default $gp.links, $gp.inputs, $gp.response
	if( typeof(gp_editing) == 'undefined' ){
		$gp.defaults['links'] = $gp.Properties($gp.links);
		$gp.defaults['inputs'] = $gp.Properties($gp.inputs);
		$gp.defaults['response'] = $gp.Properties($gp.response);
	}

	$gp.CacheInterface(function(){

		//set the current editing interface aside so the new one can be created
		if( typeof(gp_editing) !== 'undefined' ){
			if( gp_editing.RestoreCached(area_id) ){
				return;
			}
		}else{
			$gp.LoadStyle('/include/css/inline_edit.css');
			$gp.LoadStyle('/include/css/manage_sections.css');
		}

		$gp.curr_edit_id	= area_id;
		var $edit_div		= $gp.CurrentDiv();

		$gp.DefinedObjects();
		$gp.loading();


		//legacy inline editing support
		//can also be used for development/troubleshooting
		if( typeof(gplinks[arg]) === 'function' ){
			gplinks[arg].call(this,arg,evt);
			return;
		}

		var script		= strip_from(href,'#');
		script			+= '&gpreq=json&defined_objects='+$gp.DefinedObjects();

		if( arg != 'manage_sections' ){
			script		+= '&cmd=inlineedit&area_id='+area_id+'&section='+$edit_div.data('gp-section');
		}


		//get the new editor
		$.getScript( script,function(data){
			if( data === 'false' ){
				alert($gp.error);
				$gp.loaded();
				return;
			}

			if( typeof(gp_editor.wake) == 'function' ){
				gp_editor.wake();
			}
		});

	});
};

/**
 * Get object properties
 *
 */
$gp.Properties = function(obj){
	var properties = [];
	for( var i in obj ){
		if( obj.hasOwnProperty(i) ){
			properties.push(i);
		}
	}
	return properties;
}


/**
 * Get the current editing div
 *
 */
$gp.CurrentDiv = function(){
	return $('#ExtraEditArea'+$gp.curr_edit_id);
}


/**
 * Cache the current editing interface
 *
 */
$gp.CacheInterface = function(callback){


	$gp.CurrentDiv().removeClass('gp_edit_current');


	//if gp_editing is not defined then we don't have anything to cache yet
	if( typeof(gp_editing) == 'undefined' ){
		callback.call();
		return;
	}


	//only continue if we can save
	gp_editing.save_changes(function(){

		var html							= $('#ck_area_wrap').html();
		var $interface						= $('#ckeditor_area').detach();

		if( typeof(gp_editor.sleep) == 'function' ){
			gp_editor.sleep();
		}

		$gp.interface[$gp.curr_edit_id]		= $interface;
		$gp.editors[$gp.curr_edit_id]		= gp_editor;


		//cache $gp.links that were defined by the current gp_editor
		$gp.cached[$gp.curr_edit_id]		= {};
		$gp.CacheObjects( 'links' );
		$gp.CacheObjects( 'inputs' );
		$gp.CacheObjects( 'response' );



		$('#ck_area_wrap').html( html );
		$('.cktabs .ckeditor_control.selected').removeClass('selected');


		callback.call();
	});
}


/**
 * Cache $gp.links, $gp.inputs and $gp.Response
 * ?? gpresponse, gplinks, gpinputs ??
 */
$gp.CacheObjects = function(type, cache_loc){

	var from								= $gp[type];
	$gp.cached[$gp.curr_edit_id][type]		= {};

	for( var i in from ){
		if( !from.hasOwnProperty(i) ){
			continue;
		}

		if( $gp.defaults[type].indexOf(i) > -1 ){
			continue;
		}
		$gp.cached[$gp.curr_edit_id][type][i] = from[i];
	}
}


/**
 * Restore $gp.links, $gp.inputs and $gp.Response
 * ?? gpresponse, gplinks, gpinputs ??
 */
$gp.RestoreObjects = function(type, id){

	var from = $gp.cached[id][type];

	for( var i in from ){
		if( !from.hasOwnProperty(i) ){
			continue;
		}
		$gp[type][i] = from[i];
	}
}


/**
 * Send to the server which javascript objects are already defined
 *
 */
$gp.defined_objects = [];
$gp.DefinedObjects = function(){

	//get all objects the first time
	if( typeof(gp_editing) == 'undefined' ){
		for( var i in window ) {
			if( typeof(window[i]) != 'object' ){
				continue;
			}
			$gp.defined_objects.push(i);
		}
	}

	//compare with $gp.defined_objects
	var objects = [];
	for( var i in window ) {
		if( typeof(window[i]) != 'object' ){
			continue;
		}
		if( $gp.defined_objects.indexOf(i) != -1 ){
			continue;
		}
		objects.push(i);
	}

	return objects.join(',');
}


/**
 * Remote Browse
 * @param object evt Event object
 *
 */
$gp.links.remote = function(evt){
	evt.preventDefault();
	var src = $gp.jPrep(this.href,'gpreq=body');

	//can remote install
	if( gpRem ){
		var pathArray = window.location.href.split( '/' );
		var url = pathArray[0] + '//' + pathArray[2]+gpBase;
		if( window.location.href.indexOf('index.php') > 0 ){
			url += '/index.php';
		}

		src += '&inUrl='+encodeURIComponent(url)
			+ '&gpRem='+encodeURIComponent(gpRem);
	}

	//40px margin + 17px*2 border + 20px padding + 10 (extra padding) = approx 130
	var height = $gp.$win.height() - 130;

	var opts = {context:'iframe',width:780};

	var iframe = '<iframe src="'+src+'" style="height:'+height+'px;" frameborder="0" />';
	$gp.AdminBoxC(iframe,opts);
};


/**
 * Add stylesheets to the current document
 * @param string file
 * @param bool already_prefixed
 */
$gp.LoadStyle = function(file, already_prefixed){

	var time	= req_time || new Date().getTime();
	file		= already_prefixed ? file : gpBase+file;
	file		= file+'?t='+time;


	//already loaded?
	if( $('link[href="'+file+'"]').length ){
		return;
	}

	//href set after appending to head so that it works properly in IE
	$('<link rel="stylesheet" type="text/css" />')
		.appendTo('head')
		.attr({'href':file});
};

/**
 * Show content (data) in #gp_admin_box
 * This is used instead of colorbox for admin content
 *		- this box resizes without javascript calls (height)
 *		- less animation
 */
$gp.AdminBoxC = function(data,options){
	$gp.CloseAdminBox();
	if( data === '' ){
		return false;
	}

	if( typeof(options) == 'string' ){
		options = {context:options}
	}else if( typeof(options) == 'undefined' ){
		options = {};
	}

	options = $.extend({context:'',width:640}, options);

	/*
	var win_width = $gp.$win.width();
	var box_width = Math.max(660, Math.round(win_width*0.70));
	*/
	var box_width = options.width;
	var left = Math.round( ($gp.$win.width() - box_width - 40)/2);
	var height = Math.max( $gp.$doc.height(), $('body').outerHeight(true) );

	$gp.div('gp_admin_box1')
		.css({'zIndex':11000,'min-height':height})
		.stop(true,true)
		.fadeTo(0,0) //fade in from transparent
		.fadeTo(200,0.2);

	$gp.div('gp_admin_box')
				.css({'zIndex':'11001','left':left,'top': $gp.$win.scrollTop() })
				.stop(true,true)
				.fadeIn(400)
				.html('<a class="gp_admin_box_close" data-cmd="admin_box_close"></a><div id="gp_admin_boxc" class="'+(options.context||'')+'" style="width:'+box_width+'px"></div>')
				.find('#gp_admin_boxc')
				.html(data)
				.find('input:visible:first')
				.focus();

	$('.messages').detach();

	//close on escape key
	$gp.$doc.on('keyup.abox',function(e) {
		if( e.keyCode == 27 ){
			$gp.CloseAdminBox();
		}
	});

	return true;
};


/**
 * Close gp_admin_box
 *
 */
$gp.CloseAdminBox = function(evt){
	if( evt ){
		evt.preventDefault();
	}

	$gp.$doc.off('keyup.abox');

	$('#gp_admin_box1').fadeOut();
	$('#gp_admin_box').fadeOut(300,function(){

		//move contents back to document if they came from an inline element
		//remove other content
		var boxc = $('#gp_admin_boxc');
		if( boxc.hasClass('inline') ){
			$('#gp_admin_boxc').children().appendTo('#gp_hidden');
		}else{
			$('#gp_admin_boxc').children().remove();
		}

	});
	if( typeof($.fn.colorbox) !== 'undefined' ){
		$.fn.colorbox.close();
	}
};
$gp.links.admin_box_close = gpinputs.admin_box_close = $gp.CloseAdminBox;


/**
 * Save admin user settings
 *
 */
$gp.SaveGPUI = function(){
	if( !isadmin ){
		return;
	}
	var data = 'do=savegpui';
	$.each(gpui,function(i,value){
		data += '&gpui_'+i+'='+value;
	});

	$gp.postC( window.location.href, data);
	//for debugging, see gpsession::SaveGPUI()
};


/**
 * Drop down menus
 *
 */
$gp.links.dd_menu = function(evt){

	evt.preventDefault();
	evt.stopPropagation();

	$('.messages').detach(); //remove messages since we can't set the z-index properly
	var that = this;
	var $list = $(this).parent().find('.dd_list');

	//display the list and add other handlers
	$list.show();

	//scroll to show selected
	var $selected = $list.find('.selected');
	if( $selected.length ){
		var $ul = $list.find('ul:first');
		var pos = $list.find('.selected').prev().prev().prev().position();
		if( pos ){
			$ul.scrollTop( pos.top + $ul.scrollTop() );
		}
	}


	//hide the list and remove handlers
	$('body').on('click.gp_select',function(evt){
		$list.hide();
		$list.off('.gp_select');
		$('body').off('.gp_select');

		//stop propogation if it's a click on the current menu so it will remain hidden
		if( $(evt.target).closest(that).length ){
			evt.stopPropagation();
		}
	});

};


/**
 * A simple tab method for switching content area visibility
 *
 */
$gp.links.tabs = function(evt){
	evt.preventDefault();
	var $this = $(this);
	$this.siblings('a').removeClass('selected').each(function(b,c){
		if( c.hash ){
			$(c.hash).hide();
		}
	});

	if( this.hash ){
		$this.addClass('selected');
		$(this.hash).show();
	}
};



/**
 * Use jQuery's Ajax functions to load javascripts and call callback when complete
 * @depredated 2.0.2 Keep for Simple Slideshow Plugin
 *
 */
$gp.Loaded = {};
$gp.LoadScripts = function(scripts,callback,relative_path){

	var script_count = scripts.length,
						d=new Date(),
						t=d.getTime(),
						base='';

	relative_path = relative_path||false;

	if( relative_path ){
		base = gpBase;
	}

	//use $.each to prevent Array.prototype.xxxx from affecting the for loop
	$.each(scripts,function(i,script){
		script = base+script;
		if( $gp.Loaded[script] ){
			sload();
		}else{
			$gp.Loaded[script] = true;
			$.getScript( $gp.jPrep(script,'t='+t), function(){
				sload();
			});
		}
	});

	function sload(){
		script_count--;
		if(script_count === 0){
			if( typeof(callback) === 'function' ){
				callback.call(this);
			}
		}
	}

};



/**
 * Refresh the current page
 * ie doesn't handle href="" the same way as other browsers
 */
$gp.links.gp_refresh = function(evt){
	evt.preventDefault();
	$gp.Reload();
};


/**
 * Change the display of the admin toolbar
 *
 */
$gp.links.toggle_panel = function(evt){
	var c,panel = $('#simplepanel');
	evt.preventDefault();

	var classes = '';

	if( panel.hasClass('minb') ){
		classes = '';
		c = 0;
	}else if( panel.hasClass('compact') ){
		classes = 'minb toggledmin';
		c = 3;
	}else{
		classes = 'compact';
		c = 1;
	}
	if( !panel.hasClass('toggledmin') ){
		panel.unbind('mouseenter touchstart').bind('mouseenter touchstart',function(event){panel.unbind(event).removeClass('toggledmin');});
	}
	panel.attr('class','keep_viewable '+classes);

	gpui.cmpct = c;
	$gp.SaveGPUI();
};



/**
 * Handle clicks on headers within the main admin toolbar
 *
 */
$gp.links.toplink = function(){

	//must not be compact
	var $this = $(this);
	var $panel = $('#simplepanel');
	if( $panel.hasClass('compact') ){
		return;
	}

	var b		= $this.next();
	gpui.vis	= !(b.is(':visible') && (b.height() > 0));

	//hide visible areas
	$panel.find('.panelgroup2:visible').slideUp(300);

	if( gpui.vis ){
		gpui.vis = $this.data('arg');
		b.slideDown(300);
	}


	$gp.SaveGPUI();
};



/**
 * Collapsible area
 *
 */
$gp.links.collapsible = function(){
	var area = $(this).parent();

	//only show one
	if( area.hasClass('one') && area.hasClass('gp_collapsed') ){
		area.parent().find('.head').addClass('gp_collapsed');
		area.parent().find('.collapsearea').slideUp(300);
		area.removeClass('gp_collapsed').next().slideDown(300);
	//ability to show multiple
	}else{
		area.toggleClass('gp_collapsed').next().slideToggle(300);
	}

};


/**
 * Load content in #gp_admin_box
 * @deprecated 2.5, use data-cmd="gpabox" instead
 *
 */
$gp.links.ajax_box = $gp.links.admin_box = function(evt){
	alert(' "ajax_box" and "admin_box" are deprecated link arguments. Use gpabox instead.');
	evt.preventDefault();
	$gp.loading();
	var href = $gp.jPrep(this.href,'gpreq=flush');
	$.get(href,'',function(data){
		$gp.AdminBoxC(data);
		$gp.loaded();
	},'html');
};


/**
 * Load content for #gp_admin_box
 * This method allows for other actions to be sent to the client in addition to admin_box content
 */
$gp.links.gpabox = function(evt){
	evt.preventDefault();
	$gp.loading();
	var href = $gp.jPrep(this.href)+'&gpx_content=gpabox';
	$.getJSON(href,$gp.Response);
};


/**
 * Add a table row
 *
 */
$gp.links.add_table_row = function(evt){
	var $tr = $(this).closest('tr');
	var $new_row = $tr.closest('tbody').find('tr:first').clone();
	$new_row.find('.class_only').remove();
	$new_row.find('input').val('').attr('value','');
	$tr.before($new_row);
}

/**
 * Remove a table row
 *
 */
$gp.links.rm_table_row = function(evt){
	var $this = $(this);
	if( $this.closest('tbody').find('.rm_table_row').length < 2 ){
		return;
	}
	$this.closest('tr').remove();
}

/**
 * Post a gpabox request
 *
 */
$gp.inputs.gpabox = function(){
	return $gp.post(this,'gpx_content=gpabox');
};


/**
 * Toggle the "checked" class on the parent of a checkbox when clicked
 *
 */
$gp.inputs.gpcheck = function(){
	if( this.checked ){
		$(this).parent().addClass('checked');
	}else{
		$(this).parent().removeClass('checked');
	}
};


/**
 * Check or uncheck all checkboxes in a form
 *
 */
$gp.inputs.check_all = function(){
	$(this).closest('form').find('input[type=checkbox]').prop('checked',this.checked);
};


/**
 * Send form using cookiecmd
 *
 */
$gp.inputs.cnreq = function(evt){
	evt.preventDefault();
	var query = $(this.form).serialize();
	$gp.Cookie('cookie_cmd',encodeURIComponent(query),1);
	window.location = strip_from(strip_from(this.form.action,'#'),'?');
};


/**
 * Escape special html characters in a string similar to php's htmlspecialchars() function
 *
 */
$gp.htmlchars = function(str){
	str = str || '';
	return $('<a>').text(str).html();
};


/**
 * Redirect the user's browser to a url
 *
 */
$gp.response.location = function(obj){
	window.setTimeout(function(){
		window.location = obj.SELECTOR;
	},obj.CONTENT);
};


/**
 * Get the edit area id
 *
 */
$gp.AreaId = function( $node ){

	var area_id	= $node.data('gp-area-id');

	if( typeof(area_id) != 'undefined' ){
		return parseInt(area_id);
	}

	//ExtraEditArea6
	//ExtraEditLink6
	var area_id = $node.attr('id');
	if( typeof(area_id) != 'undefined' ){
		return parseInt($node.attr('id').substr(13));
	}

}



/**
 * Onload
 *
 */
$(function(){

	$gp.$win = $(window);
	$gp.$doc = $(document);

	//add return value to form
	$gp.$doc.on('mousedown','form',function(){
		var $this = $(this);

		if( $this.data('gpForms') === 'checked' ){
			return;
		}

		if( typeof(this['return']) !== 'undefined' ){
			this['return'].value = window.location; //set the return path
		}

		$this.data('gpForms','checked');
	});

	if( !isadmin || (typeof(gp_bodyashtml) !== 'undefined') ){
		return;
	}

	$('body').addClass('gpAdmin');



	window.setTimeout(function(){
		EditOutlines();
		UIEffects();
	},1);


	/**
	 * Update character counts
	 *
	 */
	$gp.$doc.on('keyup keypress paste change', '.show_character_count textarea', function(){
		$(this).parent().find('.character_count span').html( this.value.length );
	});


	$gp.links.expand = function(){

		var list	= $(this).siblings('ul');

		if( list.css('display') == 'block' ){
			return;
		}

		list.css('display','block');

		$(document).one('click',function(evt){
			list.css('display','');
		});

	}


	/**
	 * Editable area outline
	 *
	 */
	function EditOutlines(){
		var timeout = false, overlay, lnk_span=false, edit_area, highlight_box, fixed_pos = false;

		overlay = $gp.div('gp_edit_overlay');
		overlay.click(function(evt){

			//if a link is clicked, prevent the overlay from being shown right away
			var target = $(evt.target);
			if( target.filter('a').length > 0 ){
				if( target.hasClass('gp_overlay_close') ){
					evt.preventDefault();
				}
				if( edit_area){
					edit_area.addClass('gp_no_overlay');
				}
			}
			HideOverlay();

		}).on('mouseleave touchend',function(){
			StartOverlayHide();
		}).on('mouseenter touchstart',function(){
			if( timeout ){
				window.clearTimeout(timeout);
			}
		});

		//show the edit link when hovering over an editable area
		//	using mouseenter to show link an area filled with an iframe
		$('.editable_area').bind('mousemove.gp mouseenter.gp touchstart.gp',function(e){
			if( timeout ){
				window.clearTimeout(timeout);
			}

			var new_area = $(this);

			// don't show overlay
			//	- for an area that is being edited
			//	- if we've already shown it
			if( new_area.hasClass('gp_no_overlay') || new_area.hasClass('gp_editing') ){
				return;
			}

			if( new_area.parent().closest('.editable_area').length > 0 ){
				e.stopPropagation();
			}

			//area han't changed, so just show the span
			if( edit_area && new_area.attr('id') === edit_area.attr('id') ){
				lnk_span.show();
				return;
			}

			rmNoOverlay(edit_area);

			edit_area = new_area;

			AreaOverlay(edit_area);

		}).bind('mouseleave touchend',function(){
			StartOverlayHide();
			rmNoOverlay(edit_area);
		});

		$gp.$win.scroll(function(){
			SpanPosition();
		});


		function rmNoOverlay(edit_area){

			if( !edit_area ){
				return;
			}
			edit_area.removeClass('gp_no_overlay');
		}

		/**
		 * Make sure the span for the current edit area is within the viewable window
		 *
		 */
		function SpanPosition(){

			if( !lnk_span || fixed_pos ){
				return;
			}

			var off		= lnk_span.offset(),
				pos		= lnk_span.position(),
				top		= $gp.$win.scrollTop(),
				diff	= Math.max(0,top - (off.top - pos.top));

			lnk_span.stop(true,true).animate({'top':diff});
		}


		function StartOverlayHide(){
			if( timeout ){
				window.clearTimeout(timeout);
			}

			timeout = window.setTimeout(
				function(){
					HideOverlay();
				},200);
		}

		function HideOverlay(){

			//hide links
			lnk_span
				.stop(true,true)
				.hide(500,function(){
					ResetMenu();
				});

			fixed_pos = false;


			//hide the box
			var box = overlay.find('div');
			box.stop(true,true).fadeOut();
		}


		/**
		 * Display the edit links and overlay that outlines an editable area
		 *
		 */
		function AreaOverlay(edit_area){
			var id,loc,width;

			id = edit_area.attr('id');
			if( !id ){
				return;
			}

			id = id.substr(13); //edit_area is always ExtraEditArea#

			//get the edit links
			var edit_links = $('#ExtraEditLnks'+id).children();
			if( edit_links.length === 0 ){
				edit_links = $('#ExtraEditLink'+id);
				if( edit_links.length === 0 ){
					return;
				}
			}

			//edit area location
			loc = $gp.Coords(edit_area);
			overlay.show().css({'top':(loc.top-3),'left':(loc.left-2),'width':(loc.w+6)});

			if( !lnk_span ){
				lnk_span = $('<span>');
				highlight_box = $('<div>');
				overlay.html(highlight_box).append(lnk_span);
			}else{
				lnk_span.stop(true,true).show().removeClass('gp_hover');
			}

			highlight_box.stop(true,true).hide().css({'height':(loc.h+5),'width':(loc.w+4)});

			SpanPosition();

			//add the edit links
			edit_links = edit_links.clone(true)
				.removeClass('ExtraEditLink');

			//
			lnk_span.html('<a class="gp_overlay_expand fa fa-pencil"></a>')
				.append(edit_links);


			ResetMenu();
		}

		/**
		 * Reset Context Menu
		 *
		 */
		function ResetMenu(){
			fixed_pos = false;
			lnk_span
				.css({'left':'auto','top':0,'right':0,'position':'absolute'})
				.removeClass('gp_hover')
				.unbind('mouseenter touchstart')
				.one('mouseenter touchstart',function(){
					if( edit_area.hasClass('gp_no_overlay') ){
						return;
					}
					ShowMenu();
				});
		}

		/**
		 * Display the editable area options
		 *
		 */
		function ShowMenu(){
			lnk_span
				.addClass('gp_hover')
				.stop(true,true)
				.show();

			//show the overlay
			highlight_box.stop(true,true).fadeIn();
		}


		/**
		 * Left click to show menu
		 * This may not always work. Some template/user js may cancel event bubbling
		 * Clicking on links still works
		 *
		 */
		$gp.$doc.on('click','.editable_area, #gp_edit_overlay',function(evt){
			if( ShowableMenu(evt) ){
				MenuPos(evt);
			}
		});


		/**
		 * Position link at cursor
		 *
		 */
		function MenuPos(evt){

			fixed_pos	= true;
			var left	= evt.pageX-$gp.$win.scrollLeft();
			var diff	= left + lnk_span.width() - $gp.$win.width();
			var top		= evt.pageY - $gp.$win.scrollTop();

			if( diff > 0 ){
				left -= diff;
			}

			lnk_span.show().stop(true,true).css({'top':top,'left':left,'right':'auto','position':'fixed'});
		}


		/**
		 * Return true if we can show the gpEasy context menu
		 *
		 */
		function ShowableMenu(evt){

			if( evt.ctrlKey || evt.altKey || evt.shiftKey ){
				return;
			}

			if( !edit_area || edit_area.hasClass('gp_editing') || edit_area.hasClass('gp_no_overlay') || !lnk_span ){
				return;
			}

			return true;
		}

	} /* end EditOutlines */


	function UIEffects(){

		SimpleDrag('#simplepanel .toolbar, #simplepanel .toolbar a', '#simplepanel', 'fixed', function(newpos){
			gpui.tx = newpos.left;
			gpui.ty = newpos.top;
			$gp.SaveGPUI();
		},true);


		//keep expanding areas within the viewable window
		$('.in_window').parent().bind('mouseenter touchstart',function(){
			var $this = $(this);
			var panel = $this.children('.in_window').css({'right':'auto','left':'100%','top':0});
			window.setTimeout(function(){
				var pos = panel.offset();
				var right = pos.left + panel.width();
				var bottom = pos.top + panel.height();


				if( right > $gp.$win.width() + $gp.$win.scrollLeft() ){
					panel.css({'right':'100%','left':'auto'});
				}

				var winbottom = $gp.$win.height() + $gp.$win.scrollTop();
				if( bottom > winbottom ){
					var diff = winbottom +  - bottom - 10;
					panel.css({'top':diff});
				}
			},1);
		});


	}


	/**
	 * Reduce a list of titles by search criteria entered in gpsearch areas
	 *
	 */
	$(document).on('keyup','input.gpsearch',function(){
		var search = this.value.toLowerCase();
		$(this.form).find('.gpui-scrolllist > *').each(function(){
			var $this = $(this);
			if( $this.text().toLowerCase().indexOf(search) == -1 ){
				$this.addClass('filtered');
			}else{
				$this.removeClass('filtered');
			}
		});
	});


});


/**
 * A simple drag function for use with gpEasy admin areas
 * Works with absolute and fixed positioned elements
 * Different from other drag script in that the mouse will not trigger any mouseover/mousenter events because the drag box will be under the mouse
 *
 * @param string selector
 * @param string drag_area
 * @param string positioning (absolute,relative,fixed)
 * @param function callback_done function to call once the drag 'n drop is done
 */
function SimpleDrag(selector, drag_area, positioning, callback_done){

	var tolerance	= -10;
	var $drag_area	= $(drag_area);


	//dragging
	$gp.$doc.off('mousedown.sdrag',selector).on('mousedown.sdrag',selector,function(e){

		if( e.which != 1 ) return;

		var box, click_offsetx, click_offsety;
		e.preventDefault();
		if( $drag_area.length < 1 ){
			return;
		}
		init();
		function init(){
			var pos = $drag_area.offset();
			click_offsetx = e.clientX - pos.left + $gp.$win.scrollLeft();
			click_offsety = e.clientY - pos.top + $gp.$win.scrollTop();
		}


		$gp.$doc.bind('mousemove.sdrag',function(e){

			//initiate the box
			if( !box ){
				var pos = $drag_area.offset();
				var w = $drag_area.width();
				var h = $drag_area.height();
				box = $gp.div('admin_drag_box')
					.css({'top':pos.top,'left':pos.left,'width':w,'height':h});
			}

			box.css({'left':Math.max(tolerance,e.clientX - click_offsetx),'top': Math.max(tolerance,e.clientY - click_offsety)});
			e.preventDefault();

			return false;
		});



		$gp.$doc.unbind('mouseup.sdrag').bind('mouseup.sdrag',function(e){
			var newposleft,newpostop,pos_obj;
			$gp.$doc.unbind('mousemove.sdrag mouseup.sdrag');

			if( !box ){
				return false;
			}
			e.preventDefault();

			//clean
			box.remove();
			box = false;

			//new
			newposleft = e.clientX - click_offsetx;
			newpostop = e.clientY - click_offsety;
			//newposleft = Math.max(0,e.clientX - click_offsetx);
			//newpostop = Math.max(0,e.clientY - click_offsety);

			//add scroll back in for absolute position
			if( positioning === 'absolute' ){
				newposleft += $gp.$win.scrollLeft();
				newpostop += $gp.$win.scrollTop();
			}

			newposleft = Math.max(tolerance,newposleft);
			newpostop = Math.max(tolerance,newpostop);


			pos_obj = {'left':newposleft,'top': newpostop};

			$drag_area.css(pos_obj).data({'gp_left':newposleft,'gp_top':newpostop});

			if( typeof(callback_done) === 'function' ){
				callback_done.call($drag_area,pos_obj,e);
			}

			$drag_area.trigger('dragstop');
			return false;
		});

		return false;
	});



	if( $drag_area.css('position') === 'fixed' || $drag_area.parent().css('position') === 'fixed' ){
		KeepViewable( $drag_area.addClass('keep_viewable') ,true);
	}

	function KeepViewable($elem,init){

		if( !$elem.hasClass('keep_viewable') ){
			return;
		}

		var gp_left
			, css = {}
			, pos = $elem.position();


		//move back to the right if $elem has been moved left
		if( init ){
			$elem.data({'gp_left':pos.left,'gp_top':pos.top});
		}else if( gp_left = $elem.data('gp_left') ){
			pos.left = css.left = gp_left;
			pos.top = css.top = $elem.data('gp_top');
		}

		var width = $elem.width();


		//keep the top of the area from being placed too high in the window
		var winbottom = $gp.$win.height();
		if( pos.top < tolerance ){
			css.top = tolerance;

		//keep the top of the area from being placed too low
		}else if( pos.top > winbottom ){
			css.top = winbottom + 2*tolerance; //tolerance is negative
		}


		//right
		var checkright = $gp.$win.width()  - width - tolerance;
		if( pos.left > checkright ){
			css.left = checkright;
		}

		if( css.left || css.top ){
			$elem.css(css);
		}
	}

	$gp.$win.resize(function(){
		$('.keep_viewable').each(function(){
			KeepViewable($(this),false);
		});
	});

}


/**
 * Initialize functionality for the rename/details dialog
 *
 */
$gp.response.renameprep = function(){


	var $form			= $('#gp_rename_form');
	var old_title		= $('#old_title').val().toLowerCase();
	var $new_title		= $form.find('input.new_title').bind('keyup change',ShowRedirect);
	var space_char		= $('#gp_space_char').val();


	$('input:disabled').each(function(a,b){
		$(b).fadeTo(400,0.6);
	});

	$('input.title_label').bind('keyup change',SyncSlug).change();

	$gp.links.showmore = function(){
		$('#gp_rename_table tr').show(500);
		$(this).parent().remove();
	};

	/**
	 * Toggle synchronization/customization of a field
	 *
	 */
	$gp.links.ToggleSync = function(evt){
		var td		= $(this).closest('td');
		var vis		= td.find('a:visible');

		td.find('a').show();
		vis.hide();

		vis			= td.find('a:visible');

		if( vis.length ){
			if( vis.hasClass('slug_edit') ){
				td.find('input').addClass('sync_label').prop('disabled',true).fadeTo(400,0.6);
				SyncSlug();
			}else{
				td.find('input').removeClass('sync_label').prop('disabled',false).fadeTo(400,1);
			}
		}
	}


	/**
	 * Show the redirct option if the new slug doesn't match the old slug
	 *
	 */
	function ShowRedirect(){
		var new_val = $new_title.val().replace(/ /g,space_char).toLowerCase();

		if( new_val !== old_title ){
			$('#gp_rename_redirect').show(500);
		}else{
			$('#gp_rename_redirect').hide(300);
		}
	}


	/**
	 * Update the value of the slug/url field with the title
	 *
	 */
	function SyncSlug(){

		var label = $('input.title_label').val();


		$new_title.filter('.sync_label').val( LabelToSlug(label) );

		$('input.browser_title.sync_label').val(label);

		ShowRedirect();

		return true;
	}


	/**
	 * Convert a label to a slug
	 *
	 */
	function LabelToSlug(str){

		// Remove control characters
		str = str.replace(/[\x00-\x1F\x7F]/g,'');

		//illegal characters
		//str = str.replace(/("|'|\?|\*|:|#)/g,'');
		str = str.replace(/(\?|\*|:|#)/g,'');

		//after removing tags, unescape special characters
		str = strip_tags(str);

		str = SlugSlashes(str);

		//all spaces should be underscores
		return str.replace(/ /g,space_char);
	}

	/**
	 * Remove html tags from a string
	 *
	 */
	function strip_tags(str){
		return str.replace(/(<(\/?[a-zA-Z0-9][^<>]*)>)/ig,"");
	}


	/**
	 * Take care of slashes and periods
	 *
	 */
	function SlugSlashes(str){

		//replace \
		str = str.replace(/[\\]/g,'/');

		//remove trailing "/."
		str = str.replace(/^\.+[\/\/]/,'/');

		//remove trailing "/."
		str = str.replace(/[\/\/]\.+$/,'/');

		//remove any "/./"
		str = str.replace(/[\/\/]\.+[\/\/]/g,'/');

		//remove consecutive slashes
		str = str.replace(/[\/\/]+/g,'/');

		if( str === '.' ){
			return '';
		}

		//left trim /
		str = str.replace(/^\/\/*/g,'');

		return str;
	}

};



