


$(function(){

	/**
	 *
	 */
	$('.checkbox_table tr').click(function(evt){
		var $this = $(this);
		var $input = $this.find('input');

		if( evt.target.nodeName != 'INPUT' ){
			if( $input.prop('checked') ){
				$input.prop('checked',false);
			}else{
				$input.prop('checked',true);
			}
		}
		if( $input.prop('checked') ){
			$this.addClass('checked');
		}else{
			$this.removeClass('checked');
		}

	});

	/**
	 * Use jquery ui autocomplete in place of <select>
	 *
	 */
	$(document).on('mousedown','span.combobox',function(evt){
		if( evt.target.nodeName != 'INPUT' ){
			evt.preventDefault();
			$(this).find('input').focus();
		}
	});

	$(document).on('focus','input.combobox',function(){

		//once the comobox is initiated, we dont' need to create it again
		var $search		= $(this).removeClass('combobox');
		var $parent		= $search.parent();
		var source		= $( $parent.data('source') ).data('json');


		// create autocomplete
		var $autocomplete = $search.not(':ui-autocomplete')
			.autocomplete({
				source:		source,
				delay:		100,
				minLength:	1,

				select: function(event,ui){
					if( ui.item ){

						$search.val( ui.item[0] );

						$parent.css({'border-color':''});
						return false;
					}
				}
			});


		// support jqueryui changes
		var data_key = 'autocomplete';
		if( $autocomplete.data('ui-autocomplete') ){
			data_key = 'ui-autocomplete';
		}
		$autocomplete.data( data_key )._renderItem = function( ul, item ) {
			return $( '<li></li>' )
				.data( 'item.autocomplete', item )
				.append( '<a>' + item[0] + '<span>'+item[1]+'</span></a>' )
				.appendTo( ul );
		};

	});

});
