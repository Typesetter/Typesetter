
$(function(){

	var iframe		= document.getElementById('gp_layout_iframe');
	var $rows		= $('#revision_rows').children();

	$rows.find('[target=gp_layout_iframe]').hide();

	/**
	 * Show a revision
	 *
	 */
	function ShowRevision($row){

		if( !$row.length ){
			return;
		}

		$rows.removeClass('active');
		$row.addClass('active');

		var href		= $row.find('a[target=gp_layout_iframe]').get(0).href;
		iframe.src		= href;
	}


	/**
	 * Handle clicks on table rows
	 *
	 */
	$rows.click(function(evt){


		if( evt.target.nodeName == 'A' ){
			return;
		}

		var $this = $(this);
		ShowRevision($this);


	}).first().addClass('active');

	/**
	 * Show previous revision
	 *
	 */
	$gp.links.OlderRevision = function(){
		var $row = $rows.filter('.active').next();
		ShowRevision($row);
	}

	/**
	 * Show next revision
	 *
	 */
	$gp.links.NewerRevision = function(){
		var $row = $rows.filter('.active').prev();
		ShowRevision($row);
	}

});
