
var gp_editor = false;

var debug_area;
function debug(arg){
	if( !debug_area ) debug_area = $('<div id="debug" style="position:absolute;top:0;left:0;background:#fff;padding:10px;z-index:99999;border:1px solid #333;max-width:30%;white-space:pre-wrap">').prependTo('body');
	debug_area.prepend('<div>'+LOGO(arg)+'</div><hr/>');
}
function LOGO(obj){
	var type = typeof(obj);
	var a = "\n<br/> ("+type+') ';
	switch(type){
		case 'object':
			for(var i in obj){
				a += "\n<b>"+i+ '</b> = ';
				try{
					a += obj[i].toString().replace(/</g,'&lt;').replace(/>/g,'&gt;');
				}catch(m){
					a += " -- not allowed -- ";
				}
			}
		break;
		default:
			a += obj;
		break;
	}
	return a;
}


//get the coordinates for positioning editable area overlays
function GetCoords(a){
	if( a.hasClass('inner_size') ){
		a = a.children(':first');
	}
	loc = a.offset();
	loc.w = a.outerWidth();
	loc.h = a.outerHeight();
	return loc;
}

$(function(){

	$gp.div = function(id){
		var div = $('#'+id);
		if( div.length == 0 ){
			div = $('<div id="'+id+'"></div>').appendTo('#gp_admin_html');
		}
		return div;
	}


	if( !isadmin ){
		return;
	}

	var $body = $('body');

	if( typeof(gp_bodyashtml) != 'undefined' ){
		AddgpLinks();
		return;
	}

	ContentPosition();
	AddgpLinks();
	$body.addClass('gpAdmin').trigger('AdminReady');

	window.setTimeout(function(){
		EditOutlines();
		EditableBar();
		UIEffects();
	}
	,500);


	/**
	 * Update character counts
	 *
	 */
	$(document).on('keyup keypress paste change', '.show_character_count', function(){
		$(this).parent().find('.character_count span').html( this.value.length );
	});


	window.onbeforeunload = function(){

		if( !gp_editor ) return;
		if( typeof(gp_editor.checkDirty) == 'undefined' ) return;

		if( gp_editor.checkDirty() ){
			return 'Unsaved changes will be lost.';
		}
		return;
	}


	/*
	 * Dynamically load inline editing
	 */
	gplinks.inline_edit_generic = function(rel,evt){
		evt.preventDefault();
		loading();

		//legacy inline editing support
		//can also be used for development/troubleshooting
		if( typeof(gplinks[rel]) == 'function' ){
			gplinks[rel].call(this,rel,evt);
			return;
		}

		$gp.LoadStyle('/include/css/inline_edit.css');

		var file_path = strip_from(this.href,'#');
		var id = $(this).attr('id').substr(13);

		script = file_path+'&cmd=inlineedit&area_id='+id;
		$.getScript( script,function(data){
			if( data == 'false' ){
				alert(gp_error);
				loaded();
			}
			//for debugging
			//debug(data);
		});
	}

	$gp.LoadStyle = function(file){
		var d=new Date(),
			t=d.getTime();

		//href set after appending to head so that it works properly in IE
		$('<link rel="stylesheet" type="text/css" />')
			.appendTo('head')
			.attr({'href':gpBase+file+'?t='+t});

	}


	function AddgpLinks(){

		/* ie doesn't handle href="" the same way as other browsers */
		gplinks.gp_refresh = function(rel,evt){
			evt.preventDefault();
			window.location = strip_from(window.location.href,'#');
		}

		/**
		 * Change the display of the admin toolbar
		 *
		 */
		gplinks.toggle_panel = function(rel,evt){
			var panel = $('#simplepanel');
			evt.preventDefault();

			var classes = '';
			var has_min = panel.hasClass('min');

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
				panel.unbind('mouseenter').bind('mouseenter',function(event){panel.unbind(event).removeClass('toggledmin');});
			}
			panel.attr('class','keep_viewable '+classes);

			gpui.cmpct = c;
			$gp.SaveGPUI();
		}

		/**
		 * Handle clicks on headers within the main admin toolbar
		 *
		 */
		gplinks.toplink = function(rel,evt){
			evt.preventDefault();

			//must not be compact
			var panel = $('#simplepanel');
			if( panel.hasClass('compact') ) return;

			var b = $(this).next();
			var is_visible = b.is(':visible') && (b.height() > 0);

			//hide visible areas
			$('#simplepanel .panelgroup2:visible').slideUp(300);
			gpui['vis'] = false;

			if( !is_visible ){
				gpui['vis'] = rel;
				b.slideDown(300);
			}
			$gp.SaveGPUI();
		}

		gplinks.collapsible = function(rel,evt){
			evt.preventDefault();

			var area = $(this).parent();

			//only show one
			if( area.hasClass('one') && area.hasClass('hidden') ){
				area.parent().find('.head').addClass('hidden');
				area.parent().find('.collapsearea').slideUp(300);
				area.removeClass('hidden').next().slideDown(300);
			//ability to show multiple
			}else{
				area.toggleClass('hidden').next().slideToggle(300);
			}

		}

		/**
		 * Load content in #gp_admin_box
		 * @deprecated 2.5, use name="gpabox" instead
		 */
		gplinks.ajax_box = gplinks.admin_box = function(rel,evt){
			evt.preventDefault();
			loading();
			var href = jPrep(this.href,'gpreq=flush');
			$.get(href,'',function(data, textStatus, XMLHttpRequest){
				$gp.AdminBoxC(data);
				loaded();
			},'html');
		}


		/**
		 * Load content for #gp_admin_box
		 * This method allows for other actions to be sent to the client in addition to admin_box content
		 */
		gplinks.gpabox = function(rel,evt){
			evt.preventDefault();
			loading();
			var href = jPrep(this.href)+'&gpx_content=gpabox';
			$.getJSON(href,ajaxResponse);
		}

		gpinputs.gpabox = function(){
			return $gp.post(this,'gpx_content=gpabox');
		}



		/**
		 * Show content (data) in #gp_admin_box
		 * This is used instead of colorbox for admin content
		 * 		- this box resizes without javascript calls (height)
		 * 		- less animation
		 */
		$gp.AdminBoxC = function(data,context){
			$gp.CloseAdminBox();
			if( data == '' ) return false;

			/*
			var win_width = $win.width();
			var box_width = Math.max(660, Math.round(win_width*0.70));
			*/
			var $win = $(window);
			var box_width = 640;
			var left = Math.round( ($win.width() - box_width - 40)/2);
			var height = Math.max( $(document).height(), $body.outerHeight(true) );

			$gp.div('gp_admin_box1')
				.css({'zIndex':11000,'min-height':height})
				.stop(true,true,true)
				.fadeTo(0,0) //fade in from transparent
				.fadeTo(200,.2);

			var box = $gp.div('gp_admin_box')
						.css({'zIndex':'11001','left':left,'top': $win.scrollTop() })
						.stop(true,true,true)
						.fadeIn(400)
						.html('<a class="gp_admin_box_close" name="admin_box_close"></a><div id="gp_admin_boxc" class="'+(context||'')+'" style="width:'+box_width+'px"></div>')
						.find('#gp_admin_boxc')
						.html(data);

			$('.messages').detach();
			return true;
		}


		/**
		 * Close gp_admin_box
		 */
		$gp.CloseAdminBox = function(evt1,evt){
			if( evt ) evt.preventDefault();
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
		}
		gplinks.admin_box_close = gpinputs.admin_box_close = $gp.CloseAdminBox;


		/**
		 * Remote Browse
		 *
		 */
		gplinks.remote = function(rel,evt){
			evt.preventDefault();
			var loc = strip_from(window.location.href,'#');
			var src = jPrep(this.href,'gpreq=body');

			//can remote install
			if( gpRem ){
				src += '&in='+encodeURIComponent(loc)
					+ '&blink='+encodeURIComponent(gpBLink);
			}

			//40px margin + 17px*2 border + 20px padding + 10 (extra padding) = approx 130
			var height = $(window).height() - 130;

			var iframe = '<iframe src="'+src+'" style="height:'+height+'px;" frameborder="0" />';
			$gp.AdminBoxC(iframe,'iframe');
		}


		gpinputs.gpcheck = function(){
			if( this.checked ){
				$(this).parent().addClass('checked');
			}else{
				$(this).parent().removeClass('checked');
			}
		}

		gpinputs.check_all = function(){
			$(this).closest('form').find('input[type=checkbox]').prop('checked',this.checked);
		}


		/**
		 * Save admin user settings
		 *
		 */
		$gp.SaveGPUI = function(){
			l = 'cmd=savegpui';
			$.each(gpui,function(i,value){
				l += '&gpui_'+i+'='+value;
			});

			$gp.postC( window.location.href, l);
			//for debugging, see gpsession::SaveGPUI()
		}


		/**
		 * Drop down menus
		 *
		 */
		gplinks.dd_menu = function(rel,evt){

			evt.preventDefault();
			$('.messages').detach(); //remove messages since we can't set the z-index properly
			var that = this;
			var $list = $(this).parent().find('.dd_list');
			ShowList();
			evt.stopPropagation();

			//display the list and add other handlers
			function ShowList(){
				$list.show();

				//scroll to show selected
				var $selected = $list.find('.selected');
				if( $selected.length ){
					var $ul = $list.find('ul:first');
					var top = $list.find('.selected').parent().position().top + $ul.scrollTop() - 30;
					$ul.scrollTop(top);
				}

				$('body').on('click.gp_select',function(evt){
					HideList();

					//stop propogation if it's a click on the current menu so it will remain hidden
					if( $(evt.target).closest(that).length ){
						evt.stopPropagation();
					}
				});

			}

			//hide the list and remove handlers
			function HideList(){
				$list.hide();
				$list.off('.gp_select');
				$('body').off('.gp_select');
			}

		}


	} /* AddgpLinks */


	function ContentPosition(){
		var admin_content,parent,container,dock_area;

		//position admincontent over page
		admin_content = $('#admincontent');
		if( admin_content.length < 1 ){
			return;
		}

		dock_area = admin_content.parent().parent();

		container = admin_content.parent()
					.wrap('<div id="admincontainer" class="gp_floating_area"></div>')
					.parent()
					.appendTo('#gp_admin_html');


		//move the after content down
		$('#gpAfterContent').css('margin-top',container.height()+100);
		//$('#gpAfterContent').height(container.height()+100);
		//dock_area.height(container.height()+100);

		Put(false); //$gp.SaveGPUI won't be avail yet

		SimpleDrag('#admincontent_panel',container,'absolute',function(newpos,e){
			gpui.pposx = newpos.left;
			gpui.pposy = newpos.top;
			gpui.pdock = false;
			Put(true);
		});

		//determine positioning of #admincontainer and save
		function Put(save){
			var top,left,width;

			width = gpui.pw;
			if( width < 300 ){
				width = dock_area.outerWidth()-10;
			}

			top = gpui.pposy;
			left = gpui.pposx;

			//if the admin window is docked
			if( gpui.pdock || (top == 0 && left == 0) ){
				var pos = dock_area.offset();
				top = Math.min(300,pos.top+5);
				left = pos.left+5;
			}


			container.css({
							'left':Math.max(10,left)
							,'top':Math.max(10,top)
							});

			if( admin_resizable ){
				container.width(width);
			}



			//container class for css differences
			if( gpui.pdock ){
				container.addClass('docked');
			}else{
				container.removeClass('docked');
			}


			//don't save during init since nothing has changed
			if( save ) $gp.SaveGPUI();
		}

		gplinks.gp_docklink = function(rel,evt){
			evt.preventDefault();
			gpui.pdock = !gpui.pdock;
			Put(true);
		}

	} /* end ContentPosition() */


	/**
	 * Populate the editable areas section of "Current Page" on hover
	 * Links are listed in order that they appear in the DOM
	 *
	 */
	function EditableBar(){

		$('#current_page_panel').bind('mouseenter.edb',function(){
			var count = 0,box, $win = $(window);
			var list = $('#editable_areas_list').html('');

			//the overlay box
			box = $gp.div('gp_edit_box');

			$('a.ExtraEditLink')
				.clone(false)
				.attr('class','')
				.css('display','block')
				.show()
				.each(function(){
					var title,$b,area;
					$b = $(this);
					var id_number = $b.attr('id').substr(13);
					area = $('#ExtraEditArea'+id_number);

					if( area.hasClass('gp_no_overlay') || area.length == 0 ){
						return true;
					}
					count++;

					title = this.title.replace(/_/g,' ');
					title = decodeURIComponent(title);
					if( title.length > 15 ){
						title = title.substr(0,14)+'...';
					}


					$b
						//add to list
						.attr('id','editable_mark'+id_number)
						.text(title)
						.appendTo(list)
						.wrap('<li>')

						//add handlers
						.hover(function(){

							//the red edit box
							var loc = GetCoords(area);
							box	.stop(true,true,true)
								.css({'top':(loc.top-3),'left':(loc.left-2),'width':(loc.w+4),'height':(loc.h+5)})
								.fadeIn();

							//scroll to show edit area
							if( $win.scrollTop() > loc.top || ( $win.scrollTop() + $win.height() ) < loc.top ){
								$('html,body').stop(true,true,true).animate({scrollTop: Math.max(0,loc.top-100)},'slow');
							}

						},function(){
							box.stop(true,true,true).fadeOut();
						}).click(function(){
							$(this).unbind('mouseenter');
							window.setTimeout(function(){
								$(this).remove();
								box.hide();
							},100);
						});
				});

			//if( !count ){
			//	alert('count: '+count);
			//}

		});
	}


	/**
	 * Editable area outline
	 *
	 */
	function EditOutlines(){
		var timeout = false,overlay,lnk_span=false,edit_area,highlight_box;

		overlay = $gp.div('gp_edit_overlay');
		overlay.bind('click',function(evt){

			//if a link is clicked, prevent the overlay from being shown right away
			var target = $(evt.target);
			if( target.filter('a').length > 0 ){
				if( target.attr('name') == 'gp_overlay_close' ){
					evt.preventDefault();
				}
				if( edit_area) edit_area.addClass('gp_no_overlay');
			}
			HideOverlay();

		}).bind('mouseleave',function(evt){
			StartOverlayHide();
		}).bind('mouseenter',function(){
			if( timeout ) window.clearTimeout(timeout);
		});

		//show the edit link when hovering over an editable area
		//	using mouseenter to show link an area filled with an iframe
		$('.editable_area').bind('mousemove.gp mouseenter.gp',function(e){
			if( timeout ) window.clearTimeout(timeout);


			var new_area = $(this);
			if( new_area.parent().closest('.editable_area').length > 0 ){
				e.stopPropagation();
			}

			if( edit_area && new_area.attr('id') == edit_area.attr('id') ){
				return;
			}else if( edit_area ){
				rmNoOverlay(edit_area);
			}

			edit_area = new_area;

			AreaOverlay(edit_area);

		}).bind('mouseleave',function(){
			StartOverlayHide();
			rmNoOverlay(edit_area);
		});

		$(window).scroll(function(){
			SpanPosition();
		});


		function rmNoOverlay(edit_area){

			if( !edit_area ) return;

			if( edit_area.hasClass('gp_editing') ){
				return;
			}
			edit_area.removeClass('gp_no_overlay');
		}

		/**
		 * Make sure the span for the current edit area is within the viewable window
		 *
		 */
		function SpanPosition(){
			if( !lnk_span ) return;

			var off = lnk_span.offset(),
				pos = lnk_span.position(),
				top = $(window).scrollTop(),
				diff = Math.max(0,top - (off.top - pos.top));

			lnk_span.stop(true,true,true).animate({'top':diff});
		}


		function StartOverlayHide(){
			if( timeout ) window.clearTimeout(timeout);

			timeout = window.setTimeout(
				function(){
					HideOverlay();
				},200);
		}

		function HideOverlay(){
			edit_area = false;

			//hide links
			overlay.find('span').stop(true,true,true).hide(500);

			//hide the box
			var box = overlay.find('div');
			box	.stop(true)
				.animate({'width':0,'height':0},{
					complete:function(){
						box.hide();
					}
				});
		}


		/**
		 * Display the eidt links and overlay that outlines an editable area
		 *
		 */
		function AreaOverlay(edit_area){
			var id,loc,width;

			//don't show overlay
			//	- for an area that is being edited
			//	- if we've already shown it
			if( edit_area.hasClass('gp_no_overlay') ){
				return;
			}


			id = edit_area.attr('id').substr(13); //edit_area is always ExtraEditArea#
			loc = GetCoords(edit_area);
			overlay.show().css({'top':(loc.top-3),'left':(loc.left-2),'width':(loc.w+6)});

			if( !lnk_span ){
				lnk_span = $('<span>');
				highlight_box = $('<div>');
				overlay.html(highlight_box).append(lnk_span);
			}else{
				lnk_span.stop(true,true,true).show();
			}

			highlight_box.stop(true,true,true).css({'height':0,'width':0});

			SpanPosition();

			//add the edit links
			var tmp = $('#ExtraEditLnks'+id).find('a');
			if( tmp.length == 0 ){
				tmp = $('#ExtraEditLink'+id);
			}
			tmp = tmp.clone(true)
				.removeClass('ExtraEditLink')
				;

			var close_text = '<a class="gp_overlay_expand" name="gp_overlay_close"></a>';
			lnk_span.html(close_text).unbind('mouseenter').one('mouseenter',function(){
				if( edit_area.hasClass('gp_no_overlay') ){
					return;
				}

				lnk_span
					.html(close_text)
					.stop(true,true,true)
					.show()
					.append(tmp)
					.find('.gp_overlay_expand').attr('class','gp_overlay_close')
					;

				//show the overlay
				highlight_box.stop(true,true,true).show().delay(200).animate({'width':(loc.w+4),'height':(loc.h+5)});
			});

		}

	} /* end EditOutlines */


	function UIEffects(){

		SimpleDrag('#simplepanel .toolbar, #simplepanel .toolbar a','#simplepanel','fixed',function(newpos,e){
			gpui.tx = newpos.left;
			gpui.ty = newpos.top;
			$gp.SaveGPUI();
		},true);


		if( admin_resizable ){
			SimpleResize('#admincontainer',
				{
					min_w:300,
					finish:function(width,left){
						gpui.pposx = left;
						gpui.pw = width;
						$gp.SaveGPUI();
					}
				});
		}


		//keep expanding areas within the viewable window
		$('.in_window').parent().bind('mouseenter',function(){
			var $this = $(this);
			var panel = $this.children('.in_window').css({'right':'auto','left':'100%','top':0});
			window.setTimeout(function(){
				var pos = panel.offset();
				var right = pos.left + panel.width();
				var bottom = pos.top + panel.height();
				var $win = $(window);


				if( right > $win.width() + $win.scrollLeft() ){
					panel.css({'right':'100%','left':'auto'});
				}

				var winbottom = $win.height() + $win.scrollTop();
				if( bottom > winbottom ){
					var diff = winbottom +  - bottom - 10;
					panel.css({'top':diff});
				}
			},1);
		});




	}


	/*
	 * Use jQuery's Ajax functions to load javascripts and call callback when complete
	 * @depredated 2.0.2 Keep for Simple Slideshow Plugin
	 *
	 */
	$gp.Loaded = {};
	$gp.LoadScripts = function(scripts,callback,relative_path){

		var script_count = scripts.length,
							d=new Date(),
							t=d.getTime(),
							script,
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
				$.getScript( jPrep(script,'t='+t), function(){
					sload();
				});
			}
		});

		function sload(){
			script_count--;
			if(script_count == 0){
				if( typeof(callback) == 'function' ){
					callback.call(this);
				}
			}
		}

	}


	/**
	 * Escape special html characters in a string similar to php's htmlspecialchars() function
	 *
	 */
	$gp.htmlchars = function(str){
		str = str || '';
		return $('<a>').text(str).html();
	}

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
function SimpleDrag(selector,drag_area,positioning,callback_done){
	var tolerance = -10;
	var $drag_area = $(drag_area);
	var $win = $(window);
	var $doc = $(document);


		//dragging
		$(selector).die('mousedown.sdrag').live('mousedown.sdrag',function(e){
			/* if( e.target.nodeName != 'DIV') return; */

			var box, click_offsetx, click_offsety;
			e.preventDefault();
			if( $drag_area.length < 1 ){
				return;
			}
			init();
			function init(){
				var pos = $drag_area.offset();
				click_offsetx = e.clientX - pos.left + $win.scrollLeft();
				click_offsety = e.clientY - pos.top + $win.scrollTop();

				//$drag_area.fadeTo(0,.5);

				//if( positioning == 'fixed' ){
					//box = $drag_area;
				//}
			}


			$doc.bind('mousemove.sdrag',function(e){

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



			$doc.unbind('mouseup.sdrag').bind('mouseup.sdrag',function(e){
				var newposleft,newpostop,pos_obj;
				$doc.unbind('mousemove.sdrag mouseup.sdrag');

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
				if( positioning == 'absolute' ){
					newposleft += $win.scrollLeft();
					newpostop += $win.scrollTop();
				}

				newposleft = Math.max(tolerance,newposleft);
				newpostop = Math.max(tolerance,newpostop);


				pos_obj = {'left':newposleft,'top': newpostop};

				$drag_area.css(pos_obj).data({'gp_left':newposleft,'gp_top':newpostop});

				if( typeof(callback_done) == 'function' ){
					callback_done.call($drag_area,pos_obj,e);
				}
				return false;
			});

			return false;
		});



		if( $drag_area.css('position') == 'fixed' || $drag_area.parent().css('position') == 'fixed' ){
			KeepViewable( $drag_area.addClass('keep_viewable') ,true);
		}

		function KeepViewable($elem,init){

			if( !$elem.hasClass('keep_viewable') ) return;

			var is_fixed = ($elem.css('position') == 'fixed');
			var pos, gp_left, css = {};

			//get current position
			pos = $elem.position();
			if( is_fixed ){
				pos.left -= $win.scrollLeft();
				pos.top -= $win.scrollTop();
			}

			//move back to the right if $elem has been moved left
			if( init ){
				$elem.data({'gp_left':pos.left,'gp_top':pos.top});
			}else if( gp_left = $elem.data('gp_left') ){
				pos.left = css.left = gp_left;
				pos.top = css.top = $elem.data('gp_top');
			}

			var width = $elem.width();
			var height = $elem.height();


			//keep the top of the area from being placed too high in the window
			var winbottom = $win.height();
			if( pos.top < tolerance ){
				css.top = tolerance;

			//keep the top of the area from being placed too low
			}else if( pos.top > winbottom ){
				css.top = winbottom + 2*tolerance; //tolerance is negative
			}


			/*
			var winheight = $win.height();
			var checkbottom = $win.height() - height - tolerance;
			if( pos.top < tolerance ){
				css.top = tolerance;

			}else if( pos.top > checkbottom ){
				if( height > winheight ){
					css.top = checkbottom + ( height - winheight);
				}else{
					css.top = checkbottom;
				}
			}
			*/

			//right
			var checkright = $win.width()  - width - tolerance;
			if( pos.left > checkright ){
				css.left = checkright;
			}

			if( css.left || css.top ){
				$elem.css(css);
			}
		}

		$win.resize(function(){
			$('.keep_viewable').each(function(){
				KeepViewable($(this),false);
			});
		});

}


/**
 * Initialize functionality for the rename/details dialog
 *
 */
function RenamePrep(){
	var $form;
	var old_title;
	var $new_title;
	Setup();

	function Setup(){
		$form = $('#gp_rename_form');
		old_title = $form.find('input[name=old_title]').val().toLowerCase();
		$new_title = $('input.new_title');

		SyncSlug();
		$('input[disabled=disabled]').each(function(a,b){
			$(b).fadeTo(400,.6);
		});

		$('input.title_label').bind('keyup change',SyncSlug);
		$('.label_synchronize a').click(RenameSetup);

		gplinks.showmore = function(rel,evt){
			evt.preventDefault();
			$('#gp_rename_table tr').show(500);
			$(this).parent().remove();
		}

		$new_title.bind('keyup change',function(){
			ShowRedirect();
		});


		//Changes to the slug
		/* this moves the cursor to the end of the input field
		$('input.new_title').bind('keyup change',function(){
			this.value = this.value.replace(/ /g,'_');
		});
		*/

	}

	function ShowRedirect(){
		var new_val = $new_title.val().replace(/_/g,' ').toLowerCase();

		if( new_val != old_title ){
			$('#gp_rename_redirect').show(500);
		}else{
			$('#gp_rename_redirect').hide(300);
		}
	}

	//toggle sync
	function RenameSetup(e){
		e.preventDefault();

		//toggle visible link
		var td = $(this).closest('td');
		var vis = td.find('a:visible');
		td.find('a').show();
		vis.hide();

		var vis = td.find('a:visible');
		if( vis.length ){
			if( vis.hasClass('slug_edit') ){
				td.find('input').addClass('sync_label').prop('disabled','disabled').fadeTo(400,.6);
				SyncSlug();
			}else{
				td.find('input').removeClass('sync_label').prop('disabled','').fadeTo(400,1);
			}
		}
	}

	function SyncSlug(){

		var label = $('input.title_label').val();


		$('input.new_title.sync_label').val( LabelToSlug(label) );

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
		return str.replace(/ /g,'_');
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

		if( str == '.' ){
			return '';
		}

		//left trim /
		str = str.replace(/^\/\/*/g,'');

		return str;
	}


}




function SimpleResize(resize_area,options){

	var defaults = {
		max_w:10000,
		min_w:0,
		finish:function(area,width){}
	};

	var options = $.extend({}, defaults, options);
	var tolerance = -10;
	var $resize_area = $(resize_area);
	if( $resize_area.length < 1 ){
		return;
	}


	$handle_areas = $('<span class="gp_admin_resize"></span><span class="gp_admin_resize gp_resize_right"></span>')
		.appendTo($resize_area)
		.bind('mousedown.sres',function(evt){

			var start_x = evt.clientX;
			var new_w = start_w = $resize_area.width();
			var new_l = start_l = $resize_area.position().left;
			var $this = $(this);

			evt.preventDefault();

			$(document).bind('mousemove.sres',function(evt){
				$('body').disableSelection();
				evt.preventDefault();
				//evt.stopPropagation();
				//evt.stopImmediatePropagation();

				if( $this.hasClass('gp_resize_right') ){
					new_w = evt.clientX - start_x + start_w;
				}else{
					new_l = evt.clientX - start_x + start_l;
					new_w = start_x - evt.clientX + start_w;
				}

				new_w = Math.min(options.max_w,new_w);
				new_w = Math.max(options.min_w,new_w);
				$resize_area.width(new_w);

				new_l = Math.max(0,new_l);
				$resize_area.css({left:new_l});

				return false;

			}).unbind('mouseup.sres').bind('mouseup.sres',function(evt){
				var newposleft,newpostop,pos_obj;

				evt.preventDefault();
				$('body').enableSelection();
				$(document).unbind('mousemove.sres mouseup.sres')

				options.finish.call($resize_area,new_w,new_l);

				return false;
			});

			return false;
		});


}

/**
 * Disable/Enable Selection
 * TODO: These override the functions in jquery ui and should probably tested and changed or removed
 *
 */
$.fn.disableSelection = function(){
	return $(this).attr('unselectable', 'on')
			.css('-moz-user-select', 'none')
			.each(function() {
			this.onselectstart = function() { return false; };
			});
};

$.fn.enableSelection = function() {
	return $(this).attr('unselectable', 'off')
			.css('-moz-user-select', '')
			.each(function() {
				this.onselectstart = function() { return false; };
			});
};
