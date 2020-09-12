/**
 * Bootstrap 4 - Typesetter CMS theme
 * javascript loaded with all layouts
 */


var Theme_Bootstrap4 = {

	init:	function(){

		$(window)
			.on('resize', Theme_Bootstrap4.detectBreakpoint)
			.trigger('resize');

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
	},

	breakpoint : false,
	navbar_expanded : false,

	detectBreakpoint : function(){
		var breakpoint = false;
		$('#detect-bootstrap-breakpoints > div').each( function(){
			var $this = $(this);
			// console.log($this.data('breakpoint'), $this.is(':visible'));
			if( $this.is(':visible') ){
				breakpoint = $this.data('breakpoint');
				return;
			}
		});

		if( breakpoint !== Theme_Bootstrap4.breakpoint ){ // breakpoint change
			$('html')
				.removeClass('breakpoint-xs breakpoint-sm breakpoint-md breakpoint-lg breakpoint-xl')
				.addClass('breakpoint-' + breakpoint);

			$(document).trigger('breakpoint_change', [
				Theme_Bootstrap4.breakpoint, // 1st param = from
				breakpoint // 2nd param = to
			]);

			Theme_Bootstrap4.breakpoint = breakpoint;

			var navbar_expanded = $('#breakpoint-navbar-expanded').is(':visible');
			if( Theme_Bootstrap4.navbar_expanded !== navbar_expanded ){
				Theme_Bootstrap4.navbar_expanded = navbar_expanded;
				$('html').toggleClass('navbar-expanded', Theme_Bootstrap4.navbar_expanded);
				$(document).trigger('navbar_' + (navbar_expanded ? 'expanded' : 'collapsed'));
			}

		}

	}

};


$(function(){

	// enable bootstrap tooltips and popovers
	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover();

	Theme_Bootstrap4.init();

});


// example how to use the breakpoint_change event
/*// <- remove the * to uncomment the block
$(document).on('breakpoint_change', function(evt, from, to){
	console.log('breakpoint changed from ' + from + ' to ' + to);
});
//*/


// examples how to use the global navbar_expanded and navbar_collapsed events
/*// <- remove the * to uncomment the block
$(document).on('navbar_expanded', function(evt){
	console.log('navbar expanded');
});
$(document).on('navbar_collapsed', function(evt){
	console.log('navbar collapsed');
});
//*/
