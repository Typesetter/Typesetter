

if( typeof(gp_Image_Common) == 'undefined' ){

	var gp_Image_Common = new function(){

		/**
		 * Folder drop down
		 *
		 */
		$('body').on('click', function(evt){

			var $area = false;
			if( evt.target ){
				$area = $(evt.target).closest('.gp_edit_select');
			}
			if( !$area.length ){
				CloseFolder();
				return;
			}

			//show
			var $options = $area.find('.gp_edit_select_options');
			if( !$options.is(':visible') ){
				$options.slideDown(500,function(){
					$('#gp_image_area').addClass('gp_active');
				});
				return;
			}

			//close if the target was in the selector
			var $control = $(evt.target).closest('.gp_selected_folder');
			if( $control.length ){
				CloseFolder();
			}
		});

		function CloseFolder(){
			if( !$('.gp_active').length ){
				return;
			}
			$('#gp_gallery_avail_imgs').animate({'height':220},500);
			$('.gp_edit_select_options').slideUp(500,function(){
				$('#gp_image_area').removeClass('gp_active');
			});

		}


		$gp.links.gp_gallery_folder = function(evt,arg){
			evt.preventDefault();
			LoadImages(arg);
		}


		/**
		 * Remove an image from the list of available images
		 *
		 */
		$gp.response.img_deleted_id = function(obj){
			$('#'+obj.CONTENT).remove();
		}


		/**
		 * Add folder to images
		 *
		 */
		$gp.inputs.gp_gallery_folder_add = function(rel,evt){
			evt.preventDefault();
			var frm = this.form;
			var dir = frm.dir.value;
			var newdir = dir+'/'+frm.newdir.value
			LoadImages(newdir,gp_editor);
		}

		this.LoadImages = function(directory){
			var edit_path = strip_from(gp_editor.save_path,'?');
			if( directory ){
				edit_path += '?cmd=gallery_folder&dir='+encodeURIComponent(directory);
			}else{
				edit_path += '?cmd=gallery_images';
			}
			$gp.jGoTo(edit_path);
		}

	};

	function LoadImages(directory){
		gp_Image_Common.LoadImages(directory);
	}
}
