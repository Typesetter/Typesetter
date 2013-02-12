/**
 * Inline editing with CKEditor 4
 *
 */

function gp_init_inline_edit(area_id,section_object){

	// add external plugins
	if( typeof(gp_add_plugins) == 'object' ){
		$.each(gp_add_plugins,function(name,path){
			CKEDITOR.plugins.addExternal(name,path);
		});
	}


	//ckeditor configuration
	var config = $.extend({}, gp_ckconfig);
	if( typeof(config.extraPlugins) == 'undefined' ){
		config.extraPlugins = 'sharedspace';
	}else{
		config.extraPlugins += ',sharedspace';
	}
	config.sharedSpaces = {
			top:'ckeditor_top'
			,bottom:'ckeditor_bottom'
		};



	//get area
	var save_path = gp_editing.get_path(area_id);
	var edit_div = gp_editing.get_edit_area(area_id);
	if( edit_div == false || save_path == false ){
		return;
	}
	gp_editing.editor_tools();
	edit_div.prop('contenteditable',true);
	var inner = edit_div.get(0);
	inner.innerHTML = section_object.content;


	CKEDITOR.disableAutoInline = true;
	gp_editor = CKEDITOR.inline( inner, config );
	gp_editor.save_path = save_path;
	gp_editor.gp_saveData = function(){
		var data = gp_editor.getData();
		return 'gpcontent='+encodeURIComponent(data);
	}
	$gp.loaded();


	//replace resized image paths
	/*
	if( section_object.resized_imgs ){
		$.each(section_object.resized_imgs,function(resized_path,original_path){
			edit_div.find('img').each(function(){
				alert(this.src +"\n vs \n"+resized_path);
				if( this.src == resized_path ){
					this.src = original_path;
					alert('found');
				}
			});
		});
	}
	*/
}
