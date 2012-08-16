/*
 * Use jquery to find newHeight
 * fire resizeEditor once editor is loaded and ready
 * removed unused code
 *
 * Scrollbars appear sometimes when they shouldn't
 * 		Tried setting the iframe scrolling="no" while resizing then setting scrolling="" after, but it seems to break the editor
 *
 */


(function(){
	function resizeEditor( editor ){

		var doc = editor.document,
			currentHeight = editor.window.getViewPaneSize().height,
			currentWidth = editor.window.getViewPaneSize().width,
			newHeight,
			newWidth,
			$body;



		/* my changes
		 * Use jquery to get dimensions
		 */
		$body = $(doc.$.body);
		newHeight = $body.outerHeight(true);
		//newHeight = $body.height();
		newWidth = $(doc.$).width();



		//var db = doc.$.body;
		//var dde = doc.$.documentElement;
		//newHeight = Math.max(db.scrollHeight, dde.scrollHeight, db.offsetHeight, dde.offsetHeight, db.clientHeight, dde.clientHeight)


		/* old
		// We can not use documentElement to calculate the height for IE (#6061).
		// It is not good for IE Quirks, yet using offsetHeight would also not work as expected (#6408).
		if ( CKEDITOR.env.ie ){
			newHeight = doc.getBody().$.scrollHeight + ( CKEDITOR.env.ie && CKEDITOR.env.quirks ? 0 : 24 );
		}else{
			newHeight = doc.getDocumentElement().$.offsetHeight;
		}
		*/

		/* removed max size */

		var min = editor.config.autoGrow_minHeight || 200;
		newHeight = Math.max( newHeight, min );



		// if the width isn't correct it could be because of scrollbars.
		// before the resize happens, scrollbars have probably been added, browsers won't always remove them properly so we check the width
		//if( newWidth > currentWidth ){
			//newHeight += 20;


			//alert('too wide: '+newWidth+ ' vs '+currentWidth);
			//window.status = 'too wide: '+newWidth+ ' vs '+currentWidth+ ' .. '+Math.random();

			//just fix the width... this could prevent the background from displaying properly... which might not be a bad thing.
			//$(doc.$.body).width(currentWidth);

			//fix the width to avoid the side scrollbar then reset it
			// this is the best way to fix the scrollbar problem so aside from suppressing the scrollbar
			//$body.width(currentWidth);
			//window.setTimeout(function(){
				//$body.width('auto');
			//},100);
		//}

		//window.status = 'newHeight: '+newHeight + ' currh: '+currentHeight;

		if ( newHeight != currentHeight ){

			//when suppressing the scrollbar, we need to make sure that we're always at the top
			editor.window.$.scrollTo(0,0);

			newHeight = editor.fire( 'autoGrow', { currentHeight : currentHeight, newHeight : newHeight } ).newHeight;

			editor.resize( editor.container.getStyle( 'width' ), newHeight, true );

		}

	};



	/**
	 * ResizeTextarea() code from: TextAreaExpander plugin for jQuery
	 *
	 * TextAreaExpander plugin for jQuery
	 * v1.0
	 * Expands or contracts a textarea height depending on the
	 * quatity of content entered by the user in the box.
	 *
	 * By Craig Buckler, Optimalworks.net
	 *
	 * As featured on SitePoint.com:
	 * http://www.sitepoint.com/blogs/2009/07/29/build-auto-expanding-textarea-1/
	 *
	 * Please use as you wish at your own risk.
	 */
	function ResizeTextarea(editor){

		var textarea = editor.textarea.$,
			expandMin = editor.config.autoGrow_minHeight || 20,
			//expandMax = editor.config.autoGrow_maxHeight || 10000,
			hCheck = !($.browser.msie || $.browser.opera);


		// find content length and box width
		var vlen = textarea.value.length, ewidth = textarea.offsetWidth;
		if (vlen != textarea.valLength || ewidth != textarea.boxWidth){

			if (hCheck && (vlen < textarea.valLength || ewidth != textarea.boxWidth)) textarea.style.height = "0px";
			//var newheight = Math.max(expandMin, Math.min(textarea.scrollHeight, expandMax));
			var newheight = Math.max(expandMin, textarea.scrollHeight);
			var currheight = $(textarea).height();

			if( newheight == currheight ){
				return;
			}

			textarea.style.overflow = (textarea.scrollHeight > newheight ? "auto" : "hidden");
			textarea.style.height = newheight + "px";

			textarea.valLength = vlen;
			textarea.boxWidth = ewidth;


			$(textarea).closest('.cke_contents').height(newheight);

			//there is an bug affecting google chrome with resize() that removes the focus from the textarea
			//editor.resize( editor.container.getStyle( 'width' ), newheight, true );
		}

		return;
	};


	CKEDITOR.plugins.add( 'gpautogrow',
	{
		init : function( editor ){


			for( var eventName in { contentDom:1, key:1, selectionChange:1, insertElement:1, mode:1 } ){

				editor.on( eventName, function( evt ){

					//don't resize when maximized
					var maximize = editor.getCommand( 'maximize' );
					if( maximize && maximize.state == CKEDITOR.TRISTATE_ON ){
						return;
					}

					if( evt.editor.mode == 'source' ){
						window.setTimeout( function(){ ResizeTextarea( evt.editor ); }, 100 );
					}

					// Some time is required for insertHtml, and it gives other events better performance as well.
					if ( evt.editor.mode == 'wysiwyg' ){
						window.setTimeout( function(){ resizeEditor( evt.editor ); }, 100 );
					}
				});
			}

		}
	});


})();
/**
 * The minimum height to which the editor can reach using AutoGrow.
 * @name CKEDITOR.config.autoGrow_minHeight
 * @type Number
 * @default 200
 * @since 3.4
 * @example
 * config.autoGrow_minHeight = 300;
 */

/**
 * The maximum height to which the editor can reach using AutoGrow. Zero means unlimited.
 * @name CKEDITOR.config.autoGrow_maxHeight
 * @type Number
 * @default 0
 * @since 3.4
 * @example
 * config.autoGrow_maxHeight = 400;
 */

/**
 * Fired when the AutoGrow plugin is about to change the size of the editor.
 * @name CKEDITOR#autogrow
 * @event
 * @param {Number} data.currentHeight The current height of the editor (before the resizing).
 * @param {Number} data.newHeight The new height of the editor (after the resizing). It can be changed
 *				to determine another height to be used instead.
 */
