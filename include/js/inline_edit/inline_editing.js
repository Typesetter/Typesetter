
(function(){

gp_editing = {

	is_extra_mode:	false,

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
	SaveChanges:function(callback){

		if( !gp_editor ) return;

		if( !gp_editing.IsDirty() ){
			if( typeof(callback) == 'function' ){
				callback.call();
			}
			return;
		}


		var $wrap = $('#ckeditor_wrap');

		if( $wrap.hasClass('ck_saving') ){
			return;
		}

		$wrap.addClass('ck_saving');


		var $edit_div	= $gp.CurrentDiv();
		var path		= strip_from(gp_editor.save_path,'#');
		var query		= '';
		var save_data	= gp_editing.GetSaveData();

		if( path.indexOf('?') > 0 ){
			query = strip_to(path,'?')+'&';
			path = strip_from(path,'?');
		}

		query			+= 'cmd=save_inline&section='+$edit_div.data('gp-section')+'&req_time='+req_time+'&';
		query			+= save_data;
		query			+= '&verified='+encodeURIComponent(post_nonce);
		query			+= '&gpreq=json&jsoncallback=?';


		// saving to same page as the current url
		if( gp_editing.SamePath(path) ){
			query += '&gpreq_toolbar=1';
		}


		//the saved function
		$gp.response.ck_saved = function(){

			//mark as draft
			gp_editing.DraftStatus( $edit_div, 1);
			gp_editing.PublishButton( $edit_div );


			if( !gp_editor ) return;


			//if nothing has changed since saving
			if( gp_editing.GetSaveData() == save_data ){
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
	 * Get the data to be saved from the gp_editor
	 * @since 5.0
	 *
	 */
	GetSaveData: function(){

		if( typeof(gp_editor.SaveData) == 'function' ){
			return gp_editor.SaveData();
		}

		return gp_editor.gp_saveData();
	},


	/**
	 * Display the publish button if the edit extra area is a draft
	 *
	 */
	PublishButton: function($area){

		$('.ck_publish').hide();

		if( !$area || $area.data('draft') == undefined ){
			return;
		}

		if( $area.data('draft') == 1 ){
			$('.ck_publish').show();
		}

		$gp.IndicateDraft();
	},


	/**
	 * Set the draft status for an edit area
	 *
	 */
	DraftStatus: function($area, status){

		if( !$area || $area.data('draft') == undefined ){
			return;
		}

		$area.data('draft',status).attr('data-draft',status);
		$gp.IndicateDraft();
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

		var $ck_area_wrap = $('#ck_area_wrap');

		//inline editor html
		if( !$ck_area_wrap.length ){
			var html = '<div id="ckeditor_wrap" class="nodisplay">';

			html += '<a id="cktoggle" data-cmd="ToggleEditor"><i class="fa fa-angle-double-left"></i><i class="fa fa-angle-double-right"></i></a>';

			//tabs
			html += '<div id="ckeditor_tabs">';
			html += '</div>';

			html += '<div id="ck_area_wrap">';
			html += '</div>';

			html += '<div id="ckeditor_save">';
			html += '<a data-cmd="ck_save" class="ckeditor_control ck_save">'+gplang.Save+'</a>';
			html += '<span class="ck_saved">'+gplang.Saved+'</span>';
			html += '<a data-cmd="Publish" class="ckeditor_control ck_publish">'+gplang.Publish+'</>';
			html += '<span class="ck_saving">'+gplang.Saving+'</span>';
			html += '<a data-cmd="ck_close" class="ckeditor_control">'+gplang.Close+'</a>';
			html += '</div>';

			html += '</div>';

			$('#gp_admin_html').append(html);

			$ck_area_wrap = $('#ck_area_wrap');
		}


		//ck_area_wrap
		var html = '<div id="ckeditor_area">';
		html += '<div class="toolbar"></div>';
		html += '<div class="tools">';
		html += '<div id="ckeditor_top"></div>';
		html += '<div id="ckeditor_controls"></div>';
		html += '<div id="ckeditor_bottom"></div>';
		html += '</div>';
		html += '</div>';
		$ck_area_wrap.html(html);


		gp_editing.ShowEditor();
	},


	/**
	 * Which edit mode? Page or Extra Content
	 *
	 */
	IsExtraMode: function(){

		var $edit_area = $gp.CurrentDiv();

		if( !$edit_area.length ){
			return gp_editing.is_extra_mode;
		}


		if( typeof($edit_area.data('gp-section')) == 'undefined' ){
			gp_editing.is_extra_mode = true;
			return true;
		}

		gp_editing.is_extra_mode = false;
		return false;
	},


	/**
	 * Display the editing window and update the editor heading
	 *
	 */
	ShowEditor: function(){

		var $edit_area		= $gp.CurrentDiv();
		var $ckeditor_wrap	= $('#ckeditor_wrap').addClass('show_editor');
		$gp.$win.resize();



		//tabs
		var $tabs			= $('#ckeditor_tabs').html('');
		var extra_mode		= gp_editing.IsExtraMode();

		if( extra_mode ){
			$ckeditor_wrap.addClass('edit_mode_extra');
			$tabs.append('<a href="?cmd=ManageSections" data-cmd="inline_edit_generic" data-arg="manage_sections">'+gplang.Extra+'</a>');
		}else{
			$ckeditor_wrap.removeClass('edit_mode_extra');
			$tabs.append('<a href="?cmd=ManageSections" data-cmd="inline_edit_generic" data-arg="manage_sections">'+gplang.Page+'</a>');
		}

		if( $edit_area.length != 0 ){
			var label		= gp_editing.SectionLabel($edit_area);
			$('<a>').text(label).appendTo( $tabs );
		}


		// Hide save buttons for extra content list
		if( $edit_area.length == 0 && extra_mode ){
			$('#ckeditor_save').hide();
		}else{
			$('#ckeditor_save').show();
		}



		gp_editing.PublishButton( $edit_area );

	},


	/**
	 * Get a section label
	 *
	 */
	SectionLabel: function($section){

		var label	= $section.data('gp_label');
		if( !label ){
			var type	= gp_editing.TypeFromClass($section);
			label	= gp_editing.ucfirst(type);
		}

		return label;
	},


	/**
	 * Get the content type from the class name
	 * todo: use regexp to find filetype-.*
	 */
	TypeFromClass: function(div){
		var type = $(div).prop('class').substring(16);
		return type.substring(0, type.indexOf(' '));
	},


	/**
	 * Capitalize the first letter of a string
	 *
	 */
	ucfirst: function( str ){
		return str.charAt(0).toUpperCase() + str.slice(1);
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


		gp_editing.ShowEditor();

		if( typeof(gp_editor.wake) == 'function' ){
			gp_editor.wake();
		}

		var $edit_div = $gp.CurrentDiv().addClass('gp_edit_current');


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
	},


	/**
	 * Deprecated methods
	 */
	save_changes: function(callback){
		console.log('Please use gp_editing.SaveChanges() instead of gp_editing.save_changes()');
		gp_editing.SaveChanges(callback);
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

		gp_editing.SaveChanges(function(){
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
	}


	/**
	 * Warn before closing a page if an inline edit area has been changed
	 *
	 */
	$(window).on('beforeunload',function(){

		//check cached editors
		for(i in $gp.editors){
			if( typeof($gp.editors[i].checkDirty) !== 'undefined' && $gp.editors[i].checkDirty() ){
				return 'Unsaved changes will be lost.';
			}
		}

		//check current editor
		if( typeof(gp_editor.checkDirty) !== 'undefined' && gp_editor.checkDirty() ){
			return 'Unsaved changes will be lost.';
		}

	});


	/**
	 * Switch between edit areas
	 *
	 * Using $gp.$doc.on('click') so we can stopImmediatePropagation() for other clicks
	 *
	 * Not using $('#ExtraEditLink'+area_id).click() to avoid triggering other click handlers
	 *
	 */
	$gp.$doc.on('click','.editable_area:not(.filetype-wrapper_section)',function(evt){


		//get the edit link
		var area_id		= $gp.AreaId( $(this) );
		if( area_id == $gp.curr_edit_id ){
			return;
		}

		evt.stopImmediatePropagation(); //don't check if we need to swith back to the section manager

		var $lnk = $('#ExtraEditLink'+area_id);
		var arg = $lnk.data('arg');
		$gp.LoadEditor($lnk.get(0).href, area_id, arg);

	});


	/**
	 * Switch back to the section manager
	 * Check for .cke_reset_all because ckeditor creates dialogs outside of gp_admin_html
	 *
	 */
	$gp.$doc.on('click',function(evt){

		if( $(evt.target).closest('.editable_area, #gp_admin_html, .cke_reset_all, a, input').length ){
			return;
		}

		$gp.LoadEditor('?cmd=ManageSections', 0, 'manage_sections');

	});


	// auto save
	window.setInterval(function(){

		if( typeof(gp_editor.CanAutoSave) == 'function' && !gp_editor.CanAutoSave() ){
			return;
		}

		gp_editing.SaveChanges();

	},5000);


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
			$gp.$win.resize();
		}else{
			gp_editing.ShowEditor();
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
		if( $ckeditor_area.length ){
			var maxHeight			= $gp.$win.height();
			maxHeight				-= $ckeditor_area.position().top;
			maxHeight				-= $('#ckeditor_save').outerHeight();

			$('#ckeditor_area').css({'max-height':maxHeight});

			AdjustForEditor();
		}

	}).resize();


	/**
	 * Publish the current draft
	 *
	 */
	$gp.links.Publish = function(){

		var $edit_area		= $gp.CurrentDiv();
		var id_num			= $gp.AreaId( $edit_area );
		var href			= gp_editing.get_path( id_num );
		href				= $gp.jPrep(href,'cmd=PublishDraft');

		$(this).data('gp-area-id',id_num);

		$gp.jGoTo(href,this);
	}


	/**
	 * Response when an area
	 *
	 */
	$gp.response.DraftPublished = function(){
		var $this		= $(this).hide();
		var id_number	= $gp.AreaId( $this );
		var $area		= $('#ExtraEditArea'+id_number);

		gp_editing.DraftStatus( $area, 0);
	}


	$('.editable_area').off('.gp');
	$gp.$doc.off('click.gp');

})();
