

$(function(){


	//change to all or none checked
	$('input.select_all').click(function(e){
		var checked = this.checked;

		var container = contained(this);
		container.find('input[type=checkbox]').each(function(b,c){

			if( c.disabled ){
				return true;
			}
			if( checked ){
				c.checked = true;
			}else{
				c.checked = false;
			}

		});

		CheckBoxes(container);

	});

	$('label.all_checkbox').click(function(evt){
		//evt.preventDefault();
		var a = contained(this);
		CheckBoxes(a);
	});
	Setup();

	//cannot just hide() input because of explorer
	function Setup(){
		$('.all_checkboxes').addClass('checkboxes_init').each(function(){
			var area = $(this);
			CheckBoxes(area);

			//find reset buttons in the form
			area
				.closest('form')
				.find('input[type="reset"]')
				.unbind('click.checkboxes')
				.bind('click.checkboxes',function(){
					window.setTimeout(function(){
						Reset();
					},50);
				});


		});
	}

	function Reset(){
		$('.all_checkboxes').each(function(){
			var area = $(this);
			CheckBoxes(area);
		});
	}

	function CheckBoxes(container){
		var all = true;


		//update all the checkboxes
		container.find('label.all_checkbox input').each(function(a,b){
			if( b.checked ){
				$(b.parentNode).addClass('checked').removeClass('unchecked');
			}else{
				all = false;
				$(b.parentNode).removeClass('checked').addClass('unchecked');
			}
		});


		//change the corresponding
		if( !all ){
			container.find('input.select_all').prop('checked',false);
		}
	}

	function contained(a){
		return $(a).closest('.all_checkboxes');
	}

});

