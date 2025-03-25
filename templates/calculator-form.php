<div class="kua-calculator-container">
    <form id="kua-calculator-form" class="kua-calculator-form">
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
            <button type="button" id="kua-calculate-button" class="kua-submit-button">
                <?php _e('Apskaičiuoti Kua skaičių', 'kua-calculator'); ?>
            </button>
        </div>
    </form>

    <div id="kua-result" class="kua-result-container" style="display: none;">
        <div class="kua-result-number">
            <span class="kua-label"><?php _e('Jūsų Kua skaitmuo:', 'kua-calculator'); ?></span>
            <span id="kua-number-display" class="kua-number"></span>
        </div>

        <div class="kua-result-description">
            <p id="kua-description"></p>
        </div>

        <div id="kua-product-recommendations" class="kua-products" style="display: none;">
            <h3><?php _e('Rekomenduojami produktai', 'kua-calculator'); ?></h3>
            <div id="kua-products-list"></div>
        </div>
    </div>

    <div id="kua-error" class="kua-error-container" style="display: none;">
        <p id="kua-error-message"></p>
    </div>
</div>