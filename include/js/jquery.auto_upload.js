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
				//xhr.setRequestHeader("X-File-Name", filename ); // not needed and causes error with unicode filenames
				xhr.setRequestHeader("X-File-Size", file.fileSize);


				// chrome, firefox 4+, IE 10+
				// https://developer.mozilla.org/en/using_xmlhttprequest
				var f = new FormData();

				// add the form elements
				$.each(form_array,function(i,form_input){
					f.append(form_input.name,form_input.value);
				});

				f.append(fieldname, file);
				xhr.send(f);
			}

		}/* end upload() */


		/*
		 * upload handler for older browsers
		 */
		function init_legacy(){

			$input = $(this);
			$form = $(this.form);

			//when changed
			$input.on('change',function(){
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
				$this.on('change.auto_upload', upload);
			}else{
				init_legacy.call(this);
			}


			$this.on('destroy.auto_upload', function() {
				$this.off('.auto_upload');
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
