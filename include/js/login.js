

$(function(){
	if( IE_LT_8 ){
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

		for(var i = 0; i < 20; i++ ){
			var shaObj = new jsSHA(pwd,"TEXT");
			pwd = shaObj.getHash("SHA-512", "HEX");
		}

		var shaObj = new jsSHA(nonce+pwd,"TEXT");
		pwd = shaObj.getHash("SHA-512", "HEX");

		return pwd;
	}

});
