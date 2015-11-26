
$(function(){

	$('tr.orphaned').hide();

	$gp.links.ViewOrphaned = function(evt){
		evt.preventDefault();
		$('tr.orphaned').show('500');
	}

});