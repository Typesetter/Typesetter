
/*
 *
 * Inline Editing of Galleries
 *
 *
 *	@deprecated 2.0.2 Use gallery_edit_202.js instead
 *
 */



	gplinks.gallery_inline_edit = function(rel,evt,options){
		evt.preventDefault();

		var defaults = {
			sortable_area_sel:	'.gp_gallery',
			img_name:			'gallery',
			img_rel:			'gallery_gallery'
		};

		var settings = $.extend(settings, defaults, options);


		//get the editable area
		var edit_div = gp_editing.new_edit_area(this);
		if( edit_div == false ){
			return;
		}

		//components that can be removed
		var edit_links = false,
			content_cache = false,
			current_image = false,
			file_path = strip_from(this.href,'#'),
			sortable_area;





		gp_editor = {
			save_path: this.href,

			destroy:function(){
				sortable_area.sortable('destroy');
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
				data.find('ul').removeClass('ui-sortable');
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
		gpresponse.rawcontent = function(obj){
			edit_div.get(0).innerHTML = obj.CONTENT;
			ShowEditor();
		}
		$gp.jGoTo(file_path+'&cmd=rawcontent');


		function ShowEditor(){
			sortable_area = edit_div.find(settings.sortable_area_sel);
			MakeSortable();
			gp_editor.resetDirty();

			var edit_path = strip_from(file_path,'?');

			gp_editing.editor_tools();

			//floating editor
			$('#ckeditor_top').html('<div id="gp_image_area"></div><div id="gp_upload_queue"></div>');
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
				div.append('<a href="#" name="'+name+'"><img src="'+gpBase+'/include/imgs/blank.gif" height="16" width="16" class="'+img+'"/></a>');
			}

			gplinks.gp_gallery_caption = function(rel,evt){
				evt.preventDefault();
				edit_links.hide();
				var $li = $(current_image);
				var caption = $li.find('.caption').html();
				var img_over = $li.find('img').attr('title');

				var popup = '<div class="inline_box" id="gp_gallery_caption"><form><h3>'+gplang.cp+'</h3>'
							+ '<textarea name="caption" cols="40" rows="8">'+$gp.htmlchars(caption)+'</textarea>'
							+ '<p><input type="submit" name="cmd" value="'+gplang.up+'" class="gp_gallery_update" /> '
							+ '<input type="button" name="" value="'+gplang.ca+'" class="admin_box_close" /></p>'
							+ '</form></div>';

				$gp.AdminBoxC(popup);
			}

			gplinks.gp_gallery_rm = function(rel,evt){
				evt.preventDefault();
				$(current_image).remove();
				edit_links.hide(); //so that a new mouseover will happen
			}

			gpinputs.gp_gallery_update = function(evt){

				evt.preventDefault();
				var text = $(this.form).find('textarea').val();
				var caption_div = $(current_image).find('.caption');
				caption_div.html(text);
				text = caption_div.html(); //browsers may change the text

				$(current_image).find('a').attr('title', text );
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
		function LoadImages(directory){
			var edit_path = strip_from(file_path,'?');
			edit_path += '?cmd=gallery_images';
			if( directory ){
				edit_path += '&dir='+encodeURIComponent(directory);
			}
			$gp.jGoTo(edit_path);
		}

		gplinks.gp_gallery_add = function(rel,evt){
			evt.preventDefault();
			var $this = $(this).stop(true,true);
			AddImage($this.clone());

			$this.parent().fadeTo(100,.2).fadeTo(2000,1);

		}

		gplinks.gp_gallery_add_all = function(rel,evt){
			evt.preventDefault();
			$('#gp_gallery_avail_imgs a[name=gp_gallery_add]').each(function(a,b){
				AddImage( $(this).clone() );
			});
		}


		function AddImage(img){
			sortable_area.find('.gp_to_remove').remove();
			img.attr({'name':settings.img_name,'rel':settings.img_rel,'title':''}).removeAttr('class');
			var li = $('<li>').append(img).append('<div class="caption"></div>');
			sortable_area.append(li);

			AddListeners(); //refresh the listeners
		}


		gpresponse.gp_gallery_images = function(data){
			MultipleFileHandler($('#gp_upload_form'));
		}


		function MultipleFileHandler(form){
			var action = form.attr('action');
			var progress_bars = {};

			form.find('.file').auto_upload({

				start: function(name, settings){
					settings['bar'] = $('<a href="#" name="gp_file_uploading">'+name+'</a>').appendTo('#gp_upload_queue');
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
						var img_link = img.find('a[name=gp_gallery_add]');
						AddImage(img_link.clone());

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


		gplinks.gp_file_uploading = function(rel,evt){
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

		/*
		 * drop down menu
		 *
		 */
		gplinks.gp_show_select = function(rel,evt){
			evt.preventDefault();
			var $this = $(this);
			var $options = $this.siblings('.gp_edit_select_options');

			if( $options.is(':visible') ){
				$options.hide();
			}else{
				$options.show();

				$this.parent().unbind('mouseleave').bind('mouseleave',function(){
					$options.hide();
				});
			}

		}

		gplinks.gp_gallery_folder = function(rel,evt){
			evt.preventDefault();
			LoadImages(rel);
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

