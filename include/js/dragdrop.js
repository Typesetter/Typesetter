
	WBd = {

		scroll:false,
		scrolly:0,
		scrollx:0,
		scrolle:false,

		//DragMouseUP
		DMU : function(e){

			//this doesn't prevent clicks from happening
			//if( WBd.started ){
				//e.stopPropagation();
				//e.preventDefault();
			//}

			var a = $('.draggable_droparea > .target');
			if( a.size() < 1 ){
				WBd.clean();
				return;
			}
			var b = a.get(0);
			if( !b || (b == WBd.dragEl) ){
				WBd.clean();
				return;
			}


			var lnk = $(WBd.dragEl).find('a.dragdroplink:first');
			if( lnk.size() < 1 ){
				WBd.clean();
				return;
			}

			lnk = lnk.clone(true).appendTo('body'); //append to body so the click works

			var c = a.find('a.dragdroplink:first').html();
			lnk.attr('href',lnk.attr('href').replace('%s',c));
			lnk.click();

			WBd.clean();
		},


		//clean up
		clean : function(){

			$('.draggable_droparea').removeClass('drag_active'); //important for proper css display of drop down menus

			$('.WB_DRAG_BOX').hide();

			//remove listeners
			$(document).unbind('mousemove.drag',WBd.DMM).unbind('mouseup.drag',WBd.DMU);
			$('.draggable_droparea > *').unbind('mouseover.drag').unbind('mouseout.drag').removeClass('target');

			WBd.VARS();

		},

		VARS : function(){
			WBd.dragEl = false;
			WBd.started = false;
		},

		//	DragMouseDown
		//	e	event
		dMDn : function(e){
			var b,c,l;

			//don't drag when mousing down on a drop down menu (expandable areas can be children of draggable areas)
			if( $(e.target).closest('.expand ul').size() > 0 ){
				return;
			}

			$(document).bind('mouseup.drag',WBd.DMU);

			var a = WBd.dragEl = this; //refers to draggable element in this situation


			var margx = (a.offsetWidth/2);
			var margy = (a.offsetHeight/2);


			//box size and adjust margins to center them around cursor
			$('#WB_DRAG_BOX_TOP').height('30px').width(a.offsetWidth).css('marginTop',-(margy+30)).css('marginLeft',-margx);

			$('#WB_DRAG_BOX_BOTTOM').width(a.offsetWidth).css('marginTop',margy).css('marginLeft',-margx);

			$('#WB_DRAG_BOX_LEFT').height(a.offsetHeight).width(1).css('marginTop',-margy).css('marginLeft',-margx);

			$('#WB_DRAG_BOX_RIGHT').height(a.offsetHeight).width(1).css('marginTop',-margy).css('marginLeft',margx);


			//position box
			$(document).bind('mousemove.drag',WBd.DMM);
			//WBd.DMM(e);


			return false;
		},


		//DragMouseMove
		DMM : function(e){
			if( !e ) return; //because of scrolling

			if( !WBd.started ){
				WBd.started = true;

				//setting the target
				$('.draggable_droparea > *:not([class~=draggable_nodrop])').bind('mouseover.drag',function(){
					$(this).addClass('target');
				}).bind('mouseout.drag',function(){
					$(this).removeClass('target');
				});


				//show drag placeholding box
				$('.draggable_droparea').addClass('drag_active');
				$('.WB_DRAG_BOX').show();
			}

			var x,y;
			var wn = $(window);
			x = (e.clientX + wn.scrollLeft());
			y = (e.clientY + wn.scrollTop());

			//move box
			$('.WB_DRAG_BOX').css('left',x).css('top',y);


			//scroll window if needed
			x = y = 0;

			if(e.clientY < 80 ){
				y = -30;
			}else if( (e.clientY + 60) > wn.height() ){
				y = 30;
			}

			if( e.clientX < 60 ){
				x = -30;
			}else if( (e.clientX + 60) > wn.width() ){
				x = 30;
			}

			if( (x === 0) && (y == 0) ){
				WBd.SCR();

			}else{

				WBd.scrollx = x;
				WBd.scrolly = y;
				WBd.scrolle = e;

				if( WBd.scroll === false ){
					WBd.scroll = window.setInterval(function(){

						//stop in case the drag ended of the screen
						if( !WBd.dragEl ){
							WBd.SCR();
							return;
						}

						window.scrollBy(WBd.scrollx,WBd.scrolly);
						WBd.DMM(WBd.scrolle);

					},150);
				}
			}
			return false;
		},


		//SCROLL RESET
		SCR:function(){
			if( WBd.scroll ){
				window.clearInterval(WBd.scroll);
			}
			WBd.scroll = WBd.scrolle = false;
		}

	};


	$( function() {
		WBd.VARS();
		$('.draggable_element').live('mousedown',WBd.dMDn);

		var a = $('<div style="position:absolute;z-index:10000;cursor:move;display:none;" class="WB_DRAG_BOX"></div>');

		a.clone().css('borderBottom','2px dashed #bbb').attr('id','WB_DRAG_BOX_TOP').appendTo('body');
		a.clone().css('borderLeft','2px dashed #bbb').attr('id','WB_DRAG_BOX_LEFT').appendTo('body');
		a.clone().css('borderTop','2px dashed #bbb').attr('id','WB_DRAG_BOX_BOTTOM').appendTo('body');
		a.clone().css('borderRight','2px dashed #bbb').attr('id','WB_DRAG_BOX_RIGHT').appendTo('body');
	});







