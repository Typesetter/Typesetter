// Sidebar Menu
// not a default Bootstrap menu type

var SidebarMenu = {

  ariaLabel : 'Toggle subnavigation',
  animDuration : 200, // animation duration in ms

  init : function(){
    $('.sidebar ul.nav-stacked li.expandable').each( function(){

      var $this = $(this);
      if( !$this.hasClass('expanded') ){
        $this.children('ul').slideUp(0);
      }

      var aria_id = SidebarMenu.generateAriaId();
      var aria_expanded = $this.hasClass('expanded') ? 'true' : 'false';

      $this.children('ul').first().attr('id', aria_id);

      $('<span class="expand-toggler" ' + 
        'title="' + SidebarMenu.ariaLabel + '" ' +
        'aria-label="' + SidebarMenu.ariaLabel + '" ' +
        'aria-controls="' + aria_id + '" ' + 
        'aria-expanded="' + aria_expanded + '">')
        .on('click', SidebarMenu.toggle)
        .prependTo($this);

      // if link points to an anchor use it as expand-toggler as well
      $this.children('a[href^="#"]').on('click', SidebarMenu.toggle);
    });
  },

  toggle : function(){
    var $this = $(this).is('a') ? $(this).closest('li').children('.expand-toggler') : $(this);
    var $li = $this.closest('li');
    $li.children('ul').slideToggle(SidebarMenu.animDuration, function(){
      var expanded = $this.attr('aria-expanded') === 'true';
      $li.toggleClass('expanded', !expanded);
      $attr_val = expanded ? 'false' : 'true';
      $this.attr('aria-expanded', $attr_val);
    });
  },

  generateAriaId : function(length){
    var length = length || 8;
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var clength = chars.length;
    var result = [];
    for(var i = 0; i < length; i++ ){
       result.push(chars.charAt(Math.floor(Math.random() * clength)));
    }
    return 'aria-' + result.join('');
  },

};


// init sidebar menu on domready
$(function(){
  SidebarMenu.init();
});
