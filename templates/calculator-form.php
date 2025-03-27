<?php
// Determine if this is a yearly gua (male only) calculator
$is_yearly_gua = isset($calculator_type) && $calculator_type === 'yearly_gua';
$form_id = $is_yearly_gua ? 'yearly-gua-form' : 'ming-gua-form';
$calculator_class = $is_yearly_gua ? 'yearly-gua-calculator' : 'ming-gua-calculator';
$button_text = $is_yearly_gua ? 'Sužinoti metų skaičių' : 'Sužinoti asmeninį skaičių';
$kua_result_title = $is_yearly_gua ? 'Metų feng shui skaičius:' : 'Jūsų asmeninis feng shui skaičius:';
?>
<div class="kua-calculator-container <?php echo esc_attr($calculator_class); ?>">
    <form id="<?php echo esc_attr($form_id); ?>" class="kua-calculator-form" data-calculator-type="<?php echo $is_yearly_gua ? 'yearly_gua' : 'ming_gua'; ?>">
        <div class="kua-form-row">
            <div class="kua-form-group">
                <label for="kua-birth-date"><?php _e('Gimimo data:', 'kua-calculator'); ?></label>
                <input type="date" id="kua-birth-date" name="birth_date" required min="1920-01-01" max="2080-12-31">
            </div>
        </div>

        <?php if ($gender === 'male') : ?>
            <!-- If gender attribute is provided as "male", output a hidden (but checkable) radio input -->
            <input type="radio" name="gender" value="male" checked style="display: none;">
        <?php else : ?>
            <div class="kua-form-row">
                <div class="kua-form-group">
                    <label><?php _e('Lytis:', 'kua-calculator'); ?></label>
                    <div class="kua-radio-group">
                        <label>
                            <input type="radio" name="gender" value="male" required>
                            <?php _e('Vyras', 'kua-calculator'); ?>
                        </label>
                        <label>
                            <input type="radio" name="gender" value="female">
                            <?php _e('Moteris', 'kua-calculator'); ?>
                        </label>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="kua-form-row">
            <button type="button" class="kua-calculate-button kua-submit-button">
                <?php _e(esc_html($button_text), 'kua-calculator'); ?>
            </button>
        </div>
    </form>

    <div class="kua-result kua-result-container" style="display: none;">
        <div class="kua-result-number">
            <span class="kua-label"><?php _e(esc_html($kua_result_title), 'kua-calculator'); ?></span>
            <span class="kua-number-display kua-number"></span>
        </div>

        <div class="kua-result-description">
            <p class="kua-description"></p>
        </div>

        <div class="kua-product-recommendations kua-products" style="display: none;">
            <h3><?php _e('Rekomenduojami produktai', 'kua-calculator'); ?></h3>
            <div class="kua-products-list"></div>
        </div>
    </div>

    <div class="kua-error kua-error-container" style="display: none;">
        <p class="kua-error-message"></p>
    </div>
</div>