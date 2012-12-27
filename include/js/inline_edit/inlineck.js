
/*
 * attempting better ckeditor integration
 * 		Get full WYSIWYG editing by assigning the appropriate class to the ckeditor.
 * 		Dynamic ckeditor creation instead of reloading page
 *
 *
 * Full WYSIWYG
 * 		http://drupal.ckeditor.com/tricks
 * 		http://cksource.com/forums/viewtopic.php?t=17023
 *
 * 	Autogrow
 * 		http://cksource.com/forums/viewtopic.php?t=14178
 *
 *
 *
 */


	$gp.InitCKInline = function(){

		CKEDITOR.on( 'instanceCreated', function( evt ){
			var css;
			if( gp_ckconfig.bodyId ){
				css = 'html,body,#'+gp_ckconfig.bodyId;
			}else{
				css = 'html,body';
			}
			css += '{';

			//the 1px padding seems to help jquery with the height calculation (both chrome and firefox)
			css += 'width:100%;display:block !important;margin:-1px 0 0 -1px !important;padding:1px 0 0 1px !important;top:0 !important;left:0 !important;right:0 !important;bottom:0 !important;height:auto !important;float:none !important;min-width:0 !important;';
			css += 'border:0 none !important;border-radius: 0 !important;-moz-border-radius:0 !important;-o-border-radius:0 !important-webkit-border-radius:0 !important';
			css += '}';

			//css for autogrow
			//scroll in the body, not the iframe
			css += 'html{overflow:hidden}';
			css += 'body{overflow-x:auto;overflow-y:hidden;}';

			evt.editor.addCss(css);
		});

		//hack for editor width in IE
		// .cke_editor is a <table> and when inside a floated div with width:100% in IE, the table will not have the correct width
		CKEDITOR.on( 'instanceReady', function( evt ){
			$('.cke_editor').each(function(){
				var $this = $(this);
				if( $this.find('iframe').length == 0 ){
					return;
				}
				var this_width = $this.css('width');
				var parwidth = $this.parent().css('width');

				if( this_width < parwidth ){
					$this.css('width',parwidth);
				}
			});

		});

		//gp_ckconfig
		(function(config){
			config.toolbarCanCollapse = false;
			config.autoGrow_minHeight = 10;
			config.resize_enabled=false;
			config.sharedSpaces={top:'ckeditor_top',bottom:'ckeditor_bottom'};
			config.toolbar='inline';
			config.extraPlugins='gpautogrow';
			config.FillEmptyBlocks = false;

			//config.protectedSource = [/\{\{[^\]]+\}\}/g];   // includes
			//config.enterMode = CKEDITOR.ENTER_BR;
			//config.useComputedState = true;
		})(gp_ckconfig);


		$.each(gp_styles,function(i,style){
			$(style.selector).addClass('gp_style_parent').data('gp_style',style);
		});
	}


	function gp_init_inline_edit(area_id,section_object){

		var save_path = gp_editing.get_path(area_id);
		var edit_div = gp_editing.get_edit_area(area_id);
		if( edit_div == false || save_path == false ){
			return;
		}

		if( typeof( $gp.InitCKInline ) == 'function' ){
			$gp.InitCKInline();
			delete $gp.InitCKInline;
		}
		edit_div.parent().addClass('inline_editor'); //for ckeditor css

		gp_editing.editor_tools();


		//style?
		var editor_style = edit_div.closest('.gp_style_parent').data('gp_style');
		if( editor_style ){
			gp_ckconfig.bodyId = editor_style.bodyId;
			gp_ckconfig.bodyClass = editor_style.bodyClass;
			//gp_ckconfig.contentsCss = [$('#theme_stylesheet').attr('href'),gpBase+'/include/js/inline_edit/x_inlineck.css'];
			gp_ckconfig.contentsCss = $('#theme_stylesheet').attr('href');

			//set the height before
			gp_ckconfig.height = Math.max(10,edit_div.height()); //needed for small areas

		}else{
			gp_ckconfig.bodyId=false;
			gp_ckconfig.bodyClass=false;
			gp_ckconfig.contentsCss=gpBase+'/include/css/ckeditor_contents.css';
		}

		var inner = edit_div.get(0);

		inner.innerHTML = section_object.content;

		//replace resized image paths
		/*
		if( section_object.resized_imgs ){
			$.each(section_object.resized_imgs,function(resized_path,original_path){
				edit_div.find('img').each(function(){
					alert(this.src +"\n vs \n"+resized_path);
					if( this.src == resized_path ){
						this.src = original_path;
						alert('found');
					}
				});
			});
		}
		*/

		//edit_div.html(data); //we lose things like <script>
		//gp_ckconfig.Value = section_object.content; //this method executes javascript so shouldn't be used
		gp_editor = CKEDITOR.replace( inner, gp_ckconfig);
		gp_editor.save_path = save_path;

		gp_editor.gp_saveData = function(){
			var data = gp_editor.getData();
			return 'gpcontent='+encodeURIComponent(data);
		}
		$gp.loaded();
	}
