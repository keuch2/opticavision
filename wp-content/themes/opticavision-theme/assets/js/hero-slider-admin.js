/**
 * Hero Slider Admin JavaScript
 * Funcionalidad para la administración del slider hero
 */

jQuery(document).ready(function($) {
    'use strict';
    
    let slideIndex = 0;
    let mediaUploader;
    let currentImageTarget;
    
    // Initialize
    init();
    
    function init() {
        updateSlideNumbers();
        initSortable();
        bindEvents();
        
        // Expand first slide by default
        $('.slide-item:first').addClass('expanded');
    }
    
    function bindEvents() {
        // Add new slide
        $(document).on('click', '#add-new-slide', addNewSlide);
        
        // Save slides
        $(document).on('click', '#save-slides', saveSlides);
        
        // Toggle slide content
        $(document).on('click', '.slide-header', toggleSlide);
        
        // Delete slide
        $(document).on('click', '.slide-delete', deleteSlide);
        
        // Select image
        $(document).on('click', '.select-image', selectImage);
        
        // Remove image
        $(document).on('click', '.remove-image', removeImage);
        
        // Prevent header click when clicking controls
        $(document).on('click', '.slide-controls', function(e) {
            e.stopPropagation();
        });
    }
    
    function initSortable() {
        $('#hero-slides-container').sortable({
            items: '.slide-item',
            handle: '.slide-handle',
            placeholder: 'slide-placeholder',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            update: function() {
                updateSlideNumbers();
                updateSlideIndices();
            }
        });
    }
    
    function addNewSlide() {
        const template = $('#slide-template').html();
        const newIndex = $('.slide-item').length;
        
        let newSlide = template
            .replace(/\{\{INDEX\}\}/g, newIndex)
            .replace(/\{\{NUMBER\}\}/g, newIndex + 1);
        
        const $newSlide = $(newSlide);
        
        // Remove no-slides message if exists
        $('.no-slides-message').remove();
        
        // Add to container
        $('#hero-slides-container').append($newSlide);
        
        // Expand new slide
        $newSlide.addClass('expanded');
        
        // Scroll to new slide
        $('html, body').animate({
            scrollTop: $newSlide.offset().top - 100
        }, 500);
        
        updateSlideNumbers();
        slideIndex++;
    }
    
    function toggleSlide(e) {
        if ($(e.target).closest('.slide-controls').length) {
            return;
        }
        
        const $slide = $(this).closest('.slide-item');
        $slide.toggleClass('expanded');
    }
    
    function deleteSlide(e) {
        e.stopPropagation();
        
        if (!confirm(heroSliderAjax.strings.removeSlide)) {
            return;
        }
        
        const $slide = $(this).closest('.slide-item');
        
        $slide.fadeOut(300, function() {
            $(this).remove();
            updateSlideNumbers();
            updateSlideIndices();
            
            // Show no-slides message if no slides left
            if ($('.slide-item').length === 0) {
                $('#hero-slides-container').html(
                    '<div class="no-slides-message">' +
                    '<p>' + heroSliderAjax.strings.noSlides + '</p>' +
                    '</div>'
                );
            }
        });
    }
    
    function selectImage(e) {
        e.preventDefault();
        
        const $button = $(this);
        const target = $button.data('target'); // 'desktop' or 'mobile'
        const $slideItem = $button.closest('.slide-item');
        
        currentImageTarget = {
            button: $button,
            target: target,
            slide: $slideItem
        };
        
        // Create media uploader if not exists
        if (!mediaUploader) {
            mediaUploader = wp.media({
                title: target === 'desktop' ? 
                    heroSliderAjax.strings.selectDesktopImage : 
                    heroSliderAjax.strings.selectMobileImage,
                button: {
                    text: 'Seleccionar'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                setSelectedImage(attachment);
            });
        } else {
            // Update title for current selection
            mediaUploader.options.title = target === 'desktop' ? 
                heroSliderAjax.strings.selectDesktopImage : 
                heroSliderAjax.strings.selectMobileImage;
        }
        
        mediaUploader.open();
    }
    
    function setSelectedImage(attachment) {
        if (!currentImageTarget) return;
        
        const { button, target, slide } = currentImageTarget;
        const $preview = button.siblings('.image-preview');
        const $input = button.siblings('input[type="hidden"]');
        
        // Set image preview
        $preview.html(
            '<img src="' + attachment.sizes.medium.url + '" alt="">' +
            '<button type="button" class="remove-image">×</button>'
        ).addClass('has-image');
        
        // Set hidden input value
        $input.val(attachment.id);
        
        // Update button text
        button.text('Cambiar Imagen');
        
        currentImageTarget = null;
    }
    
    function removeImage(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $preview = $(this).parent();
        const $button = $preview.siblings('.select-image');
        const $input = $preview.siblings('input[type="hidden"]');
        
        // Clear preview
        $preview.empty().removeClass('has-image');
        
        // Clear input
        $input.val('');
        
        // Reset button text
        $button.text('Seleccionar Imagen');
    }
    
    function updateSlideNumbers() {
        $('.slide-item').each(function(index) {
            $(this).find('.slide-number').text('Slide ' + (index + 1));
        });
    }
    
    function updateSlideIndices() {
        $('.slide-item').each(function(index) {
            const $slide = $(this);
            
            // Update data-index
            $slide.attr('data-index', index);
            
            // Update all input names
            $slide.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                
                if (name && name.includes('[')) {
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $input.attr('name', newName);
                }
            });
        });
    }
    
    function saveSlides() {
        const $button = $('#save-slides');
        const originalText = $button.text();
        
        // Show loading state
        $button.text('Guardando...').prop('disabled', true);
        $('.slide-item').addClass('saving');
        
        // Collect slide data
        const slidesData = [];
        
        $('.slide-item').each(function() {
            const $slide = $(this);
            const slideData = {};
            
            // Collect all form data for this slide
            $slide.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                
                if (!name) return;
                
                // Extract field name from slides[index][field] format
                const matches = name.match(/slides\[\d+\]\[([^\]]+)\]/);
                if (!matches) return;
                
                const fieldName = matches[1];
                
                if ($input.attr('type') === 'checkbox') {
                    slideData[fieldName] = $input.is(':checked') ? 1 : 0;
                } else {
                    slideData[fieldName] = $input.val();
                }
            });
            
            slidesData.push(slideData);
        });
        
        // Send AJAX request
        $.ajax({
            url: heroSliderAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_hero_slides',
                nonce: heroSliderAjax.nonce,
                slides: slidesData
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data || heroSliderAjax.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(heroSliderAjax.strings.error, 'error');
            },
            complete: function() {
                // Reset button state
                $button.text(originalText).prop('disabled', false);
                $('.slide-item').removeClass('saving');
            }
        });
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        $('.hero-slider-message').remove();
        
        // Create new message
        const $message = $('<div class="hero-slider-message ' + type + '">' + message + '</div>');
        
        // Insert after header
        $('.hero-slider-header').after($message);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 300);
    }
    
    // Handle page unload with unsaved changes
    let hasUnsavedChanges = false;
    
    $(document).on('change', '.slide-item input, .slide-item select, .slide-item textarea', function() {
        hasUnsavedChanges = true;
    });
    
    $(document).on('click', '#save-slides', function() {
        hasUnsavedChanges = false;
    });
    
    $(window).on('beforeunload', function() {
        if (hasUnsavedChanges) {
            return 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
        }
    });
});
