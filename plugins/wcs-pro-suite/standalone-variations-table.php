<?php
/**
 * Standalone Variations Matrix Grid: Premium Edition
 * Features: High-end UI/UX, Dynamic Price Fix, Matrix Layout.
 */

if ( ! class_exists( 'WCS_Standalone_Variations_Matrix' ) ) {

    class WCS_Standalone_Variations_Matrix {

        public function __construct() {
            // High priority to appear correctly on product pages
            add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_matrix' ), 20 );
            
            add_action( 'wp_ajax_wcs_bulk_add_to_cart', array( $this, 'ajax_add' ) );
            add_action( 'wp_ajax_nopriv_wcs_bulk_add_to_cart', array( $this, 'ajax_add' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        }

        public function enqueue_assets() {
            if ( ! is_product() ) return;
            
            // Premium Styling: Using Modern Design Tokens
            wp_add_inline_style( 'woocommerce-general', "
                @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

                .wcs-matrix-box { 
                    margin: 40px 0; 
                    background: #ffffff; 
                    border-radius: 20px; 
                    border: 1px solid rgba(0,0,0,0.05); 
                    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08); 
                    overflow: hidden; 
                    font-family: 'Plus Jakarta Sans', sans-serif; 
                    clear: both;
                    transition: all 0.3s ease;
                }
                
                .wcs-m-header { 
                    padding: 25px 30px; 
                    background: linear-gradient(to right, #fcfcfc, #ffffff); 
                    border-bottom: 1px solid #f3f4f6; 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center; 
                }
                
                .wcs-m-header span { 
                    font-size: 13px; 
                    font-weight: 700; 
                    text-transform: uppercase; 
                    letter-spacing: 1px; 
                    color: #94a3b8; 
                }
                
                .wcs-m-header strong { 
                    font-size: 28px; 
                    color: #1e293b; 
                    font-weight: 800; 
                    background: linear-gradient(45deg, #6366f1, #a855f7);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                }

                .wcs-m-table-wrap { width: 100%; overflow-x: auto; }
                .wcs-m-table { width: 100%; border-collapse: collapse; min-width: 500px; }
                
                .wcs-m-table th { 
                    background: #ffffff; 
                    padding: 18px 15px; 
                    text-align: center; 
                    border-bottom: 2px solid #f1f5f9; 
                    font-size: 13px; 
                    color: #64748b; 
                    text-transform: uppercase; 
                    font-weight: 700;
                    letter-spacing: 0.5px;
                }
                
                .wcs-m-table th.row-label { 
                    text-align: left; 
                    background: #f8fafc; 
                    border-right: 1px solid #f1f5f9; 
                    padding-left: 30px; 
                    width: 200px; 
                }
                
                .wcs-m-table td { 
                    padding: 15px 10px; 
                    text-align: center; 
                    border-bottom: 1px solid #f1f5f9; 
                    transition: background 0.2s ease;
                }
                
                .wcs-m-table tr:hover td { background: #fdfdff; }
                
                .wcs-m-table td.row-name { 
                    text-align: left; 
                    font-weight: 700; 
                    background: #f8fafc; 
                    border-right: 1px solid #f1f5f9; 
                    padding-left: 30px; 
                    color: #334155; 
                    font-size: 15px; 
                }
                
                /* Modern Input Styling */
                .wcs-input-grid { 
                    width: 85px !important; 
                    height: 44px !important; 
                    border-radius: 12px !important; 
                    border: 2px solid #e2e8f0 !important; 
                    text-align: center !important; 
                    font-weight: 700 !important; 
                    font-size: 16px !important; 
                    padding: 0 !important; 
                    margin: 0 auto; 
                    display: block; 
                    transition: all 0.3s ease !important;
                    background: #fff !important;
                    appearance: textfield !important;
                    -moz-appearance: textfield !important;
                }
                
                .wcs-input-grid:focus { 
                    border-color: #6366f1 !important; 
                    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
                    outline: none !important; 
                }
                
                .wcs-input-grid:hover { border-color: #cbd5e1 !important; }

                .wcs-m-footer { 
                    padding: 30px; 
                    background: #ffffff; 
                    border-top: 1px solid #f1f5f9; 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center; 
                    flex-wrap: wrap;
                    gap: 20px;
                }
                
                .wcs-m-stats { 
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    color: #64748b; 
                    font-size: 14px; 
                    font-weight: 600;
                }
                
                .wcs-m-stats .stat-item {
                    background: #f8fafc;
                    padding: 10px 15px;
                    border-radius: 10px;
                    border: 1px solid #f1f5f9;
                }
                
                .wcs-m-stats strong { 
                    color: #0f172a; 
                    font-size: 24px; 
                    margin-left: 8px; 
                    vertical-align: middle;
                }
                
                .wcs-m-bulk-btn { 
                    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important; 
                    color: #ffffff !important; 
                    padding: 16px 40px !important; 
                    border-radius: 14px !important; 
                    border: none !important; 
                    font-weight: 800 !important; 
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    cursor: pointer !important; 
                    box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4) !important;
                    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
                }
                
                .wcs-m-bulk-btn:hover { 
                    transform: translateY(-2px);
                    box-shadow: 0 20px 35px -10px rgba(99, 102, 241, 0.5) !important;
                }
                
                .wcs-m-bulk-btn:active { transform: translateY(0); }
                
                .wcs-m-bulk-btn:disabled { 
                    background: #e2e8f0 !important; 
                    box-shadow: none !important; 
                    cursor: not-allowed !important; 
                    transform: none !important;
                    color: #94a3b8 !important;
                }
                
                .cell-price { 
                    display: block; 
                    font-size: 11px; 
                    color: #64748b; 
                    margin-top: 5px; 
                    font-weight: 600;
                }

                @media (max-width: 600px) {
                    .wcs-m-footer { flex-direction: column; text-align: center; }
                    .wcs-m-bulk-btn { width: 100%; }
                }
            " );

            wp_add_inline_script( 'jquery', "
                jQuery(document).ready(function($) {
                    const inputs = $('.wcs-input-grid');
                    const btn = $('.wcs-m-bulk-btn');

                    function sync() {
                        let totalQty = 0; 
                        let totalPrice = 0;
                        
                        inputs.each(function() {
                            let qty = parseInt($(this).val()) || 0;
                            let price = parseFloat($(this).attr('data-price')) || 0;
                            
                            if (qty > 0) {
                                totalQty += qty;
                                totalPrice += (qty * price);
                                $(this).addClass('active-input').parent().parent().addClass('active-row');
                            } else {
                                $(this).removeClass('active-input').parent().parent().removeClass('active-row');
                            }
                        });
                        
                        $('.wcs-m-count').text(totalQty);
                        $('.wcs-m-total').text(totalPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        btn.prop('disabled', totalQty === 0);
                    }

                    inputs.on('input change focus', sync);

                    btn.on('click', function() {
                        const items = [];
                        inputs.each(function() {
                            let val = parseInt($(this).val()) || 0;
                            if (val > 0) items.push({ id: $(this).data('id'), qty: val });
                        });
                        
                        const loaderTxt = 'Adding to Cart...';
                        const originalTxt = btn.text();
                        btn.text(loaderTxt).prop('disabled', true);

                        $.post('" . admin_url('admin-ajax.php') . "', {
                            action: 'wcs_bulk_add_to_cart',
                            items: items,
                            sec: '" . wp_create_nonce('matrix_grid_nonce') . "'
                        }, function(res) {
                            if (res.success) {
                                btn.text('Items Added! ✅').css('background', '#22c55e');
                                $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash]);
                                setTimeout(() => {
                                    btn.text(originalTxt).css('background', '');
                                    inputs.val(0).trigger('input');
                                }, 3000);
                            } else {
                                btn.text('Error!').css('background', '#ef4444');
                                setTimeout(() => btn.text(originalTxt).css('background', '').prop('disabled', false), 2000);
                            }
                        });
                    });
                });
            " );
        }

        public function render_matrix() {
            global $product;
            if ( ! is_object( $product ) || ! $product->is_type( 'variable' ) ) {
                $product = wc_get_product( get_the_ID() );
                if ( ! is_object( $product ) || ! $product->is_type( 'variable' ) ) return;
            }

            $vars = $product->get_available_variations();
            $attrs = $product->get_variation_attributes();
            if ( empty($attrs) ) return;

            $keys = array_keys($attrs);
            if ( count($keys) < 2 ) {
                $this->render_simple_list($vars, $attrs);
                return;
            }

            $v_id = $keys[0]; // Vertical (Rows)
            $h_id = $keys[1]; // Horizontal (Cols)
            $v_opts = $attrs[$v_id];
            $h_opts = $attrs[$h_id];

            $grid = array(); 
            $prices_check = array();
            
            foreach ($vars as $v) {
                $v_val = ''; $h_val = '';
                foreach($v['attributes'] as $attr_key => $attr_val) {
                    $ck = str_replace('attribute_', '', $attr_key);
                    if ($ck === $v_id || $ck === sanitize_title($v_id) || $ck === 'pa_' . sanitize_title($v_id)) $v_val = $attr_val;
                    if ($ck === $h_id || $ck === sanitize_title($h_id) || $ck === 'pa_' . sanitize_title($h_id)) $h_val = $attr_val;
                }
                $grid[sanitize_title($v_val)][sanitize_title($h_val)] = $v;
                $prices_check[] = floatval($v['display_price']);
            }

            $is_uniform_price = count(array_unique($prices_check)) === 1;
            // Get accurate price HTML for header
            $header_price_html = $is_uniform_price ? $vars[0]['price_html'] : '';

            echo '<div class="wcs-matrix-box">';
                if ($header_price_html) {
                    echo '<div class="wcs-m-header"><span>Standard Pricing:</span><strong>'.$header_price_html.'</strong></div>';
                }

                echo '<div class="wcs-m-table-wrap"><table class="wcs-m-table"><thead><tr>';
                echo '<th class="row-label">'.wc_attribute_label($v_id).'</th>';
                foreach($h_opts as $h) echo '<th>'.esc_html($h).'</th>';
                echo '</tr></thead><tbody>';
                
                foreach($v_opts as $v_opt) {
                    echo '<tr><td class="row-name">'.esc_html($v_opt).'</td>';
                    foreach($h_opts as $h_opt) {
                        $v_slug = sanitize_title($v_opt);
                        $h_slug = sanitize_title($h_opt);
                        $data = isset($grid[$v_slug][$h_slug]) ? $grid[$v_slug][$h_slug] : null;

                        echo '<td>';
                        if ($data) {
                            // Using data-price for accurate JS calc
                            echo '<input type="number" class="wcs-input-grid" value="0" min="0" data-id="'.esc_attr($data['variation_id']).'" data-price="'.esc_attr($data['display_price']).'">';
                            if (!$is_uniform_price) {
                                echo '<span class="cell-price">'.strip_tags($data['price_html']).'</span>';
                            }
                        } else {
                            echo '<span style="color:#e2e8f0; font-size: 20px;">-</span>';
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
                
                echo '<div class="wcs-m-footer">';
                    echo '<div class="wcs-m-stats">';
                        echo '<div class="stat-item">ITEMS: <strong class="wcs-m-count">0</strong></div>';
                        echo '<div class="stat-item">SUBTOTAL: <strong>'.get_woocommerce_currency_symbol().'<span class="wcs-m-total">0.00</span></strong></div>';
                    echo '</div>';
                    echo '<button type="button" class="wcs-m-bulk-btn" disabled>Add Selected to Cart</button>';
                echo '</div>';
            echo '</div>';
            
            echo '<style>.variations_form { display: none !important; }</style>';
        }

        public function render_simple_list($vars, $attrs) {
            $aid = array_key_first($attrs);
            echo '<div class="wcs-matrix-box"><table class="wcs-m-table"><thead><tr><th class="row-label">'.wc_attribute_label($aid).'</th><th>Price</th><th>Quantity</th></tr></thead><tbody>';
            foreach ($vars as $v) {
                $label = $v['attributes']['attribute_'.$aid] ?: array_values($v['attributes'])[0];
                echo '<tr><td class="row-name">'.esc_html($label).'</td><td>'.$v['price_html'].'</td><td><input type="number" class="wcs-input-grid" value="0" min="0" data-id="'.esc_attr($v['variation_id']).'" data-price="'.esc_attr($v['display_price']).'"></td></tr>';
            }
            echo '</tbody></table><div class="wcs-m-footer"><div class="stat-item">ITEMS: <strong class="wcs-m-count">0</strong></div><div class="stat-item">SUBTOTAL: <strong>'.get_woocommerce_currency_symbol().'<span class="wcs-m-total">0.00</span></strong></div><button type="button" class="wcs-m-bulk-btn" disabled>Add Selected to Cart</button></div></div>';
            echo '<style>.variations_form { display: none !important; }</style>';
        }

        public function ajax_add() {
            check_ajax_referer( 'matrix_grid_nonce', 'sec' );
            if ( isset( $_POST['items'] ) ) {
                foreach ( $_POST['items'] as $item ) { WC()->cart->add_to_cart( intval($item['id']), intval($item['qty']) ); }
                wp_send_json_success( array( 'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ), 'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ) ) );
            }
            wp_send_json_error();
        }
    }
    new WCS_Standalone_Variations_Matrix();
}
