/* 
######################################################################
JS/jQuery script for Typesetter CMS - Cajón Parallax - User runtime
Author: J. Krausz
Date: 2017-05-16
Version: 2.0
######################################################################
*/

var CajonParallax = {

  breakpoint : 768, // min width of viewport (px) to enable parallax effects

  init : function(){
    var ww=$(window).innerWidth(), wh=$(window).innerHeight(); 
    $('.cajon-parallax-image.scroll-parallax.scaling-cover').each(function(){
      var d=$(this), i=d.find('img'), ha=(d.attr('data-halign')||50);
      i.css({'width':'auto','height':'auto'});
      var dp=d.width()/d.height(), ip=i.width()/i.height();
      if(ip>dp){
        i.css('height',d.height()+'px')
         .css({'margin-left':((d.width()-i.width())*(ha/100))+'px','margin-top':'0'});
      }else{
        i.css('width',d.width()+'px')
         .css({'margin-left':'0','margin-top':((d.height()-i.height())/2)+'px'});
      }
      var sf=(d.attr('data-scroll-speed')||50)/100, 
        s=sf+((1-sf)*(wh/i.height())),
        sc=s<1?1:s;
      i.attr('data-scale',sc);
    });
    $('.cajon-parallax-image.scroll-fixed.scaling-cover').each(function(){
      var d=$(this), dw=d.width(), dol=d.offset().left, 
        i=d.find('img'), ih=i.height(), iw=i.width(), ip=iw/ih,
        wp=ww/wh, ha=(d.attr('data-halign')||50),
        niw=ip>wp?iw*(wh/ih):ww,
        bgx=(dol*(100-ha)/100)+(dol+dw-niw)*(ha/100)+'px';
      d.css('background-position',bgx+' 50%');
    });
  }, /* /(fn)init */

  scroll : function(){
    if($(window).innerWidth()<=CajonParallax.breakpoint){return;}
    var wh=$(window).innerHeight(), 
      wst=$(window).scrollTop();
    $('.cajon-parallax-image.scroll-parallax.scaling-cover').each(function(){
      var d=$(this), dh=d.height(), dt=d.offset().top;
      if((dt-wst)>wh||(dt+dh-wst)<0){return;}
      var i=d.find('img'),
        ha=(d.attr('data-halign')||50),
        sf=(d.attr('data-scroll-speed')||50)/100,
        s=i.attr('data-scale') || 1,
        tY=((wh/2-(dt+dh/2-wst))*(1-sf))+'px';
      i.css({
        '-webkit-transform-origin':ha+'% 50%',
        '-ms-transform-origin':ha+'% 50%',
        'transform-origin':ha+'% 50%',
        '-webkit-transform':'translateY('+tY+') scale('+s+')',
        '-ms-transform':'translateY('+tY+') scale('+s+')',
        'transform':'translateY('+tY+') scale('+s+')'
      });
    });
    $('.cajon-parallax-image.scroll-parallax.scaling-tile').each(function(){
      var bgy=wst*(1-($(this).attr('data-scroll-speed')||50)/100),
      ha=($(this).attr('data-halign')||50);
      $(this).css('background-position',ha+'% '+bgy+'px');
    });
  } /* /(fn)scroll */

}; /* /(obj)ParralaxImage */



$(window).on("load", function(){
  if( !isadmin ){
    var uaStr = window.navigator.userAgent;
    if( uaStr.indexOf('AppleWebKit/') !== -1 && uaStr.indexOf('Safari/') !== -1 ){
      $.scrollSpeed(80, 800);
    }
    if( uaStr.indexOf('Chrome/') !== -1 && parseInt(uaStr.substr(uaStr.indexOf('Chrome/') + 7, 2)) <= 49){
      $.scrollSpeed(80, 800);
    }
    // Internet Explorer + Edge:
    if ( uaStr.indexOf('MSIE ') !== -1 || uaStr.indexOf('Trident/') !== -1 || uaStr.indexOf('Edge/') !== -1 ){ 
      $.scrollSpeed(80, 800);
    }
    // Firefox doesn't need it, scrolls just fine
  }
});


$(window).on("load resize", function(){
  if(window.innerWidth<=CajonParallax.breakpoint){
    $(".cajon-parallax-image.scroll-parallax, .cajon-parallax-image.scroll-fixed")
      .addClass("scroll-static");
  }else{
    $(".cajon-parallax-image.scroll-parallax.scroll-static, .cajon-parallax-image.scroll-fixed")
      .removeClass("scroll-static");
    CajonParallax.init();
    CajonParallax.scroll();
  }
});

$(window).on("scroll", CajonParallax.scroll);
