
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

			var args				= {};
			args.section_order		= [];
			args.attributes			= [];
			args.contains_sections	= [];
			args.cmd				= 'SaveSections';

			$('#gpx_content').find('.editable_area').each( function(i) {


				//new section order and new sections
				var $this	= $(this);
				var type	= gp_editor.TypeFromClass(this);
				var value	= $this.data('gp-section');

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
			$('<ul id="section_sorting" class="section_drag_area" style="overflow:auto">')
				.appendTo('#ckeditor_top')


			gp_editor.resetDirty();
			gp_editor.InitSorting();
			gp_editor.InitNewSection();


			$gp.$win.on('resize', gp_editor.MaxHeight ).resize();

			$('#ckeditor_area').on('dragstop',gp_editor.MaxHeight);
		},

		/**
		 * Set maximum height of editor
		 *
		 */
		MaxHeight: function(){
			var $ckeditor_area	= $('#ckeditor_area');
			var $section_area	= $('#section_sorting');
			var listMaxHeight	= $gp.$win.height() - $ckeditor_area.offset().top - $ckeditor_area.height() + $section_area.height() + $gp.$win.scrollTop();

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
				stop:					gp_editor.DragStop,
				connectWith:			'.section_drag_area'
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


				var type = gp_editor.TypeFromClass(this);

				html += '<li data-area-id="'+this.id+'">';
				html += '<div><span class="options">';
				html += '<a class="gpicon_edapp" data-cmd="SectionOptions" title="Options"></a>';
				html += '<a class="copy_icon" data-cmd="CopySection" title="Copy"></a>';
				html += '<a class="bin_icon RemoveSection" data-cmd="RemoveSection" title="Remove"></a>';
				html += '</span>';
				html += '<i>'+(i+1)+' '+gp_editor.ucfirst(type)+'</i>';
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
	 * Handle new section response from server
	 *
	 */
	$gp.response.AddSection = function(data){
		$('#gpx_content').append(data.CONTENT);
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
		var area	= gp_editor.GetArea( $(this).closest('li') ).clone();
		var id		= 'Copied_'+Math.floor((Math.random() * 100000) + 1)+'_'+area.attr('id');
		area.attr('id',id).addClass('new_section');
		$('#gpx_content').append(area);
		gp_editor.InitSorting();
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
	 * Start Editor
	 *
	 */
	gp_editing.editor_tools();
	gp_editor.InitEditor();
	loaded();

