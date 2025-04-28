<?php

/**
 * Plugin Name: Feng Shui skaičiuoklė
 * Plugin URI: https://12gm.lt
 * Description: Paskaičiuoja Kua skaičių pagal įvestą gimimo datą ir lytį. Į Puslapį įdedama per shortcode [kua_calculator]. Galima suporuoti su produktais.
 * Version: 1.1.0
 * Author: Sandra Valavičiūtė
 * Author URI: https://12gm.lt
 * Text Domain: kua-calculator
 * License: GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('KUA_CALCULATOR_VERSION', '1.1.0');
define('KUA_CALCULATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KUA_CALCULATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Kua_Calculator
{

    /**
     * Instance of this class
     */
    protected static $instance = null;

    /**
     * Initialize the plugin
     */
    public function __construct()
    {
        // Register shortcodes
        add_shortcode('ming_gua', array($this, 'render_calculator'));
        add_shortcode('yearly_gua', array($this, 'render_yearly_gua'));

        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Load text domain
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));

        // AJAX handlers
        add_action('wp_ajax_get_kua_products', array($this, 'ajax_get_kua_products'));
        add_action('wp_ajax_nopriv_get_kua_products', array($this, 'ajax_get_kua_products'));
        add_action('wp_ajax_get_yearly_gua_products', array($this, 'ajax_get_yearly_gua_products'));
        add_action('wp_ajax_nopriv_get_yearly_gua_products', array($this, 'ajax_get_yearly_gua_products'));
        add_action('wp_ajax_search_wc_products', array($this, 'ajax_search_wc_products'));

        // Admin AJAX handlers
        add_action('admin_post_save_kua_products', array($this, 'save_kua_products_handler'));
        add_action('admin_post_save_yearly_gua_products', array($this, 'save_yearly_gua_products_handler'));
        add_action('admin_post_save_kua_description', array($this, 'save_kua_description_handler')); // Kept for backwards compatibility
    }

    /**
     * Return an instance of this class
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Register and enqueue scripts and styles
     */
    public function register_scripts()
    {
        wp_register_script(
            'kua-calculator-js',
            KUA_CALCULATOR_PLUGIN_URL . 'assets/js/kua-calculator.js',
            array('jquery'),
            KUA_CALCULATOR_VERSION,
            true
        );

        // Localize script with needed variables
        $this->localize_calculator_script();

        wp_register_style(
            'kua-calculator-css',
            KUA_CALCULATOR_PLUGIN_URL . 'assets/css/kua-calculator.css',
            array(),
            KUA_CALCULATOR_VERSION
        );
    }

    /**
     * Localize the calculator script with needed variables
     */
    private function localize_calculator_script()
    {
        wp_localize_script('kua-calculator-js', 'kua_calculator_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kua_calculator_nonce'),
            'loading_text' => __('Kraunami rekomenduojami produktai...', 'kua-calculator'),
            'error_text' => __('Klaida užkraunant rekomendacijas. Pamėginkite dar kartą.', 'kua-calculator'),
            'no_products_text' => __('Rekomenduojamų produktų nėra.', 'kua-calculator'),
            'view_product_text' => __('Peržiūrėti produktą', 'kua-calculator'),
            'error_incomplete' => __('Užpildykite visus laukelius', 'kua-calculator'),
            'error_calculation' => __('Klaida atliekant skaičiavimą. Pasitikrinkite, ar gerai įvedėte savo gimimo datą.', 'kua-calculator'),
            'ming_gua_descriptions' => $this->get_kua_descriptions_with_custom('ming_gua'),
            'yearly_gua_descriptions' => $this->get_kua_descriptions_with_custom('yearly_gua'),
            'locale' => get_locale()
        ));
    }

    /**
     * Render the calculator form via shortcode
     */
    public function render_calculator($atts)
    {
        // Enqueue the required scripts and styles
        wp_enqueue_script('kua-calculator-js');
        wp_enqueue_style('kua-calculator-css');

        // Merge passed attributes with defaults
        $atts = shortcode_atts([
            'gender' => ''
        ], $atts, 'calculator_shortcode');
        
        // Add the calculator type
        $atts['calculator_type'] = 'ming_gua';

        // Start output buffering
        ob_start();

        // Extract to get variables
        extract($atts);
        
        // Include the template
        include(KUA_CALCULATOR_PLUGIN_DIR . 'templates/calculator-form.php');

        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Render the yearly gua calculator (male-only) via shortcode
     */
    public function render_yearly_gua($atts)
    {
        // Enqueue the required scripts and styles
        wp_enqueue_script('kua-calculator-js');
        wp_enqueue_style('kua-calculator-css');

        // Set gender to male for yearly gua
        $atts = [
            'gender' => 'male',
            'calculator_type' => 'yearly_gua'
        ];

        // Start output buffering
        ob_start();

        // Extract to get variables
        extract($atts);
        
        // Include the template
        include(KUA_CALCULATOR_PLUGIN_DIR . 'templates/calculator-form.php');

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Add admin menu for Kua Number Product Associations
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Produktų priskyrimas', 'kua-calculator'),
            __('Feng Shui skaičiuoklė', 'kua-calculator'),
            'manage_options',
            'kua-calculator-products',
            array($this, 'render_admin_page'),
            'dashicons-calculator',
            30
        );
    }

    /**
     * Render admin page for associating products with Kua numbers
     */
    public function render_admin_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Enqueue admin scripts and styles
        wp_enqueue_style('kua-calculator-admin-css', KUA_CALCULATOR_PLUGIN_URL . 'assets/css/kua-calculator-admin.css', array(), KUA_CALCULATOR_VERSION);
        wp_enqueue_script('kua-calculator-admin-js', KUA_CALCULATOR_PLUGIN_URL . 'assets/js/kua-calculator-admin.js', array('jquery'), KUA_CALCULATOR_VERSION, true);

        // Add WordPress editor styles if needed
        wp_enqueue_editor();

        // Localize admin script
        wp_localize_script('kua-calculator-admin-js', 'kua_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kua_admin_nonce'),
            'copied_text' => __('Nukopijuota!', 'kua-calculator'),
            'select_kua_first' => __('Pirmiau reikia pasirinkti Kua skaičių', 'kua-calculator'),
            'searching' => __('Ieškoma...', 'kua-calculator'),
            'no_products' => __('Produktų nerasta', 'kua-calculator'),
            'search_error' => __('Klaida ieškant produktų', 'kua-calculator'),
            'product_exists' => __('Šis produktas jau priskirtas pasirinktam Kua skaičiui', 'kua-calculator'),
            'add_text' => __('Pridėti', 'kua-calculator')
        ));

        // Include admin template
        include(KUA_CALCULATOR_PLUGIN_DIR . 'templates/admin-page.php');
    }

    /**
     * Calculate Kua number
     * This is used both in frontend JS and in admin pages
     */
    public function calculate_kua($year, $month, $day, $gender)
    {
        // Solar new year dates (Lì Chūn) for years 1920-2080
        $solar_new_year_dates = array(
            1920 => "02-04",
            1921 => "02-04",
            1922 => "02-04",
            1923 => "02-04",
            1924 => "02-05",
            1925 => "02-04",
            1926 => "02-04",
            1927 => "02-04",
            1928 => "02-05",
            1929 => "02-04",
            1930 => "02-04",
            1931 => "02-04",
            1932 => "02-05",
            1933 => "02-04",
            1934 => "02-04",
            1935 => "02-04",
            1936 => "02-05",
            1937 => "02-04",
            1938 => "02-04",
            1939 => "02-04",
            1940 => "02-05",
            1941 => "02-04",
            1942 => "02-04",
            1943 => "02-04",
            1944 => "02-05",
            1945 => "02-04",
            1946 => "02-04",
            1947 => "02-04",
            1948 => "02-05",
            1949 => "02-04",
            1950 => "02-04",
            1951 => "02-04",
            1952 => "02-05",
            1953 => "02-04",
            1954 => "02-04",
            1955 => "02-04",
            1956 => "02-05",
            1957 => "02-04",
            1958 => "02-04",
            1959 => "02-04",
            1960 => "02-05",
            1961 => "02-04",
            1962 => "02-04",
            1963 => "02-04",
            1964 => "02-05",
            1965 => "02-04",
            1966 => "02-04",
            1967 => "02-04",
            1968 => "02-05",
            1969 => "02-04",
            1970 => "02-04",
            1971 => "02-04",
            1972 => "02-05",
            1973 => "02-04",
            1974 => "02-04",
            1975 => "02-04",
            1976 => "02-05",
            1977 => "02-04",
            1978 => "02-04",
            1979 => "02-04",
            1980 => "02-05",
            1981 => "02-04",
            1982 => "02-04",
            1983 => "02-04",
            1984 => "02-05",
            1985 => "02-04",
            1986 => "02-04",
            1987 => "02-04",
            1988 => "02-04",
            1989 => "02-04",
            1990 => "02-04",
            1991 => "02-04",
            1992 => "02-04",
            1993 => "02-04",
            1994 => "02-04",
            1995 => "02-04",
            1996 => "02-04",
            1997 => "02-04",
            1998 => "02-04",
            1999 => "02-04",
            2000 => "02-04",
            2001 => "02-04",
            2002 => "02-04",
            2003 => "02-04",
            2004 => "02-04",
            2005 => "02-04",
            2006 => "02-04",
            2007 => "02-04",
            2008 => "02-04",
            2009 => "02-04",
            2010 => "02-04",
            2011 => "02-04",
            2012 => "02-04",
            2013 => "02-04",
            2014 => "02-04",
            2015 => "02-04",
            2016 => "02-04",
            2017 => "02-03",
            2018 => "02-04",
            2019 => "02-04",
            2020 => "02-04",
            2021 => "02-03",
            2022 => "02-04",
            2023 => "02-04",
            2024 => "02-04",
            2025 => "02-03",
            2026 => "02-04",
            2027 => "02-04",
            2028 => "02-04",
            2029 => "02-03",
            2030 => "02-04",
            2031 => "02-04",
            2032 => "02-04",
            2033 => "02-03",
            2034 => "02-04",
            2035 => "02-04",
            2036 => "02-04",
            2037 => "02-03",
            2038 => "02-04",
            2039 => "02-04",
            2040 => "02-04",
            2041 => "02-03",
            2042 => "02-04",
            2043 => "02-04",
            2044 => "02-04",
            2045 => "02-03",
            2046 => "02-04",
            2047 => "02-04",
            2048 => "02-04",
            2049 => "02-03",
            2050 => "02-04",
            2051 => "02-04",
            2052 => "02-04",
            2053 => "02-03",
            2054 => "02-04",
            2055 => "02-04",
            2056 => "02-04",
            2057 => "02-03",
            2058 => "02-04",
            2059 => "02-04",
            2060 => "02-04",
            2061 => "02-03",
            2062 => "02-04",
            2063 => "02-04",
            2064 => "02-04",
            2065 => "02-03",
            2066 => "02-04",
            2067 => "02-04",
            2068 => "02-04",
            2069 => "02-03",
            2070 => "02-04",
            2071 => "02-04",
            2072 => "02-04",
            2073 => "02-03",
            2074 => "02-04",
            2075 => "02-04",
            2076 => "02-04",
            2077 => "02-03",
            2078 => "02-04",
            2079 => "02-04",
            2080 => "02-04"
        );

        // Check if birth year is supported
        if (!isset($solar_new_year_dates[$year])) {
            return 'error';
        }

        // Check if person was born before solar new year
        $adjusted_year = $year;
        $solar_date = $solar_new_year_dates[$year];
        list($solar_month, $solar_day) = explode('-', $solar_date);
        $solar_month = intval($solar_month);
        $solar_day = intval($solar_day);

        if ($month < $solar_month || ($month === $solar_month && $day < $solar_day)) {
            $adjusted_year -= 1;
        }

        // Sum digits of the adjusted birth year
        $sum = 0;
        foreach (str_split((string)$adjusted_year) as $digit) {
            $sum += intval($digit);
        }

        // If sum is double-digit, sum again
        while ($sum >= 10) {
            $new_sum = 0;
            foreach (str_split((string)$sum) as $digit) {
                $new_sum += intval($digit);
            }
            $sum = $new_sum;
        }

        // Calculate Kua number based on gender
        if ($gender === 'male') {
            $kua_number = 11 - $sum;
        } else {
            $kua_number = $sum + 4;
        }

        // If result is double-digit, sum digits again
        while ($kua_number >= 10) {
            $new_kua = 0;
            foreach (str_split((string)$kua_number) as $digit) {
                $new_kua += intval($digit);
            }
            $kua_number = $new_kua;
        }

        // Special cases: if Kua = 5 for males, become 2; if Kua = 5 for females, become 8
        if ($kua_number === 5) {
            $kua_number = ($gender === 'male') ? 2 : 8;
        }

        return $kua_number;
    }

    /**
     * Get descriptions for all Kua numbers
     */
    public static function get_kua_descriptions()
    {
        return array(
            1 => __('Kua number 1 is associated with water element and north direction. Lucky colors are blue and black.', 'kua-calculator'),
            2 => __('Kua number 2 is associated with earth element and southwest direction. Lucky colors are yellow and brown.', 'kua-calculator'),
            3 => __('Kua number 3 is associated with wood element and east direction. Lucky colors are green and brown.', 'kua-calculator'),
            4 => __('Kua number 4 is associated with wood element and southeast direction. Lucky colors are green and blue.', 'kua-calculator'),
            6 => __('Kua number 6 is associated with metal element and northwest direction. Lucky colors are white, gold, and silver.', 'kua-calculator'),
            7 => __('Kua number 7 is associated with metal element and west direction. Lucky colors are white, gold, and copper.', 'kua-calculator'),
            8 => __('Kua number 8 is associated with earth element and northeast direction. Lucky colors are yellow and brown.', 'kua-calculator'),
            9 => __('Kua number 9 is associated with fire element and south direction. Lucky colors are red and purple.', 'kua-calculator'),
        );
    }

    /**
     * Get custom descriptions for Kua numbers from options
     * 
     * @param string $type The type of calculator ('ming_gua' or 'yearly_gua')
     * @return array Array of descriptions indexed by Kua number
     */
    public function get_kua_descriptions_with_custom($type = 'ming_gua')
    {
        $default_descriptions = self::get_kua_descriptions();
        $custom_descriptions = array();
        
        // Determine the option prefix based on the calculator type
        $option_prefix = ($type === 'yearly_gua') ? 'yearly_gua_description_' : 'kua_calculator_description_';

        // Get custom descriptions from options
        foreach (array(1, 2, 3, 4, 6, 7, 8, 9) as $kua_number) {
            $custom_description = get_option($option_prefix . $kua_number, '');
            if (!empty($custom_description)) {
                $custom_descriptions[$kua_number] = $custom_description;
            } else {
                $custom_descriptions[$kua_number] = $default_descriptions[$kua_number];
            }
        }

        return $custom_descriptions;
    }

    /**
     * AJAX handler for getting products associated with a Kua number (ming gua)
     */
    public function ajax_get_kua_products()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kua_calculator_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        // Get Kua number from request
        $kua_number = isset($_POST['kua_number']) ? intval($_POST['kua_number']) : 0;

        // Validate Kua number
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            wp_send_json_error(array('message' => 'Nėra tokio Kua skaičiaus.'));
        }

        // Get products for this Kua number
        $products = $this->get_kua_products($kua_number);

        // Send response
        wp_send_json_success(array(
            'kua_number' => $kua_number,
            'products' => $products
        ));
    }
    
    /**
     * AJAX handler for getting products associated with a Yearly Gua number (male-only)
     */
    public function ajax_get_yearly_gua_products()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kua_calculator_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        // Get Kua number from request
        $kua_number = isset($_POST['kua_number']) ? intval($_POST['kua_number']) : 0;

        // Validate Kua number
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            wp_send_json_error(array('message' => 'Nėra tokio Kua skaičiaus.'));
        }

        // Get products for this Kua number
        $products = $this->get_yearly_gua_products($kua_number);

        // Send response
        wp_send_json_success(array(
            'kua_number' => $kua_number,
            'products' => $products
        ));
    }

    /**
     * AJAX handler to search for WooCommerce products
     */
    public function ajax_search_wc_products()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kua_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Check if WooCommerce is active
        if (!function_exists('wc_get_products')) {
            wp_send_json_error(array('message' => 'WooCommerce is not active'));
            return;
        }

        // Get search term
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (empty($search_term)) {
            wp_send_json_error(array('message' => 'No search term provided'));
            return;
        }

        // Query for products
        $args = array(
            'status' => 'publish',
            'limit' => 10,
            's' => $search_term,
        );

        $products = wc_get_products($args);

        if (empty($products)) {
            wp_send_json_success(array('products' => array()));
            return;
        }

        // Format results
        $results = array();
        foreach ($products as $product) {
            $results[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price_html' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'url' => get_permalink($product->get_id())
            );
        }

        wp_send_json_success(array('products' => $results));
    }

    /**
     * Admin handler for saving product associations and description for Ming Gua
     */
    public function save_kua_products_handler()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'kua-calculator'));
        }

        // Verify nonce
        $kua_number = isset($_POST['kua_number']) ? intval($_POST['kua_number']) : 0;
        if (!isset($_POST['kua_nonce_' . $kua_number]) || !wp_verify_nonce($_POST['kua_nonce_' . $kua_number], 'save_kua_products_' . $kua_number)) {
            wp_die(__('Security check failed.', 'kua-calculator'));
        }

        // Get product IDs from request
        $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : array();

        // Get description from request
        $description = isset($_POST['kua_description']) ? $_POST['kua_description'] : '';

        // Save products associations
        $products_result = $this->save_kua_product_associations($kua_number, $product_ids);
        
        // Save description, specifying this is for ming_gua
        $description_result = $this->save_kua_description($kua_number, $description, 'ming_gua');
        
        // Success if both operations succeeded
        $result = $products_result && $description_result;

        // Redirect back to admin page
        $redirect_url = add_query_arg(
            array(
                'page' => 'kua-calculator-products',
                'tab' => 'ming_gua',
                'updated' => $result ? 'true' : 'false'
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit();
    }
    
    /**
     * Admin handler for saving product associations and description for Yearly Gua
     */
    public function save_yearly_gua_products_handler()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'kua-calculator'));
        }

        // Verify nonce
        $kua_number = isset($_POST['kua_number']) ? intval($_POST['kua_number']) : 0;
        if (!isset($_POST['yearly_gua_nonce_' . $kua_number]) || !wp_verify_nonce($_POST['yearly_gua_nonce_' . $kua_number], 'save_yearly_gua_products_' . $kua_number)) {
            wp_die(__('Security check failed.', 'kua-calculator'));
        }

        // Get product IDs from request
        $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : array();
        
        // Get description from request
        $description = isset($_POST['kua_description']) ? $_POST['kua_description'] : '';

        // Save products associations
        $products_result = $this->save_yearly_gua_product_associations($kua_number, $product_ids);
        
        // Save description, specifying this is for yearly_gua
        $description_result = $this->save_kua_description($kua_number, $description, 'yearly_gua');
        
        // Success if both operations succeeded
        $result = $products_result && $description_result;

        // Redirect back to admin page with the yearly gua tab active
        $redirect_url = add_query_arg(
            array(
                'page' => 'kua-calculator-products',
                'tab' => 'yearly_gua',
                'updated' => $result ? 'true' : 'false'
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit();
    }

    /**
     * Admin handler for saving Kua descriptions - Kept for backwards compatibility
     * Now descriptions are saved together with products in the save_kua_products_handler
     * and save_yearly_gua_products_handler methods
     */
    public function save_kua_description_handler()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'kua-calculator'));
        }

        // Verify nonce
        $kua_number = isset($_POST['kua_number']) ? intval($_POST['kua_number']) : 0;
        if (!isset($_POST['kua_description_nonce_' . $kua_number]) || !wp_verify_nonce($_POST['kua_description_nonce_' . $kua_number], 'save_kua_description_' . $kua_number)) {
            wp_die(__('Security check failed.', 'kua-calculator'));
        }

        // Get description from request
        $description = isset($_POST['kua_description']) ? $_POST['kua_description'] : '';
        
        // Determine the calculator type (default to ming_gua for backward compatibility)
        $type = isset($_POST['calculator_type']) ? sanitize_text_field($_POST['calculator_type']) : 'ming_gua';

        // Save description with the correct type
        $result = $this->save_kua_description($kua_number, $description, $type);

        // Get the current tab for redirect
        $tab = ($type === 'yearly_gua') ? 'yearly_gua' : 'ming_gua';

        // Redirect back to admin page
        $redirect_url = add_query_arg(
            array(
                'page' => 'kua-calculator-products',
                'tab' => $tab,
                'updated' => $result ? 'true' : 'false'
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit();
    }

    /**
     * Save product associations with Ming Gua numbers (admin function)
     */
    public function save_kua_product_associations($kua_number, $product_ids)
    {
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            return false;
        }

        // Sanitize product IDs
        $product_ids = array_map('intval', $product_ids);

        // Save the association
        update_option('kua_calculator_products_' . $kua_number, $product_ids);

        return true;
    }
    
    /**
     * Save product associations with Yearly Gua numbers (admin function)
     */
    public function save_yearly_gua_product_associations($kua_number, $product_ids)
    {
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            return false;
        }

        // Sanitize product IDs
        $product_ids = array_map('intval', $product_ids);

        // Save the association with a different option name
        update_option('yearly_gua_products_' . $kua_number, $product_ids);

        return true;
    }

    /**
     * Save custom description for a Kua number
     * 
     * @param int $kua_number The Kua number to save the description for
     * @param string $description The description content
     * @param string $type The type of calculator ('ming_gua' or 'yearly_gua')
     * @return bool Whether the save was successful
     */
    public function save_kua_description($kua_number, $description, $type = 'ming_gua')
    {
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            return false;
        }

        // Sanitize description
        $description = wp_kses_post($description);
        
        // Determine the option name based on calculator type
        $option_name = ($type === 'yearly_gua') ? 'yearly_gua_description_' . $kua_number : 'kua_calculator_description_' . $kua_number;

        // Save the description
        update_option($option_name, $description);

        return true;
    }

    /**
     * Get products associated with a specific Ming Gua number
     */
    public function get_kua_products($kua_number)
    {
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            return array();
        }

        $product_ids = get_option('kua_calculator_products_' . $kua_number, array());

        if (empty($product_ids) || !function_exists('wc_get_product')) {
            return array();
        }

        $products = array();
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product && $product->is_visible()) {
                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price_html' => $product->get_price_html(),
                    'url' => get_permalink($product_id),
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail')
                );
            }
        }

        return $products;
    }
    
    /**
     * Get products associated with a specific Yearly Gua number
     */
    public function get_yearly_gua_products($kua_number)
    {
        if (!in_array($kua_number, array(1, 2, 3, 4, 6, 7, 8, 9))) {
            return array();
        }

        $product_ids = get_option('yearly_gua_products_' . $kua_number, array());

        if (empty($product_ids) || !function_exists('wc_get_product')) {
            return array();
        }

        $products = array();
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product && $product->is_visible()) {
                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price_html' => $product->get_price_html(),
                    'url' => get_permalink($product_id),
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail')
                );
            }
        }

        return $products;
    }

    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('kua-calculator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

/**
 * Initialize the plugin
 */
function kua_calculator_init()
{
    return Kua_Calculator::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'kua_calculator_init');

/**
 * Create plugin assets folders on activation
 */
function kua_calculator_activate()
{
    // Create necessary directories if they don't exist
    $directories = array(
        'assets/css',
        'assets/js',
        'templates',
        'languages'
    );

    foreach ($directories as $dir) {
        $path = KUA_CALCULATOR_PLUGIN_DIR . $dir;
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }
    }
}
register_activation_hook(__FILE__, 'kua_calculator_activate');
