

$(function(){

	$(document).on('focus', 'input.autocomplete', function(){

		$(this)
			.css({'position':'relative',zIndex:12000}) //position and zIndex are needed because of bugs with the jquery ui
			.autocomplete('destroy')
			.autocomplete({

				source:gptitles,
				delay: 100, /* since we're using local data */
				minLength: 0,
				select: function(event,ui){
					if( ui.item ){
						this.value = ui.item;
						return false;
					}
				}


		}).data( "autocomplete" )._renderItem = function( ul, item ) {
			return $( "<li></li>" )
				.data( "item.autocomplete", item[1] )
				.append( '<a>' + $gp.htmlchars(item[0]) + '<span>'+$gp.htmlchars(item[1])+'</span></a>' )
				.appendTo( ul );
		};

	});

});
