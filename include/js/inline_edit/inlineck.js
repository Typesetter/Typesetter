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

	//get area
	var edit_div = gp_editing.get_edit_area(area_id);
	if( edit_div == false ){
		console.log('no edit div',area_id,edit_div);
		return;
	}

	var save_path = gp_editing.get_path(area_id);
	if( save_path == false ){
		console.log('no save_path',area_id,save_path);
		return;
	}

	gp_editing.editor_tools();
	edit_div.prop('contenteditable',true);
	var inner = edit_div.get(0);
	inner.innerHTML = section_object.content;


	CKEDITOR.disableAutoInline = true;
	gp_editor = CKEDITOR.inline( inner, gp_ckconfig );
	gp_editor.save_path = save_path;
	gp_editor.SaveData = function(){
		var data = gp_editor.getData();
		return 'gpcontent='+encodeURIComponent(data);
	}
	$gp.loaded();
}
