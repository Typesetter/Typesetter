
/*
 *
 * Inline Editing of Galleries
 *
 * uses jquery ui sortable
 * http://jqueryui.com/demos/sortable/
 *
 */


	/*
	 *
	$gp.links.browser_dialog = function(evt){

		evt.preventDefault();

		var $window = $(window);
		var windowFeatures =    'height=400'+
						',width=800'+
						',toolbar=0'+
						',scrollbars=0'+
						',status=0'+
						',resizable=1'+
						',location=0'+
						',menuBar=0'+
						',left='+ Math.round( ($window.width()-800)/2 )+
						',top='+ Math.round( ($window.height()-400)/2 )
						;

		window.open(this.href, 'select_images',windowFeatures).focus();
	};
	 */


	gp_editor = {

		sortable_area_sel:	'.gp_gallery',
		img_name:			'gallery',
		img_rel:			'gallery_gallery',
		edit_links_target:	false,
		auto_start:			false,
		make_sortable:		true,

		/**
		 * Called when a caption is edited
		 *
		 */
		updateCaption:function(current_image,caption){
		},

		/**
		 * Called before an image is removed
		 *
		 */
		removeImage:function(image){
		},

		/**
		 * Called after an image is removed
		 *
		 */
		removedImage:function(edit_div){
		},

		/**
		 * Called when an image is added to the gallery
		 * @param object $li A jquery object of the new <li> element
		 *
		 */
		addedImage:function($li){
		},

		/**
		 * Called when the inline editor has loaded
		 *
		 */
		editorLoaded:function(){
		},

		/**
		 * Called when the width setting is changed
		 *
		 * widthChanged:function(width){}
		 *
		 */
		widthChanged: false,

		/**
		 * Called when the height setting is changed
		 *
		 * heightChanged:function(height){},
		 */
		heightChanged: false,


		checkDirty:function(){
			return false;
		},
		getData:function(edit_div){

			var args = {
				images: [],
				captions: []
			};


			//images
			edit_div.find(gp_editor.sortable_area_sel).find('li > a').each(function(){
				args.images.push( $(this).attr('href') );
			});

			//captions
			edit_div.find(gp_editor.edit_links_target).find('.caption').each(function(){
				args.captions.push( $(this).html() );
			});


			//height & width
			args.width = $('#gp_gallery_width').val();
			args.height = $('#gp_gallery_height').val();
			args.auto_start = $('#gp_gallery_auto_start').prop('checked');

			//get content
			var data = edit_div.clone();
			data.find('li.holder').remove();
			data.find('ul').enableSelection().removeClass('ui-sortable').removeAttr('unselectable');
			data.find('.gp_nosave').remove();
			data = data.html();
			return $.param(args)+'&gpcontent='+encodeURIComponent(data);
		},
		updateElement:function(){
		}
	};

	function gp_init_inline_edit(area_id,section_object){

		$gp.LoadStyle('/include/css/inline_image.css');

		//options for inline editing can be set using the global variable gp_gallery_options
		// @deprecated gp_gallery_options
		if( typeof(gp_gallery_options) !== 'undefined' ){
			$.extend(gp_editor, gp_gallery_options);
		}
		if( !gp_editor.edit_links_target ){
			gp_editor.edit_links_target = gp_editor.sortable_area_sel+' > li'
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

		gp_editor.save_path = save_path;

		gp_editor.destroy = function(){
			sortable_area.filter(':ui-sortable').sortable('destroy');
			edit_div.html(content_cache.html());
			sortable_area.children('li').unbind('mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit');
			edit_links.remove();
		};

		gp_editor.checkDirty = function(){

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
		};

		gp_editor.gp_saveData = function(){
			return gp_editor.getData(edit_div,gp_editor);
		}


		gp_editor.resetDirty = function(){
			content_cache = edit_div.clone(false);
			content_cache.find('.ui-sortable').removeClass('ui-sortable');
		};


		//replace with raw content then start ckeditor
		edit_div.get(0).innerHTML = section_object.content;
		ShowEditor();
		gp_editor.editorLoaded();


		function ShowEditor(){
			sortable_area = edit_div.find(gp_editor.sortable_area_sel);
			if( gp_editor.make_sortable ){
				MakeSortable();
			}
			gp_editor.resetDirty();

			var edit_path = strip_from(save_path,'?');

			gp_editing.editor_tools();

			//floating editor
			$('#ckeditor_top').html('<div id="gp_image_area"></div><div id="gp_upload_queue"></div>');
			$('#ckeditor_controls').prepend('<div id="gp_folder_options"></div>');

			LoadImages(false,gp_editor);

			var option_area = $('<div>').prependTo('#ckeditor_save');


			/**
			 * Height Option
			 *
			 */
			if( gp_editor.heightChanged ){

				$('<div class="half_area">'+gplang.Height+': <input class="ck_input" type="text" id="gp_gallery_height" name="height" /></div>')
					.appendTo(option_area)
					.find('input')
					.val(section_object.height)
					.on('keyup paste change',gp_editor.heightChanged)
					;

			}


			/**
			 * Width Option
			 *
			 */
			if( gp_editor.widthChanged ){
				debug(gp_editor.widthChanged);

				$('<div class="half_area">'+gplang.Width+': <input class="ck_input" type="text" id="gp_gallery_width" name="width" /></div>')
					.appendTo(option_area)
					.find('input')
					.val(section_object.width)
					.on('keyup paste change',gp_editor.widthChanged)
					;

			}


			/**
			 * Auto Start
			 *
			 */
			if( gp_editor.auto_start ){
				gplang.Auto_Start = 'Auto Start';
				$('<div class="half_area">'+gplang.Auto_Start+': <input class="ck_input" type="checkbox" id="gp_gallery_auto_start" name="auto_start" /></div>')
					.appendTo(option_area)
					.find('input')
					.prop('checked',section_object.auto_start)
					;

			}


			/**
			 * Caption & delete links
			 *
			 */
			function AddLink(div,name,img){
				div.append('<a data-cmd="'+name+'"><img src="'+gpBase+'/include/imgs/blank.gif" height="16" width="16" class="'+img+'"/></a>');
			}
			edit_links = $('<span class="gp_gallery_edit gp_floating_area"></span>').appendTo('body').hide();
			AddLink(edit_links,'gp_gallery_caption','page_edit');
			AddLink(edit_links,'gp_gallery_rm','delete');
			edit_links.bind('mouseenter.gp_edit',function(){
				edit_links.show();
			}).bind('mouseleave.gp_edit',function(){
				edit_links.hide();
			});


			$(document).delegate(gp_editor.edit_links_target,{
				'mouseenter.gp_edit':function(){
					var offset = $(this).offset();
					edit_links.show().css({'left':offset.left,'top':offset.top});
					current_image = this;

				},
				'mouseleave.gp_edit':function(){
					edit_links.hide();
				},
				'mousedown.gp_edit':function(){
					edit_links.hide();
				}
			});

			$gp.links.gp_gallery_caption = function(){
				edit_links.hide();
				var $li = $(current_image);
				var caption = $li.find('.caption').html() || $li.find('a:first').attr('title'); //title attr for backwards compat


				var popup = '<div class="inline_box" id="gp_gallery_caption"><form><h3>'+gplang.cp+'</h3>'
							+ '<textarea name="caption" cols="50" rows="3">'+$gp.htmlchars(caption)+'</textarea>'
							+ '<p><input type="submit" name="cmd" value="'+gplang.up+'" class="gp_gallery_update" /> '
							+ '<input type="button" name="" value="'+gplang.ca+'" class="admin_box_close" /></p>'
							+ '</form></div>';

				$gp.AdminBoxC(popup);
			}

			$gp.links.gp_gallery_rm = function(){
				gp_editor.removeImage(current_image);
				$(current_image).remove();
				edit_links.hide(); //so that a new mouseover will happen
				gp_editor.removedImage(edit_div);
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

				$gp.CloseAdminBox();
				gp_editor.updateCaption(current_image,text);
			}

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

			edit_div.find('.gp_to_remove').remove();
			img.attr({'data-cmd':gp_editor.img_name,'data-arg':gp_editor.img_rel,'title':'','class':gp_editor.img_rel})
			var li = $('<li>').append(img).append('<div class="caption"></div>');
			if( holder ){
				holder.replaceWith(li);
			}else{
				sortable_area.append(li);
			}

			li.trigger('gp_gallery_add');
			gp_editor.addedImage(li);
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

				start: function(name, args){
					args['bar'] = $('<a data-cmd="gp_file_uploading">'+name+'</a>').appendTo('#gp_upload_queue');
					args['holder'] = $('<li class="holder" style="display:none"></li>').appendTo(sortable_area);
					return true;
				},

				progress: function(progress, name, args) {
					progress = Math.round(progress*100);
					progress = Math.min(98,progress-1);
					args['bar'].text(progress+'% '+name);
				},

				finish: function( response, name, args) {
					var progress_bar = args['bar'];
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
						AddImage(img_link.clone(),args['holder']);

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
			LoadImages(newdir,gp_editor);
		}


	}

