<?php
/**
 * Standalone Variations Matrix Grid for WooCommerce
 * Layout: Flavors (Vertical) | Strengths (Horizontal)
 * Smart Pricing: Shows price at top if uniform.
 */

if ( ! class_exists( 'WCS_Standalone_Variations_Matrix' ) ) {

    class WCS_Standalone_Variations_Matrix {

        public function __construct() {
            add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_matrix' ), 15 );
            add_action( 'wp_ajax_wcs_bulk_add_to_cart', array( $this, 'ajax_add' ) );
            add_action( 'wp_ajax_nopriv_wcs_bulk_add_to_cart', array( $this, 'ajax_add' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        }

        public function enqueue_styles() {
            if ( ! is_product() ) return;

            wp_add_inline_style( 'woocommerce-general', "
                .wcs-matrix-container {
                    margin: 35px 0;
                    font-family: 'Inter', system-ui, sans-serif;
                    background: #fff;
                    border-radius: 12px;
                    border: 1px solid #f0f0f0;
                    box-shadow: 0 15px 40px rgba(0,0,0,0.03);
                    overflow: hidden;
                }
                .wcs-matrix-header {
                    padding: 20px 25px;
                    background: #fafafa;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .wcs-top-price { font-size: 24px; font-weight: 800; color: #2d3436; }
                .wcs-matrix-table-wrapper { width: 100%; overflow-x: auto; }
                .wcs-matrix-table { width: 100%; border-collapse: collapse; min-width: 600px; }
                .wcs-matrix-table th {
                    background: #fff;
                    padding: 15px;
                    text-align: center;
                    border-bottom: 1.5px solid #eee;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #636e72;
                }
                .wcs-matrix-table th.flavor-col { text-align: left; width: 200px; padding-left: 25px; background: #fafafa; border-right: 1.5px solid #eee; }
                .wcs-matrix-table td {
                    padding: 12px 15px;
                    text-align: center;
                    border-bottom: 1px solid #f5f5f5;
                }
                .wcs-matrix-table td.flavor-name { text-align: left; padding-left: 25px; font-weight: 600; background: #fafafa; border-right: 1.5px solid #eee; color: #2d3436; }
                .wcs-qty-grid {
                    width: 70px !important;
                    height: 38px !important;
                    text-align: center !important;
                    border-radius: 6px !important;
                    border: 1.5px solid #e0e0e0 !important;
                    font-size: 14px !important;
                    padding: 0 !important;
                }
                .wcs-qty-grid:focus { border-color: #a29bfe !important; outline: none; }
                .wcs-matrix-footer {
                    padding: 25px;
                    background: #fff;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid #eee;
                }
                .wcs-total-summary { font-size: 14px; color: #636e72; }
                .wcs-total-summary strong { color: #2d3436; font-size: 20px; }
                .wcs-bulk-btn {
                    background: #a29bfe !important;
                    color: #fff !important;
                    padding: 14px 40px !important;
                    border-radius: 10px !important;
                    font-weight: 700 !important;
                    border: none !important;
                    cursor: pointer !important;
                    box-shadow: 0 8px 25px rgba(162, 155, 254, 0.45) !important;
                    transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }
                .wcs-bulk-btn:hover { background: #6c5ce7 !important; transform: translateY(-2px); }
                .wcs-bulk-btn:disabled { background: #dfe6e9 !important; box-shadow: none !important; cursor: not-allowed; transform: none; }
                .cell-price { display: block; font-size: 11px; margin-top: 5px; color: #b2bec3; }
            " );

            wp_add_inline_script( 'jquery', "
                jQuery(document).ready(function($) {
                    const inputs = $('.wcs-qty-grid');
                    const btn = $('.wcs-bulk-btn');

                    function update() {
                        let total = 0; let totalP = 0;
                        inputs.each(function() {
                            let q = parseInt($(this).val()) || 0;
                            total += q;
                            totalP += (q * parseFloat($(this).data('price')));
                        });
                        $('.wcs-count').text(total);
                        $('.wcs-grand').text(totalP.toFixed(2));
                        btn.prop('disabled', total === 0);
                    }

                    inputs.on('input', update);

                    btn.on('click', function() {
                        const items = [];
                        inputs.each(function() {
                            let q = parseInt($(this).val()) || 0;
                            if (q > 0) items.push({ id: $(this).data('id'), qty: q });
                        });

                        $(this).text('Processing...').prop('disabled', true);

                        $.post('" . admin_url('admin-ajax.php') . "', {
                            action: 'wcs_bulk_add_to_cart',
                            items: items,
                            sec: '" . wp_create_nonce('matrix_nonce') . "'
                        }, function(res) {
                            if (res.success) {
                                btn.text('Successfully Added!').css('background', '#00b894');
                                $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash]);
                                setTimeout(() => {
                                    btn.text('Add Selected to Cart').css('background', '#a29bfe');
                                    inputs.val(0); update();
                                }, 2500);
                            }
                        });
                    });
                });
            " );
        }

        public function render_matrix() {
            global $product;
            if ( ! $product->is_type( 'variable' ) ) return;

            $variations = $product->get_available_variations();
            $attributes = $product->get_variation_attributes();

            // Needs exactly 2 attributes for a matrix grid
            if ( count($attributes) !== 2 ) {
                echo '<p style="color:#d63031; font-weight:600;">Matrix grid requires exactly 2 attributes (e.g. Flavor & Strength).</p>';
                return;
            }

            $attr_names = array_keys($attributes);
            $primary_attr = $attr_names[0]; // Vertical (Rows)
            $secondary_attr = $attr_names[1]; // Horizontal (Cols)
            $primary_options = $attributes[$primary_attr];
            $secondary_options = $attributes[$secondary_attr];

            // Map variations for quick access
            $matrix = array();
            $prices = array();
            foreach ($variations as $v) {
                $p1 = $v['attributes']['attribute_'.$primary_attr];
                $p2 = $v['attributes']['attribute_'.$secondary_attr];
                $matrix[$p1][$p2] = $v;
                $prices[] = floatval($v['display_price']);
            }

            // Price Check: Same for all?
            $unique_prices = array_unique($prices);
            $is_same_price = count($unique_prices) === 1;
            $display_top_price = $is_same_price ? array_shift($variations)['price_html'] : '';

            echo '<div class="wcs-matrix-container">';
                if ($display_top_price) {
                    echo '<div class="wcs-matrix-header">';
                        echo '<span style="color:#636e72; font-size:14px;">Uniform Price:</span>';
                        echo '<div class="wcs-top-price">' . $display_top_price . '</div>';
                    echo '</div>';
                }

                echo '<div class="wcs-matrix-table-wrapper">';
                    echo '<table class="wcs-matrix-table">';
                        echo '<thead><tr>';
                            echo '<th class="flavor-col">' . wc_attribute_label($primary_attr) . '</th>';
                            foreach ($secondary_options as $opt) {
                                echo '<th>' . esc_html($opt) . '</th>';
                            }
                        echo '</tr></thead>';
                        echo '<tbody>';
                            foreach ($primary_options as $p_opt) {
                                echo '<tr>';
                                    echo '<td class="flavor-name">' . esc_html($p_opt) . '</td>';
                                    foreach ($secondary_options as $s_opt) {
                                        $v_data = isset($matrix[$p_opt][$s_opt]) ? $matrix[$p_opt][$s_opt] : null;
                                        echo '<td>';
                                            if ($v_data) {
                                                echo '<input type="number" class="wcs-qty-grid" value="0" min="0" data-id="'.$v_data['variation_id'].'" data-price="'.$v_data['display_price'].'">';
                                                if (!$is_same_price) {
                                                    echo '<span class="cell-price">' . strip_tags($v_data['price_html']) . '</span>';
                                                }
                                            } else {
                                                echo '<span style="color:#eee;">-</span>';
                                            }
                                        echo '</td>';
                                    }
                                echo '</tr>';
                            }
                        echo '</tbody>';
                    echo '</table>';
                echo '</div>';

                echo '<div class="wcs-matrix-footer">';
                    echo '<div class="wcs-total-summary">';
                        echo 'Items: <span class="wcs-count">0</span> | Total: <strong>' . get_woocommerce_currency_symbol() . '<span class="wcs-grand">0.00</span></strong>';
                    echo '</div>';
                    echo '<button type="button" class="wcs-bulk-btn" disabled>Add Selected to Cart</button>';
                echo '</div>';
            echo '</div>';

            echo '<style>.variations_form { display: none !important; }</style>';
        }

        public function ajax_add() {
            check_ajax_referer( 'matrix_nonce', 'sec' );
            if ( ! isset( $_POST['items'] ) ) wp_send_json_error();

            foreach ( $_POST['items'] as $item ) {
                WC()->cart->add_to_cart( intval($item['id']), intval($item['qty']) );
            }

            wp_send_json_success( array(
                'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
                'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() )
            ) );
        }
    }
    new WCS_Standalone_Variations_Matrix();
}
