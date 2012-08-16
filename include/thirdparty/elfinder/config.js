
$(function(){

	var uiOptions = {
		// toolbar configuration
		toolbar : [
			['back', 'forward','reload', 'up'],//'home',
			['mkdir', 'upload'], //'mkfile',
			['open', 'download', 'getfile'],
			['info'],
			['quicklook'],
			['copy', 'cut', 'paste'],
			['rm'],
			['duplicate', 'rename', 'edit', 'resize'],
			//['extract', 'archive'],
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

	$.extend(elfinder_opts,{
		uiOptions : uiOptions
	});


	/**
	 * Start elfinder for ckeditor
	 *
	 */
	if( elfinder_opts.getFileCallback ){
		elfinder_opts.getFileCallback = function(file) {
			var funcNum = getUrlParam('CKEditorFuncNum');
			window.top.opener.CKEDITOR.tools.callFunction(funcNum, file);
			window.top.close();
			window.top.opener.focus() ;
		};
	var $elfinder = $('#elfinder').elfinder(elfinder_opts);
	var $window = $(window);

	$window.resize(function(){
		var win_height = $window.height()-10;
		if( $elfinder.height() != win_height ){
			$elfinder.height(win_height).resize();
		}
	})

	/**
	 * Start elfinder for uploaded files manager
	 *
	 */
	}else{
		$('#elfinder')
			.elfinder(elfinder_opts)
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
 * How to modify CKEditor to open elFinder in dialog, not in new browser window?
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
					,title:'elFinder'
					,zIndex: 99999
					,create: function(event, ui) {
						$(this).elfinder({
							resizable:false
							,//lang:'ru', // Optional
							,url : '/elfinder/php/connector.php?mode=image'
							,getFileCallback : function(url) {
								if( $('input#cke_118_textInput').is(':visible') ){
									$('input#cke_118_textInput').val(url);
								}else{
									$('input#cke_79_textInput').val(url);
								}
								$('a.ui-dialog-titlebar-close[role="button"]').click()
							}
						}).elfinder('instance')
					}
				})
			}
		}
	}
});
*/
