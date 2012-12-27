
/* need a more object oriented approach
 * http://www.webreference.com/js/column79/4.html
 *
 */

var gp_editing = {

	/*
	 * Get the id associated with the edit link
	 */
	id:function(a){
		return $(a).attr('id').substr(13);
	},

	/*
	 * Returns the area being edited or false
	 * First Checks for existing editor and checks for unsaved changes
	 */
	new_edit_area:function(a){
		var id = gp_editing.id(a);
		return gp_editing.get_edit_area(id);
	},

	get_path:function(id_num){
		var lnk = $('a#ExtraEditLink'+id_num);
		if( lnk.length == 0 ){
			return false;
		}
		return lnk.attr('href');
	},

	get_edit_area:function(id_num){

		var content = $('#ExtraEditArea'+id_num);
		if( content.length == 0 ){
			return false;
		}

		$('#edit_area_overlay_top').hide();

		//prevent editing other areas
		$('.ExtraEditLink').remove();
		$('.editable_area').unbind('.gp');


		//use the div with the twysiwygr class for True WYSIWYG Replacement if it's found
		var replace_content = content.find('.twysiwygr:first');
		if( replace_content.length ){
			content = replace_content;
		}

		content.addClass('gp_editing');

		return content;
	},

	/*
	 * Close the editor instance
	 * Fired when the Close button is clicked
	 */
	close_editor:function(evt){
		evt.preventDefault();

		//reload the page so javascript elements are shown again
		window.location = strip_from(window.location.href,'#');
	},

	/*
	 * Save Changes
	 * Close after the save if 'Save & Close' was clicked
	 */
	save_changes:function(evt,arg){
		evt.preventDefault();

		if( !gp_editor ) return;

		loading();

		var path = gp_editor.save_path;
		path = strip_from(path,'#');

		var query = '';
		if( path.indexOf('?') > 0 ){
			query = strip_to(path,'?')+'&';
		}
		query += 'cmd=save&';
		query += gp_editor.gp_saveData();

		//the saved function
		gpresponse.ck_saved = function(){
			if( !gp_editor ) return;

			gp_editor.updateElement();
			gp_editor.resetDirty();
			if( arg == 'ck_close' ){
				gp_editing.close_editor(evt);
			}
		}

		$gp.postC( path, query);
	},


	/*
	 * Get the Editor Tools area
	 * Initiate dragging
	 */
	editor_tools:function(){
		var editor_area = $('#ckeditor_area');

		$('#ckeditor_top').html('');
		$('#ckeditor_bottom').html('');

		SimpleDrag(editor_area.find('.toolbar'),editor_area,'fixed',function(pos){
			gpui.ckx = pos.left;
			gpui.cky = pos.top;

			if( gpui.ckd ){
				gpui.ckd = false;
				gp_editing.setdock(true);
			}
			$gp.SaveGPUI();
		});

		//this needs to happen after SimpleDrag() setup for keep_viewable settings
		gp_editing.setdock(false);
	},


	/**
	 * Dock/Undock the floating inline editor
	 *
	 */
	setdock:function(change_dock){
		var editor_wrap = $('#ckeditor_wrap').show();
		var editor_area = $('#ckeditor_area').show();
		var $body = $('body');

		if( change_dock ){
			$gp.SaveGPUI();
		}

		if( gpui.ckd ){
			editor_area.addClass('docked').removeClass('keep_viewable');
			$body.css({'margin-top':'+=30px'});

			editor_wrap.css({'height':30});

			editor_area
			.css({'top':'auto','left':0,'bottom':0})
			.bind('mouseenter.gpdock',function(){
				editor_wrap.stop(true,true,true).animate({'height':editor_area.height()},100);
			})
			.bind('mouseleave.gpdock',function(){
				editor_wrap.stop(true,true,true).delay(500).animate({'height':30});
			});

		}else{

			editor_area.removeClass('docked').addClass('keep_viewable');
			if( change_dock ){
				$('body').css({'margin-top':'-=30px'});
			}
			editor_wrap.css({'height':0});
			editor_area
			.css({'top':gpui.cky,'left':gpui.ckx,'bottom':'auto'})
			.unbind('.gpdock');

			$(window).resize();
		}

		//return editor_area;
	},

	/*
	 * Make sure certain gpEasy elements aren't copied into the html of pages
	 * @deprecated
	 */
	strip_special:function(data){
		return data;
	}

}

$gp.links.ck_close = gp_editing.close_editor;
$gp.links.ck_save = gp_editing.save_changes;

gplinks.ck_docklink = function(rel,evt){
	gpui.ckd = !gpui.ckd;
	gp_editing.setdock(true);
}
