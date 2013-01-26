
	$(function(){

		var $sortable_area = $('#admin_menu');
		var $admin_menu_tools = $('#admin_menu_tools');
		var $current = false;
		var current_id = false;
		var original_parent = false;
		var info_html = $('#menu_info').html();//.get(0).innerHTML;
		var info_html_extern = $('#menu_info_extern').html();//.get(0).innerHTML;


		$sortable_area.nestedSortable({
			disableNesting: 'no-nest',
			forcePlaceholderSize: true,
			handle: 'a.sort',
			items: 'li',
			opacity: .8,
			placeholder: 'placeholder',
			tabSize: 25,
			toleranceElement: '> div',
			listType: 'ul',
			delay:2,
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
				data += getmenus();
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




		gpresponse.gp_menu_refresh = function(j){
			$sortable_area.nestedSortable('refresh');

			if( current_id ){
				var $new_current = $('#'+current_id);
				ShowInfo($new_current);
			}
		}


		function GetTitle(obj){
			obj = obj.find('a.label');
			if( obj.length == 0 ){
				return '';
			}
			return obj.data('arg');
		}


		$gp.links.menu_info = function(evt){
			evt.preventDefault();
			var $this = $(this).parent();

			//this should all happen after the sortable actions are done
			window.setTimeout(function(){

				ShowInfo($this);

			},100);
		}

		function ShowInfo($new_current){

			if( $current ){
				$current.removeClass('current');
			}

			$current = $new_current;
			if( !$current.length ){
				$admin_menu_tools.hide();
				return;
			}

			current_id = $current.attr('id');
			$current.addClass('current');

			InfoHtml();
			var tools_height = $admin_menu_tools.height();
			var sortable_height = $sortable_area.height();
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
		function InfoHtml(){
			//get data
			var this_html, data = jQuery.parseJSON( $current.find('span').html() );

			var external = $current.find('.external').length;

			if( external ){
				this_html = info_html_extern;
			}else{
				this_html = info_html;
			}

			data = $.extend({}, {title:'',layout_color:'',layout_label:'',types:'',size:'',mtime:'',opts:''}, data);

			var reg,parts = ['title','key','url','layout_label','types','size','mtime','opts'];
			$.each(parts,function(){
				reg = new RegExp('(%5B'+this+'%5D|\\['+this+'\\])','gi');
				this_html = this_html.replace(reg,data[this]);
			});

			$admin_menu_tools
				.show()
				.html(this_html)
				.find('.layout_icon').css({'background':data.layout_color});
				;


			//special links
			if( data.special ){
				$admin_menu_tools.find('.not_special').hide();
			}

			//has layout
			if( data.has_layout ){
				$admin_menu_tools.find('.no_layout').hide();
			}else{
				$admin_menu_tools.find('.has_layout').hide();
			}

			if( data.level >= max_level_index ){
				$admin_menu_tools.find('.insert_child').hide();
			}
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
		 * Unescape special characters in the title's data
		 * single and double quotation marks are not escaped by php for this data
		 *
		 */
		function unspecialchars(str){
			return str.replace(/&amp;/g, '&')
						.replace(/&lt;/g, '<')
						.replace(/&gt;/g, '>')
						;
		}


		/*
		 * Save the list of title that are collapsed
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
				$admin_menu_div.find('.hidechildren > div > .label').each(function(a,b){
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
		 * Get information about the current menus so we can reload them as they change
		 *
		 * menu_markers aren't next to the actual menus any more
		 *
		 */
		function getmenus(){
			var vals,id,result = '';

			$('.menu_marker').each(function(){
				var $this = $(this);
				id = $this.data('menuid');
				vals = $this.children();

				result += '&menus['+id+']='+encodeURIComponent(vals.eq(0).val());
				result += '&menuh['+id+']='+encodeURIComponent(vals.eq(1).val());
				result += '&menuc['+id+']='+encodeURIComponent(vals.eq(2).val());
			});
			return result;
		}

		gpresponse.replacemenu = function(j){
			$(j.SELECTOR).find('ul:first').replaceWith(j.CONTENT);
		}

		gpinputs.menupost = function(){
			var a = getmenus();
			var frm = $(this).closest('form');
			frm.attr('action', $gp.jPrep(frm.attr('action'),a));
			$gp.post(this);
			return false;
		}

		$gp.links.menupost = function(evt){
			evt.preventDefault();
			var query = strip_to(this.search,'?');
			query += getmenus();
			$gp.postC( this.href, query);
		}


		/*
		 * Menu Type Selector
		 */
		$('#gp_menu_select').change(function(){
			SaveSettings();

			//similar to reload, but it doesn't initiate post resend
			//we don't want a query string with menu selection to be sent
			window.location = strip_from(window.location.href,'?');
		});


		/**
		 * Use javascript to style the checkbox labels when they're checked
		 *
		 */
		$(document).on('click','input:checkbox',function(){
			$this = $(this);
			if( $this.filter(':checked').length > 0 ){
				$this.closest('li').addClass('gpui-state-checked');
			}else{
				$this.closest('li').removeClass('gpui-state-checked');
			}
		});


		/**
		 * Reduce a list of titles by search criteria entered in gpsearch areas
		 *
		 */
		$(document).on('keyup','input.gpsearch',function(){
			var search = this.value.toLowerCase();
			$(this).closest('form').find('.gpui-scrolllist li:not(.gpui-state-checked)').each(function(){
				var $this = $(this);
				if( $this.text().toLowerCase().indexOf(search) == -1 ){
					$this.hide();
				}else{
					$this.show();
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

