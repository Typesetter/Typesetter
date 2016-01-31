
(function(){

	/**
	 * Set up editor
	 *
	 */
	gp_editor = {

		save_path: '?',

		saved_data: '',

		preview_timeout: null,

		checkDirty:function(){
			var curr_data	= this.gp_saveData();
			if( this.saved_data != curr_data ){
				return true;
			}
			return false;
		},

		/**
		 * Organize the section content to be saved
		 *
		 */
		gp_saveData:function(){

			var mgr_object				= this;
			var args					= {};
			args.section_order			= [];
			args.attributes				= [];
			args.contains_sections		= [];
			args.gp_label				= [];
			args.gp_color				= [];
			args.gp_collapse			= [];
			args.cmd					= 'SaveSections';

			$('#gpx_content.gp_page_display').find('.editable_area').each( function(i) {


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
				args.attributes[i] = $this.data('gp-attrs');

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


			});

			return $.param(args);
		},


		/**
		 * Called when saved
		 *
		 */
		resetDirty:function(){
			gp_editor.SectionNumbers();
			this.saved_data	= this.gp_saveData();
		},


		/**
		 * Update Section #s
		 *
		 */
		SectionNumbers:function(){

			$('#gpx_content.gp_page_display').find('.editable_area').each( function(i){
				var $this		= $(this);

				$this.data('gp-section',i).attr('data-gp-section',i);

				var area_id		= $gp.AreaId( $this );
				var href		= $('#ExtraEditLink'+area_id).attr('href') || '';

				href = href.replace(/section\=[0-9]+/,'');
				href = $gp.jPrep(href,'section='+i);

				$('#ExtraEditLink'+area_id).attr('href',href);
			});

		},


		/**
		 * Init Editor
		 *
		 */
		InitEditor: function(){

			$('#ckeditor_top').append(section_types);
			this.InitSorting();
			this.resetDirty();


			$gp.$win.on('resize', this.MaxHeight ).resize();

			$('#ckeditor_area').on('dragstop',this.MaxHeight);

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
				tolerance:				'pointer', /** otherwise sorting elements into collapsed area causes problems */
				stop:					function(evt, ui){
											mgr_object.DragStop(evt, ui);
										},
				connectWith:			'.section_drag_area',
				cursorAt:				{ left: 7, top: 7 }

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


				var type	= gp_editing.TypeFromClass(this);

				//label
				var label	= gp_editing.SectionLabel($this);

				//color
				var color	= $this.data('gp_color') || '#aabbcc';

				//collapsed
				var style	= ' class="'+$this.data('gp_collapse')+'"';


				//classes
				var classes		= $this.data('gp-attrs').class || label;



				html += '<li data-gp-area-id="'+this.id+'" '+style+' title="'+classes+'">';
				html += '<div><a class="color_handle" data-cmd="SectionColor" style="background-color:'+color+'"></a>';
				html += '<span class="options">';
				html += '<a class="fa fa-sliders" data-cmd="SectionOptions" title="Options"></a>';
				html += '<a class="fa fa-files-o" data-cmd="CopySection" title="Copy"></a>';
				html += '<a class="fa fa-trash RemoveSection" data-cmd="RemoveSection" title="Remove"></a>';
				html += '</span>';
				html += '<i class="section_label_wrap">';

				//wrapper collapse link
				if( type == 'wrapper_section' ){
					html += '<a data-cmd="WrapperToggle" class="secsort_wrapper_toggle"/>';
				}

				html += '<span class="section_label">'+label+'</span>';
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

			var area		= this.GetArea( ui.item );
			var prev_area	= this.GetArea( ui.item.prev() );

			//moved after another section
			if( prev_area.length ){
				area.insertAfter(prev_area);
				return;
			}

			//move to beginning of gpx_content
			var $ul			= ui.item.parent().closest('ul');
			if( $ul.attr('id') == 'section_sorting' ){
				area.prependTo('#gpx_content');
				return;
			}


			//moved to beginning of wrapper
			this.GetArea( $ul.parent() ).prepend(area);

		},


		/**
		 * Setup Hover Listenter
		 *
		 */
		HoverListener: function($list){

			var mgr_object = this;

			$list.find('div').hover(function(){

				var $this = $(this).parent();

				$('.section-item-hover').removeClass('section-item-hover');
				$this.addClass('section-item-hover');

				$('.section-highlight').removeClass('section-highlight');
				mgr_object.GetArea( $this ).addClass('section-highlight');

			},function(){
				var $this = $(this).parent()
				mgr_object.GetArea( $this ).removeClass('section-highlight');
				$this.removeClass('section-item-hover');

			});

		},

		/**
		 * Get an editable area from
		 *
		 */
		GetArea: function($li){
			var id 		= $li.data('gp-area-id');
			return $('#'+id);
		},


		/**
		 * Assign a new id to a section
		 *
		 */
		NewSectionId: function(new_area){

			var area_id		= 1;
			var new_id;
			do{
				area_id++;
				new_id = 'ExtraEditArea'+area_id;

			}while( document.getElementById(new_id) || document.getElementById('ExtraEditLink'+area_id) );

			new_area.attr('id',new_id).data('gp-area-id',area_id);

			//add edit link (need to initiate editing and get the save path)
			$('<a href="?" class="nodisplay" data-cmd="inline_edit_generic" data-gp-area-id="'+area_id+'" id="ExtraEditLink'+area_id+'">').appendTo('#gp_admin_html');
		}

	}


	/**
	 * Preview new section
	 *
	 */
	$(document).on('mousemove','.preview_section',function(){
		var $this = $(this);

		if( gp_editor.preview_timeout ){
			clearTimeout(gp_editor.preview_timeout);
		}

		if( $this.hasClass('previewing') ){
			return;
		}


		//remove other preview
		$('.previewing').removeClass('previewing');
		$('.temporary-section').stop().slideUp(function(){
			$(this).remove();
		});


		gp_editor.preview_timeout = setTimeout(function(){

			//scroll the page
			var $last	= $('#gpx_content .editable_area:last');
			var top		= $last.offset().top + $last.height() - 200;
			$('html,body').stop().animate({scrollTop: top});


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
				.delay(300).slideDown();

			var node = $new_content.get(0);
			$this.data('preview-section',node);

		},200);


	}).on('mouseleave','.preview_section',function(){

		if( gp_editor.preview_timeout ){
			clearTimeout(gp_editor.preview_timeout);
		}

		$(this).removeClass('previewing');

		$('.temporary-section').stop().slideUp(function(){
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

		//change the previewed section to an editable area
		if( !$this.hasClass('previewing') ){
			console.log('not previewing');
			return;
		}

		var section		= $this.data('preview-section');
		var $section	= $(section).addClass('editable_area').removeClass('temporary-section');
		gp_editor.NewSectionId($section);


		//child sections
		$section.find('.temporary-section').each(function(){
			var $child = $(this).addClass('editable_area').removeClass('temporary-section');
			gp_editor.NewSectionId($child);
		});


		gp_editor.InitSorting();
		$this.removeClass('previewing').trigger('mousemove');
	}


	/**
	 * Remove a section
	 *
	 */
	$gp.links.RemoveSection = function(evt){

		//make sure there's at least one section
		if( $('#gpx_content').find('.editable_area').length > 1 ){
			var $li = $(this).closest('li');
			gp_editor.GetArea( $li ).remove();
			$li.remove();
		}
	}

	/**
	 * Copy selected section
	 *
	 */
	$gp.links.CopySection = function(evt){
		var from_area	= gp_editor.GetArea( $(this).closest('li') );
		var new_area	= from_area.clone();

		gp_editor.NewSectionId(new_area);
		from_area.after(new_area);
		gp_editor.InitSorting();
	}

	/**
	 * Section Color
	 *
	 */
	$gp.links.SectionColor = function(evt){

		var $this	= $(this);
		var $li		= $this.closest('li');
		var colors	= [ '#1192D6','#3E5DE8','#8D3EE8','#C41FDD', '#ED2F94','#ED4B1E','#FF8C19','#FFD419','#C5E817','#5AC92A','#0DA570','#017C7C','#DDDDDD','#888888','#555555','#000000' ];


		//build html
		var html = '<span class="secsort_color_swatches">';
		for( var i=0; i<colors.length; i++ ){
			html += '<a style="background:' + colors[i] + ';" data-color="' + colors[i] + '"	data-cmd="SelectColor"/>';
		}

		$li.children('div').hide();
		var $colors	= $(html+'</span>').prependTo($li);

		$(document).one('click',function(){
			$colors.remove();
			$li.children().show();
		});
	}

	/**
	 * Change section color
	 *
	 */
	$gp.links.SelectColor = function(evt){

		var $this		= $(this);
		var $li			= $this.closest('li');
		var $area		= gp_editor.GetArea( $li );
		var newColor 	= $this.attr('data-color');

		$li.find('.color_handle:first').css('background-color',newColor);
		$area.attr('data-gp_color',newColor).data('gp_color',newColor);
		$li.find('.secsort_color_swatches').remove();
		$li.children().show();
	}

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

		$area.attr('data-gp_collapse',clss).data('gp_collapse',clss);
	}


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
		html = '<div class="inline_box"><form id="section_attributes_form" data-gp-area-id="'+id+'">';
		html += '<h2>Section Attributes</h2>';
		html += '<table class="bordered full_width">';
		html += '<thead><tr><th>Attribute</th><th>Value</th></tr></thead><tbody>';


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
			html += '<input class="gpinput attr_name" value="'+$gp.htmlchars(name)+'" size="8" />';
			html += '</td><td style="white-space:nowrap">';
			html += '<input class="gpinput attr_value" value="'+$gp.htmlchars(value)+'" size="40" />';
			if( name == 'class' ){
				html += '<div class="class_only admin_note">Default: GPAREA filetype-*</div>';
			}
			html += '</td></tr>';
		});

		html += '<tr><td colspan="3">';
		html += '<a data-cmd="add_table_row">Add Attribute</a>';
		html += '</td></tr>';
		html += '</tbody></table>';

		html += '<br/>';


		//available classes
		html += '<div id="gp_avail_classes">';
		html += '<table class="bordered full_width">';
		html += '<thead><tr><th colspan="2">Available Classes</th></tr></thead>';
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
		html += '<input type="button" name="" value="'+gplang.up+'" class="gpsubmit" data-cmd="UpdateAttrs" /> ';
		html += '<input type="button" name="" value="'+gplang.ca+'" class="gpcancel" data-cmd="admin_box_close" />';
		html += '</p>';

		html += '</form></div>';
		var $html = $(html);

		//
		var selects = $html.find('select').on('change input',function(){
			var $checkbox = $(this).closest('label').find('.gpcheck');
			$checkbox.prop('checked',true);
			$gp.inputs.ClassChecked.apply($checkbox);
		});

		$gp.AdminBoxC( $html );

		//$('#section_attributes_form input').on('input',function(){UpdateAttrs()});

		$(document).trigger("section_options:loaded");
	}

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

				html += '<option value="'+classes[i]+'" '+selected+'>'+classes[i]+'</option>';
			}
			html += '</select>';

			html += '<span class="gpcaret"></span>';


		//single class
		}else{

			if( current_classes.indexOf(classes[0]) >= 0 ){
				checked = 'checked';
			}

			html += '<span>'+classes[0]+'</span>';
		}


		html		= '<label class="gpcheckbox"><input class="gpcheck" type="checkbox" data-cmd="ClassChecked" '+checked+'/>'+html;
		html		+= '</label>';

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
		classNames = classNames.join(" ");
		setSectionClasses( classNames, 'remove');

		//add selected
		if( action == 'add' ){
			classNames	= $select.val();
			setSectionClasses( classNames, 'add');
		}
	}


	function setSectionClasses( classNames, action ){

		var input			= $('#section_attributes_form td input.attr_name[value="class"]').closest('tr').find('input.attr_value');
		var value			= input.val();
		var tmp				 = $("<div/>").addClass(value);

		if( action == 'add' ){
			tmp.addClass(classNames);
		}else{
			tmp.removeClass(classNames);
		}
		input.val(tmp.attr('class'));
		tmp.remove();
	}



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

			new_attrs[attr_name]	= '';
			$area.attr(attr_name, '');
		});


		//add new values
		$form.find('tbody tr').each(function(){
			var $row				= $(this);
			var attr_name			= $row.find('.attr_name').val();
			attr_name				= $.trim(attr_name).toLowerCase();

			if( !attr_name || attr_name == 'id' || attr_name.substr(0,7) == 'data-gp' ){
				return;
			}

			var attr_value			= $row.find('.attr_value').val();

			if( attr_name == 'class' ){
				class_value = attr_value;
				return;
			}

			new_attrs[attr_name]	= attr_value;
			$area.attr(attr_name, attr_value);
		});


		//handle class uniquely so that we don't remove classes used by gpEasy
		var curr_value			= $area.attr('class') || '';
		$temp_node.attr('class',curr_value);
		$temp_node.removeClass(old_attrs.class);
		$temp_node.addClass(class_value);
		$area.attr('class', $temp_node.attr('class'));
		new_attrs['class'] = class_value;



		//update title of <li> in section manager
		var $li		= $('#section_sorting li[data-gp-area-id='+$area.attr('id'));
		if( classes == '' ){
			classes = $li.find('> div .section_label').text();
		}
		$li.attr('title',classes);


		$area.data('gp-attrs',new_attrs);

		$gp.CloseAdminBox();
	}


	/**
	 * Init Label editing
	 *
	 */
	$(document).on('dblclick','.section_label',function(){

		var $this			= $(this);
		var $div			= $this.closest('div');
		$div.hide();
		var tmpInput		= $('<input type="text" value="' + $this.text() + '"/>')
			.insertAfter($div)
			.focus()
			.select()
			// when blurred, remove <input> and show hidden elements
			// same when esc or enter key is entered
			.on('keydown blur', function(evt){

				// stop if not enter key or
				if( evt.type != 'blur' && evt.which !== 13 && evt.which !== 27 ) return;

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
				gp_editor.GetArea( $li ).attr('data-gp_label',label).data('gp_label',label);

			});

	});


	/**
	 * Show additional editable areas
	 *
	 */
	function AddEditableLinks(){

		var list	= $('#ck_editable_areas ul').html('');
		var box		= $gp.div('gp_edit_box'); //the overlay box

		$('a.ExtraEditLink')
			.clone(false)
			.attr('class','')
			.show()
			.each(function(){

				var $b			= $(this);
				var id_number	= $gp.AreaId( $b );
				var $area		= $('#ExtraEditArea'+id_number);

				if( $area.hasClass('gp_no_overlay') || $area.length === 0 ){
					return true;
				}


				//not page sections
				if( typeof($area.data('gp-section')) != 'undefined' ){
					return true;
				}


				var loc			= $gp.Coords($area);
				var title		= this.title.replace(/_/g,' ');
				title			= decodeURIComponent(title);

				if( title.length > 15 ){
					title = title.substr(0,14);
				}


				$b
					//add to list
					.attr('id','editable_mark'+id_number)
					.html('<i class="fa fa-pencil"></i> '+title)

					//add handlers
					.on('mouseenter touchstart',function(){

						//the red edit box
						var loc = $gp.Coords($area);
						box	.stop(true,true)
							.css({'top':(loc.top-3),'left':(loc.left-2),'width':(loc.w+4),'height':(loc.h+5)})
							.fadeIn();

						//scroll to show edit area
						if( $gp.$win.scrollTop() > loc.top || ( $gp.$win.scrollTop() + $gp.$win.height() ) < loc.top ){
							$('html,body').stop(true,true).animate({scrollTop: Math.max(0,loc.top-100)},'slow');
						}
					}).on('mouseleave touchend click',function(){
						box.stop(true,true).fadeOut();
					});


				//add to list
				var $li = $('<li>')
							.append($b)
							.data('top',loc.top)
							.appendTo(list);

				//publish draft link
				if( $area.data('draft') ){
					var href = $gp.jPrep(this.href,'cmd=PublishDraft');
					$('<a class="draft" data-cmd="gpajax" data-gp-area-id="'+id_number+'">'+gplang.Draft+'</a>').attr('href',href).appendTo($li);
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


