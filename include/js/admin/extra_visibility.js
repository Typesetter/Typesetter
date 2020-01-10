
$(function(){

	$("#myTable").tablesorter({
		headers: {
			0: {
				sorter: false
			},
		}
	});

	if ($("#vis_type").val()== 0 || $("#vis_type").val()==1){
		$(".pages").hide();
	}

	$("#vis_type").change(function(){
		if($(this).val()!=0 && $(this).val()!=1){
			$(".pages").show();
		} else {
			$(".pages").hide();
		};
	});

	$("#check_all").click(function(){

		if($(this).prop("checked") == true) {
			$(".check_page").prop("checked", true);
		} else {
			$(".check_page").prop("checked", false);
		}

	});

});
