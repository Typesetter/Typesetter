
	//create gp_editor object
	gp_editor = {

		edit_img:		null,
		save_obj:		null,
		saved_data:		'',

		field_w:		null,
		field_h:		null,
		field_x:		null,
		field_y:		null,

		anim_values:	{
			posx		: 0,
			posy		: 0,
			height		: 0,
			width		: 0
			},

		timeout:		null,

		/**
		 * Return serialized data to be used with the save POST
		 *
		 */
		gp_saveData:function(){

			gp_editor.save_obj.posx		= gp_editor.field_x.value;
			gp_editor.save_obj.posy		= gp_editor.field_y.value;

			gp_editor.save_obj.width	= gp_editor.field_w.value;
			gp_editor.save_obj.height	= gp_editor.field_h.value;

			return jQuery.param( gp_editor.save_obj )+'&cmd=save_inline';
		},


		/**
		 * Check to see if there is unsaved data
		 *
		 */
		checkDirty:function(){
			var curr_data	= gp_editor.gp_saveData();
			if( gp_editor.saved_data != curr_data ){
				return true;
			}
			return false;
		},

		/**
		 * Resets the "dirty state" of the editor so subsequent calls to checkDirty will return false
		 *
		 */
		resetDirty:function(){
			gp_editor.saved_data	= gp_editor.gp_saveData();
		},

		/**
		 * Wake up this editor object
		 *
		 *
		 */
		wake: function(){
			gp_editor.timeout = window.setInterval( gp_editor.Animate ,100); //con
		},

		sleep: function(){
			window.clearInterval(gp_editor.timeout);
		},


		/**
		 * Animate dimension changes
		 *
		 */
		Animate: function(){

			console.log('animate');

			//height/width
			gp_editor.anim_values.width		= gp_editor.AnimValue( gp_editor.field_w.value, gp_editor.anim_values.width );
			gp_editor.anim_values.height	= gp_editor.AnimValue( gp_editor.field_h.value, gp_editor.anim_values.height );

			gp_editor.edit_img.stop(true,true).animate({'width':gp_editor.anim_values.width,'height':gp_editor.anim_values.height},100);


			//position
			gp_editor.anim_values.posx		= gp_editor.AnimValue( gp_editor.field_x.value, gp_editor.anim_values.posx );
			gp_editor.anim_values.posy		= gp_editor.AnimValue( gp_editor.field_y.value, gp_editor.anim_values.posy );
			gp_editor.edit_img.css({'background-position':gp_editor.anim_values.posx+'px '+gp_editor.anim_values.posy+'px'});
		},


		/**
		 * Get amount we should animate by
		 *
		 */
		AnimValue: function(desired, current){
			desired = parseInt(desired);
			current = parseInt(current);

			if( desired == current ){
				return desired;
			}

			if( desired > current ){
				return current + Math.min(20,desired-current);
			}

			return current - Math.min(20,current-desired);
		},

	};



	function gp_init_inline_edit(area_id,section_object,options){

		//show edit window
		$gp.LoadStyle('/include/css/inline_image.css');

		gp_editor.save_path		= gp_editing.get_path(area_id);
		gp_editor.edit_img		= gp_editing.get_edit_area(area_id);

		gp_editor.edit_img.addClass('gp_image_edit');

		gp_editor.save_obj	= {
			src			: gp_editor.edit_img.attr('src')
			};


		// gpEasy 4.6a2+
		// use the original image
		if( section_object.orig_src ){
			gp_editor.save_obj.src		= section_object.orig_src;
			gp_editor.save_obj.posx		= section_object.posx;
			gp_editor.save_obj.posy		= section_object.posy;
			gp_editor.save_obj.width		= section_object.attributes.width;
			gp_editor.save_obj.height 	= section_object.attributes.height;
		}



		$gp.loaded();
		gp_editing.editor_tools();

		LoadImageOptions();


		/**
		 * Load the editing options from php
		 *
		 */
		function LoadImageOptions(){
			var path = strip_from(gp_editor.save_path,'?') + '?cmd=image_editor';
			$gp.jGoTo(path);
		}


		/**
		 * Set up editing display after content has loaded from php
		 *
		 */
		gpresponse.image_options_loaded = function(){

			gp_editor.field_w			= input('width');
			gp_editor.field_h			= input('height');
			gp_editor.field_x			= input('left');
			gp_editor.field_y			= input('top');



			gp_editing.CreateTabs();
			LoadImages(false);


			//change src to blank and set as background image
			gp_editor.anim_values.width		= gp_editor.edit_img.width();
			gp_editor.anim_values.height	= gp_editor.edit_img.height();

			SetCurrentImage( gp_editor.save_obj.src, gp_editor.anim_values.width, gp_editor.anim_values.height );
			SetupDrag();

			gp_editor.edit_img.attr('src',gp_blank_img); //after getting size

			gp_editor.saved_data = gp_editor.gp_saveData();


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

			gp_editor.edit_img.disableSelection();
			gp_editor.edit_img.mousedown(function(evt){
				evt.preventDefault();
				mousedown = true;

				pos_startx = posx = parseInt(gp_editor.field_x.value || 0);
				pos_starty = posy = parseInt(gp_editor.field_y.value || 0);


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
		}

		function SetPosition(posx,posy){
			gp_editor.field_x.value = posx;
			gp_editor.field_y.value = posy;
		}


		/**
		 * Set the current image
		 *
		 */
		function SetCurrentImage( src, width, height){
			delete gp_editor.save_obj.src;
			if( src !== gp_editor.save_obj.src ){
				gp_editor.save_obj.src = src;
			}
			gp_editor.edit_img.css({'background-image':'url("'+src+'")'});
			$('#gp_current_image img').attr('src', src );

			if( width > 0 && height > 0 ){

				gp_editor.field_w.value	= width;
				gp_editor.field_h.value	= height;
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
			var img = $('<img>').css({'height':'auto','width':'auto','padding':0}).attr('src',gp_editor.save_obj.src).appendTo('body');

			gp_editor.field_w.value 		= img.width();
			gp_editor.field_h.value		= img.height();

			SetPosition(0,0);

			img.remove();
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

