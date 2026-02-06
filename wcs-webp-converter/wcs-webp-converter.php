<?php
/**
 * Plugin Name: WCS Image Booster
 * Description: Auto-convert images to WebP, track size savings, and manage bulk optimizations.
 * Version: 2.0.0
 * Author: WCS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCS_Image_Booster {

    public function __construct() {
        // Menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Upload Handling
        add_filter( 'wp_handle_upload', array( $this, 'handle_upload_conversion' ) );
        
        // Bulk Actions
        add_filter( 'bulk_actions-upload', array( $this, 'register_bulk_action' ) );
        add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_bulk_action' ), 10, 3 );
        
        // Styles
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Image Booster',
            'Img Booster',
            'manage_options',
            'wcs-img-booster',
            array( $this, 'dashboard_page' ),
            'dashicons-performance',
            65
        );
    }

    public function register_settings() {
        register_setting( 'wcs_booster_group', 'wcs_booster_auto_convert' );
        register_setting( 'wcs_booster_group', 'wcs_booster_quality' );
    }

    public function admin_styles( $hook ) {
        if ( $hook != 'toplevel_page_wcs-img-booster' ) return;
        echo '<style>
            .wcs-stat-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 5px; text-align: center; flex: 1; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .wcs-stat-number { font-size: 32px; font-weight: bold; color: #6244f5; margin-bottom: 5px; display: block; }
            .wcs-stat-label { color: #50575e; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
            .wcs-dashboard-grid { display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
            .wcs-progress-wrap { background: #e0e0e0; border-radius: 10px; height: 20px; width: 100%; margin: 20px 0; overflow: hidden; }
            .wcs-progress-bar { background: #6244f5; height: 100%; text-align: center; font-size: 12px; line-height: 20px; color: #fff; transition: width 0.5s; }
            .wcs-saving-badge { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 20px; font-weight: bold; display: inline-block; margin-top: 10px; }
        </style>';
    }

    public function dashboard_page() {
        // Collect Stats
        $args = array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'post_mime_type' => 'image' );
        $all_images = get_posts( $args );
        
        $total_count = count( $all_images );
        $converted_count = 0;
        $total_original_size = 0;
        $total_compressed_size = 0;

        foreach ( $all_images as $img ) {
            $mime = get_post_mime_type( $img->ID );
            if ( $mime === 'image/webp' ) {
                $converted_count++;
                
                // Retrieve sizes if stored
                $orig = get_post_meta( $img->ID, '_wcs_original_size', true );
                $comp = get_post_meta( $img->ID, '_wcs_compressed_size', true );
                
                // Fallback if not stored (estimate or skip)
                if ( $comp ) {
                    $total_compressed_size += intval($comp);
                    if ( $orig ) $total_original_size += intval($orig);
                } else {
                    // It's a webp but we didn't track it, assume it counts as connected
                    $filesize = filesize( get_attached_file( $img->ID ) );
                    $total_compressed_size += $filesize;
                    // Estimate original was 30% larger if unknown
                    $total_original_size += ($filesize * 1.3); 
                }
            } else {
                // Not converted yet
                $path = get_attached_file( $img->ID );
                if(file_exists($path)) {
                    $total_original_size += filesize( $path );
                }
            }
        }
        
        $remaining = $total_count - $converted_count;
        $saved_bytes = max( 0, $total_original_size - $converted_count > 0 ? $total_compressed_size + ($remaining > 0 ? ($total_original_size/$total_count)*$remaining : 0 ) : $total_original_size ); // Rough logic, simplified below:
        
        // Better simplified math:
        // Total Original Size = (Sum of all current files that are NOT webp) + (Stored Original Size of WebP files)
        // Saved = (Stored Original Size of WebP files) - (Stored Compressed Size of WebP files)
        
        $real_saved_bytes = 0;
        foreach ( $all_images as $img ) {
            $orig = get_post_meta( $img->ID, '_wcs_original_size', true );
            $comp = get_post_meta( $img->ID, '_wcs_compressed_size', true );
            if($orig && $comp) {
                $real_saved_bytes += ($orig - $comp);
            }
        }

        $percentage = $total_count > 0 ? round( ($converted_count / $total_count) * 100 ) : 0;
        $saved_mb = number_format( $real_saved_bytes / 1024 / 1024, 2 );

        ?>
        <div class="wrap">
            <h1>Image Booster Dashboard</h1>
            
            <div class="wcs-dashboard-grid">
                <div class="wcs-stat-card">
                    <span class="wcs-stat-number"><?php echo $converted_count; ?> / <?php echo $total_count; ?></span>
                    <span class="wcs-stat-label">Images Optimized</span>
                </div>
                <div class="wcs-stat-card">
                    <span class="wcs-stat-number"><?php echo $remaining; ?></span>
                    <span class="wcs-stat-label">Remaining</span>
                </div>
                <div class="wcs-stat-card">
                    <span class="wcs-stat-number"><?php echo $saved_mb; ?> MB</span>
                    <span class="wcs-stat-label">Total Space Saved</span>
                    <div class="wcs-saving-badge">🚀 High Performance</div>
                </div>
            </div>

            <div class="wcs-progress-wrap">
                <div class="wcs-progress-bar" style="width: <?php echo $percentage; ?>%"><?php echo $percentage; ?>% Optimized</div>
            </div>

            <h2>Run Optimizer</h2>
            <p>To optimize remaining images, go to <strong>Media Library (List View)</strong> and use Bulk Actions.</p>
            <a href="<?php echo admin_url( 'upload.php?mode=list' ); ?>" class="button button-primary button-large">Go to Media Library</a>

            <hr style="margin: 30px 0;">

            <h2>Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'wcs_booster_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto-Optimize New Uploads</th>
                        <td>
                            <label><input type="checkbox" name="wcs_booster_auto_convert" value="1" <?php checked( get_option( 'wcs_booster_auto_convert', 1 ), 1 ); ?>> Enable</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Quality (1-100)</th>
                        <td>
                            <input type="number" name="wcs_booster_quality" value="<?php echo esc_attr( get_option( 'wcs_booster_quality', 85 ) ); ?>" min="1" max="100">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // --- CONVERSION LOGIC ---

    public function handle_upload_conversion( $file_info ) {
        if ( ! get_option( 'wcs_booster_auto_convert', 1 ) ) return $file_info;

        if ( isset( $file_info['type'] ) && strpos( $file_info['type'], 'image' ) !== false && $file_info['type'] !== 'image/webp' ) {
            $file_path = $file_info['file'];
            $file_size = filesize( $file_path ); // Original Size
            
            $webp_path = $this->convert_to_webp( $file_path );
            
            if ( $webp_path ) {
                $new_size = filesize( $webp_path );
                
                // Store stats temporarily in a global or similar? No, can't easily attach to post ID yet as it doesn't exist.
                // For upload filter, we can't attach meta yet. 
                // We'll hook into 'add_attachment' later to save meta if needed, but 'wp_handle_upload' is early.
                // Alternative: Just convert. The stats might be missed for auto-uploads unless we hook `wp_generate_attachment_metadata`.
                
                $file_info['file'] = $webp_path;
                $file_info['url']  = preg_replace( '/\.[^.]+$/', '.webp', $file_info['url'] );
                $file_info['type'] = 'image/webp';
                
                // Hacky pass of sizes to the next step via global or session is risky. 
                // Let's rely on Bulk Actions for perfect stats, and for Auto-upload just do the job.
            }
        }
        return $file_info;
    }

    /**
     * Hook to save stats for NEW uploads (Auto)
     * Using wp_generate_attachment_metadata to capture the fact it is WebP and maybe we can guess original was larger?
     * Actually difficult to get "Before" size on direct upload conversion since we replaced the file.
     * We will skip detailed "saved" stats for auto-uploads in this simple version, or assume 30% saving.
     */

    private function convert_to_webp( $file_path ) {
        $editor = wp_get_image_editor( $file_path );
        if ( is_wp_error( $editor ) ) return false;
        
        $editor->set_quality( get_option( 'wcs_booster_quality', 85 ) );
        $webp_path = preg_replace( '/\.[^.]+$/', '.webp', $file_path );
        $result = $editor->save( $webp_path, 'image/webp' );
        
        if ( is_wp_error( $result ) ) return false;
        return $webp_path;
    }

    // --- BULK ACTION LOGIC ---

    public function register_bulk_action( $bulk_actions ) {
        $bulk_actions['wcs_boost_opt'] = 'Boost Optimize (WebP)';
        return $bulk_actions;
    }

    public function handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
        if ( $doaction !== 'wcs_boost_opt' ) return $redirect_to;

        $count = 0;
        foreach ( $post_ids as $post_id ) {
            $file_path = get_attached_file( $post_id );
            if ( ! file_exists( $file_path ) ) continue;

            $mime = get_post_mime_type( $post_id );
            if ( strpos( $mime, 'image' ) === false || $mime === 'image/webp' ) continue;

            $original_size = filesize( $file_path ); // Bytes
            $webp_path = $this->convert_to_webp( $file_path );

            if ( $webp_path ) {
                $new_size = filesize( $webp_path );
                
                // Save Stats
                update_post_meta( $post_id, '_wcs_original_size', $original_size );
                update_post_meta( $post_id, '_wcs_compressed_size', $new_size );

                update_attached_file( $post_id, $webp_path );
                wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $webp_path ) );
                
                wp_update_post( array( 'ID' => $post_id, 'post_mime_type' => 'image/webp' ) );
                
                // unlink($file_path); // Optional: Delete original
                $count++;
            }
        }
        return add_query_arg( 'wcs_boosted', $count, $redirect_to );
    }
}

new WCS_Image_Booster();
