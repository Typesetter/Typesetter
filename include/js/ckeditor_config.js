

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





