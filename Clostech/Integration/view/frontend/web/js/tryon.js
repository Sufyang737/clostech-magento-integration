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

        // URL de la API de Clostech
        const CLOSTECH_API_URL = 'https://identic-keenan-nonvalorous.ngrok-free.dev';

        // Obtener datos del producto desde el botón
        const productId = $button.data('product-id');
        const productSku = $button.data('product-sku');
        const productName = $button.data('product-name');
        const storeId = $button.data('store-id');
        const productImageUrl = $button.data('product-image');

        // Variable para guardar el archivo de la foto del usuario
        let userPhotoFile = null;

        // Click en el botón "Virtual Try-On"
        $button.on('click', function () {
            $modalProductName.text(productName);
            $modal.show();
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
                userPhotoFile = file;
                
                const reader = new FileReader();
                reader.onload = function (e) {
                    $previewImage.attr('src', e.target.result);
                    $preview.show();
                    $submitBtn.prop('disabled', false);
                };
                reader.readAsDataURL(file);
            }
        });

        // Click en "Try On"
        $submitBtn.on('click', function () {
            console.log(' Starting Try-On process...');
            $submitBtn.prop('disabled', true);
            $result.show();
            $result.html('<p> Processing with AI... Please wait.</p>');

            processTryOn();
        });

        /**
         * Proceso completo de Try-On
         */
        async function processTryOn() {
            try {
                // Obtener API Key
                console.log('Getting API Key...');
                const apiKey = await getApiKey(storeId);
                console.log(' API Key obtained');

                // Subir foto del usuario
                console.log('Uploading user photo...');
                const userImageUrl = await uploadUserPhoto(userPhotoFile);
                console.log(' User photo uploaded:', userImageUrl);

                // Procesar con IA
                console.log(' Processing with AI...');
                const resultImageUrl = await processWithAI({
                    api_key: apiKey,
                    store_id: storeId,
                    product_id: productId,
                    variant_id: null,
                    variant_values: null,
                    user_image_url: userImageUrl,
                    cloth_image_url: productImageUrl
                });
                console.log(' AI processing complete:', resultImageUrl);

                // Mostrar resultado
                $result.html(
                    '<h3> Result</h3>' +
                    '<img src="' + resultImageUrl + '" alt="Try-on result" style="max-width: 100%; height: auto; border-radius: 8px;">' +
                    '<p style="color: green;">Try-on completed successfully!</p>'
                );

                // Guardar en localStorage
                saveClosetchUsage(productId, productName, productSku, resultImageUrl);
                console.log(' Try-on saved to localStorage');

            } catch (error) {
                console.error(' Error in Try-On process:', error);
                $result.html(
                    '<h3> Error</h3>' +
                    '<p style="color: red;">' + error.message + '</p>' +
                    '<button onclick="location.reload()">Try Again</button>'
                );
            } finally {
                $submitBtn.prop('disabled', false);
            }
        }

        /**
         * PASO 1: Obtener API Key de Clostech
         */
        function getApiKey(storeId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: CLOSTECH_API_URL + '/api/apikeys/store',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ store_id: storeId }),
                    success: function(response) {
                        if (response && response.api_key) {
                            resolve(response.api_key);
                        } else {
                            reject(new Error('No API key returned'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('Failed to get API key: ' + error));
                    }
                });
            });
        }

        /**
         * PASO 2: Subir foto del usuario
         */
        function uploadUserPhoto(file) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', file);

                $.ajax({
                    url: CLOSTECH_API_URL + '/api/upload-image',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response && response.user_image_url) {
                            resolve(response.user_image_url);
                        } else {
                            reject(new Error('No image URL returned'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('Failed to upload image: ' + error));
                    }
                });
            });
        }

        /**
         * PASO 3: Procesar con IA
         */
        function processWithAI(data) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: CLOSTECH_API_URL + '/api/ai-v2',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response && response.result_image_url) {
                            resolve(response.result_image_url);
                        } else {
                            reject(new Error('No result image returned'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('AI processing failed: ' + error));
                    }
                });
            });
        }

        /**
         * Guardar que el usuario usó Clostech
         */
        function saveClosetchUsage(productId, productName, productSku, imageUrl) {
            const tryOnData = {
                productId: productId,
                productName: productName,
                productSku: productSku,
                timestamp: Date.now(),
                imageUrl: imageUrl,
                used: true
            };
            
            localStorage.setItem('clostech_tryon_' + productId, JSON.stringify(tryOnData));
            
            // Marcar que el usuario usó Clostech (para el checkout)
            localStorage.setItem('use_clostech', 'true');
            
            console.log('Saved to localStorage:', tryOnData);
        }
    };
});