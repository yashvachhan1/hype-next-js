<?php
/**
 * Module: Variations Table
 * Description: Displays a grid of variations with quantity inputs for bulk ordering.
 */

if ( ! class_exists( 'WCS_Wholesale_Table' ) ) {

    class WCS_Wholesale_Table {

        protected $plugin_name;
        protected $version;

        public function __construct( $plugin_name, $version ) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;

            // Display on Single Product Page
            add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_variations_table' ), 15 );
            
            // AJAX Add to Cart
            add_action( 'wp_ajax_wcs_bulk_add_to_cart', array( $this, 'bulk_add_to_cart' ) );
            add_action( 'wp_ajax_nopriv_wcs_bulk_add_to_cart', array( $this, 'bulk_add_to_cart' ) );
            
            // Enqueue Scripts/Styles
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }

        public function enqueue_scripts() {
            if ( ! is_product() ) return;
            
            // Inline CSS for Premium Look
            wp_add_inline_style( 'woocommerce-general', "
                .wcs-variations-table-container {
                    margin: 30px 0;
                    background: #fff;
                    border-radius: 12px;
                    border: 1px solid #f0f0f0;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
                    font-family: 'Inter', -apple-system, sans-serif;
                }
                .wcs-v-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .wcs-v-table th {
                    background: #f8f9fa;
                    padding: 15px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: #2d3436;
                    border-bottom: 2px solid #eee;
                    font-size: 14px;
                }
                .wcs-v-table td {
                    padding: 15px 20px;
                    border-bottom: 1px solid #f0f0f0;
                    vertical-align: middle;
                    color: #636e72;
                    font-size: 15px;
                }
                .wcs-v-table tr:hover {
                    background: #fafafa;
                }
                .wcs-qty-input {
                    width: 80px !important;
                    padding: 8px 12px !important;
                    background: #fff !important;
                    border: 1px solid #dfe6e9 !important;
                    border-radius: 6px !important;
                    text-align: center !important;
                    -moz-appearance: textfield !important;
                }
                .wcs-v-footer {
                    padding: 20px;
                    background: #fff;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid #eee;
                }
                .wcs-v-stats {
                    font-size: 14px;
                    color: #2d3436;
                }
                .wcs-v-stats strong {
                    color: #6c5ce7;
                    font-size: 18px;
                }
                .wcs-bulk-btn {
                    background: #a29bfe !important;
                    color: #fff !important;
                    padding: 12px 30px !important;
                    border-radius: 8px !important;
                    border: none !important;
                    font-weight: 600 !important;
                    cursor: pointer !important;
                    transition: all 0.3s ease !important;
                    box-shadow: 0 4px 15px rgba(162, 155, 254, 0.3) !important;
                }
                .wcs-bulk-btn:hover {
                    background: #6c5ce7 !important;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(108, 92, 231, 0.4) !important;
                }
                .wcs-bulk-btn:disabled {
                    background: #ccc !important;
                    cursor: not-allowed !important;
                    transform: none !important;
                    box-shadow: none !important;
                }
                .wcs-price-old {
                    text-decoration: line-through;
                    color: #b2bec3;
                    font-size: 13px;
                    margin-right: 5px;
                }
                .wcs-price-new {
                    font-weight: 700;
                    color: #2d3436;
                }
            " );

            // AJAX Script
            wp_add_inline_script( 'wcs-public-js', "
                jQuery(document).ready(function($) {
                    const table = $('.wcs-variations-table-container');
                    const btn = $('.wcs-bulk-btn');
                    const totalItems = $('.wcs-total-items');
                    const totalPrice = $('.wcs-total-price');

                    function updateStats() {
                        let count = 0;
                        let total = 0;
                        $('.wcs-qty-input').each(function() {
                            let q = parseInt($(this).val()) || 0;
                            let p = parseFloat($(this).data('price')) || 0;
                            count += q;
                            total += (q * p);
                        });
                        totalItems.text(count);
                        totalPrice.text(total.toFixed(2));
                        btn.prop('disabled', count === 0);
                    }

                    $('.wcs-qty-input').on('change input', updateStats);

                    btn.on('click', function(e) {
                        e.preventDefault();
                        const items = [];
                        $('.wcs-qty-input').each(function() {
                            let q = parseInt($(this).val()) || 0;
                            if (q > 0) {
                                items.push({
                                    id: $(this).data('id'),
                                    qty: q
                                });
                            }
                        });

                        if (items.length === 0) return;

                        btn.text('Adding...').prop('disabled', true);

                        $.ajax({
                            url: wcs_shop_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'wcs_bulk_add_to_cart',
                                items: items,
                                nonce: '" . wp_create_nonce('wcs_bulk_nonce') . "'
                            },
                            success: function(response) {
                                if (response.success) {
                                    btn.text('Added!').css('background', '#00b894');
                                    $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
                                    setTimeout(() => {
                                        btn.text('Add Selected to Cart').css('background', '#a29bfe');
                                        btn.prop('disabled', false);
                                        $('.wcs-qty-input').val(0);
                                        updateStats();
                                    }, 2000);
                                }
                            }
                        });
                    });
                });
            " );
        }

        public function render_variations_table() {
            global $product;

            if ( ! $product->is_type( 'variable' ) ) return;

            $variations = $product->get_available_variations();
            if ( empty( $variations ) ) return;

            // Get labels
            $attributes = $product->get_variation_attributes();
            
            echo '<div class="wcs-variations-table-container">';
            echo '<table class="wcs-v-table">';
            echo '<thead><tr>';
            echo '<th>Flavour / Variant</th>';
            echo '<th>Price</th>';
            echo '<th>Quantity</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ( $variations as $variation ) {
                $v_obj = wc_get_product( $variation['variation_id'] );
                $name = implode( ' / ', array_values( $variation['attributes'] ) );
                
                // If clean name is empty, use the variant name or SKU
                if ( empty( str_replace( '/', '', $name ) ) ) {
                    $name = $v_obj->get_name();
                }

                $price_html = $v_obj->get_price_html();
                $price_val = $v_obj->get_price();

                echo '<tr>';
                echo '<td><strong>' . esc_html( $name ) . '</strong></td>';
                echo '<td>' . wp_kses_post( $price_html ) . '</td>';
                echo '<td><input type="number" class="wcs-qty-input" value="0" min="0" data-id="' . esc_attr( $variation['variation_id'] ) . '" data-price="' . esc_attr( $price_val ) . '"></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            echo '<div class="wcs-v-footer">';
            echo '<div class="wcs-v-stats">';
            echo 'Items: <span class="wcs-total-items">0</span> | ';
            echo 'Total: <span class="wcs-currency">' . get_woocommerce_currency_symbol() . '</span><strong class="wcs-total-price">0.00</strong>';
            echo '</div>';
            echo '<button type="button" class="wcs-bulk-btn" disabled>Add Selected to Cart</button>';
            echo '</div>';
            echo '</div>';
            
            // Hide default form for better UX
            echo '<style>.variations_form { display: none !important; }</style>';
        }

        public function bulk_add_to_cart() {
            check_ajax_referer( 'wcs_bulk_nonce', 'nonce' );

            if ( ! isset( $_POST['items'] ) ) wp_send_json_error();

            $items = $_POST['items'];
            $added = 0;

            foreach ( $items as $item ) {
                $product_id = intval( $item['id'] );
                $quantity = intval( $item['qty'] );

                if ( $quantity > 0 ) {
                    WC()->cart->add_to_cart( $product_id, $quantity );
                    $added++;
                }
            }

            if ( $added > 0 ) {
                $data = array(
                    'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
                    'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() )
                );
                wp_send_json_success( $data );
            }

            wp_send_json_error();
        }
    }
}
