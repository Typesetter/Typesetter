/**
 * Bootstrap 4 - Typesetter CMS theme
 * javascript loaded with all layouts
 */

$(function(){

	// Enable Bootstrap tooltips and popovers
	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover();


	// make Simple Blog's Archive Gadget's years expandable and collapsed by default
	if( $('.simple_blog_gadget_year').length ){
		$('.simple_blog_gadget_year')
			.css('cursor', 'pointer')	// will be clickable, so let's change the cursor to pointer 
			.on('click', function(){	// click handler
				$(this).siblings('ul').toggleClass('show');
			})
			.siblings('ul')
				.addClass('collapse'); // initially hide all but the first level ULs
	}

});
