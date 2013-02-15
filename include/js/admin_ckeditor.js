
$(function(){



	var $icon_list = $('#available_icons');

	CKEDITOR.on( 'instanceReady', function(){

		return;

		/**
		 * Get available toolbar elements
		 * ckeditor/plugins/toolbar/plugins.js getToolbarConfig() buildToolbarConfig() getItemDefinedGroups()
		 */
		var editor = CKEDITOR.instances.gpcontent;
		var items = editor.ui.items;
		var list = {};
		var output = [];
		for( i in items ){
			var item = editor.ui.create( i );

			//CKEDITOR.skin.getIconStyle( iconName, ( this.editor.lang.dir == 'rtl' ), iconName == this.icon ? null : this.icon, this.iconOffset ),
			//CKEDITOR.skin.getIconStyle(
			var style = CKEDITOR.skin.getIconStyle( i , false, null, 0);
			$icon_list.append('<span style="display:inline-block;padding:3px;margin:5px;border:1px solid #ccc;border-radius:2px;width:85px;overflow:hidden;white-space:nowrap"><span style="display:inline-block;margin-right:3px;vertical-align:bottom;width:16px;height:16px;'+style+'"></span>'+i+'</span>');
			//console.log(style);

			var item_obj = item.render( editor, output );
			//var button = item_obj.button.render( editor, output );
			//console.log(button);
			//console.log( item_obj );
			//list[ i ] = item;
			//console.log( item_obj2 );
		}

		console.log( output );
		//console.log( 'done' );
		//console.log( list );

		//for ( itemName in editor.ui.items ) {
		//alert('add');
	});
});