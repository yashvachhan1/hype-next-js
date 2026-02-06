<?php

class WCS_Cart_Notices {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Display Free Shipping Progress Bar in Cart
	 */
	/**
	 * Render Free Shipping Progress Bar (Shortcode)
	 */
	public function render_cart_notice() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		$threshold = (float) get_option( 'wcs_free_shipping_threshold', 0 );
		if ( $threshold <= 0 ) {
			return '';
		}

		// Calculate Cart Subtotal
		$current_total = WC()->cart->subtotal;

		// Calculate Progress
		$percent = 0;
		if ( $current_total < $threshold ) {
			$percent = ( $current_total / $threshold ) * 100;
			$remaining = $threshold - $current_total;
			$message = sprintf( 
				'Every order above <strong>%s</strong> will receive free standard shipping. Spend another <strong>%s</strong> to be eligible for free shipping.', 
				wc_price( $threshold ), 
				wc_price( $remaining )
			);
			$is_eligible = false;
		} else {
			$percent = 100;
			$message = sprintf( 
				'Every order above <strong>%s</strong> will receive free standard shipping. You are eligible for free shipping!', 
				wc_price( $threshold )
			);
			$is_eligible = true;
		}

		ob_start();
		?>
		<style>
			.wcs-shipping-notice {
				background-color: #fff;
				border: 1px solid #e1e1e1;
				padding: 15px;
				margin-bottom: 20px;
				border-radius: 4px;
				box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			}
			.wcs-shipping-notice-text {
				display: flex;
				align-items: center;
				gap: 10px;
				font-size: 14px;
				color: #333;
				margin-bottom: 10px;
			}
			.wcs-shipping-icon {
				color: #008000; /* Green */
				font-size: 18px;
			}
			.wcs-progress-bar-bg {
				background-color: #f0f0f0;
				height: 8px;
				border-radius: 4px;
				width: 100%;
				overflow: hidden;
			}
			.wcs-progress-bar-fill {
				background-color: #00a651; /* Green from the screenshot approx */
				height: 100%;
				width: 0%;
				transition: width 0.5s ease-in-out;
			}
		</style>

		<div class="wcs-shipping-notice">
			<div class="wcs-shipping-notice-text">
				<span class="wcs-shipping-icon dashicons dashicons-yes-alt" style="<?php echo $is_eligible ? 'display:inline-block;' : 'display:none;'; ?>"></span>
				<span class="wcs-shipping-icon dashicons dashicons-info" style="<?php echo !$is_eligible ? 'display:inline-block;' : 'display:none;'; ?>"></span>
				<span><?php echo $message; ?></span>
			</div>
			<div class="wcs-progress-bar-bg">
				<div class="wcs-progress-bar-fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}
