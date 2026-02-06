<?php

/**
 * The core plugin class.
 */
class WCS_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		if ( defined( 'WCS_VERSION' ) ) {
			$this->version = WCS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wcs-pro-suite';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		// 1. The Loader (Orchestrator)
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wcs-loader.php';
        
        // 2. Modules
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/wholesale/class-wcs-wholesale.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/shop-filter/class-wcs-shop-filter.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/hero-slider/class-wcs-hero-slider.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/cart-notices/class-wcs-cart-notices.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/trending-products/class-wcs-trending-products.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/mega-menu/class-wcs-mega-menu.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/certificates/class-wcs-certificates.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/modules/bulk-importer/class-wcs-bulk-importer.php';

        // 3. Admin logic
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wcs-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wcs-cart-settings.php';

		$this->loader = new WCS_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new WCS_Admin( $this->get_plugin_name(), $this->get_version() );
        
        // Cart Admin (Independent)
        $plugin_cart_admin = new WCS_Cart_Settings( $this->get_plugin_name(), $this->get_version() );

		// Register settings page (Main)
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        
        // Register Cart Admin Page (Submenu of Main)
        $this->loader->add_action( 'admin_menu', $plugin_cart_admin, 'add_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_cart_admin, 'register_settings' );

        // Certificates Admin
        $plugin_certs = new WCS_Certificates( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'init', $plugin_certs, 'register_resources' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_certs, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_certs, 'save_meta_boxes' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_certs, 'enqueue_admin_scripts' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
        // Wholesale Hooks (Real Price Logic)
		$plugin_wholesale = new WCS_Wholesale( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_filter( 'woocommerce_product_get_price', $plugin_wholesale, 'apply_wholesale_price', 10, 2 );
        $this->loader->add_filter( 'woocommerce_product_get_sale_price', $plugin_wholesale, 'apply_wholesale_price', 10, 2 );
        $this->loader->add_filter( 'woocommerce_product_variation_get_price', $plugin_wholesale, 'apply_wholesale_price', 10, 2 );
        $this->loader->add_filter( 'woocommerce_product_variation_get_sale_price', $plugin_wholesale, 'apply_wholesale_price', 10, 2 );

        // Shop Filter Hooks
        $plugin_filter = new WCS_Shop_Filter( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_shortcode( 'woo_custom_suite_shop', $plugin_filter, 'render_shop_shortcode' );

        // Hero Slider Hooks
        $plugin_hero = new WCS_Hero_Slider( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_shortcode( 'wcs_hero_slider_3d', $plugin_hero, 'render_hero_slider' );
        
        // Cart Notices
        $plugin_cart_notices = new WCS_Cart_Notices( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_shortcode( 'wcs_free_shipping_progress', $plugin_cart_notices, 'render_cart_notice' );

        // Trending Products
        $plugin_trending = new WCS_Trending_Products( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_shortcode( 'wcs_trending_products', $plugin_trending, 'render_trending_products' );

        // Mega Menu
        $plugin_mega_menu = new WCS_Mega_Menu( $this->get_plugin_name(), $this->get_version() );
        add_shortcode( 'wcs_mega_menu', array( $plugin_mega_menu, 'render_mega_menu' ) );

        // Secure Logout Tools
        $this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_scripts' );
        $this->loader->add_shortcode( 'wcs_logout_url', $this, 'get_logout_url' );

        // Certificates Tab
        $plugin_certs = new WCS_Certificates( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_filter( 'woocommerce_product_tabs', $plugin_certs, 'add_certificates_tab' );
	}

    /**
     * Enqueue scripts and localize logout URL
     */
    public function enqueue_public_scripts() {
        wp_register_script( 'wcs-public-js', false, array( 'jquery' ) );
        wp_enqueue_script( 'wcs-public-js' );
        
        wp_localize_script( 'wcs-public-js', 'wcs_shop_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ) );

        $logout_url = wp_logout_url( home_url( '/' ) );
        
        // This script finds any element with class 'wcs-logout-link' and sets the correct URL
        $script = "
            document.addEventListener('DOMContentLoaded', function() {
                var logoutLinks = document.querySelectorAll('.wcs-logout-link');
                logoutLinks.forEach(function(link) {
                    link.setAttribute('href', '" . esc_url_raw($logout_url) . "');
                });
            });
        ";
        wp_add_inline_script( 'wcs-public-js', $script );
    }

    /**
     * Get secure logout URL redirecting to /log-in/
     * 
     * @return string
     */
    public function get_logout_url() {
        return wp_logout_url( home_url( '/' ) );
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

}
