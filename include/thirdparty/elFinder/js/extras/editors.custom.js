(function(editors, elFinder) {
	if (typeof define === 'function' && define.amd) {
		define(['elfinder'], editors);
	} else if (elFinder) {
		var optEditors = elFinder.prototype._options.commandsOptions.edit.editors;
		elFinder.prototype._options.commandsOptions.edit.editors = optEditors.concat(editors(elFinder));
	}
}(function(elFinder) {
	"use strict";
	var apps = {},
		// get query of getfile
		getfile = window.location.search.match(/getfile=([a-z]+)/),
		useRequire = elFinder.prototype.hasRequire,
		hasFlash = (function() {
			var hasFlash;
			try {
				hasFlash = !!(new ActiveXObject('ShockwaveFlash.ShockwaveFlash'));
			} catch (e) {
				hasFlash = !!(typeof window.orientation === 'undefined' || (navigator && navigator.mimeTypes["application/x-shockwave-flash"]));
			}
			return hasFlash;
		})(),
		ext2mime = {
			bmp: 'image/x-ms-bmp',
			dng: 'image/x-adobe-dng',
			gif: 'image/gif',
			jpeg: 'image/jpeg',
			jpg: 'image/jpeg',
			pdf: 'application/pdf',
			png: 'image/png',
			ppm: 'image/x-portable-pixmap',
			psd: 'image/vnd.adobe.photoshop',
			pxd: 'image/x-pixlr-data',
			svg: 'image/svg+xml',
			tiff: 'image/tiff',
			webp: 'image/webp',
			xcf: 'image/x-xcf',
			sketch: 'application/x-sketch'
		},
		mime2ext,
		getExtention = function(mime, fm) {
			if (!mime2ext) {
				mime2ext = fm.arrayFlip(ext2mime);
			}
			var ext = mime2ext[mime] || fm.mimeTypes[mime];
			if (ext === 'jpeg') {
				ext = 'jpg';
			}
			return ext;
		},
		changeImageType = function(src, toMime) {
			var dfd = $.Deferred();
			try {
				var canvas = document.createElement('canvas'),
					ctx = canvas.getContext('2d'),
					img = new Image(),
					conv = function() {
						var url = canvas.toDataURL(toMime),
							mime, m;
						if (m = url.match(/^data:([a-z0-9]+\/[a-z0-9.+-]+)/i)) {
							mime = m[1];
						} else {
							mime = '';
						}
						if (mime.toLowerCase() === toMime.toLowerCase()) {
							dfd.resolve(canvas.toDataURL(toMime), canvas);
						} else {
							dfd.reject();
						}
					};

				img.src = src;
				$(img).on('load', function() {
					try {
						canvas.width = img.width;
						canvas.height = img.height;
						ctx.drawImage(img, 0, 0);
						conv();
					} catch(e) {
						dfd.reject();
					}
				}).on('error', function () {
					dfd.reject();
				});
				return dfd;
			} catch(e) {
				return dfd.reject();
			}
		},
		initImgTag = function(id, file, content, fm) {
			var node = $(this).children('img:first').data('ext', getExtention(file.mime, fm)),
				spnr = $('<div class="elfinder-edit-spinner elfinder-edit-image"/>')
					.html('<span class="elfinder-spinner-text">' + fm.i18n('ntfloadimg') + '</span><span class="elfinder-spinner"/>')
					.hide()
					.appendTo(this),
				url;
			
			if (!content.match(/^data:/)) {
				url = fm.openUrl(file.hash);
				node.attr('_src', content);
			}
			node.attr('id', id+'-img')
				.attr('src', url || content)
				.css({'height':'', 'max-width':'100%', 'max-height':'100%', 'cursor':'pointer'})
				.data('loading', function(done) {
					var btns = node.closest('.elfinder-dialog').find('button,.elfinder-titlebar-button');
					btns.prop('disabled', !done)[done? 'removeClass' : 'addClass']('ui-state-disabled');
					node.css('opacity', done? '' : '0.3');
					spnr[done? 'hide' : 'show']();
					return node;
				});
		},
		imgBase64 = function(node, mime) {
			var style = node.attr('style'),
				img, canvas, ctx, data;
			try {
				// reset css for getting image size
				node.attr('style', '');
				// img node
				img = node.get(0);
				// New Canvas
				canvas = document.createElement('canvas');
				canvas.width  = img.width;
				canvas.height = img.height;
				// restore css
				node.attr('style', style);
				// Draw Image
				canvas.getContext('2d').drawImage(img, 0, 0);
				// To Base64
				data = canvas.toDataURL(mime);
			} catch(e) {
				data = node.attr('src');
			}
			return data;
		};
	
	// check getfile callback function
	if (getfile) {
		getfile = getfile[1];
		if (getfile === 'ckeditor') {
			elFinder.prototype._options.getFileCallback = function(file, fm) {
				window.opener.CKEDITOR.tools.callFunction((function() {
					var reParam = new RegExp('(?:[\?&]|&amp;)CKEditorFuncNum=([^&]+)', 'i'),
						match = window.location.search.match(reParam);
					return (match && match.length > 1) ? match[1] : '';
				})(), fm.convAbsUrl(file.url));
				fm.destroy();
				window.close();
			};
		}
	}
	
	// return editors Array
	return [
		{
			// Zip Archive with FlySystem
			info : {
				id : 'ziparchive',
				name : 'btnMount',
				iconImg : 'img/toolbar.png 0 -416',
				cmdCheck : 'ZipArchive',
				edit : function(file, editor) {
					var fm = this,
						dfrd = $.Deferred();
					fm.request({
						data:{
							cmd: 'netmount',
							protocol: 'ziparchive',
							host: file.hash,
							path: file.phash
						},
						notify : {type : 'netmount', cnt : 1, hideCnt : true}
					}).done(function(data) {
						var pdir;
						if (data.added && data.added.length) {
							if (data.added[0].phash) {
								if (pdir = fm.file(data.added[0].phash)) {
									if (! pdir.dirs) {
										pdir.dirs = 1;
										fm.change({ changed: [ pdir ] });
									}
								}
							}
							fm.one('netmountdone', function() {
								fm.exec('open', data.added[0].hash);
								fm.one('opendone', function() {
									data.toast && fm.toast(data.toast);
								});
							});
						}
						dfrd.resolve();
					})
					.fail(function(error) {
						dfrd.reject(error);
					});
					return dfrd;
				}
			},
			mimes : ['application/zip'],
			load : function() {},
			save : function(){}
		},
		{
			// Simple Text (basic textarea editor)
			info : {
				id : 'textarea',
				name : 'TextArea',
				useTextAreaEvent : true
			},
			load : function(textarea) {
				// trigger event 'editEditorPrepare'
				this.trigger('Prepare', {
					node: textarea,
					editorObj: void(0),
					instance: void(0),
					opts: {}
				});
				textarea.setSelectionRange && textarea.setSelectionRange(0, 0);
				$(textarea).trigger('focus').show();
			},
			save : function(){}
		}
	];
}, window.elFinder));
