$gp.EditLayout = {

	css_editor : {},

	customizer : {},

	isDirty : false,

	checkDirty : function(){
		var checks = 
			$gp.EditLayout.css_editor.checkDirty() ||
			$gp.EditLayout.customizer.checkDirty();

		$gp.EditLayout.isDirty = checks;
		return checks;
	},

	resetDirty : function(){
		$gp.EditLayout.css_editor.resetDirty();
		$gp.EditLayout.customizer.resetDirty();
	},

	bindEvents : function(){

		$('.layout_editor_tabs .tab_switch')
			.each($gp.EditLayout.switchTab)
			.on('click', $gp.EditLayout.switchTab);

		// preview button
		$gp.inputs.preview_changes = function(evt){
			$gp.loading();
		};

		// save button TODO!
		$gp.inputs.save_changes = function(evt){
			var _this = $gp.EditLayout.css_editor;

			//_this.textarea.removeClass('edited');
			//_this.data = _this.textarea.val();

			setTimeout(function(){

				$gp.EditLayout.resetDirty();

				$('button[data-cmd="preview_changes"], ' +
					'button[data-cmd="save_changes"], ' +
					'button[data-cmd="reset_changes"]')
					.addClass('gpdisabled')
					.prop('disabled', true);
			}, 150);

			$gp.loading();
		};

		// reset button
		$gp.inputs.reset_changes = function(evt){
			// reset css editor
			$gp.EditLayout.css_editor.resetValues();
			// reset customizer
			$gp.EditLayout.customizer.resetValues();
			// update buttons
			$gp.EditLayout.updateButtons();

			if( $gp.EditLayout.isDirty ){
				$gp.inputs.save_changes();
			}
		}

		$(window).on('beforeunload', function(evt) {
			$gp.EditLayout.checkDirty();
			if( $gp.EditLayout.isDirty ){
				return 'Warning: There are unsaved changes. Proceed anyway?';
			}
		});
	},

	switchTab : function(evt){
		var activate = evt.type == 'click' || $(this).hasClass('active');
		if( activate ){
			$(this)
				.addClass('active')
				.siblings().removeClass('active');
			$('.' + $(this).data('rel'))
				.addClass('active')
				.siblings().removeClass('active');
		}
		$gp.EditLayout.css_editor.setSize();
	},

	updateButtons : function(){
		$gp.EditLayout.checkDirty();

		// always keep the preview button enabled
		$('button[data-cmd="preview_changes"]')
			.toggleClass('gpdisabled', false)
			.prop('disabled', false);
		
		$('button[data-cmd="save_changes"], ' +
			'button[data-cmd="reset_changes"]')
			.toggleClass('gpdisabled', !$gp.EditLayout.isDirty)
			.prop('disabled', !$gp.EditLayout.isDirty);
	},

	init : function(){
		$gp.EditLayout.css_editor.init();
		$gp.EditLayout.customizer.init();
		$gp.EditLayout.bindEvents();
	}

};


// customizer
$gp.EditLayout.customizer = {

	cache		: null,
	cache_arr	: null,

	data		: null,
	data_arr	: null,

	isDirty		: false,

	checkDirty : function(){
		var _this = $gp.EditLayout.customizer;

		_this.data = _this.getData();
		var is_dirty = _this.data !== _this.cache;
		_this.isDirty = is_dirty;
		_this.indicateDirty();

		return is_dirty;
	},

	resetValues	: function(){
		var _this = $gp.EditLayout.customizer;

		// console.log('customizer resetChanges'); // TODO remode
		// console.log('_this.cache_arr = ', _this.cache_arr); // TODO remode

		$.each(_this.cache_arr, function(i, cache_obj){
			var input_name	= cache_obj.name;
			var cache_value	= cache_obj.value;
			var $input = $('.customizer_area [name="' + input_name + '"]');
			if( $input.val() !== cache_value ){
				$input.val(cache_value).trigger('input');
			}
			if( $input.is('.customizer_checkbox_alias') ){
				$input.next('[type="checkbox"]')
					.prop('checked', cache_value == 'on');
			}
		});

		_this.resetDirty();
	},

	resetDirty : function(){
		var _this = $gp.EditLayout.customizer;

		_this.data			= _this.getData();
		_this.cache			= _this.data;
		_this.dataArray		= _this.getDataArray();
		_this.cacheArray	= _this.dataArray;
		_this.isDirty		= false;
		_this.indicateDirty();
	},

	indicateDirty : function(what){
		var _this = $gp.EditLayout.customizer;
		var what = typeof(what) != 'undefined' ? what : _this.isDirty;
		$('.tab_switch[data-rel="customizer_area"]')
			.toggleClass('is_dirty', what);
		$('input#gp_layout_save_customizer').val((what ? 'on' : ''));
	},

	getData : function(){
		var data = $('.customizer_area [name^="customizer["]').serialize();
		// console.log('customizer data = ', data);
		return data;
	},

	getDataArray : function(){
		var data_array = $('.customizer_area *[name^="customizer["]').serializeArray();
		// console.log('customizer data array = ', data_array);
		return data_array;
	},

	bindEvents	: function(){

		// expand/collapse customizer sections
		$gp.links.toggle_customizer_section = function(evt){
			$this = $(this);
			$this.siblings('.customizer_controls')
				.slideToggle(
					300,
					function(){
						$(this).closest('.customizer_section')
							.toggleClass('collapsed');
					}
				);
			// collapse others
			$this.closest('.customizer_area')
				.find('.customizer_section:not(.collapsed) .customizer_controls')
					.slideUp(
						300,
						function(){
							$(this).closest('.customizer_section')
								.addClass('collapsed');
						}
					);
		};


		// detect changes
		$('.customizer_area [name^="customizer["]').on('input change', function(){
			// console.log('change evt fired');
			$gp.EditLayout.customizer.checkDirty();
			$gp.EditLayout.updateButtons();
		})

		// select file
		$gp.links.customizer_select_file = function(evt){
			var $control_group	= $(this).closest('.customizer_file_group');
			var $input			= $control_group.find('.customizer_file_url');

			gp_editor = {
				FinderSelect : function(file_url){ // called by finder on file select
					if( file_url != '' ){
						$input.val(file_url).trigger('change');
					}
					return true;
				}
			};

			var finderPopUp = window.open(
					gpFinderUrl,
					'gpFinder', 
					'menubar=no,width=960,height=640'
				);

			if( window.focus ){
				finderPopUp.focus(); 
			}
		};

		// toggle checkbox - set hidden sibling input value to on|off
		$gp.inputs.toggle_customizer_checkbox = function(evt){
			$this = $(this);
			$(this).prev('input[type="hidden"]')
				.val($this.prop('checked') ? 'on' : 'off')
				.trigger('change');
		};

		// change file input value
		$('.customizer_file_url').on('change', function(){
			var value = $(this).val();
			var is_image = value.match(/\.(jpg|jpeg|png|apng|gif|webp|avif|svg|bmp|ico)$/i) !== null;
			// console.log('is_image = ', is_image);
			$(this).closest('.customizer_file_group')
				.find('.customizer_image_preview')
					.toggle(is_image)
					.find('img')
						.attr('src', (is_image ? value : ''));
		});

		// init colorpicker fields
		$('.customizer_colorpicker_group input').each(function(){
			$(this).css({
				'color' : $gp.getContrastColor($(this).val())
			});
		});

		$('.customizer_colorpicker_group').colorpicker({
				container : true,
				component : 'input'
			}).on('changeColor', function(){
				var color_val = $(this).find('input').val();
				$(this).find('input').css({
					'background-color' : color_val,
					'color' : $gp.getContrastColor(color_val)
				});
			}).on('hidePicker', function(){
				$(this).find('input').trigger('change');
			});
	},

	init		: function(){
		var _this = $gp.EditLayout.customizer;
		_this.cache		= _this.getData();
		_this.data		= _this.cache;
		_this.cache_arr	= _this.getDataArray();
		_this.data_arr	= _this.cache_arr;
		_this.bindEvents();
	},
};


// CSS/LESS/SCSS editor
$gp.EditLayout.css_editor = {

	editor		: null,

	config		: {
		mode			: 'text/x-less',
		lineWrapping	: false
	},

	textarea	: null,

	cache		: null,

	data		: null,

	isDirty		: false,

	checkDirty : function(){
		var _this = $gp.EditLayout.css_editor;

		var is_dirty = _this.data !== _this.cache;
		_this.isDirty = is_dirty;
		_this.indicateDirty();
		return is_dirty;
	},

	resetValues	: function(){
		var _this = $gp.EditLayout.css_editor;

		_this.editor.setValue(_this.cache);
		_this.editor.clearHistory();
		_this.data = _this.cache;

		_this.resetDirty();
	},

	resetDirty	: function(){
		var _this = $gp.EditLayout.css_editor;

		_this.data = _this.getData();
		_this.cache = _this.data;
		_this.isDirty = false;
		_this.indicateDirty();
	},

	indicateDirty	: function(what){ // what = optional boolen
		var _this = $gp.EditLayout.css_editor;
		var what = typeof(what) != 'undefined' ? what : _this.isDirty;
		$('.tab_switch[data-rel="css_editor_area"]')
			.toggleClass('is_dirty', what);
		$('input#gp_layout_save_css').val((what ? 'on' : ''));
	},

	getData : function(){
		var _this	= $gp.EditLayout.css_editor;

		var data	= _this.editor.getValue();
		// _this.data	= data;
		return data;
	},

	getMode : function(){
		var _this = $gp.EditLayout.css_editor;

		var mode = _this.textarea.data('mode');
		switch(mode){
			case 'scss':
				return 'text/x-scss';

			case 'less':
			default:
				return 'text/x-less';
		}
	},

	setSize : function(){
		var _this = $gp.EditLayout.css_editor;

		var parent = _this.textarea.parent();
		 //shrink the editor so we can get the container size
		_this.editor.setSize(225, 100);
		_this.editor.setSize(225, parent.height() - 5);
	},

	bindEvents : function(){
		var _this = $gp.EditLayout.css_editor;

		// events may change in future codemirror versions(!)
		_this.editor.on('change', function(evt){
			_this.data = _this.getData();
			_this.checkDirty();
			// _this.textarea.toggleClass('edited', _this.isDirty);
			$gp.EditLayout.updateButtons(); // TODO
		});

		$(window).on('resize', function(){
			_this.setSize();
		}).trigger('resize');
	},

	init : function(){
		var _this = $gp.EditLayout.css_editor;

		_this.textarea = $('#gp_layout_css');
		if( !_this.textarea.length ){
			return false;
		}
		_this.config.mode = _this.getMode();
		_this.editor = CodeMirror.fromTextArea(
			_this.textarea.get(0),
			_this.config
		);
		_this.data		= _this.getData();
		_this.cache		= _this.data;

		_this.bindEvents();
	}
};


$gp.getContrastColor = function(color_val){
	if( color_val.trim() == '' ){
		return '#000';
	}
	var $temp_elem = $('<fictum>')
		.appendTo('body')
		.css('color', color_val);
	var color = getComputedStyle($temp_elem.get(0)).color;
	$temp_elem.remove();

	if( color == 'transparent'){
		return '#000';
	}
	if( color.indexOf('rgb') !== -1 ){
		var color_array = color
			.substring(color.indexOf('(') + 1, color.length - 1)
			.replace(/ /g, '')
			.split(',');
		// console.log('color_array = ', color_array);
		if( color_array.length == 4 && color_array[3] <= 0.5 ){
			return '#000';
		}
		var r = parseInt(color_array[0]);
		var g = parseInt(color_array[1]);
		var b = parseInt(color_array[2]);
		var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
		return yiq >= 128 ? '#000' : '#fff';
	}
	return 'red'; // color parsing failed
};


$(function(){
	$gp.EditLayout.init();
});
