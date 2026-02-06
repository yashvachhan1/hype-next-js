<?php

class WCS_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    public function add_plugin_admin_menu() {
        add_menu_page(
            'Woo Settings',           // Page Title
            'Woo Suite',              // Menu Title
            'manage_options',         // Capability
            'woo-custom-suite',       // Menu Slug
            array( $this, 'display_settings_page' ), // Callback
            'dashicons-store',        // Icon
            56                        // Position
        );
    }

    public function register_settings() {
        register_setting('wcs_plugin_settings', 'swp_role_a_name');
        register_setting('wcs_plugin_settings', 'swp_role_a_percent');
        register_setting('wcs_plugin_settings', 'swp_role_a_min');
        
        
        register_setting('wcs_plugin_settings', 'swp_role_b_min');
        
        register_setting('wcs_plugin_settings', 'swp_min_type'); 
    }

    public function display_settings_page() {
        // Fetch existing values
        $role_a = get_option('swp_role_a_name', 'wholesale_customer_1');
        $disc_a = get_option('swp_role_a_percent', 20);
        $min_a  = get_option('swp_role_a_min', 500);
        
        $role_b = get_option('swp_role_b_name', 'wholesale_customer_2');
        $disc_b = get_option('swp_role_b_percent', 30);
        $min_b  = get_option('swp_role_b_min', 1000);
        
        $type   = get_option('swp_min_type', 'amount');
        ?>
        <div class="wrap">
            <h1>Woo Custom Suite: Dashboard</h1>
            
            <!-- TABS NAVIGATION -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-wholesale" class="nav-tab nav-tab-active">Wholesale Rules</a>
                <a href="#tab-shop" class="nav-tab">Shop Design</a>
            </h2>

            <form action="options.php" method="post" style="background:#fff; padding:20px; border:1px solid #ddd; border-top:0; max-width:800px; border-bottom-left-radius:5px; border-bottom-right-radius:5px;">
                <?php
                settings_fields('wcs_plugin_settings');
                do_settings_sections('wcs_plugin_settings');
                ?>

                <!-- NOTIFICATION -->
                <div style="background:#f0f7ff; border-left:4px solid #0073aa; padding:10px; margin-bottom:20px;">
                    <strong>Active Plan:</strong> <span style="color:green; font-weight:bold;">Pro (Growth Edition)</span>. You have access to all features for free until 2027.
                </div>

                <!-- TAB: WHOLESALE -->
                <div id="tab-wholesale" class="wcs-tab-content">
                    <h3>Wholesale Configuration</h3>
                    <p>Configure your wholesale roles and discount rules here.</p>
                    
                    <h3>Wholesale Tier 1</h3>
                    <table class="form-table">
                        <tr><th scope="row">Role Slug</th><td><input type="text" name="swp_role_a_name" value="<?php echo esc_attr($role_a); ?>" class="regular-text"></td></tr>
                        <tr><th scope="row">Discount %</th><td><input type="number" name="swp_role_a_percent" value="<?php echo esc_attr($disc_a); ?>"> %</td></tr>
                        <tr><th scope="row">Min Requirement</th><td><input type="number" name="swp_role_a_min" value="<?php echo esc_attr($min_a); ?>"></td></tr>
                    </table>

                    <hr>

                    <h3>Wholesale Tier 2</h3>
                    <table class="form-table">
                        <tr><th scope="row">Role Slug</th><td><input type="text" name="swp_role_b_name" value="<?php echo esc_attr($role_b); ?>" class="regular-text"></td></tr>
                        <tr><th scope="row">Discount %</th><td><input type="number" name="swp_role_b_percent" value="<?php echo esc_attr($disc_b); ?>"> %</td></tr>
                        <tr><th scope="row">Min Requirement</th><td><input type="number" name="swp_role_b_min" value="<?php echo esc_attr($min_b); ?>"></td></tr>
                    </table>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Requirement Type</th>
                            <td>
                                <select name="swp_min_type">
                                    <option value="amount" <?php selected($type, 'amount'); ?>>Total Amount ($)</option>
                                    <option value="quantity" <?php selected($type, 'quantity'); ?>>Total Quantity (Count)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- TAB: SHOP DESIGN -->
                <div id="tab-shop" class="wcs-tab-content" style="display:none;">
                     <h3>Shop Page Setup</h3>
                    <p>To display your premium shop grid, paste this shortcode on any page:</p>
                    <code style="background:#eee; padding:5px; font-size:1.2em;">[woo_custom_suite_shop]</code>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>

            <script>
                jQuery(document).ready(function($) {
                    $('.nav-tab').click(function(e) {
                        e.preventDefault();
                        // Tabs UI
                        $('.nav-tab').removeClass('nav-tab-active');
                        $(this).addClass('nav-tab-active');
                        
                        // Content Switching
                        var target = $(this).attr('href');
                        $('.wcs-tab-content').hide();
                        $(target).show();
                    });
                });
            </script>

        </div>
        <?php
    }

}
