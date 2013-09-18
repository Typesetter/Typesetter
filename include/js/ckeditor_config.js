
/**
 * Add buttons to toolbar that were added by plugins
 *
 */
CKEDITOR.on( 'instanceCreated', function(e){
	var editor = e.editor;

	//add a button for testing
	/*
	editor.ui.addButton( 'Test', {
		label: 'Test',
		command: 'test',
		toolbar: 'insert'
	});
	*/


	// add a row to the toolbar with plugin buttons
	// using uiSpace for sharedSpaces
	editor.on( 'uiSpace', function(){
	//editor.on( 'pluginsLoaded', function(){

		// this is a list of buttons standard to ckeditor
		//var standard_items = ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'BidiLtr', 'BidiRtl', 'Blockquote', 'Cut', 'Copy', 'Paste', 'TextColor', 'BGColor', 'Templates', 'CreateDiv', '-', 'NumberedList', 'BulletedList', 'Indent', 'Outdent', 'Find', 'Replace', 'Flash', 'Font', 'FontSize', 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField', 'Format', 'HorizontalRule', 'Iframe', 'Image', 'Smiley', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'Link', 'Unlink', 'Anchor', 'Maximize', 'NewPage', 'PageBreak', 'PasteText', 'PasteFromWord', 'RemoveFormat', 'Save', 'SelectAll', 'ShowBlocks', 'Source', 'Sourcedialog', 'SpecialChar', 'Styles', 'Table', 'Undo', 'Redo' ];
		var standard_items = ['About', 'Bold', 'Italic', 'Underline','Scayt', 'Strike', 'Subscript', 'Superscript', 'BidiLtr', 'BidiRtl', 'Blockquote', 'Cut', 'Copy', 'Paste', 'TextColor', 'BGColor', 'Templates', 'CreateDiv', '-', 'NumberedList', 'BulletedList', 'Indent', 'Outdent', 'Find', 'Replace', 'Flash', 'Font', 'FontSize', 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField', 'Format', 'HorizontalRule', 'Iframe', 'Image', 'Smiley', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'Link', 'Unlink', 'Anchor', 'Maximize', 'NewPage', 'PageBreak', 'PasteText', 'PasteFromWord', 'RemoveFormat', 'Save', 'SelectAll', 'ShowBlocks', 'Source', 'Sourcedialog', 'SpecialChar', 'Styles', 'Table', 'Undo', 'Redo' ];

		var plugin_buttons = [];
		for( i in editor.ui.items ){
			var is_in = jQuery.inArray(i, standard_items);
			if( is_in === -1 ){
				plugin_buttons.push(i);
			}
		}

		if( plugin_buttons.length == 0 ){
			return;
		}

		editor.config.toolbar.push( plugin_buttons );
	});

});



/**
 * Set up autocomplete for gpEasy pages
 *
 */
CKEDITOR.on( 'dialogDefinition', function( ev ){

	if( typeof(gptitles) == 'undefined' ){
		return;
	}

	// Take the dialog name and its definition from the event data.
	var dialogName = ev.data.name;
	var dialogDefinition = ev.data.definition;

	if( dialogName == 'link' ){

		//default protocol
		var auto_complete_used = false;
		var infoTab = dialogDefinition.getContents( 'info' );
		var protocol = infoTab.get( 'protocol' );
		protocol['default'] = '';
		protocol.items.unshift(['', '']);


		//prevent the enter key from affecting the autocomplete and ckeditor dialog
		dialogDefinition.onOk = CKEDITOR.tools.override(dialogDefinition.onOk, function(original) {
			return function() {
				if( auto_complete_used ){
					auto_complete_used = false;
					return false;
				}
				return original.call(this,arguments[0]);
			}
		});



		//override the onload to add autocomplete
		dialogDefinition.onLoad = CKEDITOR.tools.override(dialogDefinition.onLoad, function(original) {
			return function() {
				original.call(this);

				var url = this.getContentElement('info', 'url').getInputElement().$;
				var protocol = this.getContentElement('info', 'protocol').getInputElement().$;

				//position and zIndex are needed because of bugs with the jquery ui
				$(url).css({'position':'relative',zIndex: 12000 }).autocomplete({
					source:gptitles,
					delay: 100, // since we're using local data
					minLength: 0,
					select: function(event,ui){
						if( ui.item ){
							url.value = encodeURI(ui.item[1]);
							protocol.value = '';

							//enter key?
							if( event.which == 13 ){
								auto_complete_used = true;
							}
							event.stopPropagation();
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
		});
	}
});

/**
 * Prevent premature onbeforeupload event in IE
 *
 */
CKEDITOR.on('instanceReady', function(event) {
	if(CKEDITOR.env.ie){
		event.editor.on('dialogShow', function(dialogShowEvent) {
			$(dialogShowEvent.data._.element.$).find('a[href*="void(0)"]').removeAttr('href');
		});
	}
});



