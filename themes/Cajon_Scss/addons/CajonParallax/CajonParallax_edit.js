/*
#######################################################################
JS/jQuery script for Typesetter CMS - Cajón Parallax - Editor Component
Author: J. Krausz
Date: 2017-05-16
Version: 2.0
#######################################################################
*/

function gp_init_inline_edit(area_id,section_object) { 

  $gp.LoadStyle( CajonParallax.base + '/CajonParallax_edit.css' );
  gp_editing.editor_tools();
  var edit_div = gp_editing.get_edit_area(area_id);
  var content_cache = false;


  gp_editor = {

    edit_div            : gp_editing.get_edit_area(area_id),
    save_path           : gp_editing.get_path(area_id),

    destroy             : function(){}, // not used
    isDirty             : false,        // boolean that indicates if anything has changed
    checkDirty          : function(){}, // Typesetter calls this to determine if saving is due
    resetDirty          : function(){}, // not used
    gp_saveData         : function(){}, // Typesetter calls this when (auto)saving

    selectImage         : function(){}, // when 'Select Image' button is clicked
    selectUsingFinder   : function(){}, // invokes a Finder popup window
    setImage            : function(){}, // callback for Finder when image was selected
    generateAltText     : function(){}, // generate Alternative Text for image from file name

    getOptions          : function(){}, // get options from content section
    updateElement       : function(){}, // apply options to content section
    updateEditor        : function(){}, // apply options to editor controls

    ui                  : {},           // editor controls jQuery objects will be added here

    options             : {             // the editor's internal storage for option values
                            image_src     : '', // the image url
                            alt_text      : '', // alternative text
                            scrolling     : '', // scrolling type: 'parallax' | 'static' | 'fixed'
                            scroll_speed  : '', // number from 5 to 95 
                            halign        : '', // number from 5 to 95 
                            scaling       : '', // image scling: 'cover' or 'tile'
                          }

  }; /* gp_editor -- end */


  /* ------------------------ */
  /* --- EDITOR FUNCTIONS --- */
  /* ------------------------ */


  gp_editor.checkDirty = function(){
    return gp_editor.isDirty;
  };



  gp_editor.gp_saveData = function(){
    var save_clone = gp_editor.edit_div.clone();
    save_clone.find("img").removeAttr("style");
    save_clone.find(".gpclear").remove();
    var content = save_clone.html();
    save_clone = null;
    gp_editor.isDirty = false;
    return '&gpcontent=' + encodeURIComponent(content);
    //+ '&' + $.param(gp_editor.options); // ver 2+: options won't be stored separately in the backend anymore
  };



  gp_editor.selectImage = function(){
    gp_editor.selectUsingFinder(gp_editor.setImage);
  }; /* fnc gp_editor.selectImage --end */



  gp_editor.setImage = function(fileUrl){
    gp_editor.options.image_src = fileUrl;
    gp_editor.generateAltText(false);
    gp_editor.updateEditor();
    gp_editor.updateElement();
    gp_editor.isDirty = true;
  }; /* fnc gp_editor.setImage --end */



  gp_editor.selectUsingFinder = function(callback_fn) {
    gp_editor.FinderSelect = function(fileUrl) { 
      if (fileUrl != "") {
        $.isFunction(callback_fn) ? callback_fn(fileUrl) : false;
      }
      // kill the FinderSelect function to re-enable possible CKEditor calls
      setTimeout(function(){ delete gp_editor.FinderSelect; }, 150);
      return true;
    };
    var finderPopUp = window.open(gpFinderUrl, 'gpFinder', 'menubar=no,width=960,height=540');
    if (window.focus) { finderPopUp.focus(); }
  }; /* fnc gp_editor.selectUsingFinder --end */



  gp_editor.getOptions = function(){
    var pxi = gp_editor.edit_div.find('.cajon-parallax-image');
    var classes = pxi.attr("class").split(" ");
    var scrolling = "parallax";
    var scaling = "cover";
    $.each( classes, function(i, class_name){
      if( class_name.indexOf("scroll-") != -1 ){
        scrolling = class_name.trim().substr(7);
        // console.log("found:" + scrolling);
      }
      if( class_name.indexOf("scaling-") != -1 ){
        scaling = class_name.trim().substr(8);
        // console.log("found:" + scaling);
      }
    });
    gp_editor.options = {
      image_src     :  pxi.find("img").attr("src") || "",
      alt_text      :  pxi.find("img").attr("alt") || 'Parallax Image',
      halign        :  pxi.attr("data-halign") || "50",
      scroll_speed  :  pxi.attr("data-scroll-speed") || "50",
      scrolling     : scrolling,
      scaling       : scaling
    };
  };



  gp_editor.updateElement = function() {
    edit_div.find(".cajon-parallax-image")
      .css({
        "background-image" : "url('" + gp_editor.options.image_src + "')",
        "background-position" : gp_editor.options.halign + "% 50%",
       })
      .removeClass("scroll-parallax scroll-fixed scroll-static scaling-cover scaling-tile")
      .addClass("scroll-" + gp_editor.options.scrolling)
      .addClass("scaling-" + gp_editor.options.scaling)
      .attr("data-scroll-speed", gp_editor.options.scroll_speed)
      .attr("data-halign", gp_editor.options.halign)
      .find("img")
        .prop("src", gp_editor.options.image_src)
        .attr("alt", gp_editor.options.alt_text)
        .one("load", function() {
          // console.log("image loaded");
          $(window).trigger("resize"); // updates parallax calculations -> ParallaxImage.js
        });
  }; /* fnc gp_editor.updateElement - end */



  gp_editor.updateEditor = function(){
    var isrc = gp_editor.options.image_src;
    gp_editor.ui.img_preview_area.find("img")
      .prop("src", isrc)
      .attr("title", isrc.substring(isrc.lastIndexOf("/") + 1));
    gp_editor.ui.alt_text.val(gp_editor.options.alt_text);
    gp_editor.ui.scrolling.val(gp_editor.options.scrolling);
    gp_editor.ui.scroll_speed.val(gp_editor.options.scroll_speed);
    gp_editor.ui.scaling.val(gp_editor.options.scaling);
    gp_editor.ui.halign.val(gp_editor.options.halign);
  }; /* fnc gp_editor.updateEditor - end */



  gp_editor.generateAltText = function(force){
    if( !force && gp_editor.options.alt_text && gp_editor.options.alt_text != "" ){
      return;
    }
    var image_src = gp_editor.options.image_src;
    var autoAlt = 
    (!image_src || image_src == "") 
    ? "Parallax Image" 
    : image_src.substring(
        image_src.lastIndexOf("/") + 1, 
        image_src.lastIndexOf(".")
      ).split("_").join(" ");
    gp_editor.ui.alt_text.val(autoAlt);
    gp_editor.options.alt_text = autoAlt;
  }; /* fnc gp_editor.generateAltText --end */



  /* ----------------------- */
  /* --- EDITOR CONTROLS --- */
  /* ----------------------- */

  gp_editor.ui.option_area = $('<div id="ts_PI_options"/>').prependTo('#ckeditor_top');


  /* IMAGE PREVIEW */
  gp_editor.ui.img_preview_area = $('<div id="ts_PI_preview_area"><img src=""/></div>');
  gp_editor.ui.img_preview_area
    .on('click', gp_editor.selectImage)
    .appendTo(gp_editor.ui.option_area);

  /* SELECT IMAGE BUTTON */
  var label = $('<label class="ts_PI_editor_control" />');
  gp_editor.ui.select_image = $('<button><i class="fa fa-image"></i> Select Image</button>');
  gp_editor.ui.select_image
    .appendTo(label)
    .on('click', gp_editor.selectImage);
  label.appendTo(gp_editor.ui.option_area);


  /* ALT TEXT */
  var label = $('<label title="Alternative Text" class="ts_PI_editor_control" />');
  gp_editor.ui.alt_text = $('<input id="ts_PI_alt" placeholder="Alternative Text" type="text" />');
  gp_editor.ui.alt_text
    .appendTo(label)
    .on('keyup change input', function(){
      gp_editor.options.alt_text = $(this).val();
      gp_editor.isDirty = true;
      gp_editor.updateElement();
    });
  gp_editor.ui.gen_alt_button = $('<button id="ts_PI_generateAlt" title="Generate Alt Text from file name"><i class="fa fa-font"></i></button>');
  gp_editor.ui.gen_alt_button
    .appendTo(label)
    .on('click', function(){
      gp_editor.generateAltText(true); // true = force
      gp_editor.isDirty = true;
      gp_editor.updateElement();
    });
  label.appendTo(gp_editor.ui.option_area);


  /* SCROLLING TYPE = HIDDEN, WILL BE SET VIA SCROLL SPEED SLIDER VALUE */
  var label = $('<div style="display:none" />');
  gp_editor.ui.scrolling = $('<input type="hidden">');
  gp_editor.ui.scrolling
    .appendTo(label)
    .on('change', function(){
      gp_editor.options.scrolling = $(this).val();
      gp_editor.isDirty = true;
      gp_editor.updateElement();
    });
  label.appendTo(gp_editor.ui.option_area);


  /* SCROLL SPEED */
  var label = $('<label title="Scroll Speed" class="ts_PI_editor_control">');
  var desc = $('<span class="label-desc"><span style="float:left;">fixed</span><span style="float:right;">static</span><span>parallax</span></span>')
    .appendTo(label);
  gp_editor.ui.scroll_speed = $('<input type="range" min="0" max="100" step="5"/>');
  gp_editor.ui.scroll_speed
    .appendTo(label)
    .on('change input', function(){
      var v = $(this).val();
      // $(this).val( Math.max(Math.min( $(this).val() , 100), 0) );
      $(this).closest('label').attr('title','Scroll Speed (' + v + '%)');
      gp_editor.options.scroll_speed = v;
      switch(v){
        case '0':
          gp_editor.ui.scrolling.val('fixed').trigger('change');
          break;
        case '100':
          gp_editor.ui.scrolling.val('static').trigger('change');
          break;
        default:
          gp_editor.ui.scrolling.val('parallax').trigger('change');
      }
      gp_editor.isDirty = true;
      gp_editor.updateElement();
    });
  label.appendTo(gp_editor.ui.option_area);


  /* SCALING */
  var label = $('<label title="Scaling" class="ts_PI_editor_control" />');
  gp_editor.ui.scaling = $('<select>'
   + '<option value="cover">cover</option>'
   + '<option value="tile">tile (repeat)</option>' 
   + '</select>');
  gp_editor.ui.scaling
    .appendTo(label)
    .on('change', function(){
      gp_editor.options.scaling = $(this).val();
      gp_editor.isDirty = true;
      gp_editor.updateElement();
    });
  label.appendTo(gp_editor.ui.option_area);


  /* HORIZONTAL ALIGNMENT */
  var label = $('<label title="Horizontal Alignment" class="ts_PI_editor_control" />');
  var desc = $('<span class="label-desc"><span style="float:left;">left&nbsp;&nbsp;&nbsp;</span>'
    +' <span style="float:right;">right</span><span>center</span></span>')
    .appendTo(label);
  gp_editor.ui.halign = $('<input type="range" min="0" max="100" step="5"/>');
  gp_editor.ui.halign
    .appendTo(label)
    .on('change input', function(){
      // $(this).val( Math.max(Math.min( $(this).val(), 100), 0) );
      var v = $(this).val();
      $(this).closest('label').attr('title','Horizontal Alignment (' + v + '%)');
      gp_editor.options.halign = v;
      gp_editor.isDirty = true;
      gp_editor.updateElement();
    });
  label.appendTo(gp_editor.ui.option_area);


  /* ------------------- */
  /* --- INIT EDITOR --- */
  /* ------------------- */

  gp_editor.getOptions();   // get option values from content section
  gp_editor.updateEditor(); // apply them to the editor controls
  loaded();                 // hide loading overlay (Typesetter fnc)

} /* fnc gp_init_inline_edit --end */
