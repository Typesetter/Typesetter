
/*
 *
 * Inline Editing of Galleries
 *
 * uses jquery ui sortable
 * http://jqueryui.com/demos/sortable/
 *
 */


	gp_editor = {

		sortable_area_sel:	'.gp_gallery',
		img_name:			'gallery',
		img_rel:			'gallery_gallery',
		edit_links_target:	false,
		auto_start:			false,
		make_sortable:		true,
		edit_div:			null,
		isDirty: false,
		gallery_theme: [ 
			{ label: 'Default Theme',	class_name: 'gallery-theme-default' },
			{ label: 'Masonry',			class_name: 'gallery-theme-tiles' },
			{ label: 'Circle Grid',		class_name: 'gallery-theme-circle-grid' },
			{ label: 'Circle List',		class_name: 'gallery-theme-circle-list' }
		],
		gallery_size: [ 
			{ label: 'X-Small',			class_name: 'gallery-size-xs' },
			{ label: 'Small',			class_name: 'gallery-size-sm' },
			{ label: 'Default Size',	class_name: 'gallery-size-md' },
			{ label: 'Large',			class_name: 'gallery-size-lg' },
			{ label: 'X-Large',			class_name: 'gallery-size-xl' }
		],
		gallery_color: [ 
			{ label: 'Default Color',		class_name: 'gallery-color-default' },
			{ label: 'On Dark Backgrounds',	class_name: 'gallery-color-dark' } 
		],


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
		 * Called after the images in the gallery have been resorted
		 *
		 */
		sortStop:function(){
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

		/**
		 * Called when the interval speed setting is changed
		 *
		 * intervalSpeed:function(height){},
		 */
		intervalSpeed: false,



		checkDirty:function(){
			return false;
		},

		getData:function(edit_div, section_object){

			var args = {
				images: [],
				captions: [],
				attributes: {}
			};


			//images
			edit_div.find(gp_editor.sortable_area_sel).find('li > a').each(function(){
				args.images.push( $(this).attr('href') );
			});

			//captions
			edit_div.find(gp_editor.edit_links_target).find('.caption').each(function(){
				args.captions.push( $(this).html() );
			});

			//options
			var options = $('#gp_gallery_options').find('input,select').serialize();

			// attributes
			section_object.attributes = section_object.attributes || {};
			args.attributes.class = section_object.attributes['class'] || '';

			//get content
			var data = edit_div.clone();
			data.find('li.holder').remove();
			data.find('ul').enableSelection().removeClass('ui-sortable').removeAttr('unselectable');
			data.find('.gp_nosave').remove();
			data = data.html();
			return $.param(args)+'&'+options+'&gpcontent='+encodeURIComponent(data);
		}
	};

	function gp_init_inline_edit(area_id,section_object){

		$gp.LoadStyle('/include/css/inline_image.css');

		// console.log("section_object = " , section_object );

		//options for inline editing can be set using the global variable gp_gallery_options
		// @deprecated gp_gallery_options
		if( typeof(gp_gallery_options) !== 'undefined' ){
			$.extend(gp_editor, gp_gallery_options);
		}
		if( !gp_editor.edit_links_target ){
			gp_editor.edit_links_target = gp_editor.sortable_area_sel+' > li'
		}


		var sortable_area;
		var $current_images;
		var edit_links				= false;
		var current_image			= false;
		var save_path				= gp_editing.get_path(area_id);
		gp_editor.edit_div			= gp_editing.get_edit_area(area_id);

		if( gp_editor.edit_div == false || save_path == false ){
			return;
		}

		gp_editor.save_path			= save_path;


		/**
		 * Return true if the gallery has been edited
		 *
		 */
		gp_editor.checkDirty = function(){
			var new_content		= gp_editor.getData(gp_editor.edit_div, section_object);

			if( orig_content !== new_content || gp_editor.isDirty ){
				return true;
			}

			return false;
		};


		/**
		 * Return data to be saved
		 *
		 */
		gp_editor.SaveData = function(){
			gp_editor.isDirty = false;
			return gp_editor.getData(gp_editor.edit_div, section_object);
		}


		/**
		 * Reset the orig_content value
		 *
		 */
		gp_editor.resetDirty = function(){
			orig_content = gp_editor.getData(gp_editor.edit_div, section_object);
			gp_editor.isDirty = false;
		};


		/**
		 * Get section classes
		 *
		 */
		gp_editor.getSectionClasses = function(){
			var current_classes = gp_editor.edit_div.attr("class").split(" ");
			$.each(current_classes, function(i,v){
				var classname = v.trim();
				if( classname.indexOf('gallery-theme-') === 0 
					|| classname.indexOf('gallery-size-') === 0
					|| classname.indexOf('gallery-color-') === 0 ){
					$('#' + classname).click();
				}
			});
		};


		/**
		 * Set section classes
		 * triggered by select.SetGalleryStyles change event
		 */
		gp_editor.setSectionClasses = function(evt){
			var $button_row = $(evt.target).closest('.gallery-style-button-row');
			var $inputs = $button_row.find('input[type="radio"]');
			var possible_classes = $.map($inputs, function(input){ return $(input).val(); });
			var remove_classes = possible_classes.join(" ");
			var selected_class = $(evt.target).val();

			var current_classes = section_object.attributes['class'] || '';
			var tmp_div = $('<div class="">')
				.addClass(current_classes)
				.removeClass(remove_classes)
				.addClass(selected_class);
			var new_classes = tmp_div.attr("class");

			var gp_attrs = JSON.parse(gp_editor.edit_div.attr("data-gp-attrs"));
			gp_attrs['class'] = new_classes;

			// apply classes and data
			gp_editor.edit_div
				.removeClass(remove_classes)
				.addClass(selected_class)
				.attr("data-gp-attrs", JSON.stringify(gp_attrs));

			section_object.attributes.class = new_classes;

			gp_editor.isDirty = true;
		};



		//replace with raw content then start ckeditor
		//gp_editor.edit_div.get(0).innerHTML = section_object.content;

		ShowEditor();
		var orig_content			= gp_editor.getData(gp_editor.edit_div, section_object);
		gp_editor.editorLoaded();


		function ShowEditor(){

			//Warn if the sortable area isn't found
			sortable_area = gp_editor.edit_div.find(gp_editor.sortable_area_sel);
			if( sortable_area.length == 0 ){
				console.log('sortable area not found', gp_editor.sortable_area_sel);
				return;
			}

			gp_editor.resetDirty();

			var edit_path = strip_from(save_path,'?');

			gp_editing.editor_tools();

			//floating editor
			var html	= ''; //<h4>Gallery Images</h4>'

			if( typeof(gallery_editing_options) != 'undefined' && section_object.type == "gallery" ){ // TS 5.1+ and 'Use Gallery Legacy Style' disabled
				html += '<div class="gallery-style-button-row">';
				$.each(gp_editor.gallery_theme, function(i,v){
					var checked = v.class_name.indexOf('-theme-default') !== -1 ? ' checked="checked"' : ''; 
					html += '<div class="gallery-style-button">';
					html += '<input type="radio" id="' + v.class_name + '" name="gallery-theme" value="' + v.class_name + '"' + checked + '/>'; // title="' + v.label +  '"
					html += '<label for="' + v.class_name + '"></label>';
					html += '</div>';
				});
				html += '</div>';

				html += '<div class="gallery-style-button-row">';
				$.each(gp_editor.gallery_size, function(i,v){
					var checked = v.class_name.indexOf('-size-md') !== -1 ? ' checked="checked"' : ''; 
					html += '<div class="gallery-style-button">';
					html += '<input type="radio" id="' + v.class_name + '" name="gallery-size" value="' + v.class_name + '"' + checked + '/>'; // title="' + v.label +  '"
					html += '<label for="' + v.class_name + '"></label>';
					html += '</div>';
				});
				html += '</div>';

				html += '<div class="gallery-style-button-row">';
				$.each(gp_editor.gallery_color, function(i,v){
					var checked = v.class_name.indexOf('-color-default') !== -1 ? ' checked="checked"' : ''; 
					html += '<div class="gallery-style-button">';
					html += '<input type="radio" id="' + v.class_name + '" name="gallery-color" value="' + v.class_name + '"' + checked + '/>'; // title="' + v.label +  '"
					html += '<label for="' + v.class_name + '"></label>';
					html += '</div>';
				});
				html += '</div>';
			}

			html += '<div id="gp_current_images"></div>'
						+ '<a class="ckeditor_control full_width ShowImageSelect" data-cmd="ShowImageSelect"> ' + gplang.SelectImage + '</a>'
						+ '<div id="gp_select_wrap">'
						+ '<div id="gp_image_area"></div>'
						+ '<div id="gp_upload_queue"></div>'
						+ '<div id="gp_folder_options"></div>'
						+ '</div>';

			$('#ckeditor_top')
				.html(html)
				.find('.gallery-style-button input[type="radio"]')
					.on('change', gp_editor.setSectionClasses );

			$('#ckeditor_wrap').addClass('multiple_images'); //indicate multiple images can be added

			$current_images = $('#gp_current_images');

			ShowCurrentImages();
			LoadImages(false,gp_editor);

			var option_area = $('<div id="gp_gallery_options">').appendTo('#ckeditor_area');

			gp_editor.getSectionClasses();

			/**
			 * Height Option
			 *
			 */
			if( gp_editor.heightChanged ){

				$('<div class="half_width">'+gplang.Height+': <input class="ck_input" type="text" name="height" /></div>')
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

				$('<div class="half_width">'+gplang.Width+': <input class="ck_input" type="text" name="width" /></div>')
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
				$('<div class="half_width">'+gplang.Auto_Start+': <input class="ck_input" type="checkbox" name="auto_start" value="true" /></div>')
					.appendTo(option_area)
					.find('input')
					.prop('checked',section_object.auto_start)
					;

			}


			/**
			 * Interval Speed
			 *
			 */
			if( gp_editor.intervalSpeed ){
				gplang.Speed = 'Speed';
				$('<div class="half_width">'+gplang.Speed+': <input class="ck_input" type="text" name="interval_speed" /></div>')
					.appendTo(option_area)
					.find('input')
					.val(section_object.interval_speed)
					.on('keyup paste change input',gp_editor.intervalSpeed)
					;
			}


			/**
			 * Image options (move, caption, delete)
			 *
			 */
			function AddLink(div,name,faclass){
				div.append('<a data-cmd="'+name+'" class="'+faclass+'"></a>');
			}
			edit_links = $('<span class="gp_gallery_edit gp_floating_area"></span>').appendTo('body').hide();



			/**
			 * Caption & delete links
			 *
			 */
			AddLink(edit_links,'gp_gallery_caption','fa fa-pencil');
			AddLink(edit_links,'gp_gallery_rm','fa fa-remove');


			/**
			 * Show/Hide Edit Links
			 *
			 */
			$(document).delegate('#gp_current_images span',{
				'mousemove.gp_edit':function(){
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


			/**
			 * Return the image currently being edited
			 *
			 */
			function GetCurrentImage(node){
				var index = $(node).closest('.expand_child').index();
				return gp_editor.edit_div.find(gp_editor.edit_links_target).eq(index);
			}


			/**
			 * Display caption popup
			 *
			 */
			$gp.links.gp_gallery_caption = function(){

				current_image	= GetCurrentImage(this);
				var $li			= $(current_image);
				var caption		= $li.find('.caption').html() || $li.find('a:first').attr('title'); //title attr for backwards compat


				var popup = '<div class="inline_box" id="gp_gallery_caption"><form><h3>'+gplang.cp+'</h3>'
							+ '<textarea name="caption" cols="50" rows="3">'+$gp.htmlchars(caption)+'</textarea>'
							+ '<p><button class="gpsubmit" data-cmd="gp_gallery_update">'+gplang.up+'</button>'
							+ '<button class="gpcancel" data-cmd="admin_box_close">'+gplang.ca+'</button></p>'
							+ '</form></div>';

				$gp.AdminBoxC(popup);
			}


			/**
			 * Remove an image from a gallery
			 * gp_editor functions called before and after removal
			 *
			 */
			$gp.links.gp_gallery_rm = function(){

				//remove the image in the gallery
				current_image	= GetCurrentImage(this);
				gp_editor.removeImage(current_image);
				$(current_image).remove();
				gp_editor.removedImage(gp_editor.edit_div);

				//remove the image in editor
				$(this).closest('.expand_child').remove();
			}

			/**
			 * Update an image's caption with data supplied by user from gplinks.gp_gallery_caption()
			 *
			 */
			$gp.inputs.gp_gallery_update = function(evt){

				evt.preventDefault();

				var text			= $(this.form).find('textarea').val();
				var caption_container		= $(current_image).find('.caption');

				//console.log(text);
				//console.log(current_image);
				//console.log(caption_container);

				caption_container.html(text);
				text = caption_container.html(); //html encoded characters

				$gp.CloseAdminBox();
				gp_editor.updateCaption(current_image,text);
			}



			/**
			 * Show/hide image selection
			 *
			 */
			$gp.links.ShowImageSelect = function(){
				$(this).toggleClass('gp_display');
				$('#gp_select_wrap').toggleClass('gp_display');

			}

		}

		/**
		 * Show Images in in #ckeditor_top
		 *
		 */
		function ShowCurrentImages(){

			sortable_area.children().each(function(){
				AddCurrentImage(this);
			});


			$current_images.sortable({
				tolerance: 'pointer',
				cursorAt: { left: 25, top: 25 },

				//reorder gallery
				stop: function(){
					$current_images.children().each(function(){
						sortable_area.append( $(this).data('original') );
					});
					gp_editor.sortStop();
				}
			}).disableSelection();
		}

		function AddCurrentImage(img){
			var $img	= $(img);
			var src		= $img.find('img').attr('src');

			if( !src ){
				return;
			}

			/* create alt attribute from file name */
			var file_name = src.substring(src.lastIndexOf('/') + 1);
			file_name = file_name.substr(0, file_name.lastIndexOf('.'));
			var img_alt = file_name.substring(0, file_name.lastIndexOf('.')).split("_").join(" ");

			var $a		= $('<img>').attr({
				'src'	: src, 
				'title'	: file_name, 
				'alt'	: img_alt 
			});
			var $span	= $('<a>').append($a);
			var html	= '<div class="expand_child">'
						+ '<span>'
						+ '<a data-cmd="gp_gallery_caption" class="fa fa-pencil"></a>'
						+ '<a data-cmd="gp_gallery_rm" class="fa fa-remove"></a>'
						+ '</span>'
						+ '</div>';

			var $new	= $(html).data('original',img).append( $span ).appendTo( $current_images );

			if( $img.hasClass('gp_to_remove') ){
				$new.addClass('gp_to_remove');
			}

		}





		/**
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


		/**
		 * Add an image to the gallery
		 *
		 */
		function AddImage( $img, holder ){

			gp_editor.edit_div.find('.gp_to_remove').remove();
			$current_images.find('.gp_to_remove').remove();

			$img.attr({
				'data-cmd' : gp_editor.img_name,
				'data-arg' : gp_editor.img_rel,
				'title'    : '',
				'class'    : gp_editor.img_rel
			});

			/* auto-create caption from file name */
			var src = $img.find('img').attr('src') || '';
			var file_name = src.substring(src.lastIndexOf('/') + 1);
			file_name = file_name.substr(0, file_name.lastIndexOf('.'));
			var auto_caption = file_name.substring(0, file_name.lastIndexOf('.')).split("_").join(" ");
			$img.append('<span class="caption">' + auto_caption + '</span>');
			var li = $('<li>').append($img);
			if( holder ){
				holder.replaceWith(li);
			}else{
				sortable_area.append(li);
			}

			li.trigger('gp_gallery_add');
			gp_editor.addedImage(li);
			AddCurrentImage(li);
		}


		/**
		 * Add file upload handlers after the form is loaded
		 *
		 */
		$gp.response.gp_gallery_images = function(data){
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


	}

