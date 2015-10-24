


$(function(){

	//auto width
	var $container = $('#admincontainer');
	var container_class = $container.attr('class') || '';

	$(window).resize(function(){

		var width = $container.width();
		var cols = 1;

		if( width > 900 ){
			cols = 3;
		}else if( width > 600 ){
			cols = 2;
		}

		$container.attr('class',container_class+' columns_'+cols);

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
