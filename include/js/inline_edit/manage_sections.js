
(function(){

	var preview_timeout = null;

	/**
	 * Set up editor
	 *
	 */
	gp_editor = {

		save_path: '?',

		saved_data: '',

		allowAutoSave: true,

		CanAutoSave: function(){ 
			return gp_editor.allowAutoSave || true; 
		},

		checkDirty: function(){
			var curr_data	= this.SaveData();
			if( this.saved_data != curr_data ){
				return true;
			}
			return false;
		},

		/**
		 * Organize the section content to be saved
		 *
		 */
		SaveData: function(){

			var mgr_object				= this;
			var args					= {};
			args.section_order			= [];
			args.attributes				= [];
			args.contains_sections		= [];
			args.gp_label				= [];
			args.gp_color				= [];
			args.gp_collapse			= [];
			args.gp_hidden				= [];
			args.cmd					= 'SaveSections';

			$('#gpx_content.gp_page_display').find('.editable_area').each(function(i){

				//new section order and new sections
				var $this	= $(this);
				var type	= gp_editing.TypeFromClass(this);
				var value	= $this.data('gp-section');

				if( !type ){
					return;
				}

				if( typeof(value) == 'undefined' ){
					value = type;
				}

				args.section_order.push(value);

				//attributes
				args.attributes[i]		= $this.data('gp-attrs');

				//wrappers
				if( type == 'wrapper_section' ){
					args.contains_sections[i] = $this.children('.editable_area').length;
				}

				//label
				args.gp_label[i]		= $this.data('gp_label');

				//color
				args.gp_color[i]		= $this.data('gp_color');

				//collapse
				args.gp_collapse[i]		= $this.data('gp_collapse');

				//hidden
				args.gp_hidden[i]		= $this.data('gp_hidden');

			});

			/*
			 * FIX for too many sections issue
			 *
			 * with large amounts of sections, we may exceed max_post_values
			 * which causes an error and prevents further editing of the page.
			 *
			 * sending all the section data in a single JSON string 
			 * instead of parametrizing all values will address this issue.
			 *
			 * the current implementation should be considered as a hot fix.
			 * it should eventually be done more elegant.
			 *
			 * See its server side counterpart in /include/Page/Edit.php line 490-519
			 */

			// console.log('manage_sections -> SaveData -> args = ', args);
			var json_encoded = JSON.stringify(args, function(key, value){
				// make sure every value is a string
				switch( value ){
					case null:
					case undefined:
						value = "";
						break;

					case true:
						value = "true";
						break;

					case false:
						value = "false";
						break;

					default:
						if( $.isNumeric(value) ){
							value = "" + value;
						}
						break;
				}
				return value;
			});
			// console.log('manage_sections -> SaveData -> json_encoded = ' + json_encoded);
			return 'cmd=SaveSections&sections_json=' + encodeURIComponent(json_encoded);

			/*
			 * FIX for too many sections issue
			 * 
			 */
			 // return $.param(args);
		},


		/**
		 * Object of enqueued functions to be executed after saving is done
		 * called from gp_editor.resetDirty() which is called in the ck_saved Ajax response
		 */
		AfterSave: {
			// testing 
			/*
			 test : { 
				fnc : function(){ 
					console.log( 'Function "gp_editor.AfterSave.' , this , '" executed with \n arguments = ', arguments ); 
					return 'done';
				},
				arg : 'test argument',
				callback : function(ret_val){ // called after fnc was executed with fnc's return as argument
					console.log( 'Callback function executed with ret_val = ', ret_val,
						', returned from main functionand passed as argument'); 
				}, 
				destroy : true // true will delete the enqueued function after execution
			}
			*/
		},


		/**
		 * Called when saved
		 *
		 */
		resetDirty: function(){
			gp_editor.SectionNumbers();
			this.saved_data	= this.SaveData();

			// console.log('Start executing gp_editor.AfterSave = ', gp_editor.AfterSave);
			$.each(gp_editor.AfterSave, function(i, v){
				if( typeof(v.fnc) == 'function' ){
					var return_val = v.fnc(v.arg);
					// console.log('gp_editor.AfterSave.' , i , ' returns ', return_val);
					if( typeof(v.callback) == 'function' ){
						v.callback(return_val);
					}
				}
				if( v.destroy ){
					// console.log('gp_editor.AfterSave.' + i + ' deleted');
					delete( gp_editor.AfterSave[i] );
				}
			});
			// console.log('Done executing gp_editor.AfterSave = ', gp_editor.AfterSave);
		},


		/**
		 * Update Section #s
		 *
		 */
		SectionNumbers: function(){

			$('#gpx_content.gp_page_display').find('.editable_area').each( function(i){
				var $this		= $(this);

				$this.data('gp-section',i).attr('data-gp-section', i);

				var area_id		= $gp.AreaId( $this );
				var href		= $('#ExtraEditLink'+area_id).attr('href') || '';

				href = href.replace(/section\=[0-9] + /, '');
				href = $gp.jPrep(href, 'section=' + i);

				$('#ExtraEditLink'+area_id).attr('href', href);
			});

		},


		/**
		 * Save Section(s) to clipboard
		 *
		 */
		SaveToClipboard: function(area_id){

			// console.log("arguments = " , arguments);
			// console.log("SaveToClipboard called with area_id = ", area_id);
			var $li = $('#section_sorting li[data-gp-area-id="' + area_id + '"]');
			if( !$li.length ){
				console.log('SaveToClipboard Error: section_sorting li[data-gp-area-id="' + area_id + '"] does not exist!');
				return;
			}
			var $area			= gp_editor.GetArea( $li );
			var section_number	= $area.attr('data-gp-section');

			var data = {
				cmd				: 'SaveToClipboard',
				section_number	: section_number
			};
			// console.log("SectionToClipboard", data);
			data = $.param(data);
			$gp.postC(window.location.href, data);
			loading();
		},


		/**
		 * Append the clicked Clipboard Section(s) to the content
		 *
		 */
		SectionFromClipboard: function(item_index){

			// console.log("SectionFromClipboard: item_index = " + item_index);

			var $link = $('#section-clipboard-items '
				+ 'li[data-item_index="' + item_index + '"] '
				+ 'a.preview_section');

			// console.log("SectionFromClipboard: $link = " , $link);

			//remove preview
			$link.removeClass('previewing');
			$('.temporary-section').remove();

			// console.log("$section = " , $link.data('response'));

			// append section(s) content client side
			var $section = $($link.data('response'))
				.appendTo('#gpx_content');

			NewSectionIds($section);

			$section.trigger('SectionAdded');

			gp_editor.InitSorting();
			$link.trigger('mousemove');

			gp_editor.resetDirty(); 

			// append section(s) server side
			var data = {
				cmd			: 'AddFromClipboard',
				item_number	: item_index
			};

			// console.log("AddFromClipboard", data);
			data = $.param(data);
			$gp.postC(window.location.href, data);
			loading();
		},


		/**
		 * Save new Clipboard Items order
		 *
		 */
		ClipboardSorted: function(){

			var $items = $('#section-clipboard-items > li');
			if( !$items.length ){
				return;
			}
			var new_order = [];
			$items.each(function(i,v){
				new_order[i] = $(this).attr("data-item_index");
			});

			var data = {
				cmd		: 'ReorderClipboardItems',
				order	: new_order
			};
			data = $.param(data);
			$gp.postC(window.location.href, data);
			loading();
		},


		/**
		 * Init Section Clipboard
		 *
		 */
		InitClipboard: function(){

			// Uninstall Plugin Warning
			if( typeof(SectionClipboard_links) != 'undefined' ){
				alert(
					  'Warning \n\nThe SectionClipboard plugin seems to be installed.\n'
					+ 'This feature is now built into Typesetter.\n\n'
					+ 'Please uninstall the plugin now!'
				);
			}

			var mgr_object	= this;

			if( $('#section-clipboard-items li').length > 1 ){
				$('#section-clipboard-items').sortable({
					cancel:			'li.clipboard-empty-msg',
					distance:		4,
					tolerance:		'pointer', 
					update:			mgr_object.ClipboardSorted,
					cursorAt:		{ left: 6, top: 5 }
				}).disableSelection();
			}

			$(document).trigger("section_clipboard:loaded");
		},



		/**
		 * Init Editor
		 *
		 */
		InitEditor: function(){

			$('#ckeditor_top').append(section_types);
			this.InitSorting();
			this.InitClipboard();
			this.resetDirty();

			$gp.$win.on('resize', this.MaxHeight).trigger('resize');

			$('#ckeditor_area').on('dragstop', this.MaxHeight);

			$gp.response.clipboard_init = this.InitClipboard;

			$(document).trigger("section_sorting:loaded");
		},


		/**
		 * Wake up the editor
		 *
		 */
		wake: function(){
			AddEditableLinks();
		},


		/**
		 * Set maximum height of editor
		 *
		MaxHeight: function(){
			var $ckeditor_area	= $('#ckeditor_area');
			var $section_area	= $('#ckeditor_top').css('overflow','hidden'); //attempt to get rid of the scroll bar
			var listMaxHeight	= $gp.$win.height() - $ckeditor_area.offset().top - $ckeditor_area.height() + $section_area.height() + $gp.$win.scrollTop();

			$section_area.css('overflow','auto');
			$section_area.css( 'max-height', listMaxHeight );
			console.log('max height',listMaxHeight);
		},
		 */


		/**
		 * Initialize section sorting
		 * This data may be sent from the server
		 *
		 */
		InitSorting: function(){

			var mgr_object	= this;
			var $list		= $('#section_sorting').html('');
			var html		= this.BuildSortHtml( $('#gpx_content.gp_page_display') );

			$list.html(html);

			$('.section_drag_area').sortable({
				distance :		4,
				tolerance :		'pointer', /** otherwise sorting elements into collapsed area causes problems */
				stop :			function(evt, ui){
									mgr_object.DragStop(evt, ui);
								},
				connectWith :	'.section_drag_area',
				cursorAt :		{ left: 7, top: 7 }

			}).disableSelection();

			this.HoverListener($list);
		},


		/**
		 * Build sort html
		 *
		 */
		BuildSortHtml: function( $container ){

			var html = '';
			var mgr_object	= this;

			$container.children('.editable_area').each( function(){

				var $this	= $(this);

				if( !this.id ){
					this.id = mgr_object.GenerateId();
				}

				//type
				var type	= gp_editing.TypeFromClass(this);

				//area_id
				var area_id = $gp.AreaId($this);

				//label
				var label	= gp_editing.SectionLabel($this);

				//hidden
				var is_hidden	= $this.data('gp_hidden');

				//color
				var color	= $this.data('gp_color') || '#aabbcc';

				//collapsed
				var style	= ' class="' 
					+ ($this.data('gp_collapse') || '') 
					+ (is_hidden ? ' gp-section-hidden' : '') 
					+ '" ';

				//attrs
				var attrs	= $this.data('gp-attrs');

				//classes
				var classes	= attrs.class || label;


				// highlight sections in editor
				$this.on("mouseenter", function(){
					$('li[data-gp-area-id="' + area_id + '"]').addClass('section-sorting-highlight');
				}).on("mouseleave", function(){
					$('li[data-gp-area-id="' + area_id + '"]').removeClass('section-sorting-highlight');
				});


				html += '<li data-gp-area-id="' + area_id + '" ' + style + ' title="' + classes + '">';
				html += '<div><a class="color_handle" data-cmd="SectionColor" style="background-color:' + color + '"></a>';
				html += '<span class="options">';

				if( !$this.hasClass('filetype-wrapper_section') ){
					html += '<a class="fa fa-pencil" data-cmd="SectionEdit" title="' + gplang.edit + '"></a>';
				}

				html += '<a class="fa fa-sliders" data-cmd="SectionOptions" title="' + gplang.options + '"></a>';
				html += '<a class="fa fa-files-o" data-cmd="CopySection" title="' + gplang.Copy + '"></a>';
				html += '<a class="fa fa-clipboard" data-cmd="SectionToClipboard" title="' + gplang.CopyToClipboard + '"></a>';

				var vis_icon_class = is_hidden ? 'fa-eye' : 'fa-eye-slash';
				html += '<a class="fa ' + vis_icon_class + ' ShowHideSection" data-cmd="ShowHideSection" title="' + gplang.Visibility + '"></a>';

				html += '<a class="fa fa-trash RemoveSection" data-cmd="ConfirmDeleteSection" title="' + gplang.remove + '"></a>';
				html += '</span>';
				html += '<i class="section_label_wrap">';

				//wrapper collapse link
				if( type == 'wrapper_section' ){
					html += '<a data-cmd="WrapperToggle" class="secsort_wrapper_toggle"/>';
				}

				html += '<span class="section_label">' + label + '</span>';
				html += '</i>';
				html += '</div>';

				if( $this.hasClass('filetype-wrapper_section') ){
					html += '<ul class="section_drag_area">';
					html += mgr_object.BuildSortHtml( $this );
					html += '</ul>';
				}

				html += '</li>';
			});

			return html;
		},


		GenerateId: function(){

			var uniqid;

			do{
				var randLetter	= String.fromCharCode(65 + Math.floor(Math.random() * 26));
				uniqid			= randLetter + Date.now();
			}while( document.getElementById(uniqid) );

			return uniqid;
		},


		/**
		 * Move the content area after it has been moved in the editor
		 *
		 */
		DragStop: function(event, ui){

			var $area		= this.GetArea(ui.item);
			var $prev_area	= this.GetArea(ui.item.prev());

			//moved after another section
			if( $prev_area.length ){
				$area.insertAfter($prev_area).trigger('SectionSorted');

				// trigger immediate save
				// console.log('immediate save');
				gp_editing.SaveChanges();
				return;
			}

			//move to beginning of gpx_content
			var $ul			= ui.item.parent().closest('ul');
			if( $ul.attr('id') == 'section_sorting' ){
				$area.prependTo('#gpx_content').trigger('SectionSorted');

				// trigger immediate save
				// console.log('immediate save');
				gp_editing.SaveChanges();
				return;
			}

			//moved to beginning of wrapper
			this.GetArea($ul.parent()).prepend($area);
			$area.trigger('SectionSorted');

			// trigger immediate save
			// console.log('immediate save');
			gp_editing.SaveChanges();
		},


		/**
		 * Setup Hover Listenter
		 *
		 */
		HoverListener: function($list){

			var mgr_object = this;

			$list.find('div').on('mouseenter', function(){ // get rid of jQ 1.9 deprecated 'hover' event

				var $this = $(this).parent();
				var $area = mgr_object.GetArea($this);

				/*
				scrollto_section_timeout = setTimeout(function(){
					//scroll the page
					var top		= $area.offset().top - 200;
					$('html,body').stop().animate({scrollTop: top});
				}, 1200);
				*/


				$('.section-item-hover').removeClass('section-item-hover');
				$this.addClass('section-item-hover');

				$('.section-highlight').removeClass('section-highlight');
				$area.addClass('section-highlight');

			}).on('mouseleave', function(){

				var $this = $(this).parent();
				var $area = mgr_object.GetArea($this);

				/*
				if( scrollto_section_timeout ){
					clearTimeout(scrollto_section_timeout);
				}
				*/

				$area.removeClass('section-highlight');
				$this.removeClass('section-item-hover');
			});
		},


		/**
		 * Get an editable area from
		 *
		 */
		GetArea: function($li){
			var id = $gp.AreaId( $li );
			return $('#ExtraEditArea' + id);
		}

	}; /* /gp_editor */



	/**
	 * Preview new section
	 *
	 */
	$(document).on('mousemove', '.preview_section', function(){
		var $this = $(this);

		if( preview_timeout ){
			clearTimeout(preview_timeout);
		}

		if( $this.hasClass('previewing') ){
			return;
		}

		//remove other preview
		$('.previewing').removeClass('previewing');
		$('.temporary-section').stop().slideUp(function(){
			$(this).remove();
		});

		preview_timeout = setTimeout(function(){

			//scroll the page
			var $last	= $('#gpx_content .editable_area').last();
			var top		= $last.offset().top + $last.height() - 200;
			$('html, body').stop().animate({scrollTop: top});

			//begin new preview
			$this.addClass('previewing');

			var $new_content	= $($this.data('response'));

			$new_content
				.find('.editable_area')
				.addClass('temporary-section')
				.removeClass('editable_area');

			$new_content
				.addClass('temporary-section')
				.removeClass('editable_area')
				.appendTo('#gpx_content')
				.hide()
				.delay(300).slideDown()
				.trigger("PreviewAdded");

			var node = $new_content.get(0);
			$this.data('preview-section', node);

		}, 200);

	}).on('mouseleave', '.preview_section', function(){

		if( preview_timeout ){
			clearTimeout(preview_timeout);
		}

		$(this).removeClass('previewing');

		$('.temporary-section').stop().slideUp(function(){
			$(this).parent().trigger("PreviewRemoved");
			$(this).remove();
		});
	});


	/**
	 * Handle new section clicks
	 *
	 */
	$gp.links.AddSection = function(evt){
		var $this = $(this);

		evt.preventDefault();

		//remove preview
		$this.removeClass('previewing');
		$('.temporary-section').remove();

		//new content
		var $section = $($this.data('response')).appendTo('#gpx_content');

		NewSectionIds($section);

		$section.trigger('SectionAdded');

		gp_editor.InitSorting();
		$this.removeClass('previewing').trigger('mousemove');

		// trigger immediate save
		// console.log('immediate save');
		gp_editing.SaveChanges();
	};


	/**
	 * Handle Clipboard Item clicks
	 *
	 */
	$gp.links.AddFromClipboard = function(evt){

		var item_index	= $(this).closest('li').attr('data-item_index');
		var is_dirty	= gp_editor.checkDirty();
		if( is_dirty ){
			// inhibit auto-save while doing instant saving to prevent timing conflicts
			gp_editor.allowAutoSave = false;
			// enqueue SectionFromClipboard after Save
			gp_editor.AfterSave.SectionFromClipboardEnqueued = {
				fnc: gp_editor.SectionFromClipboard,
				arg: item_index,
				callback: function(ret){
					// re-enable auto-save in the callback
					gp_editor.allowAutoSave = true; 
				},
				// destroy the enqueued function after execution
				destroy: true 
			};
			// trigger save
			gp_editing.SaveChanges();
			return;
		}
		gp_editor.SectionFromClipboard(item_index);
	};


	/**
	 * Set Section Visibility
	 *
	 */
	$gp.links.ShowHideSection = function(evt){
		var $li = $(this).closest('li');
		var $area = gp_editor.GetArea($li);
		var is_hidden = $area.data('gp_hidden');

		if( is_hidden ){

			$(this)
				.removeClass("fa-eye")
				.addClass("fa-eye-slash");
			$li.removeClass("gp-section-hidden");

			$area
				.attr('data-gp_hidden', false)
				.data('gp_hidden', false)
				.hide().slideDown(150, function(){
					// trigger immediate save
					// console.log('immediate save');
					gp_editing.SaveChanges();
				});

		}else{

			$(this)
				.removeClass("fa-eye-slash")
				.addClass("fa-eye");
			$li.addClass("gp-section-hidden");

			$area.slideUp(150, function(){
				$area
					.attr('data-gp_hidden', true)
					.data('gp_hidden', true);

				// trigger immediate save
				// console.log('immediate save');
				gp_editing.SaveChanges();
			});
		}
	};


	/**
	 * Confirm to delete a section
	 *
	 */
	$gp.links.ConfirmDeleteSection = function(evt){
		var $this = $(this);
		var $li = $this.closest('li');
		var area_id = $li.attr("data-gp-area-id");

		if( evt.ctrlKey ){
			$gp.DeleteSection(area_id);
			return;
		}

		var html = '<div class="inline_box">';
		html += '<h2>' + gplang.del + '</h2>';
		html += '<p>';
		html += gplang.generic_delete_confirm.replace(
			'%s', '<strong>' + $li.find(".section_label").first().text() + '</strong>'
		); // gplang.Section.replace('%s','') + 
		html += '<br/><br/></p><p>';
		html += '<a class="gpsubmit" onClick="$gp.DeleteSection(' + area_id + ')">' + gplang.del + '</a>';
		html += '<a class="gpcancel" data-cmd="admin_box_close">' + gplang.ca + '</a>';
		html += '</p>';
		html += '</div>';
		$gp.AdminBoxC(html);
	};


	/**
	 * Delete a section
	 *
	 */
	$gp.DeleteSection = function(area_id){
		//make sure there's at least one section
		if( $('#gpx_content').find('.editable_area').length > 1 ){
			var $li = $('li[data-gp-area-id="' + area_id + '"]');
			if( !$li.length ){
				return;
			}
			var area = gp_editor.GetArea( $li );
			area.parent().trigger("SectionRemoved");
			area.remove();
			$li.remove();
		}
		$gp.CloseAdminBox();

		// trigger immediate save
		// console.log('immediate save');
		gp_editing.SaveChanges();
	};


	/**
	 * Remove a section
	 * Deletes a section without confirmation, deprecated as of v5.1
	 */
	$gp.links.RemoveSection = function(evt){
		//make sure there's at least one section
		if( $('#gpx_content').find('.editable_area').length > 1 ){
			var $li = $(this).closest('li');
			var $area = gp_editor.GetArea( $li );

			$area.parent().trigger("SectionRemoved");
			$area.remove();
			$li.remove();

			// trigger immediate save
			// console.log('immediate save');
			gp_editing.SaveChanges();
		}
	};


	/**
	 * Copy selected section
	 *
	 */
	$gp.links.CopySection = function(evt){
		var from_area	= gp_editor.GetArea( $(this).closest('li') );
		var new_area	= from_area.clone();

		NewSectionIds(new_area);
		from_area.after(new_area);
		new_area.trigger("SectionCopied");
		new_area.trigger("SectionAdded");
		gp_editor.InitSorting();

		// trigger immediate save
		// console.log('immediate save');
		gp_editing.SaveChanges();
	};


	/**
	 * Save selected section to the Section Clipboard
	 * We need to make sure that sections are saved
	 * Trigger SaveChanges() if required and enqueue SaveToClipboard to be executed in the ck_saved -> resetDity() callback 
	 */
	$gp.links.SectionToClipboard = function(evt){
		var area_id		= $(this).closest('li').data('gp-area-id');
		// console.log("SectionToClipboard: area_id = " + area_id);
		var is_dirty	= gp_editor.checkDirty();
		if( is_dirty ){
			// inhibit auto-save while doing instant saving to prevent timing conflicts
			gp_editor.allowAutoSave = false;
			// enqueue SectionToClipboard after Save
			gp_editor.AfterSave.SaveToClipboardEnqueued = {
				fnc: gp_editor.SaveToClipboard,
				arg: area_id,
				callback: function(ret){
					// re-enable auto-save in the callback
					gp_editor.allowAutoSave = true; 
				},
				// destroy the enqueued function after execution
				destroy: true 
			};
			// trigger save
			gp_editing.SaveChanges();
			return;
		}
		gp_editor.SaveToClipboard(area_id);
	};


	/**
	 * Remove item from the Section Clipboard
	 *
	 */
	$gp.links.RemoveSectionClipboardItem = function(evt){

		var item_index = $(this).closest('li').attr("data-item_index");
		if( !item_index ){
			console.log('RemoveSectionClipboardItem Error: Atribute data_item_index missing');
			return;
		}

		var data = {
			cmd				: 'RemoveFromClipboard',
			item_number		: item_index
		};
		data = $.param(data);
		$gp.postC(window.location.href, data);
		loading();
	};


	/**
	 * Relabel Section Clipboard Item
	 *
	 */
	$gp.links.RelabelSectionClipboardItem = function(evt){

		var item_index = $(this).closest('li').attr("data-item_index");
		if( !item_index ){
			console.log('RelabelClipboardItem Error: Atribute data_item_index missing');
			return;
		}

		var $this	= $(this);
		var $label	= $this.closest('li').find('.clipboard-item-label');

		$label.hide();
		$this.hide();

		var tmpInput = $('<input type="text" value="' + $label.text() + '"/>')
			.insertAfter($label)
			.trigger('focus')
			.select()
			// when blurred, remove <input> and show hidden elements
			// same when esc or enter key is entered
			.on('keydown blur', function(evt){

				// stop if not blur or enter key
				if( evt.type != 'blur' && evt.which !== 13 && evt.which !== 27 ){
				 return;
				}

				// $label.show();
				$label.show();
				$this.show();
				var label = tmpInput.val();
				tmpInput.remove();

				// esc key or nothing changed -> don't save changes
				if( evt.which === 27 || $label.text() === label ){
					return;
				}

				$label.text(label);
				var data = {
					cmd				: 'RelabelClipboardItem',
					item_number		: item_index,
					new_label		: label,
				};
				data = $.param(data);
				$gp.postC(window.location.href, data);
				loading();
			});
	};



	/**
	 * Section Color
	 *
	 */
	$gp.links.SectionColor = function(evt){

		var $this	= $(this);
		var $li		= $this.closest('li');
		var colors	= [ 
			'#1192D6', '#3E5DE8', '#8D3EE8', '#C41FDD',
			'#ED2F94', '#ED4B1E', '#FF8C19', '#FFD419',
			'#C5E817', '#5AC92A', '#0DA570', '#017C7C',
			'#DDDDDD', '#888888', '#555555', '#000000' 
		];

		//build html
		var html = '<span class="secsort_color_swatches">';
		for( var i = 0; i < colors.length; i++ ){
			html += '<a style="background:' + colors[i] + ';" data-color="' + colors[i] + '" data-cmd="SelectColor"/>';
		}

		$li.children('div').hide();
		var $colors	= $(html + '</span>').prependTo($li);

		$(document).one('click', function(){
			$colors.remove();
			$li.children().show();
		});
	};


	/**
	 * Change section color
	 *
	 */
	$gp.links.SelectColor = function(evt){

		var $this		= $(this);
		var $li			= $this.closest('li');
		var $area		= gp_editor.GetArea( $li );
		var newColor 	= $this.attr('data-color');

		$li.find('.color_handle').first().css('background-color', newColor);
		$area.attr('data-gp_color',newColor).data('gp_color', newColor);
		$li.find('.secsort_color_swatches').remove();
		$li.children().show();

		// trigger immediate save
		// console.log('immediate save without creating a draft');
		var callback = function(){};
		gp_editing.SaveChanges(callback, false); // passing false as 2nd argument will prevent creating a new draft
	};


	/**
	 * Toggle wrapper display
	 *
	 */
	$gp.links.WrapperToggle = function(evt){

		var $li			= $(this).closest('li');
		var clss		= 'wrapper_collapsed';
		var $area		= gp_editor.GetArea( $li );

		if( $li.hasClass(clss) ){
			$li.removeClass(clss);
			clss = '';
		}else{
			$li.addClass(clss);
		}

		$area.attr('data-gp_collapse', clss).data('gp_collapse', clss);

		// trigger immediate save
		// console.log('immediate save without creating a draft');
		var callback = function(){};
		gp_editing.SaveChanges(callback, false); // passing false as 2nd argument will prevent creating a new draft
	};


	/**
	 * Initiate editing for a section
	 *
	 */
	$gp.links.SectionEdit = function(evt){
		var $li				= $(this).closest('li');
		var $area			= gp_editor.GetArea( $li );
		var area_id			= $gp.AreaId($li);
		var $lnk			= $('#ExtraEditLink' + area_id);
		var arg				= $lnk.data('arg');

		$gp.LoadEditor($lnk.get(0).href, area_id, arg);


		//scroll the page if needed
		var el_top			= $area.offset().top;
		var el_bottom		= el_top + $area.height();

		var view_top		= $gp.$win.scrollTop();
		var view_bottom		= view_top + $gp.$win.height();

		if( (el_bottom > view_top) && (el_top < view_bottom) ){
			return;
		}

		$('html,body').stop().animate({scrollTop: el_top - 200});
	};


	/**
	 * Edit the Attributes of the section
	 * todo: language values
	 *
	 */
	$gp.links.SectionOptions = function(evt){

		var $li					= $(this).closest('li');
		var id					= $li.data('gp-area-id')
		var attrs				= gp_editor.GetArea( $li ).data('gp-attrs');
		var current_classes		= '';

		//popup
		html = '<div class="inline_box"><form id="section_attributes_form" data-gp-area-id="' + id + '">';
		html += '<h2>' + gplang.SectionAttributes + '</h2>';
		html += '<table class="bordered full_width">';
		html += '<thead><tr><th>' + gplang.Attribute + '</th><th>' + gplang.Value + '</th></tr></thead><tbody>';

		$.each(attrs,function(name){

			name = name.toLowerCase();
			if( name == 'id' ){
				return;
			}

			if( name.substr(0,7) == 'data-gp' ){
				return;
			}

			var value = $.trim(this);
			if( value == '' && name != 'class' ){
				return;
			}

			if( name == 'class' ){
				current_classes = value.split(' ');
			}

			html += '<tr><td>';
			html += '<input class="gpinput attr_name" value="' + $gp.htmlchars(name) + '" size="8" />';
			html += '</td><td style="white-space:nowrap">';
			// html += '<input class="gpinput attr_value" value="' + $gp.htmlchars(value) + '" size="40" />';
			html += '<textarea rows="1" class="gptextarea attr_value">' + $gp.htmlchars(value) + '</textarea>';
			if( name == 'class' ){
				html += '<div class="class_only admin_note">Default: GPAREA filetype-*</div>';
			}
			html += '</td></tr>';
		});

		html += '<tr><td colspan="3">';
		html += '<a data-cmd="add_table_row">' + gplang.AddAttribute + '</a>';
		html += '</td></tr>';
		html += '</tbody></table>';

		html += '<br/>';

		//available classes
		html += '<div id="gp_avail_classes">';
		/*
			html += '<table class="bordered full_width">';
			html += '<thead><tr><th colspan="2">' + gplang.AvailableClasses + '</th></tr></thead>';
			html += '<tbody>';
			for( var i=0; i < gp_avail_classes.length; i++ ){
				html += '<tr><td>';
				html += ClassSelect(gp_avail_classes[i].names, current_classes);
				html += '</td><td class="sm text-muted">';
				html += gp_avail_classes[i].desc;
				html += '</td></tr>';
			}

			html += '</table>';
			html += '</tbody>';
			html += '</div>';

			html += '<p>';
			html += '<input type="button" name="" value="' + gplang.up + '" class="gpsubmit" data-cmd="UpdateAttrs" /> ';
			html += '<input type="button" name="" value="' + gplang.ca + '" class="gpcancel" data-cmd="admin_box_close" />';
			html += '</p>';

			html += '</form></div>';
			var $html = $(html);
		*/

		html += '<table class="bordered full_width">';
		html += '<thead><tr><th>' + gplang.AvailableClasses + '</th></tr></thead>';

		html += '<tbody><tr><td>';

		html += '<div class="avail_classes_container">';
		for( var i=0; i < gp_avail_classes.length; i++ ){
			html += '<div class="avail_classes_col">';
			html += ClassSelect(gp_avail_classes[i].names, current_classes);
			html += '</div>';
			html += '<div class="avail_classes_desc">' + gp_avail_classes[i].desc + '<span x-arrow="true" class="popover_arrow"></span></div>';
		}
		html += '</div>';

		html += '</td></tr>';
		html += '</tbody></table>';

		html += '</div>';

		html += '<p>';
		html += '<input type="button" name="" value="' + gplang.up + '" class="gpsubmit" data-cmd="UpdateAttrs" /> ';
		html += '<input type="button" name="" value="' + gplang.ca + '" class="gpcancel" data-cmd="admin_box_close" />';
		html += '</p>';

		html += '</form></div>';
		var $html = $(html);


		var $cols = $html.find('.avail_classes_col')
			.on('mouseenter', function(){
				var $popup = $(this).next('.avail_classes_desc:not(:empty)');

				if( $popup.text().trim() == '' ){
					// empty / no description provided
					return;
				}

				$popup.fadeTo(0, 0.001);

				this.popup = new Popper(this, $popup.get(0), {
					placement	: 'top', // auto
					onCreate	: function(){
						$popup.fadeTo(0, 0.002).delay(750).fadeTo(150, 1); // $popup.show();
					},
					modifiers : {
						arrow : {
							enabled : true
						},
						preventOverflow: {
							escapeWithReference : true
						}
						/*
						, offset : {
							enabled: true,
							offset: '24px,24px'
						}
						*/
				 	}
				});
				// console.log('popper created');

			})
			.on('mouseleave', function(){
				this.popup.destroy();

				var $popup = $(this).next('.avail_classes_desc:not(:empty)')
					.stop()
					.hide();
			});


		var $selects = $html.find('select')
			.on('change input', function(){
				var $checkbox = $(this).closest('label').find('.gpcheck');
				$checkbox.prop('checked', true);
				$gp.inputs.ClassChecked.apply($checkbox);
			});


		$gp.AdminBoxC( $html );

		//$('#section_attributes_form input').on('input',function(){UpdateAttrs()});

		var $area = gp_editor.GetArea($li);
		$area.trigger("section_options:loaded");
	};



	/**
	 * Create a class select
	 *
	 */
	function ClassSelect(classes, current_classes){

		classes			= classes.split(' ');
		var html		= '';
		var checked		= '';

		//multiple classes
		if( classes.length > 1 ){
			html += '<select>';
			for(var i = 0; i < classes.length; i++ ){
				var selected = '';
				if( current_classes.indexOf(classes[i]) >= 0 ){
					checked = 'checked';
					selected = 'selected'
				}

				html += '<option value="' + classes[i] + '" ' + selected + '>' + classes[i] + '</option>';
			}
			html += '</select>';

			html += '<span class="gpcaret"></span>';

		//single class
		}else{

			if( current_classes.indexOf(classes[0]) >= 0 ){
				checked = 'checked';
			}

			html += '<span>' + classes[0] + '</span>';
		}

		html		=  '<label class="gpcheckbox"><input class="gpcheck" '
							+ 'type="checkbox" data-cmd="ClassChecked" '
							+ checked + '/>' + html
							+ '</label>';

		return html;
	}


	/**
	 * Handle clicks on class checkboxes
	 *
	 */
	$gp.inputs.ClassChecked = function(){

		var $checkbox	= $(this);
		var action		= $checkbox.prop('checked') ? 'add' : 'remove';
		var $select		= $checkbox.siblings('select');
		var classNames	= '';

		//span
		if( $select.length == 0 ){
			classNames	= $checkbox.siblings('span').text();
			setSectionClasses( classNames, action);
			return;
		}

		//remove all from select first
		classNames = [];
		$select.find('option').each(function(){
			classNames.push(this.value);
		});
		classNames = classNames.join(' ');
		setSectionClasses( classNames, 'remove');

		//add selected
		if( action == 'add' ){
			classNames	= $select.val();
			setSectionClasses( classNames, 'add');
		}
	}


	function setSectionClasses( classNames, action ){

		var input	= $('#section_attributes_form td input.attr_name[value="class"]')
						.closest('tr').find('.attr_value');
		var value	= input.val();
		var tmp		= $("<div/>").addClass(value);

		if( action == 'add' ){
			tmp.addClass(classNames);
		}else{
			tmp.removeClass(classNames);
		}
		input.val(tmp.attr('class')).trigger('change');
		tmp.remove();
	}


	/**
	 * Section Attributes textareas auto height
	 *
	 */
	function textareaAutoHeight(){
		$(this)
			.css('height', '1px')
			.css('height', (this.scrollHeight + 3) + 'px');
	}

	$(document).on('section_options:loaded', function(){
		setTimeout(function(){
			$('.gptextarea.attr_value').trigger('input');
		}, 100);
	});


	/**
	 * Update the attributes
	 *
	 */
	$gp.inputs.UpdateAttrs = function(){
		var $form		= $('#section_attributes_form');
		var $area		= gp_editor.GetArea( $form );
		var old_attrs	= $area.data('gp-attrs');
		var new_attrs	= {};
		var class_value	= '';

		var $temp_node	= $('<div>');
		var classes		= '';
		
		//prep old_attrs list
		//remove old attrs from $area
		$.each(old_attrs,function(attr_name){
			if( attr_name == 'class' ){
				return;
			}

			new_attrs[attr_name] = '';
			$area.attr(attr_name, '');
		});

		//add new values
		$form.find('tbody tr').each(function(){
			var $row		= $(this);
			var attr_name	= $row.find('.attr_name').val();
			attr_name		= $.trim(attr_name).toLowerCase();

			if( !attr_name || attr_name == 'id' || attr_name.substr(0, 7) == 'data-gp' ){
				return;
			}

			var attr_value	= $row.find('.attr_value').val();

			if( attr_name == 'class' ){
				class_value = attr_value;
				return;
			}

			new_attrs[attr_name]	= attr_value;
			$area.attr(attr_name, attr_value);
		});

		//handle class uniquely so that we don't remove classes used by Typesetter
		var curr_value = $area.attr('class') || '';
		$temp_node.attr('class', curr_value);
		$temp_node.removeClass(old_attrs.class);
		$temp_node.addClass(class_value);
		$area.attr('class', $temp_node.attr('class'));
		new_attrs['class'] = class_value;

		//update title of <li> in section manager
		var id		= $gp.AreaId( $area );
		var $li		= $('#section_sorting li[data-gp-area-id=' + id + ']');
		if( classes == '' ){
			classes = $li.find('> div .section_label').text();
		}
		$li.attr('title', classes);

		$area.data('gp-attrs', new_attrs);
		$gp.CloseAdminBox();
		$area.trigger('section_options:closed');

		// trigger immediate save
		// console.log('immediate save');
		gp_editing.SaveChanges();
	};


	/**
	 * Highlight trash can icons when Ctrl key is down
	 * which will bypass the delete section confirmation dialog
	 */
	$(document).on('keydown keyup', function(evt){
		// console.log('keyboard event =', evt);
		var ctrlKeyDowm = (evt.type == 'keydown' && evt.ctrlKey);
		$('#section_sorting').toggleClass('warn-instant-section-removal', ctrlKeyDowm);
	});


	/**
	 * Scroll to content section when list item is clicked
	 *
	 */
	$(document).on('click', '#section_sorting li > div', function(){
		var $li		= $(this).parent();
		var $area	= gp_editor.GetArea($li);
		var top		= $area.offset().top - 200;
		$('html,body').stop().animate({scrollTop: top});
	});


	/**
	 * Observe textareas in Section Attribute dialog .attr_value and auto-resize to the required height
	 *
	 */
	$(document).on('input change', '.gptextarea.attr_value', textareaAutoHeight);


	/**
	 * Init Label editing
	 *
	 */
	$(document).on('dblclick', '.section_label', function(){

		var $this		= $(this);
		var $div		= $this.closest('div');
		$div.hide();
		var tmpInput	= $('<input type="text" value="' + $this.text() + '"/>')
			.insertAfter($div)
			.trigger('focus')
			.select()
			// when blurred, remove <input> and show hidden elements
			// same when esc or enter key is entered
			.on('keydown blur', function(evt){

				// stop if not enter key or
				if( evt.type != 'blur' && evt.which !== 13 && evt.which !== 27 ){
					return;
				}

				$div.show();
				var label = tmpInput.val();
				tmpInput.remove();

				//esc key -> don't save changes
				if( evt.which === 27 ){
					return;
				}

				//nothing changed -> don't save changes
				if( $this.text() === label ){
					return;
				}

				$this.text( label );
				var $li		= $div.closest('li');
				gp_editor.GetArea( $li )
					.attr('data-gp_label', label)
					.data('gp_label', label);

				// trigger immediate save
				// console.log('immediate save without creating a draft');
				var callback = function(){};
				gp_editing.SaveChanges(callback, false); // passing false as 2nd argument will prevent creating a new draft
			});
	});


	/**
	 * Assign new ids to a section and it's children
	 *
	 */
	function NewSectionIds($section){

		NewSectionId($section);

		//child sections
		$section.find('.editable_area').each(function(){
			NewSectionId( $(this) );
		});
	}


	/**
	 * Assign a new id to a section
	 *
	 */
	function NewSectionId($section){

		var area_id = 1;
		var new_id;
		do{
			area_id++;
			new_id = 'ExtraEditArea' + area_id;
		}while( document.getElementById(new_id) || document.getElementById('ExtraEditLink' + area_id) );

		$section.attr('id', new_id).data('gp-area-id', area_id);

		//add edit link (need to initiate editing and get the save path)
		$('<a href="?" class="nodisplay" data-cmd="inline_edit_generic" '
			+ 'data-gp-area-id="' + area_id + '" id="ExtraEditLink' + area_id + '">')
				.appendTo('#gp_admin_html');
	}


	/**
	 * Show additional editable areas
	 *
	 */
	function AddEditableLinks(){

		var list	= $('#ck_editable_areas ul').html('');
		var box		= $gp.div('gp_edit_box'); //the overlay box

		$('a.ExtraEditLink')
			.clone(false)
			.attr('class', '')
			.show()
			.each(function(){

				var $b			= $(this);
				var id_number	= $gp.AreaId( $b );
				var $area		= $('#ExtraEditArea' + id_number);

				if( $area.hasClass('gp_no_overlay') || $area.length === 0 ){
					return true;
				}

				//not page sections
				if( typeof($area.data('gp-section')) != 'undefined' ){
					return true;
				}

				var loc			= $gp.Coords($area);
				var title		= this.title.replace(/_/g, ' ');
				title			= decodeURIComponent(title);

				$b
					//add to list
					.attr('id', 'editable_mark' + id_number)
					.html('<i class="fa fa-pencil"></i> ' + title)

					//add handlers
					.on('mouseenter touchstart',function(){

						//the red edit box
						var loc = $gp.Coords($area);
						box	.stop(true,true)
							.css({
								'top'		: (loc. top - 3),
								'left'		: (loc.left - 2),
								'width'		: (loc.w + 4),
								'height'	: (loc. h + 5)
							})
							.fadeIn();

						//scroll to show edit area
						if( $gp.$win.scrollTop() > loc.top || ( $gp.$win.scrollTop() + $gp.$win.height() ) < loc.top ){
							$('html, body')
								.stop(true, true)
								.animate({
									scrollTop: Math.max(0, loc.top - 100)
								}, 'slow');
						}
					}).on('mouseleave touchend click', function(){
						box.stop(true, true).fadeOut();
					});

				//add to list
				var $li = $('<li>')
							.append($b)
							.data('top', loc.top)
							.appendTo(list);

				//dismiss draft link
				if( $area.data('draft') ){
					var href = $gp.jPrep(this.href, 'cmd=DismissDraft');

					$('<a class="draft dismiss-draft ck_publish" title="' + gplang.DismissDraft + '" data-cmd="gpajax" data-gp-area-id="' + id_number + '">'
						 + gplang.Dismiss
						 + '</a>')
							.attr('href', href)
							.appendTo($li);
				}

				//publish draft link
				if( $area.data('draft') ){
					var href = $gp.jPrep(this.href, 'cmd=PublishDraft');

					$('<a class="draft ck_publish" title="' + gplang.PublishDraft + '" data-cmd="gpajax" data-gp-area-id="' + id_number + '">'
						 + gplang.Publish
						 + '</a>')
							.attr('href', href)
							.appendTo($li);
				}

		});

		// sort by position on page
		list.find('li').sort(function(a, b){
			var contentA = $(a).data('top');
			var contentB = $(b).data('top');
			return (contentA < contentB) ? -1 : (contentA > contentB) ? 1 : 0;
		}).appendTo(list);
	}


	/**
	 * Start Editor
	 *
	 */
	gp_editing.editor_tools();
	gp_editor.InitEditor();
	loaded();

})();
