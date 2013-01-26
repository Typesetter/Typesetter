
/*
 *
 * Inline Editing of Galleries
 *
 * uses jquery ui sortable
 * http://jqueryui.com/demos/sortable/
 *
 */


	function gp_init_inline_edit(area_id,section_object,options){

		$gp.LoadStyle('/include/css/inline_image.css');

		var defaults = {
			sortable_area_sel:	'.gp_gallery',
			img_name:			'gallery',
			img_rel:			'gallery_gallery'
		};
		var settings = $.extend(settings, defaults, options);

		//options for inline editing can be set using the global variable gp_gallery_options
		if( typeof(gp_gallery_options) !== 'undefined' ){
			var settings = $.extend(settings, defaults, gp_gallery_options);
		}

		//components that can be removed
		var edit_links = false,
			content_cache = false,
			current_image = false,
			sortable_area;

		var save_path = gp_editing.get_path(area_id);
		var edit_div = gp_editing.get_edit_area(area_id);
		if( edit_div == false || save_path == false ){
			return;
		}

		gp_editor = {
			save_path: save_path,

			destroy:function(){
				sortable_area.filter(':ui-sortable').sortable('destroy');
				edit_div.html(content_cache.html());
				sortable_area.children('li').unbind('mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit');
				edit_links.remove();
			},
			checkDirty:function(){

				sortable_area.removeClass('ui-sortable');

				//for IE8
				var orig_content = content_cache.html().replace(/>[\s]+/g,">");
				var new_content = edit_div.html().replace(/>[\s]+/g,">");

				if( orig_content != new_content ){
					sortable_area.addClass('ui-sortable');
					return true;
				}

				sortable_area.addClass('ui-sortable');
				return false;
			},
			gp_saveData:function(){

				var data = edit_div.clone();
				data.find('li.holder').remove();
				data.find('ul').enableSelection().removeClass('ui-sortable').removeAttr('unselectable');
				data.find('.gp_nosave').remove();
				data = data.html();

				return 'gpcontent='+encodeURIComponent(data);
			},
			resetDirty:function(){
				content_cache = edit_div.clone(false);
				content_cache.find('.ui-sortable').removeClass('ui-sortable');
			},
			updateElement:function(){
			}
		}


		//replace with raw content then start ckeditor
		edit_div.get(0).innerHTML = section_object.content;
		ShowEditor();


		function ShowEditor(){
			sortable_area = edit_div.find(settings.sortable_area_sel);
			MakeSortable();
			gp_editor.resetDirty();

			var edit_path = strip_from(save_path,'?');

			gp_editing.editor_tools();

			//floating editor
			$('#ckeditor_top').html('<div id="gp_image_area"></div><div id="gp_upload_queue"></div>');
			$('#ckeditor_controls').prepend('<div id="gp_folder_options"></div>');

			LoadImages(false);



			edit_links = $('<span class="gp_gallery_edit gp_floating_area"></span>').appendTo('body').hide();
			AddLink(edit_links,'gp_gallery_caption','page_edit');
			AddLink(edit_links,'gp_gallery_rm','delete');
			edit_links.bind('mouseenter.gp_edit',function(){
				edit_links.show();
			}).bind('mouseleave.gp_edit',function(){
				edit_links.hide();
			});

			AddListeners();

			function AddLink(div,name,img){
				div.append('<a data-cmd="'+name+'"><img src="'+gpBase+'/include/imgs/blank.gif" height="16" width="16" class="'+img+'"/></a>');
			}

			$gp.links.gp_gallery_caption = function(){
				edit_links.hide();
				var $li = $(current_image);
				var caption = $li.find('.caption').html() || $li.find('a:first').attr('title'); //title attr for backwards compat


				var popup = '<div class="inline_box" id="gp_gallery_caption"><form><h3>'+gplang.cp+'</h3>'
							+ '<textarea name="caption" cols="40" rows="8">'+$gp.htmlchars(caption)+'</textarea>'
							+ '<p><input type="submit" name="cmd" value="'+gplang.up+'" class="gp_gallery_update" /> '
							+ '<input type="button" name="" value="'+gplang.ca+'" class="admin_box_close" /></p>'
							+ '</form></div>';

				$gp.AdminBoxC(popup);
			}

			$gp.links.gp_gallery_rm = function(){
				$(current_image).remove();
				edit_links.hide(); //so that a new mouseover will happen
			}

			/**
			 * Update an image's caption with data supplied by user from gplinks.gp_gallery_caption()
			 *
			 */
			gpinputs.gp_gallery_update = function(evt){

				evt.preventDefault();
				var text = $(this.form).find('textarea').val();
				var caption_div = $(current_image).find('.caption');
				caption_div.html(text);
				text = caption_div.html(); //browsers may change the text

				$(current_image).find('a, img').attr('title', text );
				$gp.CloseAdminBox();
			}

		}

		function AddListeners(){

			sortable_area
			.children('li')
			.unbind('mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit')
			.bind('mouseenter.gp_edit',function(){
				var offset = $(this).offset();
				edit_links.show().css({'left':offset.left,'top':offset.top});
				current_image = this;

			}).bind('mouseleave.gp_edit',function(){
				edit_links.hide();
			}).bind('mousedown.gp_edit',function(){
				edit_links.hide();
			});
		}

		function MakeSortable(){
			sortable_area.sortable({
				placeholder: 'gp_drag_box',
				opacity: 0.6,
				tolerance: 'pointer',
				beforeStop: function(event, ui) {
					ui.item.removeAttr('style').removeAttr('class'); //clean the elements up
				}
			});
			sortable_area.disableSelection();
		}


		/*
		 * add image functions
		 *
		 */
		$gp.links.gp_gallery_add = function(evt){
			evt.preventDefault();
			var $this = $(this).stop(true,true);
			AddImage($this.clone());

			$this.parent().fadeTo(100,.2).fadeTo(2000,1);
		}

		$gp.links.gp_gallery_add_all = function(evt){
			evt.preventDefault();
			$('#gp_gallery_avail_imgs').find('a[name=gp_gallery_add],a[data-cmd=gp_gallery_add]').each(function(a,b){
				AddImage( $(this).clone() );
			});
		}


		function AddImage(img,holder){

			sortable_area.find('.gp_to_remove').remove();
			img.attr({'data-cmd':settings.img_name,'data-arg':settings.img_rel,'title':'','class':settings.img_rel})
			var li = $('<li>').append(img).append('<div class="caption"></div>');
			if( holder ){
				holder.replaceWith(li);
			}else{
				sortable_area.append(li);
			}

			AddListeners(); //refresh the listeners
		}


		/**
		 * Check to see if a deleted file is in the current gallery
		 */
		//gpresponse.img_deleted = function(data){
			//var img = edit_div.find('a[href="'+data.CONTENT+'"] img');
			//if( img.length > 0 ){
				//var path = img.attr('src')+'?';
				//img.attr('src','');
				//window.setTimeout(function(){
					//img.attr('src',path);
				//},500);
			//}
		//}



		/**
		 * Add file upload handlers after the form is loaded
		 *
		 */
		gpresponse.gp_gallery_images = function(data){
			MultipleFileHandler($('#gp_upload_form'));
		}

		function MultipleFileHandler(form){
			var action = form.attr('action');
			var progress_bars = {};

			form.find('.file').auto_upload({

				start: function(name, settings){
					settings['bar'] = $('<a data-cmd="gp_file_uploading">'+name+'</a>').appendTo('#gp_upload_queue');
					settings['holder'] = $('<li class="holder" style="display:none"></li>').appendTo(sortable_area);
					return true;
				},

				progress: function(progress, name, settings) {
					progress = Math.round(progress*100);
					progress = Math.min(98,progress-1);
					settings['bar'].text(progress+'% '+name);
				},

				finish: function( response, name, settings) {
					var progress_bar = settings['bar'];
					progress_bar.text('100% '+name);

					var $contents = $(response);
					var status = $contents.find('.status').val();
					var message = $contents.find('.message').val();

					if( status == 'success' ){
						progress_bar.addClass('success');
						progress_bar.slideUp(1200);


						var avail = $('#gp_gallery_avail_imgs');
						var img = $(message).appendTo(avail);
						//var img_link = img.find('a[name=gp_gallery_add]');
						var img_link = img.find('a[name=gp_gallery_add],a[data-cmd=gp_gallery_add]');
						AddImage(img_link.clone(),settings['holder']);

					}else if( status == 'notimage' ){
						progress_bar.addClass('success');
					}else{
						progress_bar.addClass('failed');
						progress_bar.text(name+': '+message);
					}
				},

				error: function(event, name, error) {
					alert('error: '+error);
				}
			});
		}


		$gp.links.gp_file_uploading = function(){
			var $this = $(this);
			var remove = false;
			if( $this.hasClass('failed') ){
				remove = true;
			}else if( $this.hasClass('success') ){
				remove = true;
			}
			if( remove ){
				$this.slideUp(700);
			}
		}




		/**
		 *
		 */
		gpinputs.gp_gallery_folder_add = function(rel,evt){
			evt.preventDefault();
			var frm = this.form;
			var dir = frm.dir.value;
			var newdir = dir+'/'+frm.newdir.value
			LoadImages(newdir);
		}


	}

