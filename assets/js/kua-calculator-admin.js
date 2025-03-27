/**
 * Kua Calculator Admin JavaScript
 * Handles product search and association with Kua numbers for both Ming Gua and Yearly Gua
 */
jQuery(document).ready(function($) {
    // Copy shortcode functionality
    $('.kua-copy-shortcode').on('click', function() {
        const shortcodeEl = $(this).siblings('code')[0];
        const range = document.createRange();
        range.selectNode(shortcodeEl);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
        
        const originalText = $(this).text();
        $(this).text(kua_admin_vars.copied_text);
        setTimeout(() => {
            $(this).text(originalText);
        }, 2000);
    });
    
    // Product search functionality
    let searchTimeout;
    let selectedKuaNumber = '';
    let calculatorType = '';
    
    // Handle Kua number selection
    $('#kua-number-select').on('change', function() {
        selectedKuaNumber = $(this).val();
        calculatorType = $(this).data('calculator-type');
        
        // Clear search field and results if no Kua number is selected
        if (!selectedKuaNumber) {
            $('#kua-product-search-input').val('');
            $('#kua-search-results').empty().hide();
        }
    });
    
    // Handle product search input
    $('#kua-product-search-input').on('input', function() {
        const searchTerm = $(this).val();
        
        // Clear timeout if it exists
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Check if a Kua number is selected
        if (!selectedKuaNumber) {
            $('#kua-search-results').html('<p class="kua-search-error">' + kua_admin_vars.select_kua_first + '</p>').show();
            return;
        }
        
        // Clear results if search term is empty
        if (!searchTerm || searchTerm.length < 3) {
            $('#kua-search-results').empty().hide();
            return;
        }
        
        // Set timeout to avoid excessive AJAX requests
        searchTimeout = setTimeout(function() {
            // Show loading indicator
            $('#kua-search-results').html('<p class="kua-loading">' + kua_admin_vars.searching + '</p>').show();
            
            // AJAX request
            $.ajax({
                url: kua_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'search_wc_products',
                    nonce: kua_admin_vars.nonce,
                    search: searchTerm
                },
                success: function(response) {
                    if (response.success && response.data.products.length > 0) {
                        // Create results HTML
                        let resultsHtml = '';
                        
                        $.each(response.data.products, function(index, product) {
                            resultsHtml += '<div class="kua-search-result-item" data-product-id="' + product.id + '">';
                            
                            if (product.image) {
                                resultsHtml += '<img src="' + product.image + '" alt="' + product.name + '" class="kua-product-thumbnail">';
                            }
                            
                            resultsHtml += '<div class="kua-product-info">';
                            resultsHtml += '<h4 class="kua-product-title">' + product.name + '</h4>';
                            resultsHtml += '<div class="kua-product-price">' + product.price_html + '</div>';
                            resultsHtml += '</div>';
                            
                            // Store all product data in data attributes for easy access
                            resultsHtml += '<button type="button" class="button button-secondary kua-add-product"';
                            resultsHtml += ' data-id="' + product.id + '"';
                            resultsHtml += ' data-name="' + product.name + '"';
                            resultsHtml += ' data-price="' + product.price + '"';
                            resultsHtml += ' data-image="' + (product.image || '') + '">';
                            resultsHtml += kua_admin_vars.add_text + '</button>';
                            
                            resultsHtml += '</div>';
                        });
                        
                        $('#kua-search-results').html(resultsHtml);
                    } else {
                        $('#kua-search-results').html('<p class="kua-no-results">' + kua_admin_vars.no_products + '</p>');
                    }
                },
                error: function() {
                    $('#kua-search-results').html('<p class="kua-search-error">' + kua_admin_vars.search_error + '</p>');
                }
            });
        }, 500);
    });
    
    // Handle product selection (event delegation)
    $(document).on('click', '.kua-add-product', function() {
        const $productItem = $(this).closest('.kua-search-result-item');
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        const productPrice = $productItem.find('.kua-product-price').html();
        const productImage = $(this).data('image');
        
        // Target the correct product list based on the calculator type
        const targetList = $('#' + calculatorType + '-products-list-' + selectedKuaNumber);
        
        // Check if product already exists in the list
        if (targetList.find('li[data-product-id="' + productId + '"]').length > 0) {
            alert(kua_admin_vars.product_exists);
            return;
        }
        
        // Get the template and replace placeholders
        let template = $('#kua-product-template').html();
        template = template.replace(/{id}/g, productId);
        template = template.replace(/{name}/g, productName);
        template = template.replace(/{price}/g, productPrice);
        template = template.replace(/{image}/g, productImage || '');
        
        // Add the product to the list
        targetList.append(template);
        
        // Clear the search
        $('#kua-product-search-input').val('');
        $('#kua-search-results').empty().hide();
        
        // Reset Kua number select
        $('#kua-number-select').val('');
        selectedKuaNumber = '';
    });
    
    // Handle product removal (event delegation)
    $(document).on('click', '.kua-remove-product', function() {
        $(this).closest('li').fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Handle description changes - update the hidden field value before form submission
    $(document).on('input', '.kua-description-textarea', function() {
        // Get the textarea value
        const description = $(this).val();
        
        // Find the closest form and update its hidden description field
        const $form = $(this).closest('tr').find('.kua-combined-form');
        $form.find('input[name="kua_description"]').val(description);
    });
    
    // Before form submission, make sure the description is up to date
    $(document).on('submit', '.kua-combined-form', function() {
        const $form = $(this);
        const kua_number = $form.find('input[name="kua_number"]').val();
        const calculatorType = $form.find('input[name="action"]').val().includes('yearly') ? 'yearly_gua' : 'ming_gua';
        
        // Get the textarea content and update the hidden field
        const descriptionContent = $('#' + calculatorType + '-description-' + kua_number).val();
        $form.find('input[name="kua_description"]').val(descriptionContent);
    });
});