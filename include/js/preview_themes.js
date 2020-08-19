$(function(){

	// Update New Layout button
	$gp.links.SetPreviewTheme = function(){
		var href = this.href+'&cmd=newlayout';
		$('.add_layout').attr('href', href);
	}

});
