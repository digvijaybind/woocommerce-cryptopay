<?php
/**
 * The WC_Coinbase_Commerce_Admin_Notices class.
 * 
 * @package WooCommerce_Coinbase_Commerce/Admin
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Coinbase_Commerce_Admin_Notices' ) ) :

	/**
	 * Handles admin notices.
	 *
	 * @since 1.0.0
	 */
	class WC_Coinbase_Commerce_Admin_Notices {
		/**
		 * Notices array.
		 *
		 * @var array
		 */
		public $notices = array();

		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'wp_loaded',     array( $this, 'hide_notices' ) );
		}

		/**
		 * Adds slug keyed notices (to avoid duplication).
		 *
		 * @since 1.0.0
		 * 
		 * @param string $slug        Notice slug.
		 * @param string $class       CSS class.
		 * @param string $message     Notice body.
		 * @param bool   $dismissible Allow/disallow dismissing the notice. Default value false. 
		 * 
		 * @return void
		 */
		public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
			$this->notices[ $slug ] = array(
				'class'       => $class,
				'message'     => $message,
				'dismissible' => $dismissible,
			);
		}

		/**
		 * Displays the notices.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function admin_notices() {
			// Exit if user has no privilges.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			// Basic checks.
			$this->check_environment();

			// Gateway checks.
			$this->gateway_check_environment();

			// Display the notices collected so far.
			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

				if ( $notice['dismissible'] ) {
					echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'wc-coinbase-commerce-hide-notice', $notice_key ), 'wc_coinbase_commerce_hide_notices_nonce', '_wc_coinbase_commerce_notice_nonce' ) ) . '" class="woocommerce-message-close notice-dismiss" style="position:absolute;right:1px;padding:9px;text-decoration:none;"></a>';
				}

				echo '<p>' . wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) ) . '</p>';
				
				echo '</div>';
			}
		}

		/**
		 * Handles all the basic checks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function check_environment() {
			$show_ssl_notice    = get_option( 'wc_coinbase_commerce_show_ssl_notice' );
			$show_phpver_notice = get_option( 'wc_coinbase_commerce_show_phpver_notice' );
			$show_wpver_notice  = get_option( 'wc_coinbase_commerce_show_wpver_notice' );
			$show_wcver_notice  = get_option( 'wc_coinbase_commerce_show_wcver_notice' );
			$show_curl_notice   = get_option( 'wc_coinbase_commerce_show_curl_notice' );

			if ( empty( $show_phpver_notice ) ) {
				if ( version_compare( phpversion(), WC_COINBASE_COMMERCE_MIN_PHP_VER, '<' ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'Coinbase Commerce for WooCommerce - The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-coinbase-commerce' );
					$this->add_admin_notice( 'phpver', 'error', sprintf( $message, WC_COINBASE_COMMERCE_MIN_PHP_VER, phpversion() ), true );
				}
			}

			if ( empty( $show_wpver_notice ) ) {
				global $wp_version;

				if ( version_compare( $wp_version, WC_COINBASE_COMMERCE_MIN_WP_VER, '<' ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'Coinbase Commerce for WooCommerce - The minimum WordPress version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-coinbase-commerce' );
					$this->add_admin_notice( 'wpver', 'notice notice-warning', sprintf( $message, WC_COINBASE_COMMERCE_MIN_WP_VER, WC_VERSION ), true );
				}
			}

			if ( empty( $show_wcver_notice ) ) {
				if ( version_compare( WC_VERSION, WC_COINBASE_COMMERCE_MIN_WC_VER, '<' ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'Coinbase Commerce for WooCommerce - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-coinbase-commerce' );
					$this->add_admin_notice( 'wcver', 'notice notice-warning', sprintf( $message, WC_COINBASE_COMMERCE_MIN_WC_VER, WC_VERSION ), true );
				}
			}

			if ( empty( $show_curl_notice ) ) {
				if ( ! function_exists( 'curl_init' ) ) {
					$message = __( 'Coinbase Commerce for WooCommerce - cURL is not installed.', 'woocommerce-coinbase-commerce' );
					$this->add_admin_notice( 'curl', 'notice notice-warning', $message, true );
				}
			}

			if ( empty( $show_ssl_notice ) ) {
				// Show notice if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected.
				if ( ( function_exists( 'wc_site_is_https' ) && ! wc_site_is_https() ) && ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) ) {
					/* translators: 1) link 2) link */
					$message = __( 'Coinbase Commerce for WooCommerce is enabled, but the <a href="%1$s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woocommerce-coinbase-commerce' );
					$this->add_admin_notice( 'ssl', 'notice notice-warning', sprintf( $message, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ), true );
				}
			}
		}

		/**
		 * Handles gateway checks.
		 *
		 * @since 1.0.0
		 * 
		 * @return void
		 */
		public function gateway_check_environment() {
			$coinbase_commerce_settings = get_option( 'woocommerce_coinbase_commerce_settings', false );

			$show_coinbase_commerce_webhook_notice = get_option( 'wc_coinbase_commerce_show_webhook_notice' );

			if ( ! empty( $coinbase_commerce_settings ) && 'yes' === $coinbase_commerce_settings['enabled'] && 'yes' === $show_coinbase_commerce_webhook_notice ) {
				$message = __( 'Coinbase Commerce - The Webhook Shared Secret is invalid. You will not receive notifications on the charge statuses.', 'woocommerce-coinbase-commerce' );
				$this->add_admin_notice( 'coinbase_commerce_webhook', 'error', $message, true );
			}
		}

		/**
		 * Hides any admin notices.
		 *
		 * @since 1.0.0
		 * 
		 * @return void
		 */
		public function hide_notices() {
			if ( isset( $_GET['wc-coinbase-commerce-hide-notice'] ) && isset( $_GET['_wc_coinbase_commerce_notice_nonce'] ) ) {
				if ( ! wp_verify_nonce( $_GET['_wc_coinbase_commerce_notice_nonce'], 'wc_coinbase_commerce_hide_notices_nonce' ) ) {
					wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-coinbase-commerce' ) );
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-coinbase-commerce' ) );
				}

				$notice = wc_clean( $_GET['wc-coinbase-commerce-hide-notice'] );

				switch ( $notice ) {
					case 'phpver' :
						update_option( 'wc_coinbase_commerce_show_phpver_notice', 'no' );
						break;
					case 'wcver' :
						update_option( 'wc_coinbase_commerce_show_wcver_notice', 'no' );
						break;
					case 'wpver' :
						update_option( 'wc_coinbase_commerce_show_wpver_notice', 'no' );
						break;
					case 'curl' :
						update_option( 'wc_coinbase_commerce_show_curl_notice', 'no' );
						break;
					case 'ssl' :
						update_option( 'wc_coinbase_commerce_show_ssl_notice', 'no' );
						break;
					case 'coinbase_commerce_webhook' :
						update_option( 'wc_coinbase_commerce_show_webhook_notice', 'no' );
						break;
				}
			}
		}
	}

	new WC_Coinbase_Commerce_Admin_Notices();

endif;