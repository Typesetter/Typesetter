//"use strict";



/**
 * $gp object
 *
 *
 */
var $gp = {

	inputs : {},
	response : {},
	error : 'There was an error processing the last request. Please reload this page to continue.',

	/**
	 * Handler for loading json content
	 *
	 */
	jGoTo : function(a){
		$gp.loading();
		a = $gp.jPrep(a);
		$.getJSON(a,$gp.Response);
	},


	/**
	 *  Reload page with arguments (a) set as a cookie
	 *  if samepage is false, then it will take user to a.href
	 *
	 */
	cGoTo : function(a,samepage){
		var $link = $(a);
		var query = a.search +'&verified='+encodeURIComponent($link.data('nonce'));
		$gp.Cookie('cookie_cmd',encodeURIComponent(query),1);

		if( samepage ){
			$gp.Reload();
		}else{
			window.location = strip_from(strip_from(a.href,'#'),'?');
		}
	},


	/**
	 * Post request to server
	 *
	 */
	post : function(a,data){
		$gp.loading();
		var frm = $(a).closest('form');

		var b = frm.serialize() + '&verified='+encodeURIComponent(post_nonce); //needed when $gp.post is called without an input click
		if( a.nodeName === 'INPUT' || a.nodeName === 'BUTTON' ){
			b += '&'+encodeURIComponent(a.name)+'='+encodeURIComponent(a.value);
		}
		if( data ){
			b += '&'+data;
		}

		$.post(
			$gp.jPrep(frm.attr('action')),
			b,
			$gp.Response,
			'json'
			);
		return false;
	},


	/**
	 * POST a link to the server
	 *
	 */
	post_link : function(lnk){
		$gp.loading();
		var $lnk = $(lnk);
		var data = strip_to(lnk.search,'?')
				+ '&gpreq=json&jsoncallback=?'
				+ '&verified='+encodeURIComponent($lnk.data('nonce'))
				;
		$.post(
			strip_from(lnk.href,'?'),
			data,
			$gp.Response,
			'json'
			);
	},


	/**
	 * Post content with gpEasy's verified value
	 * Arguments order is same as jQuery's $.post()
	 *
	 */
	postC : function(url,data,callback,datatype){
		callback = callback || $gp.Response;
		datatype = datatype || 'json';

		if( typeof(data) === 'object' ){
			data = $.param(data);
		}

		data += '&verified='+encodeURIComponent(post_nonce);
		if( datatype === 'json' ){
			data += '&gpreq=json&jsoncallback=?';
		}

		$.post(
			strip_from(url,'?'),
			data,
			callback,
			datatype
			);
	},



	/**
	 * Return the current colorbox settings
	 *
	 */
	cboxSettings : function(options){
		options			= options||{};

		if( typeof(colorbox_lang) != 'object' ){
			colorbox_lang	= {};
		}
		return $.extend(colorbox_lang,{opacity:0.75,maxWidth:'90%',maxHeight:'90%'},options);
	},

	/**
	 * Simple method for creating/erasing cookies
	 *
	 */
	Cookie : function(name,value,days) {
		var expires = "";
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			expires = "; expires="+date.toGMTString();
		}
		document.cookie = name+"="+value+expires+"; path=/";
	},


	/**
	 * Prepare a query for an ajax request
	 *
	 */
	jPrep : function(query,args){
		args = typeof(args) === 'undefined' ? 'gpreq=json&jsoncallback=?' : args;
		query = strip_from(query,'#');
		query += ( query.indexOf('?') === -1 ) ? '?' : '&';
		return query + args;
	},


	/**
	 * Handle ajax responses
	 *
	 */
	Response : function(data,textStatus,jqXHR){

		$('.messages').detach();
		try{
			$gp.CloseAdminBox();
		}catch(a){}

		try{
			$.fn.colorbox.close();
		} catch(a){}


		$.each(data,function(i,obj){

			if( typeof($gp.response[obj.DO]) === 'function' ){
				$gp.response[obj.DO].call(this,obj,textStatus,jqXHR);
				return;
			}

			if( typeof(gpresponse[obj.DO]) === 'function' ){
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

				case 'admin_box_data':
					$gp.AdminBoxC(obj.CONTENT);
				break;

				case 'messages':
					$(obj.CONTENT).appendTo('body').show().css({'top':0});
				break;

				default:
					//do nothing
					//alert('nothing for: '+obj.DO);
				break;
			}

			//standard functions
			var $selected = $(obj.SELECTOR);
			if( typeof($selected[obj.DO]) == 'function' ){
				$selected[obj.DO](obj.CONTENT);
			}

		});

		$gp.loaded();
	},


	/**
	 * Display an overlay to indicate loading process
	 *
	 */
	loading : function(){
		var $loading = $('#loading1');
		if( $loading.length == 0 ){
			$loading = $('<div id="loading1"/>').appendTo('body');
		}

		$loading.css('zIndex',99000).fadeIn();
	},


	/**
	 * Hide loading overlay
	 *
	 */
	loaded :function(){
		$('#loading1').clearQueue().fadeOut();
	},


	/**
	 * Assign values to the form based on hidden input elements
	 *
	 */
	CopyVals : function(selector,lnk){

		var c = $(selector).find('form').get(0);
		if( c ){
			$(lnk).find('input').each(function(i,j){
				if( c[j.name] ){
					c[j.name].value = j.value;
				}
			});
		}
	},


	/**
	 * Reload the current page
	 * Use window.location.reload(true) to prevent the browser from using the cached page unless it was a post request
	 *
	 */
	Reload : function(){
		if( typeof(req_type) && req_type == 'post' ){
			window.location.href = strip_from(window.location.href,'#');
		}else{
			window.location.reload(true);
		}
	},

	/**
	 * Link handlers
	 *
	 */
	links : {

		/**
		 * Use colorbox
		 *
		 */
		gallery : function(evt,selector){
			evt.preventDefault();
			if( selector === '' ){
				selector = this;
			}else{
				selector = 'a[rel='+selector+'],a.'+selector;
			}

			$.colorbox.remove();
			$(selector).colorbox(
				$gp.cboxSettings({resize:true,rel:selector})
			);

			$(this).trigger('click.cbox');
		}

	}


}


//erase cookie_cmd as soon as possible
$gp.Cookie('cookie_cmd','',-1);



/**
 * Onload
 *
 *
 */
$(function(){

	var $document = $(document);

	//add a class to the body
	//this also affects the display of elements using the req_script css class
	$('body').addClass('STCLASS');



	/**
	 *	Handle AJAX errors
	 *
	 */
	$document.ajaxError(function(event, XMLHttpRequest, ajaxOptions, thrownError){
		$gp.loaded();

		//
		if( XMLHttpRequest.statusText == 'abort' ){
			return;
		}

		// don't use this error handler if another one is set for the ajax request
		if( typeof(ajaxOptions.error) === 'function' ){
			return;
		}

		if( thrownError == '' ){
			return;
		}


		// collect some debug info
		var debug_info = {
			thrownError		: thrownError,
			responseText	: XMLHttpRequest.responseText,
			responseStatus	: XMLHttpRequest.status,
			statusText		: XMLHttpRequest.statusText,
			url				: ajaxOptions.url,
			type			: ajaxOptions.type,
			browser			: navigator.userAgent
		};

		// log everything if possible
		if( window.console && console.log ){
			console.log( debug_info );
		}

		// generic error message
		if( typeof(debugjs) === 'undefined' ){
			alert($gp.error);

		// detailed error
		}else if( debugjs === true && typeof(debug) === 'function' ){
			if( ajaxOptions.data ){
				debug_info.data = ajaxOptions.data.substr(0,100);
			}
			debug( debug_info );

		// send to gpeasy bug tracker
		}else if( debugjs === 'send' ){

			alert($gp.error);

			if( ajaxOptions.data ){
				debug_info.data = ajaxOptions.data;
			}

			debug_info.cmd = 'javascript_error';
			$.ajax({
				type: 'POST',
				url: 'http://www.gpeasy.com/Resources',
				data: debug_info,
				success: function(){},
				error: function(){}
			});
		}

	});



	/**
	 * Handle clicks on forms
	 *
	 */
	$document.on('click', 'input,button',function(evt){

		verify(this.form);
		var $this = $(this);

		//html5 validation
		if( $this.hasClass('gpvalidate') && typeof(this.form.checkValidity) == 'function' && !this.form.checkValidity() ){
			return;
		}

		//confirm prompt
		if( $this.hasClass('gpconfirm') && !confirm(this.title) ){
			evt.preventDefault();
			return;
		}


		//get the first class
		var cmd = $this.data('cmd');
		if( !cmd ){
			cmd = strip_from( $this.attr('class'), ' ' ); //deprecated
		}

		if( typeof($gp.inputs[cmd]) === 'function' ){
			return $gp.inputs[cmd].call(this,evt);
		}

		if( typeof(gpinputs[cmd]) === 'function' ){
			return gpinputs[cmd].call(this,evt,evt);//evt twice so the same function can be used for gplinks and gpinputs
		}


		switch(cmd){
			case 'gppost':
			case 'gpajax':
			evt.preventDefault();
			return $gp.post(this);
		}

		return true;
	});

	$document.on('submit','form',function(){
		verify(this);
	});

	//add a unique verifiable string to confirm posts are
	//called twice because of bug in jquery 1.4.2 (live) and IE
	function verify(a){
		$(a).filter('[method=post]').filter(':not(:has(input[type=hidden][name=verified]))').append('<input type="hidden" name="verified" value="'+post_nonce+'" />');
	}


	//expanding menus
	$document.delegate('.expand_child',{
		'mouseenter': function(){
			var $this = $(this).addClass('expand');
			if( $this.hasClass('simple_top') ){
				$this.addClass('simple_top_hover');
			}
		},
		'mouseleave': function(){
			$(this).removeClass('expand simple_top_hover');
		}
	});


	/**
	 * Handle all clicks on <a> tags
	 * Use of name and rel attributes is deprecated as of gpEasy 3.6
	 *
	 */
	$document.on('click', 'a',function(evt){


		var $this = $(this);
		var cmd = $this.data('cmd');
		var arg = $this.data('arg');
		if( !cmd ){
			// deprecated 3.6
			cmd = $this.attr('name');
			arg = $this.attr('rel');
		}

		if( $this.hasClass('gpconfirm') && !confirm(this.title) ){
			evt.preventDefault();
			return;
		}

		if( typeof($gp.links[cmd]) === 'function' ){
			return $gp.links[cmd].call(this,evt,arg);
		}

		// @deprecated 3.6
		if( typeof(gplinks[cmd]) === 'function' ){
			return gplinks[cmd].call(this,arg,evt);
		}

		switch(cmd){

			case 'toggle_show':
				$(arg).toggle();
			break;

			case 'inline_box':
				$gp.CopyVals(arg,this);
				$.fn.colorbox(
					//$.extend(colorbox_options,{inline:true,href:b, open:true})
					$gp.cboxSettings({inline:true,href:arg, open:true})
				);
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

			default:
			return true;
		}

		evt.preventDefault();
		return false;
	});


	$('body').trigger('gpReady');

});





function strip_to(a,b){
	if( !a ){
		return a;
	}
	var pos = a.indexOf(b);
	if( pos > -1 ){
		return a.substr(pos+1);
	}
	return a;
}

function strip_from(a,b){
	if( !a ){
		return a;
	}
	var p = a.indexOf(b);
	if( p > 0 ){
		a = a.substr(0,p);
	}
	return a;
}






/**
 * @deprecated 3.6
 */
function jPrep(a,b){
	return $gp.jPrep(a,b);
}

function ajaxResponse(data,textStatus,jqXHR){
	return $gp.Response(data,textStatus,jqXHR);
}
function loading(){
	$gp.loading();
}
function loaded(){
	$gp.loaded();
}

