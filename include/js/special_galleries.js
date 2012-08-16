
	$(function() {
		$( '.active_galleries, .inactive_galleries' ).sortable({
			connectWith: '.drag_galleries',
			tolerance: 'pointer',
			items : '.draggable',
			stop: function(event, ui){

				var item = ui.item; // the <li> element being dragged

				var data = {
					cmd : 'newdrag',
					title : item.find('.title').val()
				}

				//next element
				var next = item.next('.draggable');
				if( next.length > 0 ){
					data.next = next.find('.title').val();
				}

				//active or inactive
				if( item.parent().hasClass('active_galleries') ){
					data.active = 'active';
				}

				loading();

				data = jQuery.param(data,true);
				$gp.postC( window.location.href , data);

			}
		}).disableSelection();
	});
