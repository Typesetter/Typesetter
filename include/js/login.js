

$(function(){
	// if( typeof(IE_LT_10) != 'undefined' && IE_LT_10 ){
	if( window.navigator.userAgent.match(/(MSIE|Trident)/) ){
		$('#browser_warning').show();
	}
	$('#loginform .login_text').first().find('input').trigger('focus');


	window.setTimeout(function(){
		$('#login_timeout').show();
		$('#login_form').hide();
	},500000);//10 minutes would be 600,000


	//don't send plaintext password if possible
	//send instead md5 and sha1 encrypted strings
	$('#login_form').on('submit', function(){
		if( this.encrypted.checked ){
			var pwd					= this.password.value;
			var nonce				= this.login_nonce.value;
			this.pass_md5.value		= hex_sha1(nonce+hex_md5(pwd));
			this.pass_sha.value		= hex_sha1(nonce+hex_sha1(pwd));
			this.pass_sha512.value	= sha512(pwd);
			this.password.value		= '';

			this.user_sha.value		= hex_sha1(nonce+this.username.value);
			this.username.value		= '';
		}
	});

	function sha512(pwd){

		for(var i = 0; i < 50; i++ ){
			var ints		= pwd.replace(/[a-f]/g,'');
			var salt_start	= parseInt(ints.substr(0,1));
			var salt_len	= parseInt(ints.substr(2,1));
			var salt		= pwd.substr(salt_start,salt_len);
			var shaObj		= new jsSHA(pwd+salt,'TEXT');
			pwd = shaObj.getHash('SHA-512', 'HEX');
		}

		return pwd;
	}

});
