/**
 * Bootstrap 4 - Typesetter CMS theme
 * javascript loaded with all layouts
 */

$(function(){

	// Enable Bootstrap tooltips and popovers
	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover();


	// multi-level main nav
	$('.dropdown-menu a.dropdown-toggle').on('click', function(e){
		var $this = $(this);
		var $parent_li = $this.parent('.dropdown');
		var $next_ul = $this.next('.dropdown-menu');

		var $toggle = !$this.hasClass('dropdown-active');

		if( !$toggle ){
			$parent_li.find('.dropdown-item').removeClass('dropdown-active');
			$parent_li.find('.show').removeClass('show');
		}

		$this.toggleClass('dropdown-active', $toggle);
		$parent_li.toggleClass('show', $toggle);
		$next_ul.toggleClass('show', $toggle);

		$this.parents('li.nav-item.dropdown.show')
			.on('hidden.bs.dropdown', function(e){
				$('.dropdown-menu .show').removeClass('show');
				$this.removeClass('dropdown-active');
			});

		return false;
	});


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
