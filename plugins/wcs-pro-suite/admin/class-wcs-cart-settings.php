<?php

class WCS_Cart_Settings {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add the Cart Settings submenu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woo-custom-suite',       // Parent slug (WCS Admin)
            'Cart Goals',             // Page Title
            'Cart Goals',             // Menu Title
            'manage_options',         // Capability
            'wcs-cart-settings',      // Menu Slug
            array( $this, 'display_cart_page' ) // Callback
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting('wcs_cart_settings_group', 'wcs_free_shipping_threshold');
    }

    /**
     * Render the settings page.
     */
    public function display_cart_page() {
        $free_shipping_threshold = get_option('wcs_free_shipping_threshold', 0);
        ?>
        <div class="wrap">
            <h1>Cart Goal Settings</h1>
            
            <form action="options.php" method="post" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:5px; max-width:800px; margin-top:20px;">
                <?php
                settings_fields('wcs_cart_settings_group');
                do_settings_sections('wcs_cart_settings_group');
                ?>

                <h3>Free Shipping Goal</h3>
                <p>Set a goal amount to display a progress bar. Use shortcode: <code>[wcs_free_shipping_progress]</code></p>
                <table class="form-table">
                    <tr>
                        <th scope="row">Free Shipping Threshold ($)</th>
                        <td>
                            <input type="number" step="0.01" name="wcs_free_shipping_threshold" value="<?php echo esc_attr($free_shipping_threshold); ?>" class="regular-text">
                            <p class="description">Enter 0 to disable.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

}
