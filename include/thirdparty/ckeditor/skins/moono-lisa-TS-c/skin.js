/*
 Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
*/
CKEDITOR.skin.name="moono-lisa-TS";CKEDITOR.skin.ua_editor="ie,gecko";CKEDITOR.skin.ua_dialog="ie";
CKEDITOR.skin.chameleon=function(){var b=function(){return function(b,d){for(var a=b.match(/[^#]./g),e=0;3>e;e++){var f=e,c;c=parseInt(a[e],16);c=("0"+(0>d?0|c*(1+d):0|c+(255-c)*d).toString(16)).slice(-2);a[f]=c}return"#"+a.join("")}}(),f={editor:new CKEDITOR.template("{id}.cke_chrome [border-color:{defaultBorder};] {id} .cke_top [ background-color:{defaultBackground};border-bottom-color:{defaultBorder};] {id} .cke_bottom [background-color:{defaultBackground};border-top-color:{defaultBorder};] {id} .cke_resizer [border-right-color:{ckeResizer}] {id} .cke_dialog_title [background-color:{defaultBackground};border-bottom-color:{defaultBorder};] {id} .cke_dialog_footer [background-color:{defaultBackground};outline-color:{defaultBorder};] {id} .cke_dialog_tab [background-color:{dialogTab};border-color:{defaultBorder};] {id} .cke_dialog_tab:hover [background-color:{lightBackground};] {id} .cke_dialog_contents [border-top-color:{defaultBorder};] {id} .cke_dialog_tab_selected, {id} .cke_dialog_tab_selected:hover [background:{dialogTabSelected};border-bottom-color:{dialogTabSelectedBorder};] {id} .cke_dialog_body [background:{dialogBody};border-color:{defaultBorder};] {id} a.cke_button_off:hover,{id} a.cke_button_off:focus,{id} a.cke_button_off:active [background-color:{darkBackground};border-color:{toolbarElementsBorder};] {id} .cke_button_on [background-color:{ckeButtonOn};border-color:{toolbarElementsBorder};] {id} .cke_toolbar_separator,{id} .cke_toolgroup a.cke_button:last-child:after,{id} .cke_toolgroup a.cke_button.cke_button_disabled:hover:last-child:after [background-color: {toolbarElementsBorder};border-color: {toolbarElementsBorder};] {id} a.cke_combo_button:hover,{id} a.cke_combo_button:focus,{id} .cke_combo_on a.cke_combo_button [border-color:{toolbarElementsBorder};background-color:{darkBackground};] {id} .cke_combo:after [border-color:{toolbarElementsBorder};] {id} .cke_path_item [color:{elementsPathColor};] {id} a.cke_path_item:hover,{id} a.cke_path_item:focus,{id} a.cke_path_item:active [background-color:{darkBackground};] {id}.cke_panel [border-color:{defaultBorder};] "),panel:new CKEDITOR.template(".cke_panel_grouptitle [background-color:{lightBackground};border-color:{defaultBorder};] .cke_menubutton_icon [background-color:{menubuttonIcon};] .cke_menubutton:hover,.cke_menubutton:focus,.cke_menubutton:active [background-color:{menubuttonHover};] .cke_menubutton:hover .cke_menubutton_icon, .cke_menubutton:focus .cke_menubutton_icon, .cke_menubutton:active .cke_menubutton_icon [background-color:{menubuttonIconHover};] .cke_menubutton_disabled:hover .cke_menubutton_icon,.cke_menubutton_disabled:focus .cke_menubutton_icon,.cke_menubutton_disabled:active .cke_menubutton_icon [background-color:{menubuttonIcon};] .cke_menuseparator [background-color:{menubuttonIcon};] a:hover.cke_colorbox, a:active.cke_colorbox [border-color:{defaultBorder};] a:hover.cke_colorauto, a:hover.cke_colormore, a:active.cke_colorauto, a:active.cke_colormore [background-color:{ckeColorauto};border-color:{defaultBorder};] ")};
return function(g,d){var a=b(g.uiColor,.4),a={id:"."+g.id,defaultBorder:b(a,-.2),toolbarElementsBorder:b(a,-.25),defaultBackground:a,lightBackground:b(a,.8),darkBackground:b(a,-.15),ckeButtonOn:b(a,.4),ckeResizer:b(a,-.4),ckeColorauto:b(a,.8),dialogBody:b(a,.7),dialogTab:b(a,.65),dialogTabSelected:"#FFF",dialogTabSelectedBorder:"#FFF",elementsPathColor:b(a,-.6),menubuttonHover:b(a,.1),menubuttonIcon:b(a,.5),menubuttonIconHover:b(a,.3)};return f[d].output(a).replace(/\[/g,"{").replace(/\]/g,"}")}}();

CKEDITOR.on( 'dialogDefinition', function( ev ) {
  var dialogName = ev.data.name;
  var dialogDefinition = ev.data.definition;
  var dialog = dialogDefinition.dialog;


  if ( dialogName == 'find' ) {
    dialogDefinition.height = 215;
  };


  if ( dialogName == 'link' ) {
    dialogDefinition.height = 315;
  };


  if( dialogName == 'image' ){
    dialogDefinition.height = 465;
 
// make Image preview box more scalable
	dialogDefinition.onLoad = function () {
		var dialog = CKEDITOR.dialog.getCurrent(); 

		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutationRecord) {
				var scale;
				var img = $('.cke_dialog .ImagePreviewBox img').get(0);
				var box = $('.cke_dialog .ImagePreviewBox').get(0);
				var boxstyle = window.getComputedStyle(box, null);
				var boxW = box.clientWidth - parseFloat(boxstyle.paddingLeft) - parseFloat(boxstyle.paddingRight);
				var boxH = box.clientHeight - parseFloat(boxstyle.paddingTop) - parseFloat(boxstyle.paddingBottom);
				var inpW = dialog.getValueOf( 'info', 'txtWidth' );
				var inpH = dialog.getValueOf( 'info', 'txtHeight' )

				//workaround for ckEditor wrong % and empty sizes processing
				if ( inpW.indexOf('%') >= 0){
					imgW = Math.round(parseFloat(inpW)/100*boxW);
					$('.cke_dialog .ImagePreviewBox img').css({
						'width': imgW
					});
				}else{	
					imgW = img.offsetWidth;
					if ( inpW == '' ) {
						$('.cke_dialog .ImagePreviewBox img').css({
							'width': 'auto'
						})
					};
				};
				if ( inpH.indexOf('%') >= 0){
					imgH = Math.round(parseFloat(inpH)/100*boxH);
					$('.cke_dialog .ImagePreviewBox img').css({
						'height': imgH
					});
				}else{	
					imgH = img.offsetHeight;
					if ( inpH == '' ) {
						$('.cke_dialog .ImagePreviewBox img').css({
							'height': 'auto'
						})
					};			
				};

				//adjust preview box scale to imitate real image alignment
				if (imgW != 0) {
					scale = Math.min( boxW/(imgW*1.1), boxH/(imgH*1.1), 1 );
					tableW = Math.max( Math.round(1.1*imgW), boxW );
					$('.cke_dialog .ImagePreviewBox table').css({
						'transform-origin': 'top left',
						'transform': 'scale(' + scale + ')',
						'width': tableW
					});
				}else{
					$('.cke_dialog .ImagePreviewBox table').css({
						'transform': 'scale(1)',
						'width': 'auto'
					});
				}
			});    
		});
		var target = $('.cke_dialog .ImagePreviewBox img').get(0);
		observer.observe(target, { attributes : true, attributeFilter : ['style'] });		
      };
  };
  

  if( dialogName == 'flash' ){
    dialogDefinition.height = 340;
  };


  if( dialogName == 'table' ){
    dialogDefinition.height = 415;
  };  
});