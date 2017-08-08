/* 
 * Theme 'Cajón Scss' 3.3.6 - edit Theme settings
 *
 */

var ThemeCajonSettingsHelpers = {
  
  config : false,
  config_backup : false,

  init : function(){
    if( typeof(gp_editor) == 'object' ){
      // create a backup of current gp_editor
      ThemeCajonSettingsHelpers.gp_editor_backup = gp_editor;
    }
    ThemeCajonSettingsHelpers.config = $.extend({}, ThemeCajonConfig);
    gp_editor = {};
    gp_editor.selectFile = function(target_input) {
      gp_editor.FinderSelect = function(fileUrl) { 
        if( fileUrl != "" ){
          target_input.val(fileUrl).trigger("change");
        }
        return true;
      };
      var finderPopUp = window.open(gpFinderUrl, 'gpFinder', 'menubar=no,width=960,height=450');
      if (window.focus) { finderPopUp.focus(); }
    }; 

    $('button#theme-cajon-select-logo-image-btn').on("click", function(e){
      e.preventDefault();
      var target_input = $('input#theme-cajon-logo-image-url');
      gp_editor.selectFile(target_input);
    });

    $('#theme-cajon-settings-form input[name="logo_img_url"]').on("change input", function(){
      ThemeCajonSettingsHelpers.config.logo_img_url = $(this).val();
      ThemeCajonSettingsHelpers.apply();
    });
    $('#theme-cajon-settings-form select[name="navbar_variant"]').on("change", function(){
      ThemeCajonSettingsHelpers.config.navbar_variant = $(this).val();
      ThemeCajonSettingsHelpers.apply();
    });
    $('#theme-cajon-settings-form select[name="navbar_position"]').on("change", function(){
      ThemeCajonSettingsHelpers.config.navbar_position = $(this).val();
      ThemeCajonSettingsHelpers.apply();
    });
    $('#theme-cajon-settings-form select[name="logo_img_shape"]').on("change", function(){
      ThemeCajonSettingsHelpers.config.logo_img_shape = $(this).val();
      ThemeCajonSettingsHelpers.apply();
    });
    $('#theme-cajon-settings-form select[name="logo_img_size"]').on("change", function(){
      ThemeCajonSettingsHelpers.config.logo_img_size = $(this).val();
      ThemeCajonSettingsHelpers.apply();
    });
    $('#theme-cajon-settings-form select[name="logo_img_border"]').on("change", function(){
      ThemeCajonSettingsHelpers.config.logo_img_border = $(this).val();
      ThemeCajonSettingsHelpers.apply();
    });
    $('#theme-cajon-settings-form input[name="logo_img_collapsed"]').on("change", function(){
      ThemeCajonSettingsHelpers.config.logo_img_collapsed = $(this).prop("checked") ? "show" : "hide";
      ThemeCajonSettingsHelpers.apply();
    });
  },

  apply : function(config){
    if( typeof(config) == "undefined" ){
      config = ThemeCajonSettingsHelpers.config;
    }

    var remove_navbar_classes = "navbar-default navbar-inverse navbar-fixed-side-left navbar-fixed-side-right";
    var add_navbar_classes = "navbar-" + config.navbar_variant + " navbar-fixed-side-" + config.navbar_position;
    var remove_logo_classes = "logo-shape-default logo-shape-circle"
      + " logo-size-small logo-size-medium logo-size-large"
      + " logo-border-none logo-border-single logo-border-double logo-border-offset"
      + " logo-collapsed-show logo-collapsed-hide";
    var add_logo_classes = "logo-shape-" + config.logo_img_shape 
      + " logo-size-" + config.logo_img_size
      + " logo-border-" + config.logo_img_border
      + " logo-collapsed-" + config.logo_img_collapsed;

    $(".navbar-theme-cajon")
      .removeClass(remove_navbar_classes)
      .addClass(add_navbar_classes);
    $("img.theme-cajon-logo")
      .removeClass(remove_logo_classes)
      .addClass(add_logo_classes)
      .attr("src", config.logo_img_url);
  },

  destroy : function(reset){
    if( reset ){ 
      ThemeCajonSettingsHelpers.apply(ThemeCajonConfig);
    }else{
      ThemeCajonConfig = $.extend({}, ThemeCajonSettingsHelpers.config);
    }
    if( typeof(ThemeCajonSettingsHelpers.gp_editor_backup) == 'object' ){
      // restore backup'd gp_editor
      gp_editor = ThemeCajonSettingsHelpers.gp_editor_backup;
    }
  }
};
