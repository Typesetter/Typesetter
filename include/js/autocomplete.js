

$(function(){

	$(document).on('focus', 'input.title-autocomplete:not(.ui-autocomplete-input)', function(){

		var gptitles	= false;
		var that		= this;

		console.log('autocomplete');


		/**
		 * Load the titles dynamically
		 *
		 */
		var url = gpBLink+'/Admin?cmd=autocomplete-titles';
		$.getJSON(url,function(data){
			gptitles = data;
			Autocomplete();
		});



		/**
		 * Set up autocomplete on the field
		 *
		 */
		function Autocomplete(){

			$(that)
				.css({'position':'relative',zIndex:12000}) //position and zIndex are needed because of bugs with the jquery ui
				.autocomplete({

					source: gptitles,
					delay: 100, /* since we're using local data */
					minLength: 0,
					select: function(event,ui){
						if( ui.item ){
							this.value = ui.item[1];
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


});
