<?php

namespace WeglotWP\Third\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WeglotWP\Models\Hooks_Interface_Weglot;

/**
 * WC_Cart_Reload_Weglot
 *
 * @since 2.4.0
 */
class WC_Cart_Reload_Weglot implements Hooks_Interface_Weglot {
	protected $name_transient = 'weglot_wc_clean_cart';

	/**
	 * @since 2.4.0
	 * @return void
	 */
	public function __construct() {
		$this->wc_active_services        = weglot_get_service( 'WC_Active_Weglot' );
	}

	/**
	 * @since 2.4.0
	 * @see Hooks_Interface_Weglot
	 * @return void
	 */
	public function hooks() {
		if ( ! $this->wc_active_services->is_active() ) {
			return;
		}

		$active_wc_reload = weglot_get_option( 'active_wc_reload' );

		if ( ! $active_wc_reload ) {
			return;
		}

		add_action( 'wp_ajax_weglot_wc_reload_cart', [ $this, 'weglot_wc_reload_cart' ] );
		add_action( 'wp_ajax_nopriv_weglot_wc_reload_cart', [ $this, 'weglot_wc_reload_cart' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'weglot_wc_wp_enqueue_scripts' ] );
		add_action( 'wp_footer', [ $this, 'weglot_wc_footer' ] );
	}

	/**
	 * @since 2.4.0
	 * @return void
	 */
	public function weglot_wc_reload_cart() {
		set_transient( $this->name_transient, 'true', 12 * HOUR_IN_SECONDS );
		wp_send_json_success();
	}

	/**
	 * @since 2.4.0
	 * @return void
	 */
	public function weglot_wc_wp_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * @since 2.4.0
	 * @return void
	 */
	public  function weglot_wc_footer() {
		$click_selector = apply_filters( 'weglot_wc_reload_selector', '.weglot-flags a' );
		$ajaxurl        = admin_url( 'admin-ajax.php' );
		$load           = apply_filters( 'weglot_load_script_reload_selector', true );
		if ( ! $load ) {
			return;
		} ?>
		<script>
			document.addEventListener('DOMContentLoaded', function(){

				jQuery( '<?php echo esc_attr( $click_selector ); ?>' ).on('click', function(e) {
					e.preventDefault();
					var href = jQuery(this).attr('href')
					jQuery.ajax({
						url: '<?php echo esc_url( $ajaxurl ); ?>',
						data:{
							action :'weglot_wc_reload_cart'
						}
					})

					window.location.href = href

				})
			})
		</script>
		<?php
		$transient = get_transient( $this->name_transient );

		if ( false !== $transient ) {
			delete_transient( $this->name_transient ); //phpcs:ignore ?>
			<script>
				document.addEventListener('DOMContentLoaded', function(){
					jQuery(document.body).trigger('wc_fragment_refresh');
				})
			</script>
			<?php
		}
	}
}
