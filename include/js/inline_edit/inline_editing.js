
(function(){

gp_editing = {

	is_dirty:		false,	// true if we know gp_editor has been edited


	get_path:function(id_num){
		var lnk = $('a#ExtraEditLink'+id_num);
		if( lnk.length == 0 ){
			console.log('get_path() link not found',id_num,lnk.length);
			return false;
		}
		return lnk.attr('href');
	},

	get_edit_area:function(id_num){

		var content = $('#ExtraEditArea'+id_num);
		if( content.length == 0 ){
			console.log('no content found for get_edit_area()',id_num);
			return false;
		}

		$('#edit_area_overlay_top').hide();

		//use the div with the twysiwygr class for True WYSIWYG Replacement if it's found
		var replace_content = content.find('.twysiwygr:first');
		if( replace_content.length ){
			content = replace_content;
		}

		content.addClass('gp_editing gp_edit_current');

		return content;
	},


	/**
	 * Close the editor instance
	 * Fired when the Close button is clicked
	 *
	 */
	close_editor:function(evt){
		evt.preventDefault();

		//reload the page so javascript elements are shown again
		$gp.Reload();
	},


	/**
	 * Save Changes
	 * Close after the save if 'Save & Close' was clicked
	 *
	 */
	save_changes:function(callback){

		if( !gp_editor ) return;

		/*if( !gp_editing.IsDirty() ){
			if( typeof(callback) == 'function' ){
				callback.call();
			}
			return;
		}
		*/

		var $wrap = $('#ckeditor_wrap');

		if( $wrap.hasClass('ck_saving') ){
			return;
		}

		$wrap.addClass('ck_saving');


		var edit_div	= $gp.CurrentDiv();
		var path		= strip_from(gp_editor.save_path,'#');
		var query		= '';
		var save_data	= gp_editor.gp_saveData();

		if( path.indexOf('?') > 0 ){
			query = strip_to(path,'?')+'&';
			path = strip_from(path,'?');
		}

		query			+= 'cmd=save_inline&section='+edit_div.data('gp-section')+'&req_time='+req_time+'&';
		query			+= save_data;
		query			+= '&verified='+encodeURIComponent(post_nonce);
		query			+= '&gpreq=json&jsoncallback=?';


		// saving to same page as the current url
		if( gp_editing.SamePath(path) ){
			query += '&gpreq_toolbar=1';
		}


		//the saved function
		gpresponse.ck_saved = function(){

			if( !gp_editor ) return;

			//if nothing has changed since saving
			if( gp_editor.gp_saveData() == save_data ){
				gp_editor.resetDirty();
				gp_editing.is_dirty = false;
				gp_editing.DisplayDirty();
			}

			if( typeof(callback) == 'function' ){
				callback.call();
			}
		}


		$.ajax({
			type: 'POST',
			url: path,
			data: query,
			success: $gp.Response,
			dataType: 'json',
			complete: function( jqXHR, textStatus ){
				$wrap.removeClass('ck_saving');
			},
		});

	},


	/**
	 * Return true if the request path is the same as the path for the current url
	 *
	 */
	SamePath: function(path){
		var a = $('<a>').attr('href',path).get(0);

		if( a.pathname.replace(/^\/index.php/,'') == window.location.pathname.replace(/^\/index.php/,'') ){
			return true;
		}
		return false;
	},


	/**
	 * Get the Editor Tools area
	 * Initiate dragging
	 *
	 */
	editor_tools:function(){
		$('#ckeditor_area .toolbar').html('');
		$('#ckeditor_top').html('');
		$('#ckeditor_bottom').html('');
		$('#ckeditor_wrap').addClass('show_editor');
		AdjustForEditor();
		gp_editing.ResetTabs();
	},


	/**
	 * Set up tabs
	 *
	 */
	CreateTabs: function(){

		var $areas = $('.inline_edit_area');
		if( !$areas.length ){
			return;
		}

		var c = 'selected'
		var h = '<div id="cktabs" class="cktabs">';
		$areas.each(function(){
			h += '<a class="ckeditor_control '+c+'" data-cmd="SwitchEditArea" data-arg="#'+this.id+'">'+this.title+'</a>';
			c = '';
		});
		h += '</div>';

		$('#ckeditor_area .toolbar').append(h).find('a').mousedown(function(e) {
			e.stopPropagation(); //prevent dragging
		});

	},


	/**
	 * Add Tab
	 *
	 */
	AddTab: function(html, id){

		var $area = $('#'+id);
		if( !$area.length ){
			$area = $(html).appendTo('#ckeditor_top');

			$('<a class="ckeditor_control" data-cmd="SwitchEditArea" data-arg="#'+id+'">'+$area.attr('title')+'</a>')
				.appendTo('#cktabs')
				.click();
		}else{
			$area.replaceWith(html);
			$('#cktabs .ckeditor_control[data-arg="#'+id+'"]').click();

		}
	},


	/**
	 * Indicate which tab is selected
	 *
	 */
	ResetTabs: function(){
		$('.cktabs .selected').removeClass('selected');
		$('.cktabs a').each(function(){
			var $this = $(this);
			var $area = $( $this.data('arg') );
			if( $area.is(':visible') ){
				$this.addClass('selected');
			}
		});
	},


	/**
	 * Restore Cached
	 *
	 */
	RestoreCached: function(id){

		if( typeof($gp.interface[id]) != 'object' ){
			return false;
		}

		if( $gp.curr_edit_id === id ){
			return true;
		}

		$('#ck_area_wrap').html('').append($gp.interface[id]);

		gp_editor			= $gp.editors[id];
		$gp.curr_edit_id	= id;

		$gp.RestoreObjects( 'links', id);
		$gp.RestoreObjects( 'inputs', id);
		$gp.RestoreObjects( 'response', id);


		if( id != 0 ){
			$('#ckeditor_wrap').addClass('show_editor');
		}
		AdjustForEditor();

		if( typeof(gp_editor.wake) == 'function' ){
			gp_editor.wake();
		}

		$gp.CurrentDiv().addClass('gp_edit_current');

		return true;
	},


	/**
	 * Return true if the editor has been edited
	 *
	 */
	IsDirty: function(){

		gp_editing.is_dirty = true;

		if( typeof(gp_editor.checkDirty) == 'undefined' ){
			return true;
		}

		if( gp_editor.checkDirty() ){
			return true;
		}

		gp_editing.is_dirty = false;
		return false;
	},


	/**
	 * Hide the "Saved" indicator if the
	 *
	 */
	DisplayDirty: function(){

		if( gp_editing.is_dirty || gp_editing.IsDirty() ){
			$('#ckeditor_wrap').addClass('not_saved');
		}else{
			$('#ckeditor_wrap').removeClass('not_saved');
		}
	}

}

	/**
	 * Close button
	 *
	 */
	$gp.links.ck_close = gp_editing.close_editor;

	/**
	 * Save button clicks
	 *
	 */
	$gp.links.ck_save = function(evt,arg){
		evt.preventDefault();

		gp_editing.save_changes(function(){
			if( arg && arg == 'ck_close' ){
				gp_editing.close_editor(evt);
			}
		});
	}


	/**
	 * Control which editing area is displayed
	 *
	 */
	$gp.links.SwitchEditArea = function(evt,arg){

		if( this.href ){
			$gp.links.inline_edit_generic.call(this,evt,'manage_sections');
		}

		var $this = $(this);

		$('.inline_edit_area').hide();
		$( $this.data('arg') ).show();

		gp_editing.ResetTabs();
	}


	/**
	 * Warn before closing a page if an inline edit area has been changed
	 *
	 */
	$(window).on('beforeunload',function(){
		if( !gp_editor ){
			return;
		}
		if( typeof(gp_editor.checkDirty) === 'undefined' ){
			return;
		}

		if( gp_editor.checkDirty() ){
			return 'Unsaved changes will be lost.';
		}
		return;
	});


	/**
	 * Switch between edit areas
	 *
	 */
	$gp.$doc.on('mousedown','.editable_area:not(.filetype-wrapper_section)',function(evt){

		//get the edit link
		var area_id		= $gp.AreaId( $(this) );
		var href		= $('#ExtraEditLink'+area_id).attr('href') || '?';

		$gp.LoadEditor(href, area_id);
		gp_editing.ResetTabs();
	});


	/**
	 * Switch back to section manager
	 *
	 */
	$gp.$doc.on('click',function(evt){

		if( $(evt.target).closest('.editable_area, #ckeditor_wrap, a, input').length ){
			return;
		}

		$gp.LoadEditor('?cmd=ManageSections', 0, 'manage_sections');
		gp_editing.ResetTabs();
	});


	// auto save
	window.setInterval(gp_editing.save_changes,5000);


	// check dirty
	$gp.$doc.on('keyup mouseup',function(){
		window.setTimeout(gp_editing.DisplayDirty,100);
	});


	/**
	 * Show/Hide the editor
	 *
	 */
	$gp.links.ToggleEditor = function(){
		if( $('#ckeditor_wrap').hasClass('show_editor') ){
			$('html').css({'margin-left':0});
			$('#ckeditor_wrap').removeClass('show_editor');
		}else{
			$('#ckeditor_wrap').addClass('show_editor');
			AdjustForEditor();
		}
	};


	/**
	 * Move the page to the left to keep the editor off of the editable area if needed
	 *
	 */
	function AdjustForEditor(){


		$('html').css({'margin-left':0,'width':'auto'});

		var win_width	= $gp.$win.width();
		var $edit_div	= $gp.CurrentDiv();
		if( !$edit_div.length ){
			return;
		}

		//get max adjustment
		var left 		= $edit_div.offset().left;
		var max_adjust	= left - 10;
		if( max_adjust < 0 ){
			return;
		}


		//get min adjustment (how much the edit div will overlap the editor)
		var max_right	= win_width - $('#ckeditor_wrap').outerWidth(true);
		var min_adjust	= (left + $edit_div.outerWidth()) - max_right;
		min_adjust		+= 10;

		if( min_adjust < 0 ){
			return;
		}

		var adjust		= Math.min(min_adjust, max_adjust);

		$('html').css({'margin-left':-adjust,'width':win_width});
	}


	/**
	 * Max height of #ckeditor_area
	 *
	 */
	$gp.$win.resize(function(){
		var $ckeditor_area		= $('#ckeditor_area');
		var maxHeight			= $gp.$win.height();
		maxHeight				-= $ckeditor_area.position().top;
		maxHeight				-= $('#ckeditor_save').outerHeight();

		$('#ckeditor_area').css({'max-height':maxHeight});

		AdjustForEditor();

	}).resize();




})();
