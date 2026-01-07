define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return function (config, element) {
        const $button = $(element);
        const $modal = $('#clostech-tryon-modal');
        const $closeBtn = $('.clostech-modal-close');
        const $photoUpload = $('#clostech-photo-upload');
        const $preview = $('#clostech-preview');
        const $previewImage = $('#clostech-preview-image');
        const $submitBtn = $('#clostech-submit-btn');
        const $result = $('#clostech-result');
        const $modalProductName = $('#modal-product-name');

        // Obtener datos del producto desde el botón
        const productId = $button.data('product-id');
        const productSku = $button.data('product-sku');
        const productName = $button.data('product-name');

        // Click en el botón "Virtual Try-On"
        $button.on('click', function () {
            // Mostrar nombre del producto en el modal
            $modalProductName.text(productName);
            
            // Abrir modal
            $modal.show();
            
            // Reset del modal
            resetModal();
        });

        // Click en la X para cerrar
        $closeBtn.on('click', function () {
            $modal.hide();
        });

        // Click fuera del modal para cerrar
        $(window).on('click', function (event) {
            if (event.target.id === 'clostech-tryon-modal') {
                $modal.hide();
            }
        });

        // Cuando el usuario sube una foto
        $photoUpload.on('change', function (event) {
            const file = event.target.files[0];
            
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                
                reader.onload = function (e) {
                    // Mostrar preview de la imagen
                    $previewImage.attr('src', e.target.result);
                    $preview.show();
                    
                    // Habilitar botón "Try On"
                    $submitBtn.prop('disabled', false);
                };
                
                reader.readAsDataURL(file);
            }
        });

        // Click en "Try On"
        $submitBtn.on('click', function () {
            console.log('Try On clicked!');
            console.log('Product ID:', productId);
            console.log('Product SKU:', productSku);
            console.log('Product Name:', productName);
            
            // Aquí irá la llamada a la API de Clostech
            // Por ahora, solo mostrar mensaje
            $result.show();
            $result.html('<p>Processing... (API integration coming soon)</p>');
            
            // Simular procesamiento
            setTimeout(function() {
                $result.html(
                    '<h3>Result</h3>' +
                    '<p>Your photo with the product would appear here.</p>' +
                    '<p>API integration pending.</p>'
                );
            }, 1000);
        });

        function resetModal() {
            $photoUpload.val('');
            $preview.hide();
            $result.hide();
            $submitBtn.prop('disabled', true);
        }
    };
});