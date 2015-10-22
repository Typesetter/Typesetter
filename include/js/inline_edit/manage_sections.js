
	/**
	 * Set up editor
	 *
	 */
	gp_editor = {
		save_path: '?',

		saved_data: '',

		destroy:function(){},

		checkDirty:function(){
			var curr_data	= gp_editor.gp_saveData();
			if( gp_editor.saved_data != curr_data ){
				return true;
			}
			return false;
		},

		/**
		 * Organize the section content to be saved
		 *
		 */
		gp_saveData:function(){

			var args					= {};
			args.section_order			= [];
			args.attributes				= [];
			args.contains_sections		= [];
			args.gp_label				= [];
			args.gp_color				= [];
			args.gp_collapse			= [];
			args.cmd					= 'SaveSections';

			$('#gpx_content').find('.editable_area').each( function(i) {


				//new section order and new sections
				var $this	= $(this);
				var type	= gp_editor.TypeFromClass(this);
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
				args.gp_collapse[i]	= $this.data('gp_collapse');


			});

			return $.param(args);
		},

		/**
		 * Called when saved
		 *
		 */
		resetDirty:function(){

			$('#gpx_content').find('.editable_area').each( function(i){
				$(this).data('gp-section',i).attr('data-gp-section',i).removeClass('new_section');
			});

			gp_editor.saved_data	= gp_editor.gp_saveData();
		},

		updateElement:function(){},

		/**
		 * Init Editor
		 *
		 */
		InitEditor: function(){

			$('#ckeditor_top').append('<ul id="section_sorting" class="section_drag_area inline_edit_area" style="overflow:auto" title="Organize">');


			gp_editor.resetDirty();
			gp_editor.InitSorting();
			gp_editor.InitNewSection();


			$gp.$win.on('resize', gp_editor.MaxHeight ).resize();

			$('#ckeditor_area').on('dragstop',gp_editor.MaxHeight);

			$('#ckeditor_bottom').hide();

			gp_editing.CreateTabs();
			$(document).trigger("section_sorting:loaded");
		},


		/**
		 * Set maximum height of editor
		 *
		 */
		MaxHeight: function(){
			var $ckeditor_area	= $('#ckeditor_area');
			var $section_area	= $('#ckeditor_top').css('overflow','hidden'); //attempt to get rid of the scroll bar
			var listMaxHeight	= $gp.$win.height() - $ckeditor_area.offset().top - $ckeditor_area.height() + $section_area.height() + $gp.$win.scrollTop();

			$section_area.css('overflow','auto');
			$section_area.css( 'max-height', listMaxHeight );
		},

		/**
		 * Create selection for new section
		 *
		 */
		InitNewSection: function(){
			$('#ckeditor_top').append(section_types);
		},

		/**
		 * Initialize section sorting
		 * This data may be sent from the server
		 *
		 */
		InitSorting: function(){

			var $list	= $('#section_sorting').html('');
			var html	= gp_editor.BuildSortHtml( $('#gpx_content') );

			$list.html(html);

			$('.section_drag_area').sortable({
				//tolerance:				'pointer',
				stop:					gp_editor.DragStop,
				connectWith:			'.section_drag_area',
				cursorAt:				{ left: 7, top: 7 }

			}).disableSelection();


			gp_editor.HoverListener($list);
		},

		/**
		 * Build sort html
		 *
		 */
		BuildSortHtml: function( $container ){

			var html = '';

			$container.children('.editable_area').each( function(i){

				var $this = $(this);

				if( !this.id ){
					this.id = gp_editor.GenerateId();
				}


				var type	= gp_editor.TypeFromClass(this);

				//label
				var label	= $this.data('gp_label');
				if( !label ){
					label	= (i+1)+' '+gp_editor.ucfirst(type);
				}

				//color
				var color	= $this.data('gp_color');
				var style	= '';
				if( color ){
					style	= 'style="border-left-color:'+color+'"';
				}

				//collapsed
				style	+= ' class="'+$this.data('gp_collapse')+'"';


				html += '<li data-area-id="'+this.id+'" '+style+'>';
				html += '<div><a class="color_handle" data-cmd="SectionColor"></a>';
				html += '<span class="options">';
				html += '<a class="gpicon_edapp" data-cmd="SectionOptions" title="Options"></a>';
				html += '<a class="copy_icon" data-cmd="CopySection" title="Copy"></a>';
				html += '<a class="bin_icon RemoveSection" data-cmd="RemoveSection" title="Remove"></a>';
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
					html += gp_editor.BuildSortHtml( $this );
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
		 * Move the content area after it has been moved in the edit popup
		 *
		 */
		DragStop: function(event, ui){

			var area		= gp_editor.GetArea( ui.item );
			var prev_area	= gp_editor.GetArea( ui.item.prev() );

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
			gp_editor.GetArea( $ul.parent() ).prepend(area);

		},


		/**
		 * Get the content type from the class name
		 * todo: use regexp to find filetype-.*
		 */
		TypeFromClass: function(div){
			var type = $(div).prop('class').substring(16);
			return type.substring(0, type.indexOf(' '));
		},


		/**
		 * Setup Hover Listenter
		 *
		 */
		HoverListener: function($list){

			$list.find('div').hover(function(){

				var $this = $(this).parent();

				$('.section-item-hover').removeClass('section-item-hover');
				$this.addClass('section-item-hover');

				$('.section-highlight').removeClass('section-highlight');
				gp_editor.GetArea( $this ).addClass('section-highlight');

			},function(){
				var $this = $(this).parent()
				gp_editor.GetArea( $this ).removeClass('section-highlight');
				$this.removeClass('section-item-hover');

			});

		},

		/**
		 * Get an editable area from
		 *
		 */
		GetArea: function($li){
			var id 		= $li.data('area-id');
			return $('#'+id);
		},

		/**
		 * Capitalize the first letter of a string
		 *
		 */
		ucfirst: function( str ){
			return str.charAt(0).toUpperCase() + str.slice(1);
		}

	}


	/**
	 * Handle new section clicks
	 *
	 */
	$gp.links.AddSection = function(evt){
		evt.preventDefault();
		$(this).fadeTo(700,0.4).addClass('loading-section');
		$gp.jGoTo(this.href, this);
	}


	/**
	 * Handle new section response from server
	 *
	 */
	$gp.response.AddSection = function(data){
		$('.loading-section').removeClass('loading-section').finish().fadeTo(700,1);

		var $new_content	= $(data.CONTENT).appendTo('#gpx_content');
		var top				= Math.max( 0, $new_content.position().top - 200);
		$new_content.hide().addClass('section-highlight');

		$('html,body').stop().animate({scrollTop: top},{complete:function(){
			$new_content.delay(200).slideDown(function(){
				$new_content.removeClass('section-highlight');
			});
		}});

		gp_editor.InitSorting();
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
		var id			= 'Copied_'+Math.floor((Math.random() * 100000) + 1)+'_'+new_area.attr('id');
		new_area.attr('id',id).addClass('new_section');
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
			html += '<a style="background:' + colors[i] + ';" data-color="' + colors[i] + '"  data-cmd="SelectColor"/>';
		}

		$li.children('div').hide();
		var $colors	= $(html+'</span>').prependTo($li);

		$li.mouseleave(function(){
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

		$li.css('border-left-color', newColor);
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

		var $li		= $(this).closest('li');
		var id		= $li.data('area-id')
		var attrs	= gp_editor.GetArea( $li ).data('gp-attrs');


		//popup
		html = '<div class="inline_box"><form id="section_attributes_form" data-area-id="'+id+'">';
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

		html += '<p>';
		html += '<input type="button" name="" value="'+gplang.up+'" class="gpsubmit" data-cmd="UpdateAttrs" /> ';
		html += '<input type="button" name="" value="'+gplang.ca+'" class="gpcancel" data-cmd="admin_box_close" />';
		html += '</p>';

		html += '</form></div>';

		$gp.AdminBoxC(html);

		$(document).trigger("section_options:loaded");
	}

	/**
	 * Update the attributes
	 *
	 */
	$gp.inputs.UpdateAttrs = function(evt){
		evt.preventDefault();

		var $form		= $('#section_attributes_form');
		var $area		= gp_editor.GetArea( $form );
		var old_attrs	= $area.data('gp-attrs');
		var new_attrs	= {};

		var $temp_node	= $('<div>');

		//prep old_attrs list
		//remove old attrs from $area
		$.each(old_attrs,function(attr_name){

			new_attrs[attr_name]	= '';
			var curr_value			= $area.attr(attr_name) || '';

			$temp_node.attr('class',curr_value);
			$temp_node.removeClass(''+this);

			$area.attr(attr_name, $temp_node.attr('class'));
		});


		//add new values
		$form.find('tbody tr').each(function(){
			var $row			= $(this);
			var attr_name		= $row.find('.attr_name').val();
			var attr_value		= $row.find('.attr_value').val();
			attr_name			= $.trim(attr_name).toLowerCase();

			if( !attr_name || attr_name == 'id' || attr_name.substr(0,7) == 'data-gp' ){
				return;
			}

			var curr_value		= $area.attr(attr_name) || '';

			$temp_node.attr('class',curr_value);
			$temp_node.addClass(attr_value);
			$area.attr(attr_name, $temp_node.attr('class'));

			new_attrs[attr_name] = attr_value;
		});

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
	 * Start Editor
	 *
	 */
	gp_editing.editor_tools();
	gp_editor.InitEditor();
	loaded();




