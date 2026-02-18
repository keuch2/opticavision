jQuery(document).ready(function($) {
    // Variables globales para un mejor seguimiento del estado
    var currentPage = 1;
    var totalPages = 0;
    
    // Inicialización principal
    function init() {
        // Determinar la página actual y el total de páginas
        if ($('.woocommerce-pagination .current').length > 0) {
            currentPage = parseInt($('.woocommerce-pagination .current').data('page')) || 1;
        }
        
        totalPages = $('.woocommerce-pagination .page-numbers:not(.pagination-prev):not(.pagination-next)').length || 0;
        
        // Eliminar flechas existentes para evitar duplicados
        $('.pagination-prev, .pagination-next').remove();
        
        // Añadir las flechas de navegación si hay paginación
        if ($('.woocommerce-pagination').length > 0 && totalPages > 1) {
            addNavigationArrows();
        }
    }
    
    // Agregar flechas de navegación
    function addNavigationArrows() {
        var $prevButton = $('<a class="pagination-prev page-numbers" href="#"><i class="fa fa-chevron-left"></i></a>');
        var $nextButton = $('<a class="pagination-next page-numbers" href="#"><i class="fa fa-chevron-right"></i></a>');
        
        $('.woocommerce-pagination').prepend($prevButton);
        $('.woocommerce-pagination').append($nextButton);
        
        // Configurar los eventos
        setupArrowEvents();
    }
    
    // Configurar eventos para las flechas
    function setupArrowEvents() {
        // Flecha anterior
        $(document).off('click', '.pagination-prev').on('click', '.pagination-prev', function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                // Hay una página anterior, navegar a ella
                var targetPage = currentPage - 1;
                navigateToPage(targetPage);
            }
        });
        
        // Flecha siguiente
        $(document).off('click', '.pagination-next').on('click', '.pagination-next', function(e) {
            e.preventDefault();
            if (currentPage < totalPages) {
                // Hay una página siguiente, navegar a ella
                var targetPage = currentPage + 1;
                navigateToPage(targetPage);
            }
        });
    }
    
    // Función para navegar a una página específica
    function navigateToPage(pageNumber) {
        var $targetPageElement = $('.woocommerce-pagination .page-numbers[data-page="' + pageNumber + '"]');
        if ($targetPageElement.length) {
            $targetPageElement.trigger('click');
        }
    }
    
    // Manejar los clics en los números de página
    $(document).on('click', '.woocommerce-pagination .page-numbers:not(.pagination-prev):not(.pagination-next)', function() {
        var clickedPage = parseInt($(this).data('page')) || 1;
        currentPage = clickedPage;
        
        // Actualizar la clase current
        $('.woocommerce-pagination .page-numbers').removeClass('current');
        $(this).addClass('current');
    });
    
    // Inicializar al cargar
    init();
    
    // Reconfigurar después de cada llamada AJAX
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url.indexOf('admin-ajax.php') > -1) {
            setTimeout(init, 300);
        }
    });
    
    // Verificar periódicamente si es necesario reconfigurar
    setInterval(function() {
        if ($('.woocommerce-pagination').length > 0 && $('.pagination-prev').length === 0 && totalPages > 1) {
            init();
        }
    }, 1000);
});
