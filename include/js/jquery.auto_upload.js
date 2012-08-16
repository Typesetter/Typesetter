(function($) {
	jQuery.fn.auto_upload = function(options) {

		var undefined_str = 'undefined';
		var options = $.extend({}, $.fn.auto_upload.defaults, options);
		var supports_multiple_files = MultipleSupported();


		function MultipleSupported(){

		    var input = document.createElement('input');
			input.type = 'file';

			return 'multiple' in input
				&& typeof File != undefined_str
				&& ( typeof FormData !== undefined_str || typeof FileReader !== undefined_str )
				&& typeof XMLHttpRequest != undefined_str
				&& typeof (new XMLHttpRequest()).upload != undefined_str;

		}

		function utf8(str){
			return unescape( encodeURIComponent( str ) );
		}

		/*
		 * upload handler for newer browsers
		 */
		function upload() {

			//this.disabled = true;
			var $input = $(this);
			var fieldname = $input.attr('name');
			var $form = $(this.form);
			var form_array = $form.serializeArray();

			$.each(this.files,function(i,file){
				upload_file(file,i);
			});

			this.form.reset(); //reset the form, so if a user clicks on the same file, it will upload again


			function upload_file(file,number){

				var settings = {};
				var filename = (typeof(file.fileName) != 'undefined' ? file.fileName : file.name);

				if( !options.start(filename, settings) ){
					return;
				}

				var xhr = new XMLHttpRequest();
				xhr.onload = function(load){
					options.finish(xhr.responseText, filename, settings);
				};
				xhr.upload['onprogress'] = function(rpe){
					options.progress(rpe.loaded / rpe.total, filename, settings);
				};
				xhr.onerror = function(error){
					options.error(name, error, settings);
				};


				var method = $form.attr('method');
				var action = $form.attr('action');
				xhr.open(method, action, true);
				xhr.setRequestHeader("Cache-Control", "no-cache");
				xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
				xhr.setRequestHeader("X-File-Name", filename);
				xhr.setRequestHeader("X-File-Size", file.fileSize);


				// chrome, firefox 4+
				// https://developer.mozilla.org/en/using_xmlhttprequest
				if (window.FormData) {
					var f = new FormData();

					/* add the form elements */
					$.each(form_array,function(i,form_input){
						f.append(form_input.name,form_input.value);
					});

					f.append(fieldname, file);
					xhr.send(f);


				// ffox 3.6
				// http://www.openjs.com/articles/ajax_xmlhttp_using_post.php
				}else if (file.getAsBinary){

					var boundary = '------multipartformboundary' + (new Date).getTime();
					xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);

					var dashdash = '--';
					var crlf     = '\r\n';


					/* Build RFC2388 string. */
					/* http://www.w3.org/TR/html401/interact/forms.html#h-17.13.4.2 */
					var builder = '';

					/* add the form elements */
					$.each(form_array,function(i,form_input){
						builder += dashdash + boundary + crlf;
						builder += 'Content-Disposition: form-data; name="'+utf8(form_input.name)+'"';
						builder += crlf + crlf;
						builder += utf8(form_input.value);
						builder += crlf;
					});

					builder += dashdash + boundary + crlf;
					builder += 'Content-Disposition: form-data; name="'+fieldname+'"; filename="' + utf8( filename ) + '"';
					builder += crlf;
					builder += 'Content-Type: application/octet-stream';
					builder += crlf + crlf;

					/* Append binary data. */
					builder += file.getAsBinary();
					builder += crlf;

					/* Write boundary. */
					builder += dashdash + boundary + dashdash;
					builder += crlf;

					xhr.sendAsBinary(builder);
				}

			}

		}/* end upload() */


		/*
		 * upload handler for older browsers
		 */
		function init_legacy(){

			$input = $(this);
			$form = $(this.form);

			//when changed
			$input.bind('change',function(){
				var filename = this.value.toString();
				while(pos = filename.search('\\\\')){
					if( pos == -1){
						break;
					}
					filename = filename.substr(pos+1);
				}
				var settings = {};
				options.start(filename, settings);

				var iframe_id = 'gp_'+Math.round(Math.random()*10000000),
					$iframe = $('<iframe name="'+iframe_id+'" id="'+iframe_id+'" style="display:none"></iframe>').appendTo('body');

				$form.attr('target', iframe_id).one('submit',function(){
					$iframe.one('load',function (){
						var contents = $iframe.contents().find('html').html();
						options.finish(contents, filename, settings);

						setTimeout(function (){
							$iframe.remove();
						},10);
					});
				}).submit();
			});
		}

		return $(this).each(function(){
			$this = $(this);

			if( supports_multiple_files ){
				$this.attr('multiple','multiple');
				$this.bind('change.auto_upload', upload);
			}else{
				init_legacy.call(this);
			}


			$this.bind('destroy.auto_upload', function() {
				$this.unbind('.auto_upload');
			});

		});
	};

	$.fn.auto_upload.defaults = {
		start: function(name, settings) {
			return true;
		},
		progress: function(progress, name, settings) {},
		finish: function(response, name, settings) {},
		error: function(name, error, settings) {}
	};


})(jQuery);
