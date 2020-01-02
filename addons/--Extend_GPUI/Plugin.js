$(function(){

  var $blue_star = $('<div title="double click me away" class="gpui-poc-funky-widget"></div>')
    .appendTo('body')
    .on('dblclick', function(){
      $(this).fadeOut(1000, function(){
        $(this).remove();
      });
    })
    .draggable({
      stop : function(event, ui){
        gpui.pocx = ui.position.left; // <---- set value gpui.pocx
        gpui.pocy = ui.position.top;  // <---- set value gpui.pocy
        $gp.SaveGPUI(); // <------------------ SaveGPUI
      }
    })
    .resizable({
      stop : function(event, ui){
        gpui.pocw = ui.size.width; // <------- set value gpui.pocw
        gpui.poch = ui.size.height; // <------ set value gpui.poch
        $gp.SaveGPUI(); // <------------------ SaveGPUI
      }
    });

  console.log(
     'gpui.pocx:', gpui.pocx,
    ' gpui.pocy:', gpui.pocy,
    ' gpui.pocw:', gpui.pocw,
    ' gpui.poch:', gpui.poch
  );

  if( gpui.pocx && gpui.pocy ){
    $blue_star.css({
      'position'  : 'absolute',
      'left'      : gpui.pocx + 'px',
      'top'       : gpui.pocy + 'px'
    });
  }

  if( gpui.pocw && gpui.poch ){
    $blue_star.css({
      'position'  : 'absolute',
      'width'     : gpui.pocw + 'px',
      'height'    : gpui.poch + 'px'
    });
  }

});
