<?php
/**
 * Title: Simple Wholesale Pricing (Class Based)
 * Description: Solves "Cannot redeclare" errors by using a unique class structure.
 * Version: 2.0
 */

if ( ! class_exists( 'WCS_Wholesale_Pricing' ) ) {

    class WCS_Wholesale_Pricing {

        public function __construct() {
            // 1. Admin Menu & Settings
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );

            // 2. Price Calculation Logic (The Core)
            // Hook into ALL price getters so it works in: Grid, Cart, Checkout, Mini-Cart, API
            $filters = array(
                'woocommerce_product_get_price',
                'woocommerce_product_get_sale_price',
                'woocommerce_product_variation_get_price',
                'woocommerce_product_variation_get_sale_price',
                'woocommerce_variation_prices_price',
                'woocommerce_variation_prices_sale_price'
                // Removed: 'woocommerce_variation_prices_regular_price' to ensure Strike-through works
            );

            foreach ( $filters as $filter ) {
                add_filter( $filter, array( $this, 'apply_wholesale_price' ), 10, 2 );
            }

            // 3. Cache Hashing (Critical for Variable Products)
            add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'add_role_to_price_cache' ), 10, 3 );

            // 4. Frontend Display (Strike-through)
            add_filter( 'woocommerce_get_price_html', array( $this, 'show_wholesale_price_html' ), 100, 2 );

            // 5. Auto-Clear Cache on Settings Save
            add_action( 'update_option_swp_role_a_percent', array( $this, 'clear_all_caches' ) );
            add_action( 'update_option_swp_role_b_percent', array( $this, 'clear_all_caches' ) );
            
            // 6. Manual Clear Action
            add_action( 'admin_post_swp_manual_clear', array( $this, 'manual_cache_clear' ) );
        }

        public function manual_cache_clear() {
            if ( ! current_user_can( 'manage_options' ) ) return;
            $this->clear_all_caches();
            wp_redirect( admin_url( 'admin.php?page=swp-settings&cache-cleared=1' ) );
            exit;
        }

        public function clear_all_caches() {
            // 1. Clear Rocket Cache
            update_option( 'wcs_cache_version', time() );
            // 2. Clear Woo Price Cache
            wc_delete_product_transients();
            wc_delete_expired_transients();
            // 3. Clear Variation Transients
            global $wpdb;
            $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_var_prices_%' OR option_name LIKE '_transient_timeout_wc_var_prices_%'" );
            // 4. Object Cache Flush (Redis/Memcached)
            wp_cache_flush();
        }

        // ==========================================
        // 1. MENU & SETTINGS
        // ==========================================
        public function add_admin_menu() {
            add_menu_page(
                'Wholesale Settings',
                'Wholesale Rates',
                'manage_options',
                'swp-settings',
                array( $this, 'render_settings_page' ),
                'dashicons-tag',
                56
            );
        }

        public function register_settings() {
            $keys = array( 
                'swp_role_a_name', 'swp_role_a_percent', 'swp_role_a_min',
                'swp_role_b_name', 'swp_role_b_percent', 'swp_role_b_min',
                'swp_min_type' 
            );
            foreach ( $keys as $k ) register_setting( 'swp_plugin_settings', $k );
        }

        public function render_settings_page() {
            // Fetch existing values with defaults
            $role_a = get_option('swp_role_a_name', 'customer_category_1'); // Default matched to your needs
            $disc_a = get_option('swp_role_a_percent', 20);
            $min_a  = get_option('swp_role_a_min', 500);
            
            $role_b = get_option('swp_role_b_name', 'customer_category_2');
            $disc_b = get_option('swp_role_b_percent', 30);
            $min_b  = get_option('swp_role_b_min', 1000);
            
            $type   = get_option('swp_min_type', 'amount');
            ?>
            <div class="wrap">
                <h1>Wholesale Pricing Configuration</h1>
                
                <?php if(isset($_GET['cache-cleared'])): ?>
                    <div class="notice notice-success is-dismissible"><p><strong>Success:</strong> All price caches have been purged!</p></div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <p>Set your discount rules here. <br><strong>Tip:</strong> Copy the exact "Role Slug" from the list below.</p>
                    <a href="<?php echo admin_url('admin-post.php?action=swp_manual_clear'); ?>" class="button button-secondary" style="border-color:#d63638; color:#d63638;">🔥 Force Clear Price Cache</a>
                </div>

                <!-- Role Reference Box -->
                <div style="background:#f9f9f9; padding:15px; border:1px solid #ccc; margin-bottom:20px; border-radius:5px;">
                    <h3 style="margin-top:0;">📋 Available User Roles (Copy these Slugs)</h3>
                    <div style="max-height:100px; overflow-y:auto; font-family:monospace; font-size:12px;">
                        <?php 
                        global $wp_roles;
                        foreach ( $wp_roles->roles as $slug => $data ) {
                            echo "<strong>" . $data['name'] . ":</strong> " . $slug . "<br>";
                        }
                        ?>
                    </div>
                </div>
                
                <form action="options.php" method="post" style="background:#fff; padding:20px; border:1px solid #ddd; max-width:600px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                    <?php
                    settings_fields('swp_plugin_settings');
                    do_settings_sections('swp_plugin_settings');
                    ?>

                    <h2 style="padding-bottom:10px; border-bottom:1px solid #eee;">Tier 1 Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Role Slug</th>
                            <td><input type="text" name="swp_role_a_name" value="<?php echo esc_attr($role_a); ?>" class="regular-text"><br>
                            <small class="description">Copy exact role key (e.g. <code>customer_category_1</code>)</small></td>
                        </tr>
                        <tr>
                            <th scope="row">Discount (%)</th>
                            <td><input type="number" name="swp_role_a_percent" value="<?php echo esc_attr($disc_a); ?>" min="0" max="100"> %</td>
                        </tr>
                        <tr>
                            <th scope="row">Min Spend ($)</th>
                            <td><input type="number" name="swp_role_a_min" value="<?php echo esc_attr($min_a); ?>"></td>
                        </tr>
                    </table>

                    <h2 style="padding-bottom:10px; border-bottom:1px solid #eee; margin-top:30px;">Tier 2 Settings (Higher Priority)</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Role Slug</th>
                            <td><input type="text" name="swp_role_b_name" value="<?php echo esc_attr($role_b); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">Discount (%)</th>
                            <td><input type="number" name="swp_role_b_percent" value="<?php echo esc_attr($disc_b); ?>" min="0" max="100"> %</td>
                        </tr>
                        <tr>
                            <th scope="row">Min Spend ($)</th>
                            <td><input type="number" name="swp_role_b_min" value="<?php echo esc_attr($min_b); ?>"></td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        // ==========================================
        // 2. PRICING ENGINE
        // ==========================================
        public function apply_wholesale_price( $price, $product ) {
            // Safety
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $price;
            if ( ! $product ) return $price;

            // User Check
            if ( ! is_user_logged_in() ) return $price;
            $user = wp_get_current_user();
            $roles = (array) $user->roles;

            // Get Settings
            $role_a = get_option('swp_role_a_name', 'customer_category_1');
            $role_b = get_option('swp_role_b_name', 'customer_category_2');
            
            $discount = 0;

            // Check Role B (Higher Priority)
            if ( in_array( $role_b, $roles ) ) {
                $discount = get_option('swp_role_b_percent', 0);
            } 
            // Check Role A
            elseif ( in_array( $role_a, $roles ) || in_array( 'administrator', $roles ) ) {
                $discount = get_option('swp_role_a_percent', 0);
            }

            if ( $discount <= 0 ) return $price;

            // Calculate
            $regular_price = $product->get_regular_price();
            if ( empty($regular_price) ) return $price;  // Prevent division by zero or errors
            // Ensure numeric
            $regular_price = floatval($regular_price);

            $new_price = $regular_price - ( $regular_price * ( $discount / 100 ) );
            return $new_price;
        }

        // ==========================================
        // 3. CACHE HASHING
        // ==========================================
        public function add_role_to_price_cache( $price_hash, $product, $for_display ) {
            $user = wp_get_current_user();
            if ( $user && $user->ID ) {
                // Determine which tier they fall into to keep hash minimal
                $roles = (array) $user->roles;
                $key = 'guest';

                $role_a = get_option('swp_role_a_name', 'customer_category_1');
                $role_b = get_option('swp_role_b_name', 'customer_category_2');

                if ( in_array( $role_b, $roles ) ) $key = 'role_b';
                elseif ( in_array( $role_a, $roles ) ) $key = 'role_a';
                
                $price_hash[] = $key;
            }
            return $price_hash;
        }

        // ==========================================
        // 4. FRONTEND HTML
        // ==========================================
        public function show_wholesale_price_html( $price_html, $product ) {
            if ( is_admin() ) return $price_html;
            if ( ! is_user_logged_in() ) return $price_html;
            if ( $product->is_type( 'variable' ) ) return $price_html; // Let Woo handle ranges

            // Logic to check if price changed
            $active_price = $product->get_price();
            $regular_price = $product->get_regular_price();
            
            // Floating point comparison safety
            if ( abs( floatval($active_price) - floatval($regular_price) ) < 0.01 ) return $price_html;

            // Format: <del>$100</del> $80
            return wc_format_sale_price( 
                wc_get_price_to_display( $product, array( 'price' => $regular_price ) ), 
                wc_get_price_to_display( $product, array( 'price' => $active_price ) ) 
            ) . $product->get_price_suffix();
        }

    } // End Class

    // Init Logic
    new WCS_Wholesale_Pricing();

}
