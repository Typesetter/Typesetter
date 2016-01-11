
	/**
	 * Set up editor
	 *
	 */
	gp_editor = {

		save_path: '?',

		saved_data: '',

		destroy:function(){},

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

			$('#gpx_content').find('.editable_area').each( function(i) {


				//new section order and new sections
				var $this	= $(this);
				var type	= mgr_object.TypeFromClass(this);
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

			this.saved_data	= this.gp_saveData();
		},

		updateElement:function(){},

		/**
		 * Init Editor
		 *
		 */
		InitEditor: function(){

			$('#ckeditor_top').append('<ul id="section_sorting" class="section_drag_area inline_edit_area" style="overflow:auto" title="Organize">');


			this.resetDirty();
			this.InitSorting();
			this.InitNewSection();


			$gp.$win.on('resize', this.MaxHeight ).resize();

			$('#ckeditor_area').on('dragstop',this.MaxHeight);

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

			var mgr_object	= this;
			var $list		= $('#section_sorting').html('');
			var html		= this.BuildSortHtml( $('#gpx_content') );

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

			$container.children('.editable_area').each( function(i){

				var $this = $(this);

				if( !this.id ){
					this.id = mgr_object.GenerateId();
				}


				var type	= mgr_object.TypeFromClass(this);

				//label
				var label	= $this.data('gp_label');
				if( !label ){
					label	= (i+1)+' '+mgr_object.ucfirst(type);
				}

				//color
				var color	= $this.data('gp_color') || '#aabbcc';
				var style	= '';

				//collapsed
				style	+= ' class="'+$this.data('gp_collapse')+'"';


				//classes
				var classes		= $this.data('gp-attrs').class || label;



				html += '<li data-area-id="'+this.id+'" '+style+' title="'+classes+'">';
				html += '<div><a class="color_handle" data-cmd="SectionColor" style="background-color:'+color+'"></a>';
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
		 * Move the content area after it has been moved in the edit popup
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
	 * Preview new section
	 *
	 */
	$(document).on('mouseenter','.preview_section',function(){
		var $this = $(this);

		if( !$this.hasClass('previewing') ){

			//remove other preview
			$('.previewing').removeClass('previewing');
			$('.temporary-section').stop().slideUp(function(){
				$(this).remove();
			});

			//scroll the page
			var $last	= $('#gpx_content .editable_area:last');
			var top		= $last.offset().top + $last.height() - 200;
			$('html,body').stop().animate({scrollTop: top});


			//begin new preview
			$this.addClass('previewing');

			var that	= this;
			var href	= this.href + '&preview='+new Date().getTime();
			href		= $gp.jPrep(href);


			//cached response
			var cached	= $this.data('response');
			if( cached ){
				$gp.Response.call(that,cached);
				return;
			}

			//get a new response and cache it
			$.getJSON(href,function(data,textStatus,jqXHR){
				$this.data('response',data);
				$gp.Response.call(that,data,textStatus,jqXHR);
			});

		}
	}).on('mouseleave','.preview_section',function(){

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
			return;
		}

		//clear the cache
		$this.data('response',false);


		var section = $this.data('preview-section');
		$(section).addClass('editable_area').removeClass('temporary-section')
				.find('.temporary-section').addClass('editable_area').removeClass('temporary-section');

		gp_editor.InitSorting();
		$this.removeClass('previewing').trigger('mouseenter');
	}


	/**
	 * Handle preview section response from server
	 *
	 */
	$gp.response.PreviewSection = function(data){

		var $this = $(this);
		if( !$this.hasClass('previewing') ){
			return;
		}


		var $new_content	= $(data.CONTENT);

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
		var id					= $li.data('area-id')
		var attrs				= gp_editor.GetArea( $li ).data('gp-attrs');
		var current_classes		= '';


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
		console.log('inputs: '+input.length);
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
		var $li		= $('#section_sorting li[data-area-id='+$area.attr('id'));
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
	 * Start Editor
	 *
	 */
	gp_editing.editor_tools();
	gp_editor.InitEditor();
	loaded();




