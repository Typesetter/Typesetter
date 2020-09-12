/**
 * Bootstrap 4 - Typesetter CMS theme
 *
 * non-bootstrap menu feature extensions
 * loaded with all layouts
 *
 */

$(function(){

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



	// mobile_menu_style

	// console.log('layout_config.mobile_menu_style=', layout_config.mobile_menu_style);

	// $(document).on('show.bs.collapse shown.bs.collapse hide.bs.collapse	hidden.bs.collapse', function(evt){
	//	console.log(evt.target, evt.type);
	// });

	var $navbar_collapse	= $('.main-nav .navbar-collapse');
	var $navbar_toggler		= $('.main-nav .navbar-toggler');
	var $main_header		= $('.main-header');

	switch( layout_config.mobile_menu_style.value ){

		case 'popup':
			// console.log('Popup Menu');
			var popup_fade_duration = 300; // ms

			$navbar_collapse.on('show.bs.collapse', function(evt){
				$(this).hide().fadeIn(popup_fade_duration);
			});

			$navbar_collapse.on('shown.bs.collapse', function(evt){
				$('.navbar-toggler').clone(true).appendTo($(this));
				$('html, body').addClass('prevent-scrolling');
			});

			$navbar_collapse.on('hide.bs.collapse', function(evt){
				$('html, body').removeClass('prevent-scrolling');
				$(this).fadeOut(popup_fade_duration);
			});

			$navbar_collapse.on('hidden.bs.collapse', function(evt){
				$(this).find('.navbar-toggler').remove();
			});
			break;


		case 'slideover':
			// console.log('Slide Over Menu');

			$navbar_collapse.touch(); // init jquery-touch

			$navbar_collapse.on('show.bs.collapse', function(evt){
				var set_collapse_top = function(){
					var top = $main_header.get(0).getBoundingClientRect().bottom;
					top = top < 0 ? 0 : top;
					$navbar_collapse.css('top', top);
				}
				set_collapse_top();
				$(window).on('scroll.themebs4.slideover', set_collapse_top);
				$(document).one('navbar_expanded.themebs4.slideover', function(){
					console.log('navbar_expanded - ' + Date.now());
					$navbar_toggler.trigger('click');
				});
				$(this)
					.addClass('main-nav-sliding-in')
					.on('swipeRight.themebs4', function(evt, info){
						// console.log('jquery-touch info = ', info);
						$navbar_toggler.trigger('click');
					});
			});

			$navbar_collapse.on('shown.bs.collapse', function(evt){
				$(this).removeClass('main-nav-sliding-in');
			});

			$navbar_collapse.on('hide.bs.collapse', function(evt){
				$(this).addClass('main-nav-sliding-out');
			});

			$navbar_collapse.on('hidden.bs.collapse', function(evt){
				$(this)
					.removeClass('main-nav-sliding-in main-nav-sliding-out show')
					.off('swipeRight.themebs4');
				$(window).off('scroll.themebs4.slideover');
				$(document).off('navbar_expanded.themebs4.slideover');
			});
			break;


		case 'offcanvas':
			// console.log('Off Canvas Menu');

			$navbar_collapse.touch(); // init jquery-touch

			$navbar_collapse.on('show.bs.collapse', function(evt){
				var move_toggler = function(){
					var toggler_right = $main_header.get(0).getBoundingClientRect().right -
						$navbar_toggler.parent().get(0).getBoundingClientRect().right;
					$navbar_toggler.css({
						transform : 'translateX(' + toggler_right + 'px)'
					});
				}
				move_toggler();
				$(window).on('resize.themebs4', move_toggler);

				var nav_width = $navbar_collapse.outerWidth() - 1; // console.log('w = ', $nav_width);
				$('body').css({
					left : '-' + nav_width + 'px'
				});
				$(this)
					.addClass('main-nav-canvas-on')
					.on('swipeRight.themebs4', function(evt, info){
						// console.log('jquery-touch info = ', info);
						$navbar_toggler.trigger('click');
					});
				$(document).one('navbar_expanded.themebs4.offcanvas', function(){
					console.log('navbar_expanded - ' + Date.now());
					$navbar_toggler.trigger('click');
				});
			});

			$navbar_collapse.on('shown.bs.collapse', function(evt){
				$(this).removeClass('main-nav-canvas-on');
			});

			$navbar_collapse.on('hide.bs.collapse', function(evt){
				$('body').css({
					left : 0
				});
				$navbar_toggler.css({
					transform : 'translateX(0)'
				});
				$(this).addClass('main-nav-canvas-off');
			});

			$navbar_collapse.on('hidden.bs.collapse', function(evt){
				$(this)
					.removeClass('main-nav-canvas-on main-nav-canvas-off show')
					.off('swipeRight.themebs4');
				$navbar_toggler.removeAttr('style');
				$('body').css({
					left : 0
				});
				$(window).off('resize.themebs4');
				$(document).off('navbar_expanded.themebs4.offcanvas');
			});
			break;

	}
});
