

$(function(){

	if( typeof(IE_LT_8) != 'undefined' && IE_LT_8 ){
		$('#browser_warning').show();
	}
	$('#loginform .login_text:first').focus();


	window.setTimeout(function(){
		$('#login_timeout').show();
		$('#login_form').hide();
	},500000);//10 minutes would be 600,000


	//don't send plaintext password if possible
	//send instead md5 and sha1 encrypted strings
	$('#login_form').submit(function(){
		if( this.encrypted.checked ){
			var pwd = this.password.value;
			var nonce = this.login_nonce.value;
			this.pass_sha.value = hex_sha1(nonce+hex_sha1(pwd));
			this.pass_sha512.value = sha512(pwd,nonce);
			this.password.value = '';

			this.user_sha.value = hex_sha1(nonce+this.username.value);
			this.username.value = '';
		}
	});

	function sha512(pwd,nonce){

		var salt_start = 0;
		var salt_end = 3;
		for(var i = 0; i < 50; i++ ){
			var salt = pwd.substr(salt_start,salt_end);
			var shaObj = new jsSHA(pwd+salt,'TEXT');
			pwd = shaObj.getHash('SHA-512', 'HEX');
			var ints = pwd.replace(/[a-f]/g,'');
			salt_start = ints.substr(0,1);
			salt_end = ints.substr(2,1);
		}

		var shaObj = new jsSHA(nonce+pwd,'TEXT');
		pwd = shaObj.getHash('SHA-512', 'HEX');

		return pwd;
	}

});
