
/*
 *
 * Inline Editing of Galleries
 *
 * uses jquery ui autocomplete
 *
 */

	function gp_init_inline_edit(area_id,section_object){

		var current_content = false;
		var cache_value = '';
		var save_path = gp_editing.get_path(area_id);
		var edit_div = gp_editing.get_edit_area(area_id);
		if( edit_div == false || save_path == false ){
			return;
		}


		gp_editor = {
			save_path: save_path,

			destroy:function(){
				edit_div.html(current_content);
			},
			checkDirty:function(){
				var curr_val = gp_editor.gp_saveData();
				if( curr_val != cache_value ){
					return true;
				}
				return false;
			},

			gp_saveData:function(){
				return $('#gp_include_form').serialize();
			},
			resetDirty:function(){
				cache_value = gp_editor.gp_saveData();
			},
			updateElement:function(){
				current_content = edit_div.html();
			}
		}

		gp_editing.editor_tools();
		LoadDialog();



		function LoadDialog(){
			var edit_path = save_path+'&cmd=include_dialog';
			$gp.jGoTo(edit_path);
		}
		gpresponse.gp_include_dialog = function(data){
			$('#ckeditor_top').html(data.CONTENT);

			gp_editor.resetDirty();
			gp_editor.updateElement();
		}



		gpresponse.gp_autocomplete_include = function(data){
			var $input;
			if( data.SELECTOR == 'file' ){
				$input = $('#gp_file_include');
			}else{
				$input = $('#gp_gadget_include');
			}

			eval(data.CONTENT); //this will set the source variable

			$input
				.css({'position':'relative',zIndex: 12000 })
				.focus(function(){
					$input.autocomplete( 'search', $input.val() );
				})
				.autocomplete({

					source		: source,
					delay		: 100, /* since we're using local data */
					minLength	: 0,
					appendTo	: '#gp_admin_fixed',
					open		: function(event,ui){},
					select		: function(event,ui){

						$('#gp_include_form .autocomplete').val('');
						if( ui.item ){
							this.value = ui.item[1];
							return false;
						}
					}

			}).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
				return $( "<li></li>" )
					.data( "ui-autocomplete-item", item[1] )
					.append( '<a>' + $gp.htmlchars(item[0]) + '<span>'+$gp.htmlchars(item[1])+'</span></a>' )
					.appendTo( ul );
			};

		}



		gplinks.gp_include_preview = function(){

			$gp.loading();

			var path = gp_editor.save_path;
			path = strip_from(path,'#');

			var data = '';
			if( path.indexOf('?') > 0 ){
				data = strip_to(path,'?')+'&';
			}
			data += gp_editor.gp_saveData();
			data += '&cmd=preview';

			$gp.postC( path, data);
		}

		/*
		 * Replace the area with the new include data
		 */
		gpresponse.gp_include_content = function(obj){
			edit_div.html(obj.CONTENT);
		}


	}



