


$(function(){

	//auto width
	var $area_wrap = $('#adminlinks2');
	var $children = $area_wrap.children('div');
	var child_count = $children.length;
	var adjust = 22;
	var min_width = 280;
	var max_width = 500;
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
		cols = Math.min(child_count,cols);
		width = (width/cols)-adjust;
		width = Math.min(width, max_width);
		$children.width( width );

	}).resize();



	//expand_child_click
	$(document).on('click','.expand_child_click',function(evt){

		evt.preventDefault();
		var $this = $(this);
		var panel = $this.closest('.panelgroup').attr('id');
		var children = $this.parent().find('.expand_child_click');
		var index = children.index( this );

		if( $this.hasClass('expand') ){
			children.removeClass('expand');
			index = false;
		}else{
			$this.siblings().removeClass('expand');
			$this.addClass('expand');
		}
		StoreLocal(panel,index);
	});

	$(document).on('click','.expand_child_click li',function(evt){
		evt.stopPropagation();
	});

	function StoreLocal(key,value){

		var object = GetObject();

		if( !object || typeof(key) == 'undefined' ){
			return;
		}

		if( value !== false ){
			object[key] = value;
		}else{
			delete( object[key] );
		}
		localStorage.gp_expand_child_click = JSON.stringify( object );
	}

	function RestoreFromLocal(){

		var object = GetObject();
		if( !object ){
			return;
		}

		$.each(object,function(i,j){
			$('#'+i).find('.expand_child_click').eq(j).click();
		});
	}

	RestoreFromLocal();

	function GetObject(){
		if( typeof(localStorage) == 'undefined' || typeof(JSON) == 'undefined' ){
			return false;
		}

		if( typeof(localStorage.gp_expand_child_click) == 'undefined' ){
			localStorage.gp_expand_child_click = '{}';
		}

		return JSON.parse( localStorage.gp_expand_child_click );
	}

});
