/** global: CKEDITOR */

$(function(){

	CKEDITOR.on( 'instanceCreated', function(evt){

		evt.editor.on('change',function(){
			if( evt.editor.checkDirty() ){
				$('.gp_publish_extra').hide();
			}else{
				$('.gp_publish_extra').show();
			}
		});

		$('.gp_save_extra').on('click', function(){
			evt.editor.resetDirty();
		});

		$(window).on('beforeunload', function(){
			if( evt.editor.checkDirty() ){
				return 'Content was changed! Proceed anyway?';
			}
		});

	});


});
