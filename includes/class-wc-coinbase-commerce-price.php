<?php
/**
 * The WC_Coinbase_Commerce_Price class.
 * 
 * @package WooCommerce_Coinbase_Commerce
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Coinbase_Commerce_Price' ) ) :

	/**
	 * WC_Coinbase_Commerce_Price.
	 *
	 * @since 1.0.0
	 */
	class WC_Coinbase_Commerce_Price {

		/**
		 * Single instance of the rates.
		 *
		 * @since 1.0.0
		 *
		 * @var WooCommerce_Coinbase_Commerce
		 */
		private $rates = null;

		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			// General settings.
			$settings = get_option( 'woocommerce_coinbase_commerce_settings', false );

			// Check if display product prices in crypto is enabled.
			if ( 'yes' === $settings['display_prices_cryptocurrency'] ) {
				add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 10, 2 );
				add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), 10, 3 );
			}

			// Check if display totals in crypto is enabled.
			if ( 'yes' === $settings['display_total_cryptocurrency'] ) {
				add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'order_total_html' ), 10, 1 );
			}
		}

		/**
		 * Returns the single instance of $this->rates.
		 *
		 * Ensures only one instance of $this->rates is be loaded. I.e. only one request to the API.
		 *
		 * @since 1.0.0
		 *
		 * @return object Rates object.
		 */
		private function get_rates() {
			if( null === $this->rates ) {
				$response = json_decode( $this->api_request(), true );
				$this->rates = $response['data']['rates'];
			}

			return $this->rates;
		}

		/**
		 * API request.
		 *
		 * @since 1.0.0
		 *
		 * @return array Request response.
		 */
		public function api_request() {
			/**
			 * API URL.
			 */
			$api_url = 'https://api.coinbase.com/v2/exchange-rates?currency=' . get_woocommerce_currency();

			/**
			 * cURL headers.
			 */
			$headers = array(
				'Content-Type: application/json',
			);

			/*
			 * cURL request.
			 */
			$ch = curl_init();

			// cURL options.
			curl_setopt( $ch, CURLOPT_HTTPHEADER,     $headers );
			curl_setopt( $ch, CURLOPT_URL,            $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			
			$response = curl_exec( $ch );
			
			curl_close( $ch );

			// Return encoded response.
			return $response;
		}

		/**
		 * Calculates and returns prices in cryptocurrency.
		 *
		 * Returns the price in Bitcon, Bitcoin Cash, Ethereum, Litecoin and USD Coin.
		 *
		 * @param float $price Price in fiat currency.
		 *
		 * @since 1.0.0
		 *
		 * @return array List of prices in cryptocurrency.
		 */
		public function calc_prices( $price ) {
			// Get active currencies from gateway settings.
			$active_currencies = get_option( 'woocommerce_coinbase_commerce_settings', false )['active_cryptocurrencies'];
			
			if( $active_currencies ) {
				foreach( $active_currencies as $curr ) {
					$prices[ $curr ] = number_format( (float) $this->get_rates()[ $curr ] * (float) $price, 5 );
				}
			}

			return isset( $prices ) ? $prices : array();
		}

		/**
		 * Display prices in cryptocurrency.
		 *
		 * @param float Price in fiat.
		 *
		 * @since 1.0.0
		 *
		 * @return string Formatted prices in cryptocurrency.
		 */
		public function price_html( $price ) {
			if( 0 == $price ) return;

			/**
			 * Template args.
			 */
			$args = [
				'prices' => $this->calc_prices( $price ),
			];

			ob_start();
			wc_get_template( 'price-crypto.php', $args, '', WC_COINBASE_COMMERCE_TEMPLATES_PATH );
			
			$price_crypto = ob_get_clean();

			return $price_crypto;
		}

		/**
		 * Filter hook: woocommerce_get_price_html.
		 *
		 * @param $price   Formatted fiat price.
		 * @param $product Product object.
		 *
		 * @since 1.0.0
		 *
		 * @return string Formatted price in fiat and cryptocurrency.
		 */
		public function get_price_html( $price, $product ) {
			if ( is_single() && $product->is_type( 'variable' ) ) {
				$prices = $product->get_variation_prices( true );

				$min_price = current( $prices['price'] );
				$max_price = end( $prices['price'] );

				if ( $min_price === $max_price ) {
					return $price . $this->price_html( (float) $product->get_price() );
				}

				return $price;
			}
			
			return $price . $this->price_html( (float) $product->get_price() );
		}

		public function cart_item_price( $price, $cart_item, $cart_item_key ) {
			return $price . $this->price_html( (float) $cart_item['line_total'] );
		}

		public function order_total_html( $total ) {
			return $total . $this->price_html( (float) WC()->cart->total ); 
		}
	}
	// General settings.
	$settings = get_option( 'woocommerce_coinbase_commerce_settings', false );

	// Check if payment method is enabled.
	if ( 'yes' === $settings['enabled'] ) {
		return new WC_Coinbase_Commerce_Price;
	}

endif;