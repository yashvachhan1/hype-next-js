<?php

class WCS_Trending_Products {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Render the Trending Products Shortcode
     * [wcs_trending_products category="trending" limit="4"]
     */
    public function render_trending_products( $atts ) {
        $atts = shortcode_atts( array(
            'category' => 'trending-products',
            'limit'    => 4,
            'title'    => 'TRENDING PRODUCTS'
        ), $atts, 'wcs_trending_products' );

        // Query Products
        $args = array(
            'limit'    => $atts['limit'],
            'status'   => 'publish',
            'category' => array( $atts['category'] ),
            'orderby'  => 'date',
            'order'    => 'DESC',
        );
        $products = wc_get_products( $args );

        if ( empty( $products ) ) {
            return '<!-- No trending products found -->';
        }

        ob_start();
        $this->render_styles();
        ?>
        <div class="wcs-trending-section">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h2 class="wcs-trending-title"><?php echo esc_html( $atts['title'] ); ?></h2>
            <?php endif; ?>

            <div class="wcs-trending-grid">
                <?php foreach ( $products as $product ) : 
                    $product_id = $product->get_id();
                    $title = $product->get_name();
                    $image_id = $product->get_image_id();
                    $image_url = wp_get_attachment_image_url( $image_id, 'large' );
                    $price_html = $product->get_price_html();
                    $product_url = get_permalink( $product_id );
                    
                    // Filter brands (assuming it's a taxonomy 'product_brand' or similar)
                    $brands = wp_get_post_terms( $product_id, 'pa_brand' ); // Common WooCommerce brand taxonomy
                    if ( empty($brands) ) {
                        $brands = wp_get_post_terms( $product_id, 'product_brand' );
                    }
                    $brand_name = ! empty( $brands ) ? $brands[0]->name : '';
                ?>
                    <div class="wcs-trending-card">
                        <div class="wcs-trending-img-wrapper">
                            <a href="<?php echo esc_url( $product_url ); ?>">
                                <?php if ( $image_url ) : ?>
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="wcs-trending-img">
                                <?php else : ?>
                                    <?php echo $product->get_image( 'large' ); ?>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="wcs-trending-info">
                            <?php if ( $brand_name ) : ?>
                                <span class="wcs-trending-brand"><?php echo esc_html( $brand_name ); ?></span>
                            <?php endif; ?>
                            <h3 class="wcs-trending-product-title">
                                <a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $title ); ?></a>
                            </h3>
                            <div class="wcs-trending-price">
                                <?php if ( is_user_logged_in() ) : ?>
                                    <?php echo $price_html; ?>
                                <?php else : ?>
                                    <p class="wcs-login-to-view">Please <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">log in</a> to view the price.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Internal Styles for the Trending Section
     * Kept separate and namespaced
     */
    private function render_styles() {
        ?>
        <style>
            .wcs-trending-section {
                max-width: 1400px;
                margin: 40px auto;
                padding: 0 20px;
                font-family: 'Inter', sans-serif;
            }

            .wcs-trending-title {
                text-align: center;
                font-size: 28px;
                font-weight: 700;
                color: #333;
                margin-bottom: 30px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .wcs-trending-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 25px;
            }

            @media (max-width: 1024px) {
                .wcs-trending-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 600px) {
                .wcs-trending-grid {
                    grid-template-columns: 1fr;
                }
            }

            .wcs-trending-card {
                background: #fff;
                border: 1px solid #f0f0f0;
                border-radius: 12px;
                overflow: hidden;
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            }

            .wcs-trending-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 30px rgba(161, 1, 246, 0.12);
                border-color: #A101F6;
            }

            .wcs-trending-img-wrapper {
                position: relative;
                aspect-ratio: 1 / 1;
                overflow: hidden;
                background: #f9f9f9;
            }

            .wcs-trending-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }

            .wcs-trending-card:hover .wcs-trending-img {
                transform: scale(1.08);
            }

            .wcs-trending-info {
                padding: 20px;
                text-align: left;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
            }

            .wcs-trending-brand {
                font-size: 12px;
                color: #999;
                text-transform: uppercase;
                margin-bottom: 8px;
                display: block;
            }

            .wcs-trending-product-title {
                font-size: 15px;
                font-weight: 600;
                margin: 0 0 12px;
                line-height: 1.4;
                height: 42px;
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .wcs-trending-product-title a {
                color: #333;
                text-decoration: none;
                transition: color 0.2s;
            }

            .wcs-trending-product-title a:hover {
                color: #A101F6;
            }

            .wcs-trending-price {
                margin-top: auto;
                font-size: 16px;
                font-weight: 700;
                color: #A101F6;
            }

            .wcs-login-to-view {
                font-size: 12px;
                color: #666;
                font-weight: 400;
                margin: 0;
            }

            .wcs-login-to-view a {
                color: #ff4757;
                font-weight: 700;
                text-decoration: none;
            }

            .wcs-login-to-view a:hover {
                text-decoration: underline;
            }

            /* WooCommerce standard button styling compatibility */
            .wcs-trending-price ins {
                text-decoration: none;
            }
            .wcs-trending-price del {
                font-size: 0.8em;
                color: #999;
                font-weight: 400;
                margin-right: 5px;
            }
        </style>
        <?php
    }
}
