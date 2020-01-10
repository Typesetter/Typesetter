
$(function(){
	alert('here');

	CKEDITOR.instances.gpcontent.on("change", function(){
		if( CKEDITOR.instances.gpcontent.checkDirty() ){
			$(".gp_publish_extra").hide();
		}else{
			$(".gp_publish_extra").show();
		}
	});

	$(".gp_save_extra").on("click", function(){
		CKEDITOR.instances.gpcontent.resetDirty();
	});

	$(window).on("beforeunload", function(){
		if( CKEDITOR.instances.gpcontent.checkDirty() ){
			return "Content was changed! Proceed anyway?";
		}
	});

});
