

var $gp = function() {
		return this;
	}
$gp.links = {};
$gp.inputs = {};
$gp.inputs = {};

var gp_error = 'There was an error processing the last request. Please reload this page to continue.';

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}



function eraseCookie(name) {
	createCookie(name,"",-1);
}


function jPrep(a,b){
	b = typeof(b) == 'undefined' ? 'gpreq=json&jsoncallback=?' : b;
	a = strip_from(a,'#');
	a += ( a.indexOf('?') == -1 ) ? '?' : '&';
	return a + b;
}

function ajaxResponse(data,textStatus,jqXHR){

	$('.messages').detach();

	var cbox = false;
	$.each(data,function(i,obj){

		if( typeof(gpresponse[obj.DO]) == 'function' ){
			gpresponse[obj.DO].call(this,obj,textStatus,jqXHR);
			return;
		}

		switch(obj.DO){
			case 'replace':
				$(obj.SELECTOR).replaceWith(obj.CONTENT);
			break;

			case 'inner':
				$(obj.SELECTOR).html(obj.CONTENT);
			break;

			case 'eval':
				eval(obj.CONTENT);
			break;

			case 'renameprep':
				RenamePrep();
			break;

			case 'admin_box_data':
				cbox = $gp.AdminBoxC(obj.CONTENT);
			break;

			case 'messages':
				$(obj.CONTENT).appendTo('body').show().css({'top':0});
			break;

			default:
				//do nothing
				//alert('nothing for: '+obj.DO);
			break;
		}
	});

	if( !cbox ){
		try{
			$gp.CloseAdminBox()
		}catch(a){}
		try{
			$.fn.colorbox.close();
		} catch(a){}
	}


	loaded();
}


$(function(){

	//add a class to the body
	//this also affects the display of elements using the req_script css class
	$('body').addClass('STCLASS');

	//erase cookie_cmd
	eraseCookie('cookie_cmd');


	//general initiation
	function cInit(){

		//
		//	AJAX
		//
		$(document).ajaxError(function(event, XMLHttpRequest, ajaxOptions, thrownError){
			loaded();

			//don't use this error handler if another one is set for the ajax request
			if( typeof(ajaxOptions.error) == 'function' ){
				return;
			}
			if( thrownError == '' ){
				return;
			}

			if( typeof(debugjs) !== "undefined" ){

				//collect some debug info
				var debug_info = {
					thrownError:thrownError,
					text:XMLHttpRequest.responseText,
					status:XMLHttpRequest.status,
					statusText:XMLHttpRequest.statusText,
					url:ajaxOptions.url,
					type:ajaxOptions.type,
					browser:$.param($.browser) //$.browser is deprecated and may be removed in future jquery releases
				}
				if( ajaxOptions.data ){
					debug_info.data = ajaxOptions.data.substr(0,100);
				}

				debug( debug_info );
				//LOGO( XMLHttpRequest );
				//LOGO( event );
				//alert('Error detected');
				return;
			}

			alert(gp_error);
		});

		//this will fire event if there's an error
		//$(document).ajaxComplete( function(event, XMLHttpRequest, ajaxOptions){
		//});



		//forms
		$('form').live('mousedown',function(e){
			var $this = $(this);

			if( $this.data('gpForms') == 'checked' ){
				return;
			}

			if( typeof(this['return']) !== 'undefined' ){
				this['return'].value = window.location; //set the return path
			}

			$this.data('gpForms','checked');
		});

		$('input').live('click',function(evt){

			verify(this.form);
			var $this = $(this);

			//get the first class
			var a = strip_from(
						$this.attr('class'),
						' '
					);

			//put before switch() to allow overriding
			if( typeof(gpinputs[a]) == 'function' ){
				return gpinputs[a].call(this,evt,evt);//evt twice so the same function can be used for gplinks and gpinputs
			}


			switch(a){

				case 'gppost':
				case 'gpajax':
				return $gp.post(this);
			}

			return true;
		});

		//$('form[method=post]').live('submit',function(){
		$('form').live('submit',function(evt){
			//evt.preventDefault();
			//var first_submit = $(this).find('input[type=submit]:first');
			//$(this).find('input[type=submit]:first').click();
			verify(this);
		});

		//add a unique verifiable string to confirm posts are
		//called twice because of bug in jquery 1.4.2 (live) and IE
		function verify(a){
			$(a).filter('[method=post]').append('<input type="hidden" name="verified" value="'+post_nonce+'" />');
		}



		//expanding menus
		$('.expand_child').live('mouseenter',function(){
			$(this).addClass('expand');
			if( $(this).hasClass('simple_top') ){
				$(this).addClass('simple_top_hover');
			}
		}).live('mouseleave',function(){
			$(this).removeClass('expand').removeClass('simple_top_hover');
		});


		/**
		 * Handle all clicks on <a> tags
		 *
		 */
		$(document).on('click', 'a',function(evt){

				var $this = $(this);
				var cmd = $this.data('cmd');
				var arg = $this.data('arg');
				if( !cmd ){
					// use of name and rel attributes is deprecated
					cmd = $this.attr('name');
					arg = $this.attr('rel');
				}


				if( $this.hasClass('gpconfirm') && !confirm(this.title) ){
					evt.preventDefault();
					return;
				}

				if( typeof($gp.links[cmd]) == 'function' ){
					return $gp.links[cmd].call(this,evt,arg);
				}

				/* @deprectated 3.6 */
				if( typeof(gplinks[cmd]) == 'function' ){
					return gplinks[cmd].call(this,arg,evt);
				}

				switch(cmd){

					case 'toggle_show':
						$(arg).toggle();
					break;

					case 'inline_box':
						TransferValues(arg,this);
						$.fn.colorbox(
							//$.extend(colorbox_options,{inline:true,href:b, open:true})
							$gp.cboxSettings({inline:true,href:arg, open:true})
						);
					break;
					case 'iadmin_box': //inline admin box
						TransferValues(arg,this);
						$gp.AdminBoxC($(arg),'inline');
					break;

					case 'postlink':
						$gp.post_link(this);
					break;

					case 'gpajax':
						$gp.jGoTo(this.href);
					break;
					case 'creq':
						$gp.cGoTo(this,true);
					break;
					case 'cnreq':
						$gp.cGoTo(this,false);
					break;
					case 'close_message':
						$this.closest('div').slideUp();
					break;
					case 'gallery':
						var selector;
						if( arg == '' ){
							selector = this;
						}else{
							selector = 'a[rel='+arg+']';
						}
						$(selector).colorbox(
							$gp.cboxSettings({resize:true})
							);
						$.fn.colorbox.launch(this);
					break;

					default:
					return true;
				}

			evt.preventDefault();
			return false;
		});

		//assign values to the form based on hidden input elements
		function TransferValues(selector,lnk){

			var c = $(selector).find('form').get(0);
			if( c ){
				$(lnk).find('input').each(function(i,j){
					if( c[j.name] ){
						c[j.name].value = j.value;
					}
				});
			}
		}


	} /* end cinit() */


	/*
	AJAX w/ jQuery
	*/



	/*
	 *
	 * Public Functions
	 *
	 */


	$gp.jGoTo = function(a){
		loading();
		a = jPrep(a);
		$.getJSON(a,ajaxResponse);
	}

	/* Reload page with arguments (a) set as a cookie
	 * 	if samepage is false, then it will take user to a.href
	 */
	$gp.cGoTo = function(a,samepage){
		var l;
		if( samepage ){
			l = window.location.href;
		}else{
			l = strip_from(a.href,'?');
		}
		var query = a.search + '&verified='+post_nonce;
		createCookie('cookie_cmd',encodeURIComponent(query),1);
		window.location = strip_from(l,'#');
	}

	$gp.post = function(a,data){
		loading();
		var frm = $(a).closest('form');

		var b = frm.serialize() + '&verified='+encodeURIComponent(post_nonce); //needed when $gp.post is called without an input click
		if( a.nodeName == 'INPUT' ){
			b += '&'+encodeURIComponent(a.name)+'='+encodeURIComponent(a.value);
		}
		if( data ){
			b += '&'+data;
		}

		$.post(
			jPrep(frm.attr('action')),
			b,
			ajaxResponse,
			'json'
			);
		return false;
	}

	/*
	 * POST a link with gpEasy's verified values
	 *
	 */
	$gp.post_link = function(lnk){
		loading();
		var $lnk = $(lnk);
		var data = strip_to(lnk.search,'?')
				+ '&gpreq=json&jsoncallback=?'
				+ '&verified='+encodeURIComponent($lnk.data('nonce'))
				;
		$.post(
			strip_from(lnk.href,'?'),
			data,
			ajaxResponse,
			'json'
			);
	}

	/**
	 * 	Post content with gpEasy's verified value
	 *  Arguments order is same as jQuery's $.post()
	 *
	 */
	$gp.postC = function(url,data,callback,datatype){
		callback = callback || ajaxResponse;
		datatype = datatype || 'json';

		if( typeof(data) == 'object' ){
			data = jQuery.param(data,true);
		}

		data += '&verified='+encodeURIComponent(post_nonce);
		if( datatype == 'json' ){
			data += '&gpreq=json&jsoncallback=?';
		}
		$.post(
			strip_from(url,'?'),
			data,
			callback,
			datatype
			);
	}

	/**
	 * Return the current colorbox settings
	 *
	 */
	$gp.cboxSettings = function(options){
		options = options||{};
		colorbox_lang = colorbox_lang||{};
		return $.extend(colorbox_lang,{opacity:0.75,maxWidth:'90%',minWidth:300,minHeight:300,maxHeight:'90%'},options);
	}

	$gp.relevt = function(evt,rel){
		if( typeof(rel) == 'object' ){
			return rel;
		}
		return evt;
	}

	//init
	cInit();
	$('body').trigger('gpReady');

});


function loading(){
	$('#loading1').css('zIndex',99000).fadeTo(1,.3)
	.next().css('zIndex',99001).show();
}
function loaded(){
	$('#loading1, #loading2').clearQueue().hide();
}
function message(){
	$('#loading1, #loading2').clearQueue().hide();
}

function strip_to(a,b){
	if( !a ) return a;
	var pos = a.indexOf(b);
	if( pos > -1 ){
		return a.substr(pos+1);
	}
	return a;
}

function strip_from(a,b){
	if( !a ) return a;
	var p = a.indexOf(b);
	if( p > 0 ){
		a = a.substr(0,p);
	}
	return a;
}


