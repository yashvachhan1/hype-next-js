    <?php
    /**
     * Plugin Name: Simple Wholesale Pricing
     * Description: Adds a settings page to manage wholesale discounts for specific user roles.
     * Version: 1.0
     */

    // ==========================================
    // 1. ADD MENU ITEM TO ADMIN DASHBOARD
    // ==========================================
    function swp_add_admin_menu() {
        add_menu_page(
            'Wholesale Settings',     // Page Title
            'Wholesale Rates',        // Menu Title
            'manage_options',         // Capability
            'simple-wholesale-pricing', // Menu Slug
            'swp_settings_page_html', // Callback function
            'dashicons-tag',          // Icon
            56                        // Position
        );
    }
    add_action('admin_menu', 'swp_add_admin_menu');

    // ==========================================
    // 2. REGISTER SETTINGS
    // ==========================================
    function swp_settings_init() {
        register_setting('swp_plugin_settings', 'swp_role_a_name');
        register_setting('swp_plugin_settings', 'swp_role_a_percent');
        register_setting('swp_plugin_settings', 'swp_role_a_min');
        
        register_setting('swp_plugin_settings', 'swp_role_b_name');
        register_setting('swp_plugin_settings', 'swp_role_b_percent');
        register_setting('swp_plugin_settings', 'swp_role_b_min');
        
        register_setting('swp_plugin_settings', 'swp_min_type'); // 'amount' or 'quantity'
    }
    add_action('admin_init', 'swp_settings_init');

    // ==========================================
    // 3. SETTINGS PAGE HTML
    // ==========================================
    function swp_settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Wholesale Pricing Configuration</h1>
            <p>Set your discount rules here. No coding required!</p>
            
            <form action="options.php" method="post" style="background:#fff; padding:20px; border:1px solid #ddd; max-width:600px;">
                <?php
                settings_fields('swp_plugin_settings');
                do_settings_sections('swp_plugin_settings');
                
                // Fetch existing values
                $role_a = get_option('swp_role_a_name', 'wholesale_customer_1');
                $disc_a = get_option('swp_role_a_percent', 20);
                $min_a  = get_option('swp_role_a_min', 500);
                
                $role_b = get_option('swp_role_b_name', 'wholesale_customer_2');
                $disc_b = get_option('swp_role_b_percent', 30);
                $min_b  = get_option('swp_role_b_min', 1000);
                
                $type   = get_option('swp_min_type', 'amount');
                ?>

                <h2>Customer Category 1</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Role Name (Slug)</th>
                        <td><input type="text" name="swp_role_a_name" value="<?php echo esc_attr($role_a); ?>" class="regular-text"><br>
                        <small>e.g. <i>wholesale_customer_1</i> or <i>editor</i></small></td>
                    </tr>
                    <tr>
                        <th scope="row">Discount Percentage (%)</th>
                        <td><input type="number" name="swp_role_a_percent" value="<?php echo esc_attr($disc_a); ?>" min="0" max="100"> %</td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Requirement</th>
                        <td><input type="number" name="swp_role_a_min" value="<?php echo esc_attr($min_a); ?>"></td>
                    </tr>
                </table>

                <hr>

                <h2>Customer Category 2</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Role Name (Slug)</th>
                        <td><input type="text" name="swp_role_b_name" value="<?php echo esc_attr($role_b); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Discount Percentage (%)</th>
                        <td><input type="number" name="swp_role_b_percent" value="<?php echo esc_attr($disc_b); ?>" min="0" max="100"> %</td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Requirement</th>
                        <td><input type="number" name="swp_role_b_min" value="<?php echo esc_attr($min_b); ?>"></td>
                    </tr>
                </table>

                <hr>

                <h2>Global Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Minimum Requirement Type</th>
                        <td>
                            <select name="swp_min_type">
                                <option value="amount" <?php selected($type, 'amount'); ?>>Total Cart Amount ($)</option>
                                <option value="quantity" <?php selected($type, 'quantity'); ?>>Total Item Quantity (Count)</option>
                            </select>
                            <p class="description">Do they need to spend $500 (Amount) or buy 500 items (Quantity) to get the discount?</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    // ==========================================
    // 4. LOGIC ENGINE (Real Price Update)
    // ==========================================
    function swp_apply_real_wholesale_price( $price, $product ) {
        // 1. Safety Checks
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $price;
        if ( ! $product ) return $price;

        // 2. User Checks
        $user = wp_get_current_user();
        if ( ! $user || $user->ID === 0 ) return $price; // Logged out? No discount.

        // 3. Get Roles & Settings
        $roles = $user->roles;
        // FIXED: Hardcoding to match your Actual User Roles
        $role_a = 'customer_category_1'; 
        $role_b = 'customer_category_2';
        
        $discount = 0;
        
        // 4. Match Role => Discount
    // FIXED: Check Role B FIRST. If they have B, give B.
    if ( in_array( $role_b, $roles ) ) {
        $discount = get_option('swp_role_b_percent', 0);
    } 
    // THEN Check A or Admin. (So Admin + B gets B, but just Admin gets A)
    elseif ( in_array( $role_a, $roles ) || in_array( 'administrator', $roles ) ) {
        $discount = get_option('swp_role_a_percent', 0);
    }

        if ( $discount <= 0 ) return $price;

        // 5. Calculate New Price
        // We base it on Regular Price to avoid compounding loops
        $regular_price = $product->get_regular_price();
        if ( empty($regular_price) ) return $price;
        
        if ( empty($regular_price) ) return $price;

        $new_price = $regular_price - ( $regular_price * ( $discount / 100 ) );
        
        return $new_price;
    }

    // Hook into ALL price getters so it works in: Grid, Cart, Checkout, Mini-Cart, API
    add_filter( 'woocommerce_product_get_price', 'swp_apply_real_wholesale_price', 10, 2 );
    add_filter( 'woocommerce_product_get_sale_price', 'swp_apply_real_wholesale_price', 10, 2 );
    add_filter( 'woocommerce_product_variation_get_price', 'swp_apply_real_wholesale_price', 10, 2 );
    add_filter( 'woocommerce_product_variation_get_sale_price', 'swp_apply_real_wholesale_price', 10, 2 );

    // FIXED: Add hooks for Variable Product Range Calculation
    add_filter( 'woocommerce_variation_prices_price', 'swp_apply_real_wholesale_price', 10, 2 );
    add_filter( 'woocommerce_variation_prices_sale_price', 'swp_apply_real_wholesale_price', 10, 2 );
    add_filter( 'woocommerce_variation_prices_regular_price', 'swp_apply_real_wholesale_price', 10, 2 );

    // FIXED: Clear/Separate Cache for Different Roles (Crucial for Variable Ranges)
    add_filter( 'woocommerce_get_variation_prices_hash', 'swp_add_role_to_price_cache_hash', 10, 3 );
    function swp_add_role_to_price_cache_hash( $price_hash, $product, $for_display ) {
        $user = wp_get_current_user();
        if ( $user && $user->ID ) {
            $price_hash[] = implode( ',', $user->roles );
        }
        return $price_hash;
    }

    // ==========================================
    // 5. FRONTEND DISPLAY (Strike-through Price)
    // ==========================================
    function swp_show_wholesale_price_html( $price_html, $product ) {
        if ( is_admin() ) return $price_html;
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) return $price_html;

        // FIXED: For Variable products, let WooCommerce handle the Range (since we hooked variation_prices)
        if ( $product->is_type( 'variable' ) ) return $price_html;

        // Get current price (which is already discounted by our filter above)
        $active_price = $product->get_price();
        $regular_price = $product->get_regular_price();

        // If active price is same as regular, no discount applied
        if ( $active_price == $regular_price ) return $price_html;

        // Format: <del>$100</del> $80
        $price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $regular_price ) ), wc_get_price_to_display( $product, array( 'price' => $active_price ) ) ) . $product->get_price_suffix();
        
        return $price_html;
    }
    add_filter( 'woocommerce_get_price_html', 'swp_show_wholesale_price_html', 100, 2 );
    ?>
