

	function gp_init_inline_edit(area_id,section_object,options){

		//show edit window
		$gp.LoadStyle('/include/css/inline_image.css');

		var save_path	= gp_editing.get_path(area_id);
		var edit_img	= gp_editing.get_edit_area(area_id);

		edit_img.addClass('gp_image_edit');

		var edited		= false;
		var save_obj	= {
			src			: edit_img.attr('src'),
			posx		: 0,
			posy		: 0
			};

		var anim_values = {
			posx		: 0,
			posy		: 0,
			height		: 0,
			width		: 0,
			};


		var field_w, field_h, field_x, field_y;


		$gp.loaded();
		gp_editing.editor_tools();

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
		gpresponse.image_options_loaded = function(){

			field_w			= input('width');
			field_h			= input('height');
			field_x			= input('left');
			field_y			= input('top');



			gp_editing.CreateTabs();
			LoadImages(false);


			//change src to blank and set as background image
			anim_values.width	= edit_img.width();
			anim_values.height	= edit_img.height();

			SetCurrentImage( save_obj.src, anim_values.width, anim_values.height );
			SetupDrag();

			edit_img.attr('src',gp_blank_img); //after getting size

			//up/down arrows
			$('#gp_current_image input').on('keydown',function(evt){
				switch(evt.which){
					case 38: //up
						this.value = parseInt(this.value) + 1;
					break;

					case 40: // down
						this.value = parseInt(this.value) - 1;
					break;
				}
			});


			//set up height/width listeners
			$('#gp_current_image input').on('keyup keydown change paste',function(evt){
				edited				= true;

				//width - height
				save_obj.width		= field_w.value;
				save_obj.height		= field_h.value;

				//left - top
				var left			= field_x.value;
				var top				= field_y.value;
				SetPosition(left,top);
			});

		}

		/**
		 * Continuous animation
		 *
		 */
		window.setInterval(function(){

			//height/width
			var animw			= AnimValue( save_obj.width, anim_values.width );
			var animh			= AnimValue( save_obj.height, anim_values.height );
			anim_values.width	= animw;
			anim_values.height	= animh;

			edit_img.stop(true,true).animate({'width':animw,'height':animh},100);


			//position
			var animx			= AnimValue( save_obj.posx, anim_values.posx );
			var animy			= AnimValue( save_obj.posy, anim_values.posy );
			anim_values.posx	= animx;
			anim_values.posy	= animy;

			edit_img.css({'background-position':animx+'px '+animy+'px'});

		},100);


		/**
		 * Get amount we should animate by
		 *
		 */
		function AnimValue(desired, current){
			desired = parseInt(desired);
			current = parseInt(current);

			if( desired == current ){
				return desired;
			}

			if( desired > current ){
				return current + Math.min(10,desired-current);
			}

			return current - Math.min(10,current-desired);
		}


		function input(name){
			return $('#gp_current_image input[name='+name+']').get(0);
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

			var width			= $this.data('width');
			var height			= $this.data('height');

			SetCurrentImage( $this.attr('href'), width, height );
			SetPosition(0,0);

			//make sure this information is saved
			save_obj.width		= field_w.value;
			save_obj.height		= field_h.value;
			edited = true;
		}

		function SetPosition(posx,posy){

			field_x.value = posx;
			field_y.value = posy;

			save_obj.posx = posx;
			save_obj.posy = posy;

			//edit_img.css({'background-position':posx+'px '+posy+'px'});
			edited = true;
		}

		/**
		 * Set the current image
		 *
		 */
		function SetCurrentImage( src, width, height){
			delete save_obj.src;
			if( src !== save_obj.src ){
				save_obj.src = src;
			}
			edit_img.css({'background-image':'url("'+src+'")'});
			$('#gp_current_image img').attr('src', src );

			if( width > 0 && height > 0 ){

				field_w.value	= width;
				field_h.value	= height;

				save_obj.width	= width;
				save_obj.height = height;
			}
		}


		/**
		 * Show Images
		 *
		 */
		$gp.links.show_uploaded_images = function(){
			LoadImages(false);
		}

		$gp.links.deafult_sizes = function(){

			//get original image size
			var img = $('<img>').css({'height':'auto','width':'auto','padding':0}).attr('src',save_obj.src).appendTo('body');

			field_w.value 		= img.width();
			field_h.value		= img.height();
			save_obj.width		= field_w.value;
			save_obj.height 	= field_h.value;

			img.remove();

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

