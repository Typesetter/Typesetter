// --------------------------------------------
// Theme 'Cajón Scss' 3.3.6 -- Theme JavaScript
// --------------------------------------------

$(function(){

  $("li:not(.active-parent-li) > .sublinks-menu").hide();
  
  $(".sidebar-nav a.sublinks-toggle").on("click", function(){
    var $ul = $(this).next("ul");
    var is_open = $ul.is(":visible");
    if( is_open ){
      $(this).attr("aria-expanded", "false");
      $ul.slideUp(300);
    }else{
      $(this).attr("aria-expanded", "true");
      $ul.slideDown(300);
    }
  });

});

