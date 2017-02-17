

$(function(){

	/**
	 * Start finder for ckeditor
	 *
	 */
	if( finder_opts.getFileCallback && finder_opts.getFileCallback === true ){

		finder_opts.getFileCallback = function(file) {

			if( typeof(file) == 'object' ){
				if(checkNested(finder_opts, 'commandsOptions', 'getfile', 'multiple') && finder_opts.commandsOptions.getfile.multiple== true) {
							file = $.map(file, function(f) { return f.url; });
						} else {
							file = file.url;
					}
			}

			if( typeof(window.top.opener.gp_editor.FinderSelect) == 'function' ){
				window.top.opener.gp_editor.FinderSelect( file );

			}else{
				var funcNum = getUrlParam('CKEditorFuncNum');
				window.top.opener.CKEDITOR.tools.callFunction(funcNum, file);
			}

			window.top.close();
			window.top.opener.focus() ;
		};
	}


	/**
	 * Init finder
	 *
	 */
	var $finder = $('#finder').finder(finder_opts);


	/**
	 * Size to window
	 *
	 */
	var $window = $(window);
	$window.resize(function(){

		var top			= $finder.offset().top;
		var win_height	= $window.height();

		if( parseInt(win_height-top) != $finder.height() ){//prevent too much recursion
			$finder.height(win_height-top).resize();
		}

	}).resize();



	/**
	 * Helper function to get parameters from the query string.
	 *  Used by admin/browser & ckeditor
	 *
	 */
	function getUrlParam(paramName) {
		var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
		var match = window.top.location.search.match(reParam) ;

		return (match && match.length > 1) ? match[1] : '' ;
	}
});

/** check object keys  */
function checkNested(obj /*, level1, level2, ... levelN*/) {
  var args = Array.prototype.slice.call(arguments, 1);

  for (var i = 0; i < args.length; i++) {
    if (!obj || !obj.hasOwnProperty(args[i])) {
      return false;
    }
    obj = obj[args[i]];
  }
  return true;
}


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
