<?php
/**
 * Plugin Name: Coinbase Commerce for WooCommerce
 * Plugin URI: https://infinue.com/woocommerce-coinbase-commerce/
 * Description: Accept digital currency on your WooCommerce store.
 * Version: 1.1.2
 * Author: Infinue
 * Author URI: https://infinue.com/
 * Requires at least: 4.4.0
 * Tested up to: 5.3.2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.9.1
 *
 * Text Domain: woocommerce-coinbase-commerce
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooCommerce_Coinbase_Commerce
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 * Globals constants.
 */
define( 'WC_COINBASE_COMMERCE_MIN_PHP_VER',    '5.6.0' );
define( 'WC_COINBASE_COMMERCE_MIN_WP_VER',     '4.4.0' );
define( 'WC_COINBASE_COMMERCE_MIN_WC_VER',     '3.0.0' );
define( 'WC_COINBASE_COMMERCE_ROOT_PATH',      dirname( __FILE__ ) );
define( 'WC_COINBASE_COMMERCE_ROOT_URL',       plugin_dir_url( __FILE__ ) );
define( 'WC_COINBASE_COMMERCE_TEMPLATES_PATH', dirname( __FILE__ ) . '/templates/' );

if( ! class_exists( 'WC_Coinbase_Commerce' ) ) :

	/**
	 * The main class.
	 *
	 * @since 1.0.0
	 */
	class WC_Coinbase_Commerce {
		/**
		 * Plugin version.
		 * 
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '1.1.2';

		/**
		 * The singelton instance of WooCommerce_Coinbase_Commerce.
		 *
		 * @since 1.0.0
		 *
		 * @var WooCommerce_Coinbase_Commerce
		 */
		private static $instance = null;

		/**
		 * Returns the singelton instance of WooCommerce_Coinbase_Commerce.
		 *
		 * Ensures only one instance of WooCommerce_Coinbase_Commerce is/can be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @return WooCommerce_Coinbase_Commerce
		 */
		public static function get_instance() {
			if( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * The constructor.
		 *
		 * Private constructor to make sure it can not be called directly from outside the class.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			/*
			 * Exit if WooCommerce is not installed and active. 
			 */
			if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
				return;
			}

			$this->settings();
			$this->includes();
			$this->hooks();

			do_action( 'woocommerce_coinbase_commerce_loaded' );
		}

		/**
		 * WooCommerce fallback notice.
		 * 
		 * @since 1.0.0
		 *
		 * @return string Error message.
		 */
		public function woocommerce_missing_notice() {
			echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Coinbase Commerce for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-coinbase-commerce' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
		}

		/**
		 * Update plugin settings.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function settings() {
			$settings = get_option( 'woocommerce_coinbase_commerce_settings', false );

			if ( $settings ) {
				$settings['display_prices_cryptocurrency'] = isset( $settings['display_prices_cryptocurrency'] ) ? $settings['display_prices_cryptocurrency'] : 'yes';
				$settings['display_total_cryptocurrency'] = isset( $settings['display_total_cryptocurrency'] ) ? $settings['display_total_cryptocurrency'] : 'yes';
				$settings['active_cryptocurrencies'] = isset( $settings['active_cryptocurrencies'] ) ? $settings['active_cryptocurrencies'] : array( 'BTC', 'BCH', 'ETH', 'LTC', 'USDC' );
			}

			update_option( 'woocommerce_coinbase_commerce_settings', $settings );
		}

		/**
		 * Includes the required files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function includes() {
			/*
			 * Helpers.
			 */
			include_once WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/class-wc-coinbase-commerce-utilities.php';

			/*
			 * Back-end includes.
			 */
			if ( is_admin() ) {
				include_once WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/admin/class-wc-coinbase-commerce-admin-notices.php';
				include_once WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/admin/class-wc-coinbase-commerce-admin-assets.php';
			}
			
			/*
			 * Front-end includes.
			 */
			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				include_once WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/class-wc-coinbase-commerce-assets.php';
				include_once WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/class-wc-coinbase-commerce-price.php';
			}
		}
 
		/**
		 * Plugin hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function hooks() {
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );

			add_action( 'plugins_loaded', array( $this, 'init_gateway' ) );
			add_action( 'init',           array( $this, 'endpoint' ) );

			/*
			 * Register and add the currency column.
			 */
			$settings = get_option( 'woocommerce_coinbase_commerce_settings', false );

			if ( ! empty( $settings ) && 'yes' === $settings['currency_column'] ) {
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_digital_currency_column_header' ), 20 );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'order_digital_currency_column' ) );
			}
		}

		/**
		 * Initialize the gateway.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function init_gateway() {
			include_once WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/class-wc-gateway-coinbase-commerce.php';
		}

		/**
		 * Gateway endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function endpoint() {
			if( isset( $_GET['wc-api'] ) && 'coinbase-commerce' == $_GET['wc-api'] ) {
				WC_Gateway_Coinbase_Commerce::endpoint();
			}
		}

		/**
		 * Adds the gateway.
		 *
		 * @param array $methods Payment methods.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function add_gateway( $methods ) {
			$methods[] = 'WC_Gateway_Coinbase_Commerce';
			return $methods;
		}

		/**
		 * Creates a new order column for the digital currency.
		 *
		 * @param array $columns
		 *
		 * @return array $new_columns
		 */
		public function add_order_digital_currency_column_header( $columns ) {
			$new_columns = array();

			foreach ( $columns as $name => $info ) {
				$new_columns[ $name ] = $info;

				if ( 'order_date' === $name ) {
					$new_columns['order_digital_currency'] = __( 'Digital Currency', 'woocommerce-coinbase-commerce' );
				}
			}

			return $new_columns;
		}

		/**
		 * Adds content to the digital currency column.
		 *
		 * @param string $column
		 *
		 * @return void
		 */
		public function order_digital_currency_column( $column ) {
			global $post;
	
			if ( 'order_digital_currency' === $column ) {
				$currency             = get_post_meta( $post->ID, 'wc_coinbase_commerce_digital_currency', true );
				$available_currencies = array_keys( WC_Coinbase_Commerce_Utilities::$currencies );
				$icon_path            = WC_COINBASE_COMMERCE_ROOT_URL . 'assets/dist/images';

				if( in_array( $currency, $available_currencies ) ) {
					echo '<span title="' . WC_Coinbase_Commerce_Utilities::get_currency_name( $currency ) . '"><img width="20" height="20" src="' . $icon_path . '/icon-' . strtolower( $currency ) . '.svg"></span>';
				}
			}
		}

		/**
		 * Activation hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function activate() {
			// Nothing to do for now.
		}
		
		/**
		 * Deactivation hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function deactivate() {
			// Nothing to do for now.
		}

		/**
		 * Uninstall hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function uninstall() {
			include_once WC_COINBASE_COMMERCE_ROOT_PATH . 'uninstall.php';
		}
	}

	/**
	 * Main instance of WooCommerce_Coinbase_Commerce.
	 *
	 * Returns the main instance of WooCommerce_Coinbase_Commerce.
	 *
	 * @since  1.0.0
	 *
	 * @return WooCommerce_Coinbase_Commerce
	 */
	function wc_gateway_coinbase_commerce() {
		return WC_Coinbase_Commerce::get_instance();
	}

	// Global for backwards compatibility.
	$GLOBALS['wc_gateway_coinbase_commerce'] = wc_gateway_coinbase_commerce();

	// Plugin hooks.
	register_activation_hook( __FILE__,   array( 'WC_Coinbase_Commerce', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'WC_Coinbase_Commerce', 'deactivate' ) );
	register_uninstall_hook( __FILE__,    array( 'WC_Coinbase_Commerce', 'uninstall' ) );

endif;
