//"use strict";

var gplinks={},gpinputs={},gpresponse={};

/**
 * $gp object
 *
 *
 */
var $gp = {

	inputs : {},
	response : {},
	error : 'There was an error processing the last request. Please reload this page to continue.',
	cookie_cmd : false,

	/**
	 * Handler for loading json content
	 *
	 */
	jGoTo : function(a,this_context){
		$gp.loading();
		a = $gp.jPrep(a);
		$.getJSON(a,function(data,textStatus,jqXHR){
			$gp.Response.call(this_context,data,textStatus,jqXHR);
		});
	},


	/**
	 *  Reload page with arguments (a) set as a cookie
	 *  if samepage is false, then it will take user to a.href
	 *
	 */
	cGoTo : function(a,samepage){

		var $link	= $(a);
		var query	= a.search;
		var nonce	= $link.data('nonce');
		if( nonce ){
			query	+= '&verified='+encodeURIComponent(nonce);
		}

		$gp.SetCookieCmd(query);

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
	post : function(this_context,data){
		$gp.loading();
		var frm = $(this_context).closest('form');

		var b = frm.serialize() + '&verified='+encodeURIComponent(post_nonce); //needed when $gp.post is called without an input click
		if( this_context.nodeName === 'INPUT' || this_context.nodeName === 'BUTTON' ){
			b += '&'+encodeURIComponent(this_context.name)+'='+encodeURIComponent(this_context.value);
		}
		if( data ){
			b += '&'+data;
		}

		$.post(
			$gp.jPrep(frm.attr('action')),
			b,
			function(data,textStatus,jqXHR){
				$gp.Response.call(this_context,data,textStatus,jqXHR);
			},
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
			function(data,textStatus,jqXHR){
				$gp.Response.call(lnk,data,textStatus,jqXHR);
			},
			'json'
			);
	},

	/**
	 * Post content with Typesetter's verified value
	 * Arguments order is same as jQuery's $.post()
	 *
	 */
	postC : function(url,data,callback,datatype,this_context){
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
			function(data,textStatus,jqXHR){
				callback.call(this_context,data,textStatus,jqXHR);
			},
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
	 * Remove cookie command
	 *
	 */
	SetCookieCmd : function(query){

		$gp.Cookie('cookie_cmd',encodeURIComponent(query),1);
		$gp.cookie_cmd = true;
	},


	/**
	 * Prepare a query for an ajax request
	 *
	 */
	jPrep : function(query,args){
		args	= typeof(args) === 'undefined' ? 'gpreq=json&jsoncallback=?' : args;
		query	= strip_from(query,'#');

		if( query.indexOf('?') === -1 ){
			query += '?';
		}else if( query.indexOf('?') !== (query.length -1) ){
			query += '&';
		}

		return query + args;
	},


	/**
	 * Handle ajax responses
	 *
	 */
	Response : function(data,textStatus,jqXHR){

		try{
			if( typeof(gp_editing) == 'undefined' ){
				$gp.CloseAdminBox();
			}
		}catch(a){}

		try{
			$.fn.colorbox.close();
		} catch(a){}


		var this_context = this;

		$.each(data,function(i,obj){

			if( typeof($gp.response[obj.DO]) === 'function' ){
				$gp.response[obj.DO].call(this_context,obj,textStatus,jqXHR);
				return;
			}

			if( typeof(gpresponse[obj.DO]) === 'function' ){
				console.log('gpresponse is deprecated as of 3.6');
				gpresponse[obj.DO].call(this_context,obj,textStatus,jqXHR);
				return;
			}


			switch(obj.DO){
				case 'replace':
					CallFunc( obj.SELECTOR, 'replaceWith', obj.CONTENT);
				break;

				case 'inner':
					CallFunc( obj.SELECTOR, 'html', obj.CONTENT);
				break;

				case 'gpabox':
				case 'admin_box_data': // @deprecated 5.2
					var opts = {};
					if( $(this_context).closest('#gp_admin_box') ){ // replace the content of the currently open admin box if the link the user clicked on was in the admin box
						opts.replaceBox = true;
					}
					$gp.AdminBoxC(obj.CONTENT,opts);
				break;

				case 'messages':
					$('.messages').detach();
					$(obj.CONTENT).appendTo('body').show().css({'top':0});
				break;

				case 'reload':
					$gp.Reload();
				break;

				//standard functions
				default:
					CallFunc(obj.SELECTOR, obj.DO, obj.CONTENT);
				break;
			}
		});

		function CallFunc(sel, func, arg){

			if( sel == 'window' ){
				sel = window;
			}

			var $selected = $(sel);
			if( typeof($selected[func]) == 'function' ){
				$selected[func](arg);
			}else{
				console.log('func not found for sel',sel,'func',func);
			}
		}

		$gp.loaded();
	},


	/**
	 * Display an overlay to indicate loading process
	 *
	 */
	loading : function(){

		var $loading = $('#loading1');
		if( $loading.length == 0 ){
			$loading = $('<div id="loading1"><i class="fa fa-spinner fa-pulse fa-3x"></i></div>').appendTo('body');
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

			var rel = $(this).attr('rel');

			if( selector === '' ){
				selector = this;
			}else if( rel ){
				selector = 'a[rel='+rel+']';
			}else{
				selector = 'a.'+selector;
			}

			var settings = {
				resize	: true,
				title	: function(){
					var a = $(this);
					var caption =
						a.closest('li').find('.caption').data("originalContent")
						|| a.closest('li').find('.caption').text()
						|| a.attr('title') // backwards compat
						|| '';
					return caption;
				}
			};

			if( rel ){
				settings.rel = rel;
			}

			$.colorbox.remove();
			$(selector).colorbox(
				$gp.cboxSettings(settings)
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
	 * Remove cookie_cmd before a new page is loaded
	 * Prevents a cookie_cmd from another browser tab being sent along with a request in the current tab
	 *
	 */
	$(window).on('beforeunload', function(evt) {
		if( !$gp.cookie_cmd ){
			$gp.Cookie('cookie_cmd','',-1);
		}
	});


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
			thrownError		: thrownError
		};

		// add error details
		var detail_keys = ['name','message','fileName','lineNumber','columnNumber','stack'];
		for(var i = 0;i < detail_keys.length;i++){
			if( thrownError.hasOwnProperty(detail_keys[i]) ){
				debug_info[detail_keys[i]] = thrownError[detail_keys[i]];
			}
		}

		//get the location of the error
		if( thrownError.hasOwnProperty('lineNumber') ){
			var num					= thrownError.lineNumber;
			var lines				= XMLHttpRequest.responseText.split('\n');

			debug_info['Line-'+(num-1)]	= lines[num-2];
			debug_info['Line-'+num]		= lines[num-1];
			debug_info['Line-'+(num+1)]	= lines[num];
		}

		debug_info.responseStatus	= XMLHttpRequest.status;
		debug_info.statusText		= XMLHttpRequest.statusText;
		debug_info.url				= ajaxOptions.url;
		debug_info.type				= ajaxOptions.type;
		debug_info.browser			= navigator.userAgent;
		debug_info.responseText		= XMLHttpRequest.responseText;

		if( ajaxOptions.data ){
			debug_info.ajaxdata		= ajaxOptions.data.substr(0,100);
		}

		// log everything if possible
		if( window.console && console.log ){
			console.log( debug_info );
		}

		// send to Typesetter bug tracker
		if( typeof(debugjs) !== 'undefined' && debugjs === 'send' ){

			if( ajaxOptions.data ){
				debug_info.data = ajaxOptions.data;
			}

			debug_info.cmd = 'javascript_error';
			$.ajax({
				type: 'POST',
				url: 'https://www.typesettercms.com/Resources',
				data: debug_info,
				success: function(){},
				error: function(){}
			});
		}

		//display message to user
		if( typeof($gp.AdminBoxC) !== 'undefined' && typeof(JSON) != 'undefined' ){
			delete debug_info.responseText; //otherwise it's too long

			var _debug	= JSON.stringify(debug_info);
			_debug		= b64Encode(_debug);
			_debug		= _debug.replace(/\=/g,'');
			_debug		= _debug.replace(/\+/g,'-').replace(/\//g,'_');
			var url		= 'http://www.typesettercms.com/index.php/Debug?data='+_debug;
			$gp.AdminBoxC('<div class="inline_box"><h3>Error</h3><p>'+$gp.error+'</p><a href="'+url+'" target="_blank">More Info<?a></div>');
		}else{
			alert($gp.error);
		}

	});


	/**
	 * Unicode safe base64 encode
	 *
	 */
	function b64Encode(str) {
		return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
			return String.fromCharCode('0x' + p1);
		}));
	}


	/**
	 * Handle clicks on forms
	 *
	 */
	$document.on('click', 'input,button',function(evt){

		var $this = $(this);

		//add a unique verifiable string to confirm posts are
		$(this.form).filter('[method=post]').filter(':not(:has(input[type=hidden][name=verified]))').append('<input type="hidden" name="verified" value="'+post_nonce+'" />');


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
			console.log('gpinputs is deprecated as of 3.6');
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


	//expanding menus
	$document.on('mouseenter', '.expand_child', function(){
			var $this = $(this).addClass('expand');
			if( $this.hasClass('simple_top') ){
				$this.addClass('simple_top_hover');
			}
		}).on('mouseleave', '.expand_child', function(){
			$(this).removeClass('expand simple_top_hover');
		});


	/**
	 * Handle all clicks on <a> tags
	 * Use of name and rel attributes is deprecated as of gpEasy 3.6
	 *
	 */
	$document.on('click', 'a', function(evt){


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
			console.log('gplinks is deprecated as of 3.6');
			return gplinks[cmd].call(this,arg,evt);
		}

		switch(cmd){

			case 'toggle_show':
				$(arg).toggle();
			break;

			case 'inline_box':
				$gp.CopyVals(arg,this);
				$(this).colorbox(
					$gp.cboxSettings({inline:true,href:arg, open:true})
				);
			break;

			case 'postlink':
				$gp.post_link(this);
			break;

			case 'gpajax':
				$gp.jGoTo(this.href,this);
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
	if( p > -1 ){
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
