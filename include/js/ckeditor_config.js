
CKEDITOR.editorConfig = function( config ){

	config.toolbar = 'gpeasy';

	config.resize_minWidth = true;
	config.height = 300;

	config.contentsCss = gpBase+'/include/css/ckeditor_contents.css';

	config.fontSize_sizes = 'Smaller/smaller;Normal/;Larger/larger;8/8px;9/9px;10/10px;11/11px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px';

	config.ignoreEmptyParagraph = true;

	config.entities_latin = false;
	config.entities_greek = false;

	config.scayt_autoStartup = false;
	config.disableNativeSpellChecker = false;

	config.toolbar_gpeasy = [
		['Source','-','Templates'],
		['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
		['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
		'/',
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Link','Unlink','Anchor'],
		['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
		'/',
		['Format','Font','FontSize'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['TextColor','BGColor'],
		['Maximize', 'ShowBlocks','-','About']
	];
};



CKEDITOR.on( 'dialogDefinition', function( ev ){

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
							url.value = encodeURI(ui.item);
							protocol.value = '';

							//enter key?
							if( event.which == 13 ){
								auto_complete_used = true;
							}
							return false;
						}

					}

				}).data( "autocomplete" )._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item[1] )
						.append( '<a>' + $gp.htmlchars(item[0]) + '<span>'+$gp.htmlchars(item[1])+'</span></a>' )
						.appendTo( ul );
				};
			}
		});
	}
});





