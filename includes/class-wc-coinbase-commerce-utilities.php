<?php
/**
 * The WC_Coinbase_Commerce_Utilities class.
 * 
 * @package WooCommerce_Coinbase_Commerce
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Coinbase_Commerce_Utilities' ) ) :

	/**
	 * Introduces helper functions.
	 * 
	 * @since 1.0.0
	 */
	class WC_Coinbase_Commerce_Utilities {

		/**
		 * All the available currencies.
		 *
		 * @since 1.0.0
		 * 
		 * @var array
		 */
		public static $currencies = array(
			'BTC'  => 'Bitcoin',
			'LTC'  => 'Litecoin',
			'ETH'  => 'Ethereum',
			'BCH'  => 'Bitcoin Cash',
			'USDC' => 'USD Coin'
		);

		/**
		 * Returns the currency name.
		 * 
		 * @since 1.0.0
		 *
		 * @param string $code Currency code.
		 * 
		 * @return array List of the available currencies.
		 */
		public static function get_currency_name( $code ) {
			return self::$currencies[ $code ];
		}

		/**
		 * Returns all the sent HTTP hearders.
		 *
		 * @return array Array of headers.
		 */
		public static function getallheaders() {
			$headers = array();

			foreach ( $_SERVER as $name => $value ) { 
				if ( substr( $name, 0, 5 ) == 'HTTP_' ) { 
					$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value; 
				} 
			}
			
			return $headers;
		}

		/**
		 * Get explorer URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $currency Currency code.
		 * @param string $tx       Transaction ID.
		 *
		 * @return string Explorer URL.
		 */
		public static function get_explorer_url( $currency, $tx ) {
			switch( $currency ) {
				case 'BTC' :
					return 'https://www.blocktrail.com/BTC/tx/' . $tx;
				case 'LTC' :
					return 'https://live.blockcypher.com/ltc/tx/' . $tx;
				case 'ETH' :
					return 'https://ethplorer.io/tx/' . $tx;
				case 'BCH' :
				case 'BCC' :
					return 'https://www.blocktrail.com/BCC/tx/' . $tx;
				case 'USD' :
					return 'https://etherscan.io/tx/' . $tx;
			}
		}
	}

endif;