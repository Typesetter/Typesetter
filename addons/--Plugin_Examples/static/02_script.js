gplinks.refresh_content = function(rel,evt){
	evt.preventDefault();
	var href = jPrep(this.href)+'&cmd=refresh';
	$.getJSON(href,ajaxResponse);
}
