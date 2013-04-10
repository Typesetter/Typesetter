


$(function(){

	//auto width
	var $area_wrap = $('#adminlinks2');
	var adjust = 22;
	var min_width = 280;
	var width_2 = 600;
	var width_3 = 900;

	$('#admincontainer').resize(function(){

		var width = $area_wrap.width();
		var cols = 1;

		if( width > width_3 ){
			cols = 3;
		}else if( width > width_2 ){
			cols = 2;
		}
		$area_wrap.children('div').width( (width/cols)-adjust );
		$area_wrap.get(0).style.cssText = '-moz-column-count:'+cols;

	}).resize();



	//expand_child_click
	$(document).on('click','.expand_child_click',function(evt){
		evt.preventDefault();
		var $this = $(this);
		if( $this.hasClass('expand') ){
			$this.parent().children().removeClass('expand');
		}else{
			$this.siblings().removeClass('expand');
			$this.addClass('expand');
		}
	});
	$(document).on('click','.expand_child_click li',function(evt){
		evt.stopPropagation();
	});

});
