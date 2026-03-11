<?php
/**
 * Module: Wishlist
 * Description: Simple Wishlist for Guests (Cookie) & Users (Meta).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCS_Wishlist' ) ) {

    class WCS_Wishlist {

        public function __construct() {
            add_action( 'init', array( $this, 'register_endpoints' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

            // AJAX Hooks
            add_action( 'wp_ajax_wcs_toggle_wishlist', array( $this, 'ajax_toggle_wishlist' ) );
            add_action( 'wp_ajax_nopriv_wcs_toggle_wishlist', array( $this, 'ajax_toggle_wishlist' ) );

            // UI Actions
            add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_loop_heart' ), 15 );
            add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_heart' ), 35 );

            // Shortcode
            add_shortcode( 'wcs_wishlist', array( $this, 'render_wishlist_page' ) );
        }

        public function register_endpoints() {
            // Register My Account Endpoint if needed in future, keeping simple for now.
        }

        public function enqueue_scripts() {
            wp_enqueue_style( 'dashicons' );
            ?>
            <script>
                jQuery(document).ready(function($){
                    $(document).on('click', '.wcs-wishlist-btn', function(e){
                        e.preventDefault();
                        var btn = $(this);
                        var product_id = btn.data('id');
                        
                        btn.addClass('wcs-loading');

                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'wcs_toggle_wishlist',
                                product_id: product_id
                            },
                            success: function(response) {
                                btn.removeClass('wcs-loading');
                                if(response.success) {
                                    if(response.data.status === 'added') {
                                        btn.addClass('active');
                                    } else {
                                        btn.removeClass('active');
                                    }
                                    // Update count if we have a counter somewhere
                                }
                            }
                        });
                    });
                });
            </script>
            <style>
                .wcs-wishlist-btn { cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; color: #ccc; }
                .wcs-wishlist-btn:hover { color: #ff4444; }
                .wcs-wishlist-btn.active { color: #ff4444; }
                .wcs-wishlist-btn .dashicons { font-size: 20px; width: 20px; height: 20px; }
                .wcs-loading { opacity: 0.5; pointer-events: none; }
                
                /* Loop Position */
                .wcs-loop-heart { position: absolute; top: 10px; right: 10px; z-index: 10; background: #fff; border-radius: 50%; width: 30px; height: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                
                /* Single Position */
                .wcs-single-heart { margin-top: 10px; font-weight: 500; font-size: 14px; }
                .wcs-single-heart .dashicons { margin-right: 5px; }

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
            $is_active = $this->is_in_wishlist( $id );
            $class = $is_active ? 'active' : '';
            echo '<div class="wcs-wishlist-btn wcs-loop-heart ' . $class . '" data-id="' . $id . '"><span class="dashicons dashicons-heart"></span></div>';
        }

        public function render_single_heart() {
            global $product;
            if ( ! $product ) return;
            $id = $product->get_id();
            $is_active = $this->is_in_wishlist( $id );
            $class = $is_active ? 'active' : '';
            $text = $is_active ? 'Saved to Wishlist' : 'Add to Wishlist';
            echo '<div class="wcs-wishlist-btn wcs-single-heart ' . $class . '" data-id="' . $id . '"><span class="dashicons dashicons-heart"></span> <span class="wcs-text">' . $text . '</span></div>';
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
                echo '<p>Your wishlist is currently empty.</p>';
            } else {
                echo '<div class="wcs-wishlist-grid">';
                foreach ( $list as $pid ) {
                    $product = wc_get_product( $pid );
                    if ( ! $product ) continue;
                    ?>
                    <div class="wcs-wishlist-item">
                        <span class="dashicons dashicons-no wcs-wishlist-btn wcs-remove-wishlist active" data-id="<?php echo $pid; ?>"></span>
                        <a href="<?php echo get_permalink( $pid ); ?>">
                            <?php echo $product->get_image( 'thumbnail', array( 'class' => 'wcs-wishlist-img' ) ); ?>
                            <span class="wcs-wishlist-title"><?php echo $product->get_name(); ?></span>
                        </a>
                        <span class="price"><?php echo $product->get_price_html(); ?></span>
                        <?php echo do_shortcode( '[add_to_cart id="' . $pid . '" show_price="false" style="margin-top:10px;"]' ); ?>
                    </div>
                    <?php
                }
                echo '</div>';
            }
            return ob_get_clean();
        }
    }

    new WCS_Wishlist();
}
