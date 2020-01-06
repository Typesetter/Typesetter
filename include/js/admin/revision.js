
$(function(){
	/**
	 * Adjust link targets to point at parent unless they're layout links
	 *
	 */
	$('a').each(function(){
		this.target = '_parent';
	});

	$('.editable_area').removeClass('editable_area');

});
