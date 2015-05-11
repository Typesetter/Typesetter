

	function gp_init_inline_edit(area_id,section_object,options){

		//show edit window
		$gp.LoadStyle('/include/css/inline_image.css');

		var save_path = gp_editing.get_path(area_id);
		var edit_img = gp_editing.get_edit_area(area_id);

		// <div> or <img>?
		if( edit_img.get(0).nodeName !== 'IMG' ){
			edit_img = edit_img.find('img');
		}

		edit_img.addClass('gp_image_edit');
		var img_src = edit_img.attr('src');

		var edited = false;
		var save_obj = {};

		$gp.loaded();
		gp_editing.editor_tools();

		//$('#ckeditor_top').html('');
		$('#ckeditor_controls').prepend('<div id="gp_folder_options"></div>');




		//create gp_editor object
		gp_editor = {
			save_path: save_path,

			/**
			 * Check to see if there is unsaved data
			 *
			 */
			checkDirty:function(){
				return edited;
			},
			gp_saveData:function(){
				return jQuery.param( save_obj )+'&cmd=save_inline';
			},
			resetDirty:function(){
				edited = false;
			},
			updateElement:function(){}
		}

		LoadImageOptions();


		/**
		 * Load the editing options from php
		 *
		 */
		function LoadImageOptions(){
			var path = strip_from(save_path,'?') + '?cmd=image_editor';
			$gp.jGoTo(path);
		}


		/**
		 * Set up editing display after content has loaded from php
		 *
		 */
		var change_timeout = false;
		gpresponse.image_options_loaded = function(){

			//change src to blank and set as background image
			var width = edit_img.width();
			var height = edit_img.height()
			value('orig_width', width );
			value('orig_height', height );
			SetCurrentImage( img_src, width, height );
			SetupDrag();

			edit_img.attr('src',gp_blank_img); //after getting size

			//set up height/width listeners
			$('#gp_current_image input').on('keyup keydown change paste',function(){
				if( change_timeout ) clearTimeout(change_timeout);
				change_timeout = setTimeout(function(){
					edited = true;

					//width - height
					save_obj.width = value('width');
					save_obj.height = value('height');
					edit_img.stop(true,true).animate({'width':save_obj.width,'height':save_obj.height});

					//left - top
					var left = value('left');
					var top = value('top');
					SetPosition(left,top);

				},400);
			});
		}

		function value(name,value){
			var field = input(name);
			if( typeof(value) !== 'undefined' ){
				field.val( value );
			}
			return parseInt( field.val() );
		}

		function input(name){
			return $('#gp_current_image input[name='+name+']');
		}



		/**
		 * Initialize image dragging
		 *
		 */
		function SetupDrag(){

			var posx = posy = mouse_startx = mouse_starty = pos_startx = pos_starty = 0;
			var mousedown = false;

			edit_img.disableSelection();
			edit_img.mousedown(function(evt){
				evt.preventDefault();
				mousedown = true;

				pos_startx = posx = save_obj.posx || 0;
				pos_starty = posy = save_obj.posy || 0;

				mouse_startx = evt.pageX;
				mouse_starty = evt.pageY;

			}).on('mouseleave mouseup',function(evt){
				evt.preventDefault();
				mousedown = false;
			}).mousemove(function(evt){
				if( mousedown ){
					posx = pos_startx + evt.pageX - mouse_startx;
					posy = pos_starty + evt.pageY - mouse_starty;
					SetPosition(posx,posy);
				}
			});

		}



		/**
		 * Use an image
		 *
		 */
		$gp.links.gp_gallery_add = function(evt){
			evt.preventDefault();
			var $this = $(this).stop(true,true);

			var width = $this.data('width');
			var height = $this.data('height');

			SetCurrentImage( $this.attr('href'), width, height );
			SetPosition(0,0);

			//make sure this information is saved
			save_obj.width = value('width');
			save_obj.height = value('height');
			edited = true;
		}

		function SetPosition(posx,posy){
			save_obj.posx = posx = value('left', posx );
			save_obj.posy = posy = value('top',  posy );
			edit_img.css({'background-position':posx+'px '+posy+'px'});
			edited = true;
		}

		/**
		 * Set the current image
		 *
		 */
		function SetCurrentImage( src, width, height){
			delete save_obj.src;
			if( src !== img_src ){
				save_obj.src = src;
			}
			edit_img.css({'background-image':'url("'+src+'")'});
			$('#gp_current_image img').attr('src', src );

			if( width > 0 && height > 0 ){
				value('width', width );
				value('height', height );
				edit_img.stop(true,true).animate({'width':width,'height':height});
			}
		}


		/**
		 * Show Images
		 *
		 */
		$gp.links.show_uploaded_images = function(){
			LoadImages(false);
		}

		/*
		function setVisibleThemeImages(){
			var minWidth = value('orig_width') - (value('orig_width') * 0.20);
			var maxWidth = (value('orig_width') * 1.20);
			var minHeight = value('orig_height') - (value('orig_height') * 0.20);
			var maxHeight = (value('orig_height') * 1.20);
			$('#gp_gallery_avail_imgs a').each(function(ind){
			   var width =  $(this).attr('data-width');
			   var height =  $(this).attr('data-height');
			   if (!((width <= maxWidth) && (width >= minWidth) &&
			      (height <= maxHeight) && (height >= minHeight))) {
				  $(this).parent().hide();
			   }
			});
		}
		*/

		$gp.links.deafult_sizes = function(){
			value('width', value('orig_width') );
			value('height', value('orig_height') );
			input('width').change();

			SetPosition(0,0);
		}


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
						$(message).appendTo(avail);
						//var img_link = img.find('a[name=gp_gallery_add]');
						//AddImage(img_link.clone(),settings['holder']);

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

	}

