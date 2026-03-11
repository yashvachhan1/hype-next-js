<?php

class WCS_Hero_Slider {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    /**
     * Render the 3D Hero Slider Shortcode
     * Usage: [wcs_hero_slider_3d limit="9"]
     */
	public function render_hero_slider( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 9,
        ), $atts );

        ob_start();

        // Query WooCommerce Products
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $atts['limit'],
            'status'         => 'publish',
            // Get Featured products first, or just recent
            'orderby'        => 'date', 
            'order'          => 'DESC',
        );
        $loop = new WP_Query( $args );

        if ( ! $loop->have_posts() ) {
            return '<p>No products found for slider.</p>';
        }
        ?>

        <!-- STYLES (Scoped for this slider) -->
        <style>
            .wcs-hero-wrapper {
                width: 100%;
                height: 600px;
                background: #ffffff;
                background-image: 
                    radial-gradient(at 0% 0%, hsla(253,16%,7%,0) 0, hsla(253,16%,7%,0) 50%), 
                    radial-gradient(at 50% 0%, hsla(225,39%,30%,0) 0, hsla(225,39%,30%,0) 50%), 
                    radial-gradient(at 100% 0%, hsla(339,49%,30%,0) 0, hsla(339,49%,30%,0) 50%);
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                font-family: 'Outfit', sans-serif;
            }
            .bg-blur {
                position: absolute;
                width: 400px;
                height: 400px;
                background: rgba(161, 1, 246, 0.05);
                filter: blur(80px);
                border-radius: 50%;
                top: -100px; left: -100px; z-index: 0;
            }
            .swiper {
                width: 100%;
                padding-top: 50px; padding-bottom: 50px;
                perspective: 1000px;
            }
            /* SMOOTH LINEAR MOVEMENT */
            .swiper-wrapper {
                transition-timing-function: linear;
            }
            .swiper-slide {
                background-position: center;
                background-size: cover;
                width: 600px;
                height: 400px;
                border-radius: 20px;
                background: #fff;
                box-shadow: 0 20px 50px rgba(0,0,0,0.1);
                transition: all 0.5s ease;
                overflow: hidden;
                border: 1px solid rgba(0,0,0,0.05);
            }
            .banner-inner {
                width: 100%; height: 100%;
                display: flex; align-items: center; justify-content: space-between;
                background: #fdfdfd; position: relative;
            }
            .banner-text {
                width: 50%; padding: 40px; z-index: 2;
            }
            .tagine {
                display: inline-block; background: #f0f0f0; color: #333;
                padding: 5px 12px; border-radius: 20px;
                font-size: 12px; font-weight: 700; text-transform: uppercase;
                margin-bottom: 15px;
            }
            .wcs-hero-title {
                font-size: 38px; line-height: 1.1; color: #1a1a1a;
                margin: 0 0 15px 0; font-weight: 800;
            }
            .highlight-text { color: #A101F6; }
            .wcs-hero-desc {
                color: #666; margin-bottom: 25px; font-size: 16px;
                display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
            }
            .shop-btn {
                background: #1a1a1a; color: white; padding: 12px 30px;
                border-radius: 8px; text-decoration: none; font-weight: 700;
                display: inline-block; transition: all 0.3s;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            .shop-btn:hover {
                transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.2);
                background: #A101F6;
            }
            .banner-img-wrapper {
                width: 55%; height: 100%; position: absolute;
                right: 0; bottom: 0;
                display: flex; align-items: center; justify-content: center;
            }
            .banner-img {
                max-width: 120%; height: auto;
                transform: rotate(-10deg) translateX(20px);
                filter: drop-shadow(-20px 20px 30px rgba(0,0,0,0.15));
                transition: transform 0.5s;
                border-radius: 20px;
            }
            .swiper-slide-active .banner-img {
                transform: rotate(0deg) scale(1.05);
            }
            .swiper-pagination-bullet-active { width: 30px; border-radius: 5px; background: #A101F6; }
        </style>

        <!-- EXTERNAL ASSETS (If not loaded) -->
        <?php if ( !wp_script_is('swiper-js', 'enqueued') ) : ?>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
            <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
        <?php endif; ?>

        <div class="wcs-hero-wrapper">
            <div class="bg-blur"></div>
            
            <div class="swiper myDynamicHeroSwiper">
                <div class="swiper-wrapper">
                    
                    <?php while ( $loop->have_posts() ) : $loop->the_post(); 
                        global $product;
                        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'large' );
                        $img_url = $image ? $image[0] : wc_placeholder_img_src();
                        
                        // Extract simple short description or use fallback
                        $desc = has_excerpt() ? get_the_excerpt() : 'Experience premium quality with our latest collection.';
                        $title = get_the_title();
                    ?>
                    
                    <div class="swiper-slide">
                        <div class="banner-inner">
                            <div class="banner-text">
                                <span class="tagine">Featured</span>
                                <h2 class="wcs-hero-title"><?php echo esc_html($title); ?></h2>
                                <p class="wcs-hero-desc"><?php echo wp_trim_words( $desc, 15 ); ?></p>
                                <a href="<?php echo get_permalink(); ?>" class="shop-btn">View Product</a>
                            </div>
                            <div class="banner-img-wrapper">
                                <img src="<?php echo esc_url($img_url); ?>" class="banner-img" alt="<?php echo esc_attr($title); ?>">
                            </div>
                        </div>
                    </div>

                    <?php endwhile; wp_reset_postdata(); ?>

                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                var swiper = new Swiper(".myDynamicHeroSwiper", {
                    effect: "coverflow",
                    grabCursor: true,
                    centeredSlides: true,
                    slidesPerView: "auto",
                    initialSlide: 1, 
                    coverflowEffect: {
                        rotate: 0,
                        stretch: 0,
                        depth: 200, 
                        modifier: 1,
                        slideShadows: true,
                    },
                    loop: true,
                    speed: 5000, 
                    autoplay: {
                        delay: 0, 
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true, 
                    },
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true,
                    }
                });
            });
        </script>

        <?php
        return ob_get_clean();
	}

}
