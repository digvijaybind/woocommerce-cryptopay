<?php
/**
 * The WC_Coinbase_Commerce_Assets class.
 * 
 * @package WooCommerce_Coinbase_Commerce
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Coinbase_Commerce_Assets' ) ) :

	/**
	 * WC_Coinbase_Commerce_Assets.
	 *
	 * @since 1.0.0
	 */
	class WC_Coinbase_Commerce_Assets {
		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
		}
		
		/**
		 * Enqueue admin scripts.
		 *
		 * @since 1.0.0
		 */
		public function scripts() {
			/*
			 * Global front-end scripts. 
			 */
			wp_enqueue_script(
				'wc_coinbase_commerce_scripts',
				WC_COINBASE_COMMERCE_ROOT_URL . 'assets/dist/js/public/wc-coinbase-commerce-scripts.min.js',
				array(),
				false,
				true
			);

			/*
			 * Global front-end variables.
			 */
			wp_localize_script(
				'wc_coinbase_commerce_scripts',
				'wc_coinbase_commerce_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' )
				)
			);
		}

		/**
		 * Enqueue admin styles.
		 *
		 * @since 1.0.0
		 */
		public function styles() {
			/*
			 * Global styles.
			 */
			wp_enqueue_style( 'wc_coinbase_commerce_styles', WC_COINBASE_COMMERCE_ROOT_URL . 'assets/dist/css/public/wc-coinbase-commerce-styles.min.css', array(), false, 'all' );
		}
	}

	return new WC_Coinbase_Commerce_Assets;

endif;