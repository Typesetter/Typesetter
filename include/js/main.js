

var gpPublic;
var $gp;
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

function ajaxResponse(data){

	$('.messages').detach();

	var cbox = false;
	$.each(data,function(i,obj){ //using $.each() instead of a for loop to prevent Array.prototype.xxxx from affecting the results

		if( typeof(gpresponse[obj.DO]) == 'function' ){
			gpresponse[obj.DO].call(this,obj);
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
				var debug = {
					thrownError:thrownError,
					text:XMLHttpRequest.responseText,
					status:XMLHttpRequest.status,
					statusText:XMLHttpRequest.statusText,
					url:ajaxOptions.url,
					type:ajaxOptions.type,
					browser:$.param($.browser) //$.browser is deprecated and may be removed in future jquery releases
				}
				if( ajaxOptions.data ){
					debug.data = ajaxOptions.data.substr(0,100);
				}

				LOGO( debug );
				//LOGO( XMLHttpRequest );
				//LOGO( event );
				alert('Error detected. See bottom of page for details');
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


		//
		//live anchor clicks with name
		//


		//one function for all anchor clicks
		$('a[name]').live('click',function(evt){
				var $this = $(this);
				var a = $this.attr('name');
				var b = $this.attr('rel');


				if( $this.hasClass('gpconfirm') && !confirm(this.title) ){
					evt.preventDefault();
					return;
				}

				if( typeof(gplinks[a]) == 'function' ){
					return gplinks[a].call(this,b,evt);
				}

				switch(a){

					case 'tabs':
						tabs(this);
					break;

					case 'toggle_show':
						$(b).toggle();
					break;

					case 'inline_box':
						TransferValues(b,this);
						$.fn.colorbox(
							//$.extend(colorbox_options,{inline:true,href:b, open:true})
							$gp.cboxSettings({inline:true,href:b, open:true})
						);
					break;
					case 'iadmin_box': //inline admin box
						TransferValues(b,this);
						$gp.AdminBoxC($(b),'inline');
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
						if( b == '' ){
							selector = this;
						}else{
							selector = 'a[rel='+b+']';
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

	gpPublic = $gp = function() {
		return this;
	}

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
		var data = lnk.search;
		if( data[0] == '?' ) data = data.substring(1);
		$gp.postC(lnk.href,data);
	}

	/*
	 * 	Post content with gpEasy's verified value
	 *  Arguments order is same as jQuery's $.post()
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

function tabs(a){
	$(a.parentNode).find('a').removeClass('selected').each(function(b,c){
		var d = strip_to(c.href,'#');
		if( d ){
			$('#'+d).hide();
		}
	});

	d = strip_to(a.href,'#');

	$('#'+d).show();

	a.className = 'selected';
}

function strip_to(a,b){
	if( !a ) return a;
	var pos = a.indexOf(b);
	if( pos > -1 ){
		return a.substr(pos+1);
	}
	return false;
}

function strip_from(a,b){
	if( !a ) return a;
	var p = a.indexOf(b);
	if( p > 0 ){
		a = a.substr(0,p);
	}
	return a;
}


