

var gp_editing = {

	interface:		[],		// storage for editing interfaces
	editors:		[],		// storage for editing objects
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

		$('#ckeditor_wrap').addClass('ck_saving');


		var edit_div	= gp_editing.CurrentDiv();
		var path		= strip_from(gp_editor.save_path,'#');
		var query		= '';

		if( path.indexOf('?') > 0 ){
			query = strip_to(path,'?')+'&';
		}

		query			+= 'cmd=save_inline&section='+edit_div.data('gp-section')+'&';
		query			+= gp_editor.gp_saveData();


		//the saved function
		gpresponse.ck_saved = function(){

			if( !gp_editor ) return;

			gp_editor.updateElement();
			gp_editor.resetDirty();
			$('#ckeditor_wrap').removeClass('ck_saving');


			if( typeof(callback) == 'function' ){
				callback.call();
			}
		}

		$gp.postC( path, query);
	},


	/**
	 * Get the Editor Tools area
	 * Initiate dragging
	 *
	 */
	editor_tools:function(){

		$('#ckeditor_top').html('');
		$('#ckeditor_bottom').html('');
		$('#ckeditor_wrap').show();
		$('#ckeditor_area').show();
	},


	/**
	 * Dock/Undock the floating inline editor
	 *
	 */
	setdock:function(change_dock){

	},


	/**
	 * Make sure certain gpEasy elements aren't copied into the html of pages
	 * @deprecated
	 *
	 */
	strip_special:function(data){
		return data;
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
		var h = '<div id="cktabs">';
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
	 * Cache Interface
	 *
	 */
	CacheInterface: function(){

		gp_editing.CurrentDiv().removeClass('gp_edit_current');

		var $wrap 				= $('#ckeditor_wrap');
		var html				= $wrap.html();

		var $interface						= $('#ckeditor_area').detach();
		this.interface[$gp.last_edit_id]	= $interface;
		this.editors[$gp.last_edit_id]		= gp_editor;

		$('#ckeditor_wrap').html( html );
	},


	/**
	 * Restore Cached
	 *
	 */
	RestoreCached: function(id){

		if( typeof(this.interface[id]) != 'object' ){
			return false;
		}

		$('#ckeditor_wrap').html('').append(this.interface[id]);
		gp_editor			= this.editors[id];
		$gp.last_edit_id	= id;

		gp_editing.CurrentDiv().addClass('gp_edit_current');

		return true;
	},


	/**
	 * Get the current
	 *
	 */
	CurrentDiv: function(){
		return $('#ExtraEditArea'+$gp.last_edit_id);
	},


	/**
	 * Saved
	 *
	 */
	AutoSave: function(){

		if( !gp_editing.IsDirty() ){
			gp_editing.DisplayDirty();
			return;
		}

		//mark as saved
		gp_editing.save_changes(function(){
			gp_editing.is_dirty = false;
			gp_editing.DisplayDirty();
		});
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

$gp.links.ck_close = gp_editing.close_editor;
$gp.links.ck_save = function(evt,arg){
	evt.preventDefault();

	gp_editing.save_changes(function(){
		if( arg && arg == 'ck_close' ){
			gp_editing.close_editor(evt);
		}
	});

}


	/**
	 * Change docking of inline editor
	 *
	 */
	$gp.links.ck_docklink = function(){
		gpui.ckd = !gpui.ckd;
		gp_editing.setdock(true);
	}


	/**
	 * Control which editing area is displayed
	 *
	 */
	$gp.links.SwitchEditArea = function(){
		var $this = $(this);

		$('.ckeditor_control.selected').removeClass('selected');
		$this.addClass('selected');

		$('.manage_section_area').hide();

		$('.inline_edit_area').hide();

		$( $this.data('arg') ).show();
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
	$gp.$doc.on('click','.gp_editing:not(.gp_edit_current)',function(){
		var area_id = $(this).data('gp-area-id');
		gp_editing.CacheInterface();
		gp_editing.RestoreCached(area_id);
	});


	// auto save
	window.setInterval(gp_editing.AutoSave,5000);


	// check dirty
	$gp.$doc.on('keyup click',gp_editing.DisplayDirty);

