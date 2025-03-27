<?php
// Default to ming_gua tab if none specified
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'ming_gua';
?>
<div class="wrap kua-admin-container">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kua-admin-notice-area">
        <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Nustatymai išsaugoti sėkmingai.', 'kua-calculator'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="kua-admin-shortcode-box">
        <h2><?php _e('Shortcode', 'kua-calculator'); ?></h2>
        <p><?php _e('Nukopijuok šį Shortcode į bet kurį puslapį ar postą, kuriame nori atvaizduoti Feng Shui skaičiuoklę:', 'kua-calculator'); ?></p>
        <div class="kua-shortcode-display">
            <p>Ming Gua (Asmeninis skaičius):</p>
            <code>[ming_gua]</code>
            <button type="button" class="button button-secondary kua-copy-shortcode">
                <?php _e('Kopijuoti', 'kua-calculator'); ?>
            </button>
        </div>
        <div class="kua-shortcode-display">
            <p>Yearly Gua (Metinis skaičius, tik vyrams):</p> 
            <code>[yearly_gua]</code>
            <button type="button" class="button button-secondary kua-copy-shortcode">
                <?php _e('Kopijuoti', 'kua-calculator'); ?>
            </button>
        </div>
    </div>
    
    <div class="kua-admin-tabs">
        <h2 class="nav-tab-wrapper">
            <a href="?page=kua-calculator-products&tab=ming_gua" class="nav-tab <?php echo $active_tab === 'ming_gua' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Ming Gua (Asmeninis)', 'kua-calculator'); ?>
            </a>
            <a href="?page=kua-calculator-products&tab=yearly_gua" class="nav-tab <?php echo $active_tab === 'yearly_gua' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Yearly Gua (Metinis)', 'kua-calculator'); ?>
            </a>
        </h2>
    </div>
    
    <div class="kua-admin-products-management">
        <?php if ($active_tab === 'ming_gua') : ?>
            <h2><?php _e('Ming Gua skaičiaus ir produktų susiejimas', 'kua-calculator'); ?></h2>
        <?php else : ?>
            <h2><?php _e('Yearly Gua skaičiaus ir produktų susiejimas', 'kua-calculator'); ?></h2>
        <?php endif; ?>
        
        <?php if (!function_exists('wc_get_products')) : ?>
            <div class="notice notice-warning">
                <p><?php _e('WooCommerce is not active. Product associations require WooCommerce.', 'kua-calculator'); ?></p>
            </div>
        <?php else : ?>
            <div class="kua-product-search-container">
                <h3><?php _e('Pridėti produktą', 'kua-calculator'); ?></h3>
                <div class="kua-add-product-form">
                    <select id="kua-number-select" class="kua-select" data-calculator-type="<?php echo esc_attr($active_tab); ?>">
                        <option value=""><?php _e('Pasirinkti Ming Gua skaičių', 'kua-calculator'); ?></option>
                        <?php foreach ([1, 2, 3, 4, 6, 7, 8, 9] as $kua_number) : ?>
                            <option value="<?php echo esc_attr($kua_number); ?>">
                                <?php printf(__('%d', 'kua-calculator'), $kua_number); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="kua-product-search">
                        <input type="text" id="kua-product-search-input" class="kua-search-input" placeholder="<?php _e('Ieškoti produktų...', 'kua-calculator'); ?>">
                        <div id="kua-search-results" class="kua-search-results"></div>
                    </div>
                </div>
            </div>
            
            <!-- Kua Products Table -->
            <div class="kua-products-table-container">
                <table class="widefat fixed kua-products-table">
                    <thead>
                        <tr>
                            <th class="kua-number-column"><?php _e('Kua skaičius', 'kua-calculator'); ?></th>
                            <th class="kua-description-column"><?php _e('Aprašymas', 'kua-calculator'); ?></th>
                            <th class="kua-products-column"><?php _e('Susieti produktai', 'kua-calculator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    // Get all Kua descriptions
                    $kua_descriptions = Kua_Calculator::get_kua_descriptions();
                    
                    // Get custom descriptions for the active tab
                    $custom_descriptions = $this->get_kua_descriptions_with_custom($active_tab);
                    
                    // For each Kua number
                    foreach ([1, 2, 3, 4, 6, 7, 8, 9] as $kua_number) :
                        if ($active_tab === 'ming_gua') {
                            // For Ming Gua tab, get regular products
                            $saved_product_ids = get_option('kua_calculator_products_' . $kua_number, []);
                            $form_action = 'save_kua_products';
                            $nonce_name = 'kua_nonce_' . $kua_number;
                            $nonce_action = 'save_kua_products_' . $kua_number;
                        } else {
                            // For Yearly Gua tab, get yearly gua products
                            $saved_product_ids = get_option('yearly_gua_products_' . $kua_number, []);
                            $form_action = 'save_yearly_gua_products';
                            $nonce_name = 'yearly_gua_nonce_' . $kua_number;
                            $nonce_action = 'save_yearly_gua_products_' . $kua_number;
                        }
                    ?>
                        <tr>
                            <td class="kua-number-cell">
                                <strong><?php echo esc_html($kua_number); ?></strong>
                            </td>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="kua-combined-form">
                                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                                <input type="hidden" name="kua_number" value="<?php echo $kua_number; ?>">
                                <?php wp_nonce_field($nonce_action, $nonce_name); ?>
                                
                                <td class="kua-description-cell">
                                    <textarea name="kua_description" class="kua-description-textarea" id="<?php echo $active_tab; ?>-description-<?php echo $kua_number; ?>"><?php echo esc_textarea($custom_descriptions[$kua_number]); ?></textarea>
                                </td>
                                <td class="kua-products-cell">
                                    <ul class="kua-product-list" id="<?php echo $active_tab; ?>-products-list-<?php echo $kua_number; ?>" data-kua="<?php echo $kua_number; ?>" data-type="<?php echo $active_tab; ?>">
                                        <?php
                                        // Display already selected products
                                        foreach ($saved_product_ids as $product_id) :
                                            $product = wc_get_product($product_id);
                                            if ($product) :
                                        ?>
                                        <li class="kua-product-item" data-product-id="<?php echo esc_attr($product_id); ?>">
                                            <?php if (has_post_thumbnail($product_id)) : ?>
                                                <img src="<?php echo get_the_post_thumbnail_url($product_id, 'thumbnail'); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="kua-product-thumbnail">
                                            <?php endif; ?>
                                            <span class="kua-product-name"><?php echo esc_html($product->get_name()); ?></span>
                                            <span class="kua-product-price"><?php echo $product->get_price_html(); ?></span>
                                            <input type="hidden" name="product_ids[]" value="<?php echo esc_attr($product_id); ?>">
                                            <button type="button" class="button-link kua-remove-product"><?php _e('Pašalinti', 'kua-calculator'); ?></button>
                                        </li>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </ul>
                                    
                                    <div class="kua-form-actions">
                                        <button type="submit" class="button button-primary">
                                            <?php _e('Išsaugoti aprašymą ir produktus', 'kua-calculator'); ?>
                                        </button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/template" id="kua-product-template">
    <li class="kua-product-item" data-product-id="{id}">
        <img src="{image}" alt="{name}" class="kua-product-thumbnail">
        <span class="kua-product-name">{name}</span>
        <span class="kua-product-price">{price}</span>
        <input type="hidden" name="product_ids[]" value="{id}">
        <button type="button" class="button-link kua-remove-product"><?php _e('Pašalinti', 'kua-calculator'); ?></button>
    </li>
</script>