<?php
/**
 * Module: Bulk Product Importer (Minimalist Edition)
 * Description: 1 Row = 1 Product. Ultimate simplicity: Type, Name, Price, Attributes only.
 */

if ( ! class_exists( 'WCS_Standalone_Bulk_Importer' ) ) {

    class WCS_Standalone_Bulk_Importer {

        public function __construct() {
            add_action( 'admin_menu', array( $this, 'add_menu' ) );
            add_action( 'admin_init', array( $this, 'handle_upload' ) );
            add_action( 'admin_init', array( $this, 'handle_sample_download' ) );
        }

        public function add_menu() {
            add_menu_page( 'Bulk Importer', 'Bulk Importer', 'manage_options', 'wcs-importer-v5', array( $this, 'render_page' ), 'dashicons-upload', 58 );
        }

        public function render_page() {
            ?>
            <div class="wrap">
                <h1>Bulk Product Importer (Minimalist) 📦</h1>
                <div style="background:#fff; padding:30px; border-radius:12px; border:1px solid #ddd; margin-top:20px; max-width:850px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h3 style="margin-top:0;">1. Download Minimal Template</h3>
                    <p>Mene Description column bhi hata diya hai. Ab sirf zaroori details (Name/Price/Attributes) chahiye:</p>
                    <a href="<?php echo admin_url('admin.php?page=wcs-importer-v5&action=wcs_sample'); ?>" class="button button-large button-secondary">Download Minimal Template 📄</a>
                    
                    <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">

                    <h3>2. Upload Your CSV</h3>
                    <p>Sirf Name aur Attributes provide karein, system variations apne aap handle kar lega.</p>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'wcs_v5_action', 'wcs_v5_nonce' ); ?>
                        <input type="file" name="wcs_csv" accept=".csv" required style="margin-bottom:20px; display:block;">
                        <input type="submit" name="wcs_start" class="button button-primary button-large" value="Start Minimal Import 🚀">
                    </form>

                    <?php if ( isset( $_GET['wcs_msg'] ) ) : ?>
                        <div style="margin-top:25px; padding:15px; background:#f0fbff; border-left:5px solid #007bff; border-radius:4px;">
                            <strong>Report:</strong><br><?php echo nl2br( esc_html( urldecode( $_GET['wcs_msg'] ) ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }

        public function handle_sample_download() {
            if ( isset( $_GET['action'] ) && $_GET['action'] === 'wcs_sample' && is_admin() ) {
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=wcs_minimal_template.csv');
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); 
                fputcsv($out, array('Type', 'Name', 'Price', 'Attributes'));
                
                fputcsv($out, array(
                    'variable', 
                    'Ultra Simple Vape', 
                    '35', 
                    'Flavor:Mint,Mango|Strength:3mg,6mg', 
                ));
                
                fclose($out); exit;
            }
        }

        public function handle_upload() {
            if ( ! isset( $_POST['wcs_start'] ) || ! check_admin_referer( 'wcs_v5_action', 'wcs_v5_nonce' ) ) return;
            if ( ! empty( $_FILES['wcs_csv']['tmp_name'] ) ) {
                @set_time_limit(0);
                $handle = fopen( $_FILES['wcs_csv']['tmp_name'], "r" );
                $raw_h = fgetcsv( $handle );
                if (!$raw_h) return;
                $raw_h[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $raw_h[0]);
                $header = array_map('trim', $raw_h);
                
                $success_count = 0; $errs = array();
                while ( ( $row = fgetcsv( $handle ) ) !== FALSE ) {
                    $row = array_map('trim', $row);
                    if(count($row) < count($header)) $row = array_pad($row, count($header), '');
                    $data = @array_combine( $header, $row );
                    if(!$data) continue;
                    
                    $res = $this->process_autogen_row( $data );
                    if($res === true) $success_count++;
                    else $errs[] = $res;
                }
                fclose( $handle );
                
                $msg = "✅ Success: $success_count products processed (Minimal Mode).";
                if(!empty($errs)) $msg .= "\n⚠️ Notes: " . implode(" | ", array_slice($errs, 0, 3));
                wp_redirect( admin_url( 'admin.php?page=wcs-importer-v5&wcs_msg=' . urlencode($msg) ) );
                exit;
            }
        }

        private function process_autogen_row( $data ) {
            $type = strtolower( trim( $data['Type'] ) );
            $name = trim( $data['Name'] );
            if ( empty($name) ) return "Row skipped: Missing Name";

            global $wpdb;
            $id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'product' LIMIT 1", $name ) );
            
            // Default to variable unless 'single' or 'simple' is specified
            $is_simple = ( $type === 'single' || $type === 'simple' );
            $product = $id ? wc_get_product( $id ) : ( $is_simple ? new WC_Product_Simple() : new WC_Product_Variable() );

            $product->set_name( $name );
            $product->set_regular_price( trim( $data['Price'] ) );
            $product->set_status( 'publish' );

            $this->set_product_attributes( $product, $data, $type );

            $product_id = $product->save();

            if ( $type === 'variable' ) {
                $this->generate_variations( $product_id, $data );
            }

            return true;
        }

        private function set_product_attributes( $product, $data, $type ) {
            if ( empty( $data['Attributes'] ) ) return;
            $wc_attributes = array();
            foreach ( explode( '|', $data['Attributes'] ) as $bit ) {
                $parts = explode( ':', $bit );
                if( count($parts) == 2 ) {
                    $name = trim($parts[0]);
                    $options = array_map('trim', explode(',', $parts[1]));
                    $a = new WC_Product_Attribute();
                    $a->set_name( $name ); $a->set_options( $options );
                    $a->set_visible( true ); $a->set_variation( ($type === 'variable') );
                    $wc_attributes[ sanitize_title($name) ] = $a;
                }
            }
            $product->set_attributes( $wc_attributes );
        }

        private function generate_variations( $parent_id, $data ) {
            $attr_raw = trim($data['Attributes']);
            if ( empty($attr_raw) ) return;

            $attr_data = array();
            foreach ( explode( '|', $attr_raw ) as $bit ) {
                $pts = explode( ':', $bit );
                if(count($pts) == 2) $attr_data[ sanitize_title(trim($pts[0])) ] = array_map('trim', explode(',', $pts[1]));
            }

            $slugs = array_keys($attr_data);
            $values = array_values($attr_data);
            $combinations = array(array());

            foreach ($values as $index => $options) {
                $new_combinations = array();
                foreach ($combinations as $combination) {
                    foreach ($options as $option) {
                        $new_combinations[] = array_merge($combination, array($slugs[$index] => $option));
                    }
                }
                $combinations = $new_combinations;
            }

            $parent = wc_get_product($parent_id);
            $existing_variation_ids = $parent->get_children();

            foreach ( $combinations as $combo ) {
                $v_id = 0;
                $v_attrs = array();
                foreach($combo as $slug => $val) { $v_attrs['attribute_'.$slug] = sanitize_title($val); }

                foreach ($existing_variation_ids as $evid) {
                    $match = true;
                    foreach ($v_attrs as $k => $v) {
                        if (get_post_meta($evid, $k, true) !== $v) {
                            $match = false; break;
                        }
                    }
                    if ($match) { $v_id = $evid; break; }
                }

                $variation = $v_id ? new WC_Product_Variation($v_id) : new WC_Product_Variation();
                $variation->set_parent_id($parent_id);
                $variation->set_regular_price(trim($data['Price']));
                $variation->set_status('publish');
                $variation->set_attributes($v_attrs);
                
                $var_id = $variation->save();
                foreach($v_attrs as $k => $v) { update_post_meta($var_id, $k, $v); }
            }
            WC_Product_Variable::sync( $parent_id );
            wc_delete_product_transients( $parent_id );
        }
    }
    new WCS_Standalone_Bulk_Importer();
}