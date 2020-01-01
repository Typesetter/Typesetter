

	function ImageEditor(area_id, section_object){

		var edit_img		= null;
		var $edit_img		= null;
		var saved_data		= '';

		var field_w			= null;
		var field_h			= null;
		var field_x			= null;
		var field_y			= null;
		var field_a			= null;

		var anim_values	= {
			posx		: 0,
			posy		: 0,
			height		: 0,
			width		: 0
		};

		var anim_freq		= 100;

		var timeout			= null;


		/**
		 * Construct
		 *
		 */
		this.save_path		= gp_editing.get_path(area_id);
		$edit_img			= gp_editing.get_edit_area(area_id);
		edit_img			= $edit_img.get(0);


		$edit_img.addClass('gp_image_edit');

		var save_obj	= {
			src			: $edit_img.attr('src'),
			alt			: $edit_img.attr('alt'),
			posx		: 0,
			posy		: 0,
			width		: 0,
			height		: 0
			};


		// gpEasy 4.6a2+
		// use the original image
		if( section_object.orig_src ){
			save_obj.src		= section_object.orig_src;
			save_obj.alt		= section_object.attributes.alt;
			save_obj.posx		= section_object.posx;
			save_obj.posy		= section_object.posy;
			save_obj.width		= section_object.attributes.width;
			save_obj.height 	= section_object.attributes.height;
		}



		/**
		 * Load the editing options from php
		 *
		 */
		var path = strip_from(this.save_path,'?') + '?cmd=image_editor';
		$gp.jGoTo(path);


		/**
		 * Return serialized data to be used with the save POST
		 *
		 */
		function SaveData(){

			save_obj.posx		= field_x.value;
			save_obj.posy		= field_y.value;

			save_obj.width		= field_w.value;
			save_obj.height		= field_h.value;

			save_obj.alt		= field_a.value;

			return jQuery.param( save_obj )+'&cmd=save_inline';
		}

		this.SaveData = SaveData;

		/**
		 * Check to see if there is unsaved data
		 *
		 */
		this.checkDirty = function(){
			if( saved_data != SaveData() ){
				return true;
			}
			return false;
		}

		/**
		 * Resets the "dirty state" of the editor so subsequent calls to checkDirty will return false
		 *
		 */
		this.resetDirty = function(){
			saved_data	= SaveData();
		}

		/**
		 * Wake up this editor object
		 *
		 */
		this.wake = function(){
			timeout = window.setInterval(Animate, anim_freq); //constant animation

			$gp.response.image_options_loaded		= ImagesLoaded;
			$gp.response.gp_gallery_images			= MultipleFileHandler;

			$gp.links.show_uploaded_images = function(){
				LoadImages(false);
			}

			$gp.links.gp_gallery_add	= UseImage;
			$gp.links.deafult_sizes		= ShowImages;

			$gp.$win.trigger('resize');
		}

		this.sleep = function(){
			window.clearInterval(timeout);
		}


		/**
		 * Animate dimension changes
		 *
		 */
		function Animate(){

			var cssText				= 'background-image:url("'+$gp.htmlchars(save_obj.src)+'");';


			//height/width
			anim_values.width		= AnimValue( field_w.value, anim_values.width );
			anim_values.height		= AnimValue( field_h.value, anim_values.height );

			cssText					+= 'width: '+anim_values.width+'px !important; height:'+anim_values.height+'px !important;';


			//position
			anim_values.posx		= AnimValue( field_x.value, anim_values.posx );
			anim_values.posy		= AnimValue( field_y.value, anim_values.posy );
			cssText					+= 'background-position: '+anim_values.posx+'px '+anim_values.posy+'px';


			edit_img.style.cssText = cssText;
		}


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
				return current + Math.min(20,desired-current);
			}

			return current - Math.min(20,current-desired);
		}


		/**
		 * Set up editing display after content has loaded from php
		 *
		 */
		function ImagesLoaded(){

			field_w			= input('width');
			field_h			= input('height');
			field_x			= input('left');
			field_y			= input('top');
			field_a			= input('alt');


			field_x.value		= save_obj.posx;
			field_y.value		= save_obj.posy;
			field_w.value		= save_obj.width;
			field_h.value		= save_obj.height;
			field_a.value		= save_obj.alt;



			gp_editing.CreateTabs();
			LoadImages(false);


			//change src to blank and set as background image
			anim_values.width			= $edit_img.width();
			anim_values.height			= $edit_img.height();

			SetCurrentImage( save_obj.src, save_obj.alt, anim_values.width, anim_values.height );
			SetupDrag();

			$edit_img.attr('src',gp_blank_img); //after getting size

			$edit_img.attr('alt',gp_blank_img.split('/').pop());

			saved_data					= SaveData();


			//up/down arrows
			$('#gp_current_image input').on('keydown',function(evt){
				switch(evt.which){
					case 38: //up
						this.value	= parseInt(this.value) + 1;
					break;

					case 40: // down
						this.value	= parseInt(this.value) - 1;
					break;
				}
			});
		}

		/**
		 * Get one of the input elements
		 *
		 */
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

			$edit_img.disableSelection();
			$edit_img.mousedown(function(evt){
				evt.preventDefault();
				mousedown = true;

				pos_startx = posx = parseInt(field_x.value || 0);
				pos_starty = posy = parseInt(field_y.value || 0);


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
		 * Set the current image
		 *
		 */
		function SetCurrentImage(src, alt, width, height){
			delete save_obj.src;

			save_obj.src = src;

			save_obj.alt = alt;

			$edit_img.css({'background-image':'url("'+$gp.htmlchars(save_obj.src)+'")'});
			$('#gp_current_image img').attr({
				'src' : save_obj.src,
				'alt' : save_obj.alt 
			});

			if( width > 0 && height > 0 ){
				field_w.value	= width;
				field_h.value	= height;
				field_a.value	= alt;
			}
		}


		/**
		 * Set the x & y position of the image
		 *
		 */
		function SetPosition(posx,posy){
			field_x.value	= posx;
			field_y.value	= posy;
		}

		/**
		 * Add file upload handlers after the form is loaded
		 *
		 */
		function MultipleFileHandler(){
			var form = $('#gp_upload_form');
			var action = form.attr('action');
			var progress_bars = {};

			form.find('.file').auto_upload({

				start: function(name, settings){
					settings['bar'] = $('<a data-cmd="gp_file_uploading">'+name+'</a>')
						.appendTo('#gp_upload_queue');
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


		/**
		 * Use an image
		 *
		 */
		function UseImage(evt){
			evt.preventDefault();
			var $this = $(this).stop(true,true);

			var width			= $this.data('width');
			var height			= $this.data('height');
			var alt				= $this.attr('href').split('/').pop().split('_').join(' ');
				alt				= alt.substring(0, alt.lastIndexOf('.'));

			SetCurrentImage( $this.attr('href'), alt, width, height );

			SetPosition(0,0);
		}

		/**
		 * Show Images
		 *
		 */
		function ShowImages(){

			//get original image size
			var img = $('<img>').css({'height':'auto','width':'auto','padding':0})
				.attr({
					'src' : save_obj.src, 
					'alt' : save_obj.alt
				})
				.appendTo('body');

			field_w.value 		= img.width();
			field_h.value		= img.height();
			field_a.value 		= img.attr('alt');

			SetPosition(0,0);

			img.remove();
		}

	}



	function gp_init_inline_edit(area_id,section_object,options){

		//show edit window
		$gp.LoadStyle('/include/css/inline_image.css');


		$gp.loaded();
		gp_editing.editor_tools();

		//create gp_editor object
		gp_editor = new ImageEditor(area_id, section_object);
	}

