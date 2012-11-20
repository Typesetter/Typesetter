
$(function(){

	var uiOptions = {
		// toolbar configuration
		toolbar : [
			['back', 'forward','up','reload'],
			['home','netmount'],
			['mkdir', 'upload'], //'mkfile',
			['open', 'download', 'getfile'],
			['info'],
			['quicklook'],
			['copy', 'cut', 'paste'],
			['rm'],
			['duplicate', 'rename', 'edit', 'resize'],
			['extract', 'archive'],
			['search'],
			['view','sort'],
			['help']
		],

		// directories tree options
		tree : {
			// expand current root on init
			openRootOnLoad : true,
			// auto load current dir parents
			syncTree : true
		},

		// navbar options
		navbar : {
			minWidth : 150,
			maxWidth : 500
		},

		// current working directory options
		cwd : {
			// display parent directory in listing as ".."
			oldSchool : false
		}
	}

	finder_opts.customData = {verified : post_nonce};

	$.extend(finder_opts,{
		uiOptions : uiOptions
	});


	/**
	 * Start finder for ckeditor
	 *
	 */
	if( finder_opts.getFileCallback ){
		finder_opts.getFileCallback = function(file) {
			var funcNum = getUrlParam('CKEditorFuncNum');
			if( typeof(file) == 'object' ){
				file = file.url;
			}
			window.top.opener.CKEDITOR.tools.callFunction(funcNum, file);
			window.top.close();
			window.top.opener.focus() ;
		};
	var $finder = $('#finder').finder(finder_opts);
	var $window = $(window);

	$window.resize(function(){
		var win_height = $window.height()-10;
		if( $finder.height() != win_height ){
			$finder.height(win_height).resize();
		}
	})

	/**
	 * Start finder for uploaded files manager
	 *
	 */
	}else{
		$('#finder')
			.finder(finder_opts)
			//

			//save the height and width
			.bind('resizestop', function(){
				var $this = $(this);
				gpui.pw = $this.width();
				gpui.ph = $this.height();
				$gp.SaveGPUI();
		});
	}


	/**
	 * Helper function to get parameters from the query string.
	 *  Used by admin_browser/ckeditor
	 *
	 */
	function getUrlParam(paramName) {
		var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
		var match = window.top.location.search.match(reParam) ;

		return (match && match.length > 1) ? match[1] : '' ;
	}
});



/**
 * How to modify CKEditor to open Finder in dialog, not in new browser window?
 * http://elfinder.org/forum/#/20110728/integration-with-ckeditor-759177/
 *
CKEDITOR.on('dialogDefinition', function(event) {
	var editor = event.editor;
	var dialogDefinition = event.data.definition;
	var dialogName = event.data.name;
	var tabCount = dialogDefinition.contents.length;
	for(var i = 0; i < tabCount; i++){
		var browseButton = dialogDefinition.contents[i].get('browse');
		if( browseButton !== null ){
			browseButton.hidden = false;
			browseButton.onClick = function(dialog, i) {
				$('<div \>').dialog({
					modal:true
					,width:"80%"
					,title:'Finder'
					,zIndex: 99999
					,create: function(event, ui) {
						$(this).finder({
							resizable:false
							,//lang:'ru', // Optional
							,url : '/finder/php/connector.php?mode=image'
							,getFileCallback : function(url) {
								if( $('input#cke_118_textInput').is(':visible') ){
									$('input#cke_118_textInput').val(url);
								}else{
									$('input#cke_79_textInput').val(url);
								}
								$('a.ui-dialog-titlebar-close[role="button"]').click()
							}
						})
					}
				})
			}
		}
	}
});
*/
