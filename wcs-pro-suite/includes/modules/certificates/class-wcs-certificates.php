<?php
/**
 * Module: Product Certificates (COA)
 * Description: Robust Certificates Module with Tab Management & UI Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCS_Certificates_Standard' ) ) {

    class WCS_Certificates_Standard {

        public function __construct() {
            add_action( 'init', array( $this, 'register_resources' ) );
            add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
            
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
            add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
            
            // Frontend Tabs & Shortcode
            add_filter( 'woocommerce_product_tabs', array( $this, 'manage_product_tabs' ), 99 );
            add_shortcode( 'wcs_certificate_gallery', array( $this, 'render_gallery_shortcode' ) );
        }

        public function register_resources() {
            register_post_type( 'wcs_certificate', array(
                'labels' => array(
                    'name'               => 'Certificates',
                    'singular_name'      => 'Certificate',
                    'menu_name'          => 'Certificates',
                    'add_new'            => 'Add New Certificate',
                    'add_new_item'       => 'Add New Certificate',
                ),
                'public'             => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'menu_icon'          => 'dashicons-media-document',
                'supports'           => array( 'title' ),
                'taxonomies'         => array( 'product_cat' ),
            ) );
        }

        public function add_settings_page() {
            add_submenu_page(
                'edit.php?post_type=wcs_certificate',
                'Certificate Settings',
                'Settings',
                'manage_options',
                'wcs-certificates-settings',
                array( $this, 'render_settings_page' )
            );
        }

        public function register_settings() {
            register_setting( 'wcs_cert_settings_group', 'wcs_hide_tabs' );
            register_setting( 'wcs_cert_settings_group', 'wcs_cert_hide_images' ); 
            register_setting( 'wcs_cert_settings_group', 'wcs_gallery_source' ); // 'auto' or 'manual'
            register_setting( 'wcs_cert_settings_group', 'wcs_gallery_manual_items' ); // array of items
        }

        public function render_settings_page() {
            ?>
            <div class="wrap">
                <h1>Certificate Module Settings</h1>
                <form method="post" action="options.php">
                    <?php settings_fields( 'wcs_cert_settings_group' ); ?>
                    
                    <?php
                        $hidden_tabs = get_option( 'wcs_hide_tabs', array() );
                        if ( ! is_array( $hidden_tabs ) ) $hidden_tabs = array();
                        $hide_images = get_option( 'wcs_cert_hide_images' );
                        $gallery_source = get_option( 'wcs_gallery_source', 'auto' );
                        $manual_items = get_option( 'wcs_gallery_manual_items', array() );
                    ?>
                    
                    <h2 class="title">General Display Settings</h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Product Page Display</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="wcs_cert_hide_images" value="1" <?php checked( $hide_images, 1 ); ?>> 
                                        <strong>Hide Images (Text Only Mode)</strong>
                                    </label>
                                    <p class="description">If checked, certificate cards will only show the Title and Download Button (No images/icons).</p>
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Hide WooCommerce Tabs</th>
                            <td>
                                <fieldset>
                                    <label><input type="checkbox" name="wcs_hide_tabs[]" value="description" <?php checked( in_array( 'description', $hidden_tabs ) ); ?>> Description Tab</label><br>
                                    <label><input type="checkbox" name="wcs_hide_tabs[]" value="reviews" <?php checked( in_array( 'reviews', $hidden_tabs ) ); ?>> Reviews Tab</label><br>
                                    <label><input type="checkbox" name="wcs_hide_tabs[]" value="additional_information" <?php checked( in_array( 'additional_information', $hidden_tabs ) ); ?>> Additional Information Tab</label><br>
                                    <hr>
                                    <label><input type="checkbox" name="wcs_hide_tabs[]" value="brand" <?php checked( in_array( 'brand', $hidden_tabs ) ); ?>> Brand Tab (General)</label><br>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <hr>
                    <h2 class="title">Gallery Shortcode Settings <code>[wcs_certificate_gallery]</code></h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Gallery Data Source</th>
                            <td>
                                <select name="wcs_gallery_source" style="width: 250px;">
                                    <option value="auto" <?php selected( $gallery_source, 'auto' ); ?>>Automatic (All Product Certs)</option>
                                    <option value="manual" <?php selected( $gallery_source, 'manual' ); ?>>Manual (Specific Only)</option>
                                </select>
                                <p class="description"><strong>Automatic:</strong> Shows every certificate attached to products or categories.<br><strong>Manual:</strong> Shows only certificates added below.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Manual Gallery Items</th>
                            <td>
                                <div id="wcs-gallery-manual-repeater">
                                    <style>
                                        .wcs-setting-row { background: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
                                        .wcs-setting-row input { width: 100%; margin-bottom: 8px; }
                                    </style>
                                    <div class="wcs-gallery-items-container">
                                        <?php if ( ! empty( $manual_items ) ) : foreach ( $manual_items as $index => $item ) : ?>
                                            <div class="wcs-setting-row">
                                                <label>Title</label>
                                                <input type="text" name="wcs_gallery_manual_items[<?php echo $index; ?>][title]" value="<?php echo esc_attr($item['title']); ?>">
                                                <label>PDF URL</label>
                                                <input type="text" name="wcs_gallery_manual_items[<?php echo $index; ?>][pdf]" value="<?php echo esc_attr($item['pdf']); ?>">
                                                <button type="button" class="button button-link-delete remove-setting-row">Remove</button>
                                            </div>
                                        <?php endforeach; else: ?>
                                            <p id="no-manual-items">No manual items added yet.</p>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button" id="add-manual-item">Add Item to Gallery</button>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>
            <script>
                jQuery(document).ready(function($){
                    $('#add-manual-item').click(function(e) {
                        $('#no-manual-items').hide();
                        var index = $('.wcs-setting-row').length;
                        var html = '<div class="wcs-setting-row">' +
                            '<label>Title</label><input type="text" name="wcs_gallery_manual_items['+index+'][title]">' +
                            '<label>PDF URL</label><input type="text" name="wcs_gallery_manual_items['+index+'][pdf]">' +
                            '<button type="button" class="button button-link-delete remove-setting-row">Remove</button>' +
                        '</div>';
                        $('.wcs-gallery-items-container').append(html);
                    });
                    $(document).on('click', '.remove-setting-row', function() {
                        $(this).closest('.wcs-setting-row').remove();
                    });
                });
            </script>
            <?php
        }

        public function add_meta_boxes() {
            add_meta_box( 'wcs_cert_items_box', 'Certificate Attachments', array( $this, 'render_items_metabox' ), 'wcs_certificate', 'normal', 'high' );
            add_meta_box( 'wcs_cert_items_box', 'Certificate Attachments', array( $this, 'render_items_metabox' ), 'product', 'normal', 'high' );
        }

        public function render_items_metabox( $post ) {
            $items = get_post_meta( $post->ID, '_wcs_cert_items', true ) ?: array();
            wp_nonce_field( 'wcs_cert_save_action', 'wcs_cert_nonce' );
            ?>
            <div id="wcs-cert-repeater">
                <style>
                    .wcs-cert-row { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
                    .wcs-row-inner { display: flex; gap: 20px; align-items: flex-start; }
                    .wcs-field-group { flex: 1; }
                    .wcs-field-group label { font-weight: bold; display: block; margin-bottom: 5px; }
                    .wcs-field-group input { width: 100%; margin-bottom: 5px; }
                    .wcs-preview-img { max-width: 50px; max-height: 50px; display: block; margin-top: 5px; border: 1px solid #ccc; }
                    .wcs-actions { display: flex; align-items: center; justify-content: flex-end; margin-top: 5px; }
                </style>
                <div class="wcs-items-wrapper">
                    <?php if ( ! empty( $items ) ) : foreach ( $items as $index => $item ) : ?>
                        <?php $this->render_repeater_row( $index, $item['pdf'], $item['image'], isset($item['title']) ? $item['title'] : '' ); ?>
                    <?php endforeach; else : ?>
                        <?php $this->render_repeater_row( 0, '', '', '' ); ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button button-primary" id="wcs-add-row">Add New Certificate Row</button>
            </div>
            <script type="text/template" id="wcs-row-template">
                <?php $this->render_repeater_row( 'INDEX', '', '', '' ); ?>
            </script>
            <script>
                jQuery(document).ready(function($){
                    var rowCount = <?php echo empty($items) ? 1 : count($items); ?>;
                    $('#wcs-add-row').click(function(e) {
                        var template = $('#wcs-row-template').html();
                        template = template.replace(/INDEX/g, rowCount);
                        $('.wcs-items-wrapper').append(template);
                        rowCount++;
                    });
                    $(document).on('click', '.wcs-remove-row', function() {
                        $(this).closest('.wcs-cert-row').remove();
                    });
                    $(document).on('click', '.wcs-btn-upload', function(e) {
                        e.preventDefault();
                        var btn = $(this);
                        var type = btn.data('type');
                        var input = btn.prev('input');
                        var uploader = wp.media({ title: 'Select File', button: { text: 'Use this file' }, library: { type: type }, multiple: false })
                        .on('select', function() {
                            var attachment = uploader.state().get('selection').first().toJSON();
                            input.val(attachment.url);
                            if(type === 'image') btn.next('.wcs-preview-img').attr('src', attachment.url).show();
                        }).open();
                    });
                });
            </script>
            <?php
        }

        private function render_repeater_row( $index, $pdf, $image, $title ) {
            ?>
            <div class="wcs-cert-row">
                <div class="wcs-row-inner">
                    <div class="wcs-field-group">
                        <label>Certificate Title (Optional)</label>
                        <input type="text" name="wcs_items[<?php echo $index; ?>][title]" value="<?php echo esc_attr($title); ?>" placeholder="e.g. Lab Result Batch #102">
                        <label style="margin-top:10px;">PDF Document</label>
                        <input type="text" name="wcs_items[<?php echo $index; ?>][pdf]" value="<?php echo esc_attr($pdf); ?>" placeholder="PDF URL">
                        <button type="button" class="button wcs-btn-upload" data-type="application/pdf">Upload PDF</button>
                    </div>
                    <div class="wcs-field-group">
                        <label>Cover Image (Optional)</label>
                        <input type="text" name="wcs_items[<?php echo $index; ?>][image]" value="<?php echo esc_attr($image); ?>" placeholder="Image URL">
                        <button type="button" class="button wcs-btn-upload" data-type="image">Upload Image</button>
                        <img src="<?php echo esc_attr($image); ?>" class="wcs-preview-img" style="<?php echo empty($image) ? 'display:none;' : ''; ?>">
                    </div>
                </div>
                <div class="wcs-actions">
                    <button type="button" class="button button-link-delete wcs-remove-row">Remove Row</button>
                </div>
            </div>
            <?php
        }

        public function save_meta_boxes( $post_id ) {
            if ( isset( $_POST['wcs_cert_nonce'] ) && wp_verify_nonce( $_POST['wcs_cert_nonce'], 'wcs_cert_save_action' ) ) {
                if ( isset( $_POST['wcs_items'] ) && is_array( $_POST['wcs_items'] ) ) {
                    $sanitized = array();
                    foreach ( $_POST['wcs_items'] as $item ) {
                        if ( ! empty( $item['pdf'] ) ) {
                            $sanitized[] = array(
                                'title' => sanitize_text_field( $item['title'] ),
                                'pdf'   => esc_url_raw( $item['pdf'] ),
                                'image' => esc_url_raw( $item['image'] )
                            );
                        }
                    }
                    update_post_meta( $post_id, '_wcs_cert_items', $sanitized );
                } else {
                    delete_post_meta( $post_id, '_wcs_cert_items' );
                }
            }
        }

        private function get_all_relevant_term_ids( $product_id ) {
            $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            if ( is_wp_error( $terms ) || empty( $terms ) ) return array();
            $all_ids = $terms;
            foreach ( $terms as $term_id ) {
                $ancestors = get_ancestors( $term_id, 'product_cat' );
                if ( ! empty( $ancestors ) ) $all_ids = array_merge( $all_ids, $ancestors );
            }
            return array_unique( $all_ids );
        }

        public function manage_product_tabs( $tabs ) {
            global $post;
            if ( ! is_product() ) return $tabs;

            $direct_items = get_post_meta( $post->ID, '_wcs_cert_items', true );
            $has_certs = ! empty( $direct_items );

            if ( ! $has_certs ) {
                $all_term_ids = $this->get_all_relevant_term_ids( $post->ID );
                if ( ! empty( $all_term_ids ) ) {
                    $certs = get_posts( array(
                        'post_type'  => 'wcs_certificate',
                        'tax_query'  => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $all_term_ids, 'include_children' => true ) ),
                        'fields'     => 'ids',
                        'posts_per_page' => 1
                    ) );
                    if ( ! empty( $certs ) ) $has_certs = true;
                }
            }

            if ( $has_certs ) {
                $tabs['wcs_certs_tab'] = array( 'title' => 'CERTIFICATES', 'priority' => 30, 'callback' => array( $this, 'render_frontend_tab' ) );
            }

            $hidden_tabs = get_option( 'wcs_hide_tabs', array() );
            if ( is_array( $hidden_tabs ) ) {
                foreach($hidden_tabs as $ht) {
                    if(isset($tabs[$ht])) unset($tabs[$ht]);
                }
                if ( in_array( 'brand', $hidden_tabs ) ) {
                    if ( isset( $tabs['brand_tab'] ) ) unset( $tabs['brand_tab'] ); 
                    if ( isset( $tabs['pwb_tab'] ) ) unset( $tabs['pwb_tab'] );
                    if ( isset( $tabs['mg_brand'] ) ) unset( $tabs['mg_brand'] );
                }
            }
            return $tabs;
        }

        public function render_frontend_tab() {
            global $post;
            $all_term_ids = $this->get_all_relevant_term_ids( $post->ID );
            $all_items = array();

            $direct = get_post_meta( $post->ID, '_wcs_cert_items', true );
            if ( ! empty( $direct ) ) $all_items = array_merge( $all_items, $direct );

            if ( ! empty( $all_term_ids ) ) {
                $query = new WP_Query( array(
                    'post_type'      => 'wcs_certificate',
                    'posts_per_page' => -1,
                    'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $all_term_ids, 'include_children' => true ) )
                ) );
                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        $items = get_post_meta( get_the_ID(), '_wcs_cert_items', true );
                        if ( ! empty( $items ) ) {
                            foreach ( $items as $item ) {
                                if ( empty($item['title']) ) $item['title'] = get_the_title();
                                $all_items[] = $item;
                            }
                        }
                    }
                    wp_reset_postdata();
                }
            }
            $this->render_certificate_list( $all_items );
        }

        public function render_gallery_shortcode() {
            $source = get_option( 'wcs_gallery_source', 'auto' );
            $all_items = array();
            $cats_found = array(); 

            if ( $source === 'auto' ) {
                global $wpdb;
                $products = $wpdb->get_results("
                    SELECT post_id, meta_value FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_wcs_cert_items' 
                    AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish')
                ");

                $processed = array();

                foreach ( $products as $prod ) {
                    $p_items = maybe_unserialize( $prod->meta_value );
                    if ( ! empty( $p_items ) && is_array( $p_items ) ) {
                        
                        $prod_cats = wp_get_post_terms( $prod->post_id, 'product_cat', array('fields' => 'all') );
                        $cat_ids = array();
                        foreach($prod_cats as $pc) {
                            $cat_ids[] = $pc->term_id;
                            $cats_found[$pc->term_id] = $pc->name;
                        }
                        $cat_str = implode(',', $cat_ids);
                        $prod_img = get_the_post_thumbnail_url( $prod->post_id, 'medium' ) ?: '';

                        foreach ( $p_items as $item ) {
                            $pdf = isset( $item['pdf'] ) ? trim( $item['pdf'] ) : '';
                            $title = isset( $item['title'] ) ? trim( $item['title'] ) : '';
                            if(empty($pdf)) continue;

                            $key = md5( $title . '|' . $pdf );

                            if ( isset( $processed[$key] ) ) {
                                $existing_cats = explode(',', $processed[$key]['cats']);
                                $new_cats = array_unique( array_merge( $existing_cats, $cat_ids ) );
                                $processed[$key]['cats'] = implode(',', $new_cats);
                            } else {
                                $item['cats'] = $cat_str;
                                if ( empty($item['image']) ) $item['image'] = $prod_img;
                                $processed[$key] = $item;
                            }
                        }
                    }
                }
                $all_items = array_values($processed);

            } else {
                $all_items = get_option( 'wcs_gallery_manual_items', array() );
            }

            ob_start();
            ?>
            <style>
                .wcs-coa-layout { display: flex; gap: 30px; font-family: "Outfit", sans-serif; margin-top: 30px; }
                .wcs-coa-sidebar { width: 250px; flex-shrink: 0; border-right: 1px solid #eee; padding-right: 20px; }
                /* Right Side Wrapper */
                .wcs-coa-content-wrapper { flex: 1; display: flex; flex-direction: column; }
                
                /* Grid */
                .wcs-coa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; align-content: start; }
                
                /* Sidebar Links */
                .wcs-cat-link { display: block; padding: 10px 15px; margin-bottom: 5px; color: #555; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; }
                .wcs-cat-link:hover, .wcs-cat-link.active { background: #f0f0f0; color: #000; font-weight: 700; }
                
                /* Cards */
                .wcs-coa-card { background: #fff; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; transition: 0.3s; display: flex; flex-direction: column; }
                .wcs-coa-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-color: #ccc; }
                .wcs-coa-img-wrap { height: 180px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #eee; overflow: hidden; }
                .wcs-coa-img { width: 100%; height: 100%; object-fit: cover; }
                .wcs-coa-placeholder { font-size: 40px; color: #ccc; }
                
                .wcs-coa-body { padding: 15px; flex: 1; display: flex; flex-direction: column; }
                .wcs-coa-title { font-size: 15px; font-weight: 700; margin: 0 0 10px; color: #222; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; min-height: 40px; }
                .wcs-coa-btn { margin-top: auto; display: block; width: 100%; text-align: center; padding: 10px; background: #222; color: #fff; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600; text-transform: uppercase; transition: 0.2s; cursor: pointer; border: none; }
                .wcs-coa-btn:hover { background: #444; color: #fff; }

                @media (max-width: 768px) {
                    .wcs-coa-layout { flex-direction: column; }
                    .wcs-coa-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; display: flex; overflow-x: auto; gap: 10px; padding-right: 0; }
                    .wcs-cat-link { white-space: nowrap; background: #f5f5f5; margin: 0; }
                    .wcs-coa-title-h2 { text-align: left !important; }
                }
            </style>
            
            <div class="wcs-coa-layout">
                <!-- Sidebar -->
                <div class="wcs-coa-sidebar">
                    <div class="wcs-cat-link active" onclick="wcsFilterCOA('all', this)">All Certificates</div>
                    <?php if(!empty($cats_found)): ksort($cats_found); foreach($cats_found as $cid => $cname): ?>
                        <div class="wcs-cat-link" onclick="wcsFilterCOA('<?php echo $cid; ?>', this)"><?php echo esc_html($cname); ?></div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Right Content -->
                <div class="wcs-coa-content-wrapper">
                    <h2 class="wcs-coa-title-h2" style="font-family: 'Outfit', sans-serif; font-weight: 700; margin: 0 0 20px 0; text-align: center;">Certificate of Analysis</h2>
                    
                    <!-- Grid -->
                    <div class="wcs-coa-grid">
                        <?php if ( ! empty( $all_items ) ) : foreach ( $all_items as $item ) : 
                            $pdf_url = isset($item['pdf']) ? $item['pdf'] : '';
                            if ( empty( $pdf_url ) ) continue;
                            
                            $display_title = !empty($item['title']) ? $item['title'] : 'Certificate';
                            $cats_attr = isset($item['cats']) ? $item['cats'] : '';
                            $img_url = isset($item['image']) ? $item['image'] : '';
                            $ext = pathinfo($pdf_url, PATHINFO_EXTENSION);
                            $type = in_array(strtolower($ext), ['jpg','jpeg','png','webp']) ? 'image' : 'pdf';
                        ?>
                            <div class="wcs-coa-card" data-cats="<?php echo esc_attr($cats_attr); ?>">
                                <div class="wcs-coa-img-wrap">
                                    <?php if(!empty($img_url)): ?>
                                        <img src="<?php echo esc_url($img_url); ?>" class="wcs-coa-img" loading="lazy">
                                    <?php else: ?>
                                        <div class="wcs-coa-placeholder">📄</div>
                                    <?php endif; ?>
                                </div>
                                <div class="wcs-coa-body">
                                    <h4 class="wcs-coa-title"><?php echo esc_html($display_title); ?></h4>
                                    <button class="wcs-coa-btn" onclick="openCertModal('<?php echo esc_url($pdf_url); ?>', '<?php echo $type; ?>', '<?php echo esc_attr($display_title); ?>')">
                                        View Certificate
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <p style="color:#777;">No certificates found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <script>
                function wcsFilterCOA(catId, btn) {
                    var links = document.querySelectorAll('.wcs-cat-link');
                    links.forEach(l => l.classList.remove('active'));
                    btn.classList.add('active');

                    var cards = document.querySelectorAll('.wcs-coa-card');
                    cards.forEach(card => {
                        var cardCats = card.getAttribute('data-cats').split(',');
                        if(catId === 'all' || cardCats.includes(catId)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }
            </script>
            <?php
            
            if ( ! has_action( 'wp_footer', array( $this, 'render_modal_markup' ) ) ) {
                add_action( 'wp_footer', array( $this, 'render_modal_markup' ) );
            }

            return ob_get_clean();
        }

        public function render_modal_markup() {
            ?>
            <style>
                #wcsCertModal.wcs-modal-overlay { 
                    display: none; 
                    position: fixed !important; 
                    top: 0 !important; 
                    left: 0 !important; 
                    width: 100vw !important; 
                    height: 100vh !important; 
                    background: rgba(0,0,0,0.85); 
                    z-index: 2147483647 !important; 
                    backdrop-filter: blur(5px); 
                    align-items: center; 
                    justify-content: center; 
                    margin: 0 !important;
                    padding: 0 !important;
                    inset: 0 !important;
                }
                #wcsCertModal .wcs-modal-content {
                    position: relative;
                    width: 90%; 
                    max-width: 1000px; 
                    height: 85vh; 
                    background: #fff; 
                    border-radius: 8px; 
                    overflow: hidden; 
                    box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
                    display: flex; 
                    flex-direction: column;
                    margin: auto; 
                }
                #wcsModalTitle { font-weight:700; font-size:16px; }
                .wcs-modal-header { padding: 15px 20px; background: #222; color: #fff; display: flex; justify-content: space-between; align-items: center; }
                .wcs-modal-close { cursor: pointer; font-size: 24px; line-height: 1; transition: 0.2s; }
                .wcs-modal-close:hover { color: #ff4444; }
                .wcs-modal-body { flex: 1; background: #e5e5e5; position: relative; display: flex; align-items: center; justify-content: center; }
                .wcs-modal-frame { width: 100%; height: 100%; border: none; }
                .wcs-modal-img { max-width: 100%; max-height: 100%; object-fit: contain; }
            </style>
            <div id="wcsCertModal" class="wcs-modal-overlay" onclick="closeCertModal(event)">
                <div class="wcs-modal-content" onclick="event.stopPropagation()">
                    <div class="wcs-modal-header">
                        <span id="wcsModalTitle">Certificate View</span>
                        <span class="wcs-modal-close" onclick="closeCertModal()">&times;</span>
                    </div>
                    <div class="wcs-modal-body" id="wcsModalBody"></div>
                </div>
            </div>
            <script>
                if (typeof openCertModal !== 'function') {
                    window.openCertModal = function(url, type, title) {
                        var modal = document.getElementById("wcsCertModal");
                        var titleSpan = document.getElementById("wcsModalTitle");
                        var body = document.getElementById("wcsModalBody");
                        
                        if(!modal || !body) return;

                        if (modal.parentNode !== document.body) {
                            document.body.appendChild(modal);
                        }

                        titleSpan.innerText = title;
                        body.innerHTML = "";
                        
                        if(type === "pdf") {
                            var iframe = document.createElement("iframe");
                            iframe.className = "wcs-modal-frame";
                            iframe.src = url; 
                            body.appendChild(iframe);
                        } else {
                            var img = document.createElement("img");
                            img.className = "wcs-modal-img";
                            img.src = url;
                            body.appendChild(img);
                        }
                        modal.style.display = "flex";
                        document.body.style.overflow = "hidden";
                    };
                    window.closeCertModal = function() {
                        var modal = document.getElementById("wcsCertModal");
                        if(modal) {
                            modal.style.display = "none";
                            document.getElementById("wcsModalBody").innerHTML = "";
                            document.body.style.overflow = "auto";
                        }
                    };
                }
            </script>
            <?php
        }

        public function enqueue_admin_scripts() {
            wp_enqueue_media();
        }

        private function render_certificate_list( $all_items ) {
             // For Frontend Tab compatibility - uses shortcode layout logic inside shortcode
             // This can be kept simple for the Tab
             if ( empty( $all_items ) ) return;
             ?>
             <div class="wcs-certs-wrapper">
                 <div class="wcs-cert-list">
                     <?php foreach ( $all_items as $item ) : 
                         $pdf_url = isset($item['pdf']) ? $item['pdf'] : '';
                         if ( empty( $pdf_url ) ) continue;
                         $display_title = !empty($item['title']) ? $item['title'] : 'Certificate';
                         $ext = pathinfo($pdf_url, PATHINFO_EXTENSION);
                         $type = in_array(strtolower($ext), ['jpg','jpeg','png','webp']) ? 'image' : 'pdf';
                     ?>
                         <div class="wcs-cert-item" style="display: flex; align-items: center; justify-content: space-between; background: #fcfcfc; border: 1px solid #eee; padding: 15px 20px; border-radius: 8px; margin-bottom:10px;">
                             <div class="wcs-cert-left" style="display: flex; align-items: center; gap: 15px;">
                                 <div class="wcs-icon-box" style="width: 40px; height: 40px; background: #eee; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📄</div>
                                 <h4 style="margin:0;"><?php echo esc_html($display_title); ?></h4>
                             </div>
                             <button style="padding: 8px 15px; background: #222; color: #fff; border:none; border-radius: 4px; cursor: pointer;" onclick="openCertModal('<?php echo esc_url($pdf_url); ?>', '<?php echo $type; ?>', '<?php echo esc_attr($display_title); ?>')">VIEW</button>
                         </div>
                     <?php endforeach; ?>
                 </div>
             </div>
             <?php
             // Ensure Modal Markup is present for Tab too
             if ( ! has_action( 'wp_footer', array( $this, 'render_modal_markup' ) ) ) {
                 add_action( 'wp_footer', array( $this, 'render_modal_markup' ) );
             }
        }
    }

    new WCS_Certificates_Standard();
}
