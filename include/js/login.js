

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
			this.pass_md5.value = hex_sha1(nonce+hex_md5(pwd));
			this.pass_sha.value = hex_sha1(nonce+hex_sha1(pwd));
			this.password.value = '';

			this.user_sha.value = hex_sha1(nonce+this.username.value);
			this.username.value = '';
		}
	});

});
