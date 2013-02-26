


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
	$('span.combobox').live('mousedown',function(evt){
		if( evt.target.nodeName != 'INPUT' ){
			evt.preventDefault();
			$(this).find('input').focus();
		}
	});
	$('input.combobox').live('focus',function(){

		var search = this;
		var $search = $(this);

		//once the comobox is initiated, we dont' need to create it again
		$search.removeClass('combobox');

		/*
		var $list = $search.closest('div').find('option');
		var source = [];
		$list.each(function(){
			source.push([this.innerHTML,this.value]);
		});
		*/

		//alert( $search.closest('div').find('.data').html() );
		var source = data();

		// create autocomplete
		$search.filter(':ui-autocomplete').autocomplete('destroy');
		$search
			.autocomplete({
				source: source,
				delay: 100,
				minLength: 0,
				select: function(event,ui){
					if( ui.item ){
						this.value = ui.item[0]
										.replace(/&quot;/g, '"')
										.replace(/&#039;/g, "'")
										.replace(/&lt;/g, '<')
										.replace(/&gt;/g, '>')
										.replace(/&amp;/g, '&');
						return false;
					}
				}
			})
			.data( 'autocomplete' )._renderItem = function( ul, item ) {
				return $( '<li></li>' )
					.data( 'item.autocomplete', item[0] )
					.append( '<a>' + item[0] + '<span>'+item[1]+'</span></a>' )
					.appendTo( ul );
			};

		// open autocomplete list
		$search
			.focus(function(){
				$search.autocomplete( 'search', $search.val() );
				search.focus();
			})
			.focus();

		function data(){
			var str = $search
					.closest('div')
					.find('.data')
					.html()
					.replace(/&lt;/g, '<')
					.replace(/&gt;/g, '>')
					.replace(/&amp;/g, '&');

			return jQuery.parseJSON(str);
		}
	});

});
