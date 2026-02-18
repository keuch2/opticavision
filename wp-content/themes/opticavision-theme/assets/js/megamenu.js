/**
 * OpticaVision Megamenu JavaScript
 * Funcionalidades para el sistema de megamenús
 */

(function($) {
    'use strict';

    class OpticaVisionMegamenu {
        constructor() {
            this.init();
        }

        init() {
            this.setupMobileToggle();
            this.setupKeyboardNavigation();
            this.setupAccessibility();
            this.setupHoverDelays();
            
            // Re-initialize on window resize
            $(window).on('resize', this.handleResize.bind(this));
        }

        setupMobileToggle() {
            // Toggle megamenu on mobile
            $(document).on('click', '.has-megamenu > a', function(e) {
                if (window.innerWidth <= 1024) {
                    e.preventDefault();
                    
                    const $menuItem = $(this).parent();
                    const $megamenu = $menuItem.find('.megamenu-dropdown');
                    
                    // Close other open megamenus
                    $('.has-megamenu').not($menuItem).removeClass('menu-item-expanded');
                    $('.megamenu-dropdown').not($megamenu).slideUp(300);
                    
                    // Toggle current megamenu
                    $menuItem.toggleClass('menu-item-expanded');
                    $megamenu.slideToggle(300);
                }
            });

            // Close megamenu when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.has-megamenu').length) {
                    $('.has-megamenu').removeClass('menu-item-expanded');
                    $('.megamenu-dropdown').slideUp(300);
                }
            });
        }

        setupKeyboardNavigation() {
            // Handle keyboard navigation
            $('.main-navigation .menu-item > a').on('keydown', function(e) {
                const $menuItem = $(this).parent();
                const $menu = $menuItem.parent();
                const $allItems = $menu.find('> .menu-item');
                const currentIndex = $allItems.index($menuItem);

                switch(e.keyCode) {
                    case 37: // Left arrow
                        e.preventDefault();
                        if (currentIndex > 0) {
                            $allItems.eq(currentIndex - 1).find('> a').focus();
                        }
                        break;
                        
                    case 39: // Right arrow
                        e.preventDefault();
                        if (currentIndex < $allItems.length - 1) {
                            $allItems.eq(currentIndex + 1).find('> a').focus();
                        }
                        break;
                        
                    case 40: // Down arrow
                        e.preventDefault();
                        if ($menuItem.hasClass('has-megamenu') || $menuItem.hasClass('has-dropdown')) {
                            const $firstSubItem = $menuItem.find('.megamenu-column a, .sub-menu a').first();
                            if ($firstSubItem.length) {
                                $firstSubItem.focus();
                            }
                        }
                        break;
                        
                    case 27: // Escape
                        e.preventDefault();
                        $menuItem.removeClass('menu-item-expanded');
                        $(this).blur();
                        break;
                }
            });

            // Handle submenu navigation
            $('.megamenu-column a, .sub-menu a').on('keydown', function(e) {
                const $link = $(this);
                const $column = $link.closest('.megamenu-column, .sub-menu');
                const $allLinks = $column.find('a');
                const currentIndex = $allLinks.index($link);

                switch(e.keyCode) {
                    case 38: // Up arrow
                        e.preventDefault();
                        if (currentIndex > 0) {
                            $allLinks.eq(currentIndex - 1).focus();
                        } else {
                            // Go back to main menu item
                            $link.closest('.menu-item').find('> a').focus();
                        }
                        break;
                        
                    case 40: // Down arrow
                        e.preventDefault();
                        if (currentIndex < $allLinks.length - 1) {
                            $allLinks.eq(currentIndex + 1).focus();
                        }
                        break;
                        
                    case 37: // Left arrow (in megamenu)
                        if ($link.closest('.megamenu-dropdown').length) {
                            e.preventDefault();
                            const $currentColumn = $link.closest('.megamenu-column');
                            const $prevColumn = $currentColumn.prev('.megamenu-column');
                            if ($prevColumn.length) {
                                $prevColumn.find('a').first().focus();
                            }
                        }
                        break;
                        
                    case 39: // Right arrow (in megamenu)
                        if ($link.closest('.megamenu-dropdown').length) {
                            e.preventDefault();
                            const $currentColumn = $link.closest('.megamenu-column');
                            const $nextColumn = $currentColumn.next('.megamenu-column');
                            if ($nextColumn.length) {
                                $nextColumn.find('a').first().focus();
                            }
                        }
                        break;
                        
                    case 27: // Escape
                        e.preventDefault();
                        $link.closest('.menu-item').find('> a').focus();
                        break;
                }
            });
        }

        setupAccessibility() {
            // Add ARIA attributes
            $('.has-megamenu, .has-dropdown').each(function() {
                const $menuItem = $(this);
                const $link = $menuItem.find('> a');
                const $dropdown = $menuItem.find('.megamenu-dropdown, .sub-menu').first();
                
                const dropdownId = 'dropdown-' + Math.random().toString(36).substr(2, 9);
                
                $link.attr({
                    'aria-haspopup': 'true',
                    'aria-expanded': 'false',
                    'aria-controls': dropdownId
                });
                
                $dropdown.attr({
                    'id': dropdownId,
                    'aria-hidden': 'true'
                });
            });

            // Update ARIA states on hover/focus
            $('.has-megamenu, .has-dropdown').on('mouseenter focusin', function() {
                const $menuItem = $(this);
                const $link = $menuItem.find('> a');
                const $dropdown = $menuItem.find('.megamenu-dropdown, .sub-menu').first();
                
                $link.attr('aria-expanded', 'true');
                $dropdown.attr('aria-hidden', 'false');
            }).on('mouseleave focusout', function(e) {
                const $menuItem = $(this);
                
                // Delay to check if focus moved to submenu
                setTimeout(() => {
                    if (!$menuItem.find(':focus').length && !$menuItem.is(':hover')) {
                        const $link = $menuItem.find('> a');
                        const $dropdown = $menuItem.find('.megamenu-dropdown, .sub-menu').first();
                        
                        $link.attr('aria-expanded', 'false');
                        $dropdown.attr('aria-hidden', 'true');
                    }
                }, 100);
            });
        }

        setupHoverDelays() {
            let hoverTimeout;
            
            $('.has-megamenu').on('mouseenter', function() {
                const $menuItem = $(this);
                
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    $menuItem.addClass('megamenu-active');
                }, 150);
            }).on('mouseleave', function() {
                const $menuItem = $(this);
                
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    $menuItem.removeClass('megamenu-active');
                }, 300);
            });
        }

        handleResize() {
            // Close mobile menus on resize to desktop
            if (window.innerWidth > 1024) {
                $('.has-megamenu').removeClass('menu-item-expanded');
                $('.megamenu-dropdown').removeAttr('style');
            }
        }

        // Public method to close all menus
        closeAllMenus() {
            $('.has-megamenu').removeClass('menu-item-expanded megamenu-active');
            $('.megamenu-dropdown').slideUp(300);
        }

        // Public method to open specific menu
        openMenu(menuSelector) {
            this.closeAllMenus();
            
            const $menuItem = $(menuSelector);
            if ($menuItem.hasClass('has-megamenu')) {
                $menuItem.addClass('menu-item-expanded megamenu-active');
                $menuItem.find('.megamenu-dropdown').slideDown(300);
            }
        }
    }

    // Initialize megamenu when DOM is ready
    $(document).ready(function() {
        window.OpticaVisionMegamenu = new OpticaVisionMegamenu();
        
        // Announce to screen reader when megamenu opens
        $('.has-megamenu').on('mouseenter focusin', function() {
            const menuTitle = $(this).find('> a').text().trim();
            if (window.OpticaVisionTheme && window.OpticaVisionTheme.announceToScreenReader) {
                window.OpticaVisionTheme.announceToScreenReader(
                    `Megamenú ${menuTitle} abierto. Use las flechas para navegar.`
                );
            }
        });
    });

    // Expose megamenu instance globally
    window.OpticaVisionMegamenu = OpticaVisionMegamenu;

})(jQuery);

// Additional utility functions
(function() {
    'use strict';

    // Detect if user is using keyboard navigation
    let isUsingKeyboard = false;
    
    document.addEventListener('keydown', function(e) {
        if (e.keyCode === 9) { // Tab key
            isUsingKeyboard = true;
            document.body.classList.add('using-keyboard');
        }
    });
    
    document.addEventListener('mousedown', function() {
        isUsingKeyboard = false;
        document.body.classList.remove('using-keyboard');
    });

    // Smooth scrolling for anchor links in megamenu
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href^="#"]');
        if (link && link.closest('.megamenu-dropdown')) {
            const targetId = link.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Close megamenu after navigation
                if (window.OpticaVisionMegamenu) {
                    window.OpticaVisionMegamenu.closeAllMenus();
                }
            }
        }
    });

})();
