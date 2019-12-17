/**
 * elFinder client options and main script for RequireJS
 *
 * Rename "main.default.js" to "main.js" and edit it if you need configure elFInder options or any things. And use that in elfinder.html.
 * e.g. `<script data-main="./main.js" src="./require.js"></script>`
 **/

// console.log('finder_opts',finder_opts);

define('jquery', [], function() {
    return jQuery;
});

define('jquery-ui', [], function() {});

(function(){
	"use strict";
	var // jQuery and jQueryUI version

		// Detect language (optional)
		// TODO: rather use CMS UI language?
		lang = (function() {
			var locq = window.location.search,
				fullLang, locm, lang;
			if (locq && (locm = locq.match(/lang=([a-zA-Z_-]+)/))) {
				// detection by url query (?lang=xx)
				fullLang = locm[1];
			} else {
				// detection by browser language
				fullLang = (navigator.browserLanguage || navigator.language || navigator.userLanguage);
			}
			lang = fullLang.substr(0,2);
			if (lang === 'pt') lang = 'pt_BR';
			else if (lang === 'ug') lang = 'ug_CN';
			else if (lang === 'zh') lang = (fullLang.substr(0,5).toLowerCase() === 'zh-tw')? 'zh_TW' : 'zh_CN';
			return lang;
		})(),

		// Start elFinder (REQUIRED)
		start = function(elFinder, editors, config) {

			$(function() {
				var optEditors = {
						commandsOptions: {
							edit: {
								editors: Array.isArray(editors)? editors : []
							}
						}
					},
					opts = {};

				// Interpretation of "elFinderConfig"
				if (config && config.managers) {
					$.each(config.managers, function(id, mOpts) {
						opts = Object.assign(opts, config.defaultOpts || {});
						// editors marges to opts.commandOptions.edit
						try {
							mOpts.commandsOptions.edit.editors = mOpts.commandsOptions.edit.editors.concat(editors || []);
						} catch(e) {
							Object.assign(mOpts, optEditors);
						}
						// Make elFinder
						$('#' + id).elfinder(
							// 1st Arg - options
							$.extend(true, { lang: lang }, opts, mOpts || {}),
							// 2nd Arg - before boot up function
							function(fm, extraObj) {
								// `init` event callback function
								fm.bind('init', function() {
									// Optional for Japanese decoder "encoding-japanese"
									if (fm.lang === 'ja') {
										require(
											[ 'encoding-japanese' ],
											function(Encoding) {
												if (Encoding && Encoding.convert) {
													fm.registRawStringDecoder(function(s) {
														return Encoding.convert(s, {to:'UNICODE',type:'string'});
													});
												}
											}
										);
									}
								});
							}
						);
					});
				} else {
					alert('"elFinderConfig" object is wrong.');
				}
			});
		},

		// JavaScript loader (REQUIRED)
		load = function() {
			require(
				[
					'elfinder'
					// , 'js/extras/editors.default.min'             // load text, image editors
					, 'js/extras/editors.custom'             // load text, image editors TODO: use editors.custom.min for production
					, 'elFinderConfig'
				//	, 'extras/quicklook.googledocs.min'          // optional preview for GoogleApps contents on the GoogleDrive volume
				],
				start,
				function(error) {
					alert(error.message);
				}
			);
		},

		// is IE8 or :? for determine the jQuery version to use (optional)
		old = (typeof window.addEventListener === 'undefined' && typeof document.getElementsByClassName === 'undefined')
		       ||
		      (!window.chrome && !document.unqueID && !window.opera && !window.sidebar && 'WebkitAppearance' in document.documentElement.style && document.body.style && typeof document.body.style.webkitFilter === 'undefined');

	// config of RequireJS (REQUIRED)
	require.config({
		//baseUrl : 'js',
		paths : {

			'elfinder' : 'js/elfinder.full', // TODO: for production use 'js/elfinder.full.min',

			// 'encoding-japanese'		: '//cdn.rawgit.com/polygonplanet/encoding.js/1.0.26/encoding.min'
			'encoding-japanese'		: '../encoding.js/encoding.min'
		},
		waitSeconds : 10 // optional
	});

	// check elFinderConfig and fallback
	// This part don't used if you are using elfinder.html, see elfinder.html
	if (! require.defined('elFinderConfig')) {
		define('elFinderConfig', {
			// elFinder options (REQUIRED)
			// Documentation for client options:
			// https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
			defaultOpts : {
				url: finder_opts.url
				,height:'100%'
				,getFileCallback:function(file, finder){

					if (!window.top.opener){
						return finder.exec('quicklook');
					}

					if( typeof(window.top.opener.gp_editor.FinderSelect) == 'function' ){
						window.top.opener.gp_editor.FinderSelect( file.url );

					}else{
						var funcNum = getUrlParam('CKEditorFuncNum');
						window.top.opener.CKEDITOR.tools.callFunction(funcNum, file.url);
					}

					window.top.close();
					window.top.opener.focus() ;
				}

				,customData: finder_opts.customData
				,commandsOptions : {
					edit : {
						extraOptions : {
							// set API key to enable Creative Cloud image editor
							// see https://console.adobe.io/
							creativeCloudApiKey : '',
							// browsing manager URL for CKEditor, TinyMCE
							// uses self location with the empty value
							managerUrl : ''
						}
					}
					,quicklook : {
						// to enable CAD-Files and 3D-Models preview with sharecad.org
						sharecadMimes : ['image/vnd.dwg', 'image/vnd.dxf', 'model/vnd.dwf', 'application/vnd.hp-hpgl', 'application/plt', 'application/step', 'model/iges', 'application/vnd.ms-pki.stl', 'application/sat', 'image/cgm', 'application/x-msmetafile'],
						// to enable preview with Google Docs Viewer
						googleDocsMimes : ['application/pdf', 'image/tiff', 'application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/postscript', 'application/rtf'],
						// to enable preview with Microsoft Office Online Viewer
						// these MIME types override "googleDocsMimes"
						officeOnlineMimes : ['application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation']
					}
				}
			},
			managers : {
				'elfinder': {},
			}
		});
	}

	// load JavaScripts (REQUIRED)
	load();

})();

/** check object keys  */
function checkNested(obj) {
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
 * Helper function to get parameters from the query string.
 *  Used by admin/browser & ckeditor
 *
 */
function getUrlParam(paramName) {
	var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
	var match = window.top.location.search.match(reParam) ;

	return (match && match.length > 1) ? match[1] : '' ;
}
