
$(function(){

	$(".sortable_table").sortable({items : "tr",handle: "td"});

	$gp.inputs.DraftCheckbox = function(){
		if( this.checked ){
			$('#sb_field_publish').slideUp();
		}else{
			$('#sb_field_publish').slideDown();
		}
	}

});