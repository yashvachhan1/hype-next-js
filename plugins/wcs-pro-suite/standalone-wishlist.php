    <?php
    /**
     * Standalone Wishlist Module
     * Features: Guest/User support, AJAX Toggling, Shortcode [wcs_wishlist]
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    if ( ! class_exists( 'WCS_Standalone_Wishlist' ) ) {

        class WCS_Standalone_Wishlist {

            public function __construct() {
                add_action( 'init', array( $this, 'register_shortcodes' ) );
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                add_action( 'wp_footer', array( $this, 'print_footer_scripts' ) );

                // AJAX Hooks
                add_action( 'wp_ajax_wcs_toggle_wishlist', array( $this, 'ajax_toggle_wishlist' ) );
                add_action( 'wp_ajax_nopriv_wcs_toggle_wishlist', array( $this, 'ajax_toggle_wishlist' ) );

                // UI Actions
                add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_loop_heart' ), 15 );
                add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_heart' ), 35 );
            }

            public function register_shortcodes() {
                add_shortcode( 'wcs_wishlist', array( $this, 'render_wishlist_page' ) );
            }

            public function enqueue_scripts() {
                wp_enqueue_style( 'dashicons' );
            }

            public function print_footer_scripts() {
                ?>
                <script>
                    jQuery(document).ready(function($){
                        // 1. Listen for our internal button
                        $(document).on('click', '.wcs-wishlist-btn', function(e){
                            e.preventDefault();
                            wcs_toggle_item($(this), $(this).data('id'));
                        });

                        // 2. HIJACK Theme/Other Plugin Buttons (Aggressive Mode)
                        // Listen for clicks on ANYTHING that looks like a wishlist button
                        $(document.body).on('click', '[class*="wish"], [id*="wish"], a[href*="wish"]', function(e){
                            var btn = $(this);
                            
                            // 1. Try to find ID on the button itself
                            var pid = btn.data('product-id') || btn.data('id') || btn.data('product_id');

                            // 2. If not found, look for typical class names like 'add_to_wishlist_123'
                            if(!pid) {
                                var classes = btn.attr('class');
                                if(classes) {
                                    var match = classes.match(/add_to_wishlist_(\d+)/);
                                    if(match) pid = match[1];
                                }
                            }

                            // 3. Last Resort: Traverse UP to find the Product Card container
                            // WooCommerce loops usually have class 'post-123' or 'product-123' or 'type-product'
                            if(!pid) {
                                var productCard = btn.closest('.product, .type-product, .post-type-archive-product');
                                if(productCard.length) {
                                    // Try to find classes like 'post-123'
                                    var cardClasses = productCard.attr('class');
                                    var postMatch = cardClasses.match(/post-(\d+)/);
                                    if(postMatch) pid = postMatch[1];
                                    
                                    // Or find the 'add_to_cart' button inside the card which usually has the ID
                                    if(!pid) {
                                        var atc = productCard.find('.add_to_cart_button');
                                        if(atc.length) pid = atc.data('product_id');
                                    }
                                }
                            }

                            if(pid) {
                                // console.log('WCS Intercepted Wishlist Click for ID: ' + pid);
                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: { action: 'wcs_toggle_wishlist', product_id: pid },
                                    success: function(res) {
                                        // Silent success
                                    }
                                });
                            }
                        });

                        function wcs_toggle_item(btn, pid) {
                            btn.addClass('wcs-loading');
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: { action: 'wcs_toggle_wishlist', product_id: pid },
                                success: function(response) {
                                    btn.removeClass('wcs-loading');
                                    if(response.success) {
                                        if(response.data.status === 'added') {
                                            btn.addClass('active');
                                        } else {
                                            btn.removeClass('active');
                                        }
                                    }
                                }
                            });
                        }
                    });
                </script>
                <style>
                    .wcs-wishlist-btn { cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; color: #ccc; }
                    .wcs-wishlist-btn:hover { color: #ff4444; }
                    .wcs-wishlist-btn.active { color: #ff4444; }
                    .wcs-wishlist-btn .dashicons { font-size: 20px; width: 20px; height: 20px; }
                    .wcs-loading { opacity: 0.5; pointer-events: none; }
                    
                    /* HIDE our button since we are using the theme button */
                    .wcs-loop-heart { display: none !important; }
                    
                    /* Single Position */
                    .wcs-single-heart { margin-top: 10px; font-weight: 500; font-size: 14px; display: none !important; } 

                    /* Wishlist Grid */
                    .wcs-wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
                    .wcs-wishlist-item { border: 1px solid #eee; padding: 15px; border-radius: 8px; text-align: center; position: relative; }
                    .wcs-wishlist-img { max-width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
                    .wcs-wishlist-title { font-weight: 700; font-size: 14px; margin-bottom: 5px; display: block; color: #333; text-decoration: none; }
                    .wcs-remove-wishlist { position: absolute; top: 5px; right: 5px; color: #999; cursor: pointer; }
                    .wcs-remove-wishlist:hover { color: red; }
                </style>
                <?php
            }

            public function render_loop_heart() {
                global $product;
                if ( ! $product ) return;
                $id = $product->get_id();
                // Output generic markup just in case we enable it later, but hidden via CSS
                echo '<div class="wcs-wishlist-btn wcs-loop-heart" data-id="' . $id . '"></div>';
            }

            public function render_single_heart() {
                // Hidden
                return;
            }

            public function ajax_toggle_wishlist() {
                $product_id = intval( $_POST['product_id'] );
                if ( ! $product_id ) wp_send_json_error();

                $current_list = $this->get_wishlist();
                
                if ( in_array( $product_id, $current_list ) ) {
                    $status = 'removed';
                    $current_list = array_diff( $current_list, array( $product_id ) );
                } else {
                    $status = 'added';
                    $current_list[] = $product_id;
                }

                $this->save_wishlist( $current_list );
                wp_send_json_success( array( 'status' => $status, 'count' => count( $current_list ) ) );
            }

            private function get_wishlist() {
                if ( is_user_logged_in() ) {
                    $user_id = get_current_user_id();
                    $list = get_user_meta( $user_id, '_wcs_wishlist_items', true );
                    return ! empty( $list ) ? $list : array();
                } else {
                    if ( isset( $_COOKIE['wcs_wishlist_items'] ) ) {
                        $cookie = stripslashes( $_COOKIE['wcs_wishlist_items'] );
                        $list = json_decode( $cookie, true );
                        return ! empty( $list ) ? $list : array();
                    }
                }
                return array();
            }

            private function save_wishlist( $list ) {
                $list = array_unique( array_filter( $list ) );
                if ( is_user_logged_in() ) {
                    update_user_meta( get_current_user_id(), '_wcs_wishlist_items', $list );
                } else {
                    setcookie( 'wcs_wishlist_items', json_encode( $list ), time() + ( 86400 * 30 ), '/' ); // 30 days
                }
            }

            private function is_in_wishlist( $product_id ) {
                $list = $this->get_wishlist();
                return in_array( $product_id, $list );
            }

            public function render_wishlist_page() {
                $list = $this->get_wishlist();
                ob_start();
                if ( empty( $list ) ) {
                    echo '<div class="wcs-wishlist-empty">
                        <span class="dashicons dashicons-heart" style="font-size:50px; color:#ddd; margin-bottom:10px;"></span>
                        <p style="font-weight:600; color:#555;">Your MyList is empty.</p>
                        <p style="font-size:14px; color:#888;">Click the <strong>"Save"</strong> button on any product to add it here!</p>
                        <a href="' . wc_get_page_permalink( 'shop' ) . '" class="button">Go to Shop</a>
                    </div>';
                } else {
                    ?>
                    <div class="wcs-wishlist-table-wrapper">
                        <table class="wcs-wishlist-table">
                            <thead>
                                <tr>
                                    <th class="wcs-wl-remove">Remove</th>
                                    <th class="wcs-wl-img">Image</th>
                                    <th class="wcs-wl-title">Product Name</th>
                                    <th class="wcs-wl-price">Unit Price</th>
                                    <th class="wcs-wl-stock">Stock Status</th>
                                    <th class="wcs-wl-action">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $list as $pid ) : 
                                    $product = wc_get_product( $pid );
                                    if ( ! $product ) continue;
                                    $stock_status = $product->is_in_stock() ? 'IN STOCK' : 'OUT OF STOCK';
                                    $stock_class = $product->is_in_stock() ? 'in-stock' : 'out-of-stock';
                                ?>
                                    <tr>
                                        <td class="wcs-wl-remove" style="width:80px; text-align:center;">
                                            <div class="wcs-remove-btn wcs-remove-wishlist" data-id="<?php echo $pid; ?>" title="Remove" style="width:34px; height:34px; background:#f4f4f4; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto; cursor:pointer; position:static !important;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            </div>
                                        </td>
                                        <td class="wcs-wl-img">
                                            <a href="<?php echo get_permalink( $pid ); ?>">
                                                <?php echo $product->get_image( 'thumbnail' ); ?>
                                            </a>
                                        </td>
                                        <td class="wcs-wl-title">
                                            <a href="<?php echo get_permalink( $pid ); ?>"><?php echo $product->get_name(); ?></a>
                                        </td>
                                        <td class="wcs-wl-price">
                                            <?php echo $product->get_price_html(); ?>
                                        </td>
                                        <td class="wcs-wl-stock">
                                            <span class="wcs-stock-label <?php echo $stock_class; ?>"><?php echo $stock_status; ?></span>
                                        </td>
                                        <td class="wcs-wl-action">
                                            <?php 
                                            if ( $product->is_in_stock() ) {
                                                // Reverted to Add to Cart but kept the style
                                                echo '<a href="?add-to-cart=' . $pid . '" class="wcs-select-options-btn">ADD TO CART</a>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <style>
                        .wcs-wishlist-table-wrapper { overflow-x: auto; margin-top: 30px; font-family: "Outfit", sans-serif; border: 1px solid #eee; border-radius: 4px; }
                        .wcs-wishlist-table { width: 100%; border-collapse: collapse; background: #fff; }
                        
                        /* Headers */
                        .wcs-wishlist-table th { 
                            background: #fcfcfc; 
                            padding: 18px 15px; 
                            text-transform: uppercase; 
                            font-size: 13px; 
                            font-weight: 700; 
                            color: #666; 
                            text-align: center; 
                            border-bottom: 2px solid #eee;
                            border-right: 1px solid #eee;
                            letter-spacing: 0.5px;
                        }
                        .wcs-wishlist-table th:last-child { border-right: none; }

                        /* Cells */
                        .wcs-wishlist-table td { 
                            padding: 20px 15px; 
                            border-bottom: 1px solid #eee; 
                            border-right: 1px solid #eee; 
                            vertical-align: middle; 
                            text-align: center; 
                            font-size: 14px;
                            color: #444;
                        }
                        .wcs-wishlist-table td:last-child { border-right: none; }
                        .wcs-wishlist-table tr:hover td { background: #fafafa; }

                        /* Columns */
                        .wcs-wl-remove { width: 60px; }
                        .wcs-wl-img { width: 100px; }
                        .wcs-wl-img img { width: 70px; height: 70px; object-fit: contain; }
                        
                        .wcs-wl-title { text-align: left !important; font-weight: 600; padding-left: 20px !important; }
                        .wcs-wl-title a { color: #333; text-decoration: none; font-size: 15px; }
                        .wcs-wl-title a:hover { color: #A101F6; }
                        
                        .wcs-wl-price { font-weight: 700; color: #222; font-size: 15px; }
                        
                        /* Stock Badge */
                        .wcs-stock-label { font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 5px 10px; border-radius: 4px; letter-spacing: 0.5px; }
                        .wcs-stock-label.in-stock { color: #16a34a; background: #f0fdf4; border: 1px solid #dcfce7; }
                        .wcs-stock-label.out-of-stock { color: #dc2626; background: #fef2f2; border: 1px solid #fee2e2; }

                        /* Select/Add Button */
                        .wcs-select-options-btn { 
                            display: inline-block; 
                            background: #222; 
                            color: #fff; 
                            padding: 12px 25px; 
                            border-radius: 4px; 
                            font-weight: 700; 
                            font-size: 12px; 
                            text-transform: uppercase; 
                            text-decoration: none; 
                            transition: all 0.2s; 
                            letter-spacing: 1px;
                        }
                        .wcs-select-options-btn:hover { background: #444; color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

                        /* Remove Button (Circle) - Overrides */
                        .wcs-remove-btn:hover { background: #ff4444 !important; }
                        .wcs-remove-btn:hover svg { stroke: #fff !important; }

                        /* Empty State */
                        .wcs-wishlist-empty { text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px solid #eee; margin-top: 30px; }
                        .wcs-wishlist-empty p { font-size: 18px; color: #777; margin-bottom: 25px; }
                    </style>
                    <script>
                        jQuery(document).ready(function($){
                            // Live Remove Row
                            $(document).on('click', '.wcs-remove-wishlist', function(e){
                                e.preventDefault();
                                var btn = $(this);
                                var row = btn.closest('tr');
                                var pid = btn.data('id');
                                
                                row.css('opacity', '0.5');
                                
                                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                    action: 'wcs_toggle_wishlist',
                                    product_id: pid
                                }, function(res) {
                                    if(res.success) {
                                        row.fadeOut(300, function(){ $(this).remove(); });
                                        // Check empty
                                        if($('.wcs-wishlist-table tbody tr').length <= 1) {
                                            location.reload(); 
                                        }
                                    }
                                });
                            });
                        });
                    </script>
                    <?php
                }
                return ob_get_clean();
            }
        }

        new WCS_Standalone_Wishlist();
    }
