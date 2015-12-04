
	$(function(){

		var $sortable_area			= $('#admin_menu');
		var $admin_menu_tools		= $('#admin_menu_tools');
		var current_id				= false;
		var current_index			= false;
		var original_parent			= false;
		var info_html				= $('#menu_info').html();
		var info_html_extern		= $('#menu_info_extern').html();


		$sortable_area.nestedSortable({
			disableNesting:			'no-nest',
			forcePlaceholderSize:	true,
			handle:					'a.sort',
			items:					'li',
			opacity:				.8,
			placeholder:			'placeholder',
			tabSize:				25,
			toleranceElement:		'> div',
			listType:				'ul',
			delay:					2,

			/* cancel: 'gp_no_sort', */
			/* tolerance: 'pointer', */
			/* connectWith: '.sortable_menu', */
			stop: function(event, ui){

				var item = ui.item; // the <li> element being dragged
				var parent = item.parent().siblings('div');
				var div = item.children('div');
				var data = {
					cmd: 	'drag',
					drag_key: GetTitle(div),
					parent: GetTitle(parent),
					prev: 	GetTitle(item.prev().children('div')),
					hidden: item.closest('#menu_available_list').length
				};

				//change haschildren for new parent
				if( parent.length > 0 ){
					parent.parent(':not(.haschildren)').addClass('haschildren');
				}

				//change haschildren for original parent
				if( original_parent.length > 0 ){
					if( original_parent.find('li').length == 0 ){
						original_parent.removeClass('haschildren');
					}
				}

				ShowInfo(div);
				$gp.loading();

				data = jQuery.param(data,true);
				$gp.postC( window.location.href , data);

				return;
			},
			start:function(event,ui){
				original_parent = ui.item.parent().siblings('div').parent();
			}

			//this doesn't work properly with nestedsortable
			//change:function(event,ui){
				//state_changed = true;
			//}


		}).disableSelection();


		/**
		 * Prepare for the menu to be refreshed
		 *
		 */
		gpresponse.gp_menu_prep = function(){

			//get id of .current
			var $current	= $('.current:first');
			current_id		= $current.attr('id');

			//get index of .current
			var $all		= $('#admin_menu .gp_label');
			current_index	= $all.index($current.find('.gp_label'));
		}


		/**
		 * Make sure new menu html sent from the server asynchronously is sortable
		 *
		 */
		gpresponse.gp_menu_refresh = function(j){

			$sortable_area.nestedSortable('refresh');

			if( current_id ){
				var $current = $('#'+current_id);
			}

			if( !current_id || !$current.length ){

				if( current_index > 0 ){
					$current = $('#admin_menu .gp_label').eq(current_index).parent();
				}

				if( !$current || !$current.length ){
					$current = $('.gp_label:first').parent();
				}
			}
			ShowInfo($current);
		}


		function GetTitle(obj){
			obj = obj.find('a.gp_label');
			if( obj.length == 0 ){
				return '';
			}
			return obj.data('arg');
		}


		/**
		 * Handle clicks on sortable menu items
		 *
		 */
		$gp.links.menu_info = function(evt){

			evt.preventDefault();
			var $this		= $(this);
			var $current	= $this.parent();
			var cntrl		= evt.ctrlKey;

			if( evt.ctrlKey ){
				$current.toggleClass('current');
				$current = $('.current');

			}else if( evt.shiftKey ){

				$('#admin_menu .current').removeClass('current');
				var $all	= $('#admin_menu .gp_label');
				var i		= $all.index(this);
				var j		= 0;

				var $last 	= $('.last_clicked');
				if( $last.length ){
					j 		= $all.index($last);
				}


				var in_min	= Math.min(i,j);
				var in_max	= Math.max(i,j);

				$all.each(function(i){
					if( i >= in_min  && i <= in_max ){
						$(this).parent().addClass('current');
					}
				});

				$current = $('#admin_menu .current');

			}else{
				$('.last_clicked').removeClass('last_clicked');
				$this.addClass('last_clicked');
			}

			//display options box after the sortable actions are done
			window.setTimeout(function(){
				ShowInfo($current);
			},100);

		}


		/**
		 * Display page options for selected titles
		 *
		 */
		function ShowInfo($current){

			$('#admin_menu .current').removeClass('current');

			if( !$current.length ){
				$admin_menu_tools.hide();
				return;
			}

			$current.addClass('current');

			InfoHtml($current);


			var tools_height		= $admin_menu_tools.height();
			var sortable_height		= $sortable_area.height();

			if( sortable_height < tools_height ){
				$sortable_area.css('min-height',tools_height);
			}

			//position will at times return null because of sortable()
			var pos = $current.position();
			if( pos ){

				//prevent division by zero
				var percent = 0;
				if( pos.top > 0 ){
					percent = Math.round(pos.top/sortable_height*100);
				}
				var top = pos.top - (percent/120 * tools_height);
				top = Math.max(0,top);
				$admin_menu_tools.stop().animate({'top':top});
			}
		}

		/**
		 * Format the info window with the current title's information
		 *
		 */
		function InfoHtml($current){

			var this_html			= info_html,
				data				= jQuery.extend({}, $current.find('.gp_label').data('json')), //clone the json object
				multiple_selected	= ($current.length > 1),
				$current_li			= $current.closest('li');



			//if multiple selected, get all the keys
			if( multiple_selected ){

				data.key			= $current.find('.gp_label').map(function(){ return $(this).data('arg');}).toArray().join(',');
				data.files			= $current.length;


			//external link
			}else if( $current.find('.external').length ){
				this_html = info_html_extern;
			}

			data = $.extend({}, {title:'',layout_color:'',layout_label:'',types:'',size:'',mtime:'',opts:''}, data);



			var reg,parts = ['title','key','url','layout_label','types','size','mtime','opts','files'];
			$.each(parts,function(){
				reg = new RegExp('(%5B'+this+'%5D|\\['+this+'\\])','gi');
				this_html = this_html.replace(reg,data[this]);
			});


			$admin_menu_tools
				.show()
				.html(this_html)
				.find('.layout_icon').css({'background':data.layout_color});

			var hide = []


			//multiple
			if( multiple_selected ){
				hide.push('.not_multiple');
				$admin_menu_tools.find('.only_multiple').show();
			}else{
				hide.push('.only_multiple');
			}

			//visibility
			if( $current_li.hasClass('private-list') ){
				hide.push('.vis_public');
			}else if( $current_li.hasClass('private-inherited') ){
				hide.push('.vis_private');
				hide.push('.vis_public');
			}else{
				hide.push('.vis_private');
			}



			//special links
			if( data.special ){
				hide.push('.not_special');
			}

			//has layout
			if( data.has_layout ){
				hide.push('.no_layout');
			}else{
				hide.push('.has_layout');
			}

			if( data.level >= max_level_index ){
				hide.push('.insert_child');
			}

			$admin_menu_tools.find( hide.join(',') ).hide();

		}


		/**
		 * Handle expanding/reducing sublink lists
		 *
		 */
		$gp.links.expand_img = function(evt){
			$gp.links.menu_info.call(this,evt);

			$li = $(this).closest('li');
			if( $li.hasClass('haschildren') ){
				$li.toggleClass('hidechildren');
				SaveSettings();
			}

		}


		/**
		 * Keep track of which titles are collapsed
		 *
		 */
		function SaveSettings(){


			var val,
				newval,
				$admin_menu_div = $('#admin_menu'),
				cookie_options = {
					path: gpBLink+'/Admin_Menu', //ie8 does not like the trailing forward slash:  /Admin_Menu/
					expires: 100
					};




			//menu collapsing
			if( $admin_menu_div.length > 0 ){


				val = readCookie('gp_menu_hide')||'';
				val = decodeURIComponent(val);

				var menu_id = $('#gp_curr_menu').val();
				var reg = new RegExp('#'+menu_id+'=\\[[^#=\\]\\[]*\\]','');
				val = val.replace(reg,'');
				//alert('sripped: '+val);

				newval = '#'+$('#gp_curr_menu').val()+'=[';
				$admin_menu_div.find('.hidechildren > div > .gp_label').each(function(a,b){
					newval += $(b).data('arg')+',';
				});
				newval += ']';



				//alert('new hide: '+val+newval);
				$.cookie('gp_menu_hide', val+newval, cookie_options );
			}




			//$.cookie('gp_menu_settings', null, cookie_options );
			$.cookie('gp_menu_prefs', $('#gp_menu_select_form').serialize(), cookie_options );
		}


		/*
		 * Menu Type Selector
		 */
		$('#gp_menu_select').change(function(){
			SaveSettings();

			//similar to reload, but it doesn't initiate post resend
			//we don't want a query string with menu selection to be sent
			var loc = window.location;
			var href = loc.href;
			if( href.indexOf('?') ){
				window.location = strip_from(href,'?');
			}else{
				$gp.Reload();
			}
		});


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

		function readCookie(name) {
			name += "=";
			var ca = document.cookie.split(';');
		 	for(var i=0;i < ca.length;i++) {
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
			}
			return false;
		}

		/*
		 * Init
		 *
		 */

		function init(){
			var $new_current = $('#admin_menu').find('div:first');
			ShowInfo($new_current);
			SaveSettings();
		}


		init();



	});

