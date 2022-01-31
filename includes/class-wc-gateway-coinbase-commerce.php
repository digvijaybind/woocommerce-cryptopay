<?php
/**
 * The WC_Gateway_Coinbase_Commerce class.
 * 
 * @package WooCommerce_Coinbase_Commerce
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Gateway_Coinbase_Commerce' ) ) :

	/**
	 * WC_Gateway_Coinbase_Commerce.
	 *
	 * @since 1.0.0
	 */
	class WC_Gateway_Coinbase_Commerce extends WC_Payment_Gateway {
		/**
		 * API version.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $api_version = '2018-03-22'; //Api Version

		/**
		 * API URL.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $api_url = 'https://api.commerce.coinbase.com'; // Api Url
		
		/**
		 * The constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->has_fields         = false;
			$this->id                 = 'coinbase_commerce';
			$this->method_title       = __( 'Coinbase Commerce', 'woocommerce-coinbase-commerce' );
			$this->method_description = sprintf( __( 'Coinbase Commerce allows you to accept digial currencies on your store. %1$sSign up%2$s for a Coinbase Commerce account, and get your %3$saccount keys%4$s.', 'woocommerce-coinbase-commerce' ), '<a href="' . self::variables( 'sign_up_page_url' ) . '" target="_blank">', '</a>', '<a href="' . self::variables( 'settings_page_url' ) . '" target="_blank">', '</a>');
			$this->icon               = WC_COINBASE_COMMERCE_ROOT_URL . '/assets/dist/images/Logo.png';

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->enabled                       = $this->get_option( 'enabled' );
			$this->title                         = $this->get_option( 'title', __( 'Coinbase Commerce', 'woocommerce-coinbase-commerce' ) );
			$this->description                   = $this->get_option( 'description' );
			$this->api_key                       = $this->get_option( 'api_key' );
			$this->webhook_shared_secret         = $this->get_option( 'webhook_shared_secret' );
			$this->display_prices_cryptocurrency = $this->get_option( 'display_prices_cryptocurrency' );
			$this->display_total_cryptocurrency  = $this->get_option( 'display_total_cryptocurrency' );
			$this->active_cryptocurrencies       = $this->get_option( 'active_cryptocurrencies' );
			$this->form_title                    = $this->get_option( 'form_title' );
			$this->form_description              = $this->get_option( 'form_description' );
	
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_filter( 'woocommerce_get_order_item_totals' , array( $this , 'set_order_details' ) , 10 , 2 );
		}
		
		/**
		 * Initialize gateway settings form fields.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = require( WC_COINBASE_COMMERCE_ROOT_PATH . '/includes/admin/coinbase-commerce-settings.php' );
		}

		/**
		 * Displays admin optoins.
		 *
		 * @since 1.0.0
		 *
		 * @return string Admin options.
		 */
		public function admin_options() {	
			$output = '<h3>' . $this->method_title . '</h3>'
					. '<p>' . $this->method_description . '</p>'
					. '<table class="form-table">';
			
			$output .= $this->generate_settings_html( array(), false );

			$output .= '</table>';

			echo $output;
		}

		/**
		 * Helper method to update a settings option.
		 *
		 * @since 1.0.0
		 *
		 * @param string $key   Option key.
		 * @param string $value Option value.
		 *
		 * @return bool True if option value has changed, false if not or if update failed.
		 */
		public function update_option( $key, $value = '' ) {
			if ( empty( $this->settings ) ) {
				$this->init_settings();
			}

			$this->settings[ $key ] = $value;
			
			return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ) );
		}

		/**
		 * Processes admin options.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function process_admin_options () {
			parent::process_admin_options();
			
			/*
			 * Validate the API key.
			 */
			$post_data = $this->get_post_data();
			$api_key   = $this->get_field_value( 'api_key', 'api_key' );
			
			/*
			 * Request HTTP headers.
			 */
			$headers  = array(
				'Content-Type: application/json',
				'X-CC-Api-Key: ' . $api_key,
				'X-CC-Version: ' . $this->api_version,
			);

			/*
			 * cURL request.
			 */
			$ch = curl_init();

			// cURL options.
			curl_setopt( $ch, CURLOPT_HTTPHEADER,     $headers );
			curl_setopt( $ch, CURLOPT_URL,            $this->api_url . '/charges' );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			
			$response = curl_exec( $ch );
			
			curl_close( $ch );

			// Encoded response.
			$response = json_decode( $response );

			/*
			 * Handle response errors.
			 */
			if ( isset( $response->error ) ) {
				// Disable payment method.
				$this->update_option( 'enabled', 'no' );
				
				switch( $response->error->type ) {
					case 'authorization_error' :
						$error_message = __( 'API key is required.', 'woocommerce-coinbase-commerce' );
						break;
					case 'authentication_error':
						$error_message = __( 'The API key you entered is invalid.', 'woocommerce-coinbase-commerce' );
						break;
					default :
						$error_message = $response->error->message;
				}

				WC_Admin_Settings::add_error( $error_message );
			}

			$webhook_shared_secret = $this->get_field_value( 'webhook_shared_secret', 'webhook_shared_secret' );
			
			if ( empty( $webhook_shared_secret ) ) {
				WC_Admin_Settings::add_error( 'Webhook Shared Secret is required to receive notifications on the charge statuses.', 'woocommerce-coinbase-commerce' );
			}
		}

		/**
		 * Processes the payment and returns the result.
		 *
		 * @since 1.0.0
		 *
		 * @param int $order_id Order ID.
		 *
		 * @throws Exception if any error is received.
		 *
		 * @return array|void
		 */
		public function process_payment( $order_id ) {
			$order    = wc_get_order( $order_id );
			$settings = get_option( 'woocommerce_coinbase_commerce_settings', false );

			// The HTTP headers.
			$headers  = array(
				'Content-Type: application/json',
				'X-CC-Api-Key: ' . $settings['api_key'],
				'X-CC-Version: ' . $this->api_version,
			);

			// The POST data.
			$fields   = array(
				'name'         => isset( $settings['form_title'] ) ? $settings['form_title'] : '',
				'description'  => isset( $settings['form_description'] ) ? $settings['form_description'] : '',
				'redirect_url' => $order->get_checkout_order_received_url(),
				'pricing_type' => 'fixed_price',
				'local_price'  => array(
						'amount'   => $order->get_total(),
						'currency' => $order->get_currency(),
				),
				'metadata'     => array(
					  'order_id' => $order_id,
				),
			);

			/*
			 * cURL request.
			 */
			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_HTTPHEADER,     $headers );
			curl_setopt( $ch, CURLOPT_URL,            $this->api_url . '/charges' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS,     json_encode( $fields ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

			$result = curl_exec( $ch );

			curl_close( $ch );

			$result = json_decode( $result );

			try {

				if ( ! isset( $result->data ) ) {
					$error = isset( $result->error->message ) ? $result->error->message : __( 'Something went wrong.', 'woocommerce-coinbase-commerce' );
					throw new Exception( $error );
				}

				$hosted_url = $result->data->hosted_url;
				$code       = $result->data->code;

				// Set the value of _transaction_id to charge code.
				update_post_meta( $order_id, '_transaction_id', $code );

				// Empty cart.
				WC()->cart->empty_cart();

				// Set order status to Pending Payment.
				$order->update_status( 'pending' );

				// Add order received note.
				$order->add_order_note(
					__( 'Order received.', 'woocommerce-coinbase-commerce' ) );

				return array(
					'result'   => 'success',
					'redirect' => $hosted_url,
				);
			} catch( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}

		/**
		 * Returns global variables.
		 *
		 * @since 1.0.0
		 *
		 * @param string $variable The variable slug.
		 *
		 * @return mixed The global variable.
		 */
		public static function variables( $variable ) {
			switch( $variable ) {
				case 'webhook_url' :
					return site_url() . '/?wc-api=coinbase-commerce'; // api url
				case 'settings_page_url' :
					return 'https://commerce.coinbase.com/dashboard/settings'; //Url of page with dashboard setting 
				case 'sign_up_page_url' :
					return 'https://commerce.coinbase.com/signup'; // Url of signup page
				case 'receipts_url' :
					return 'https://commerce.coinbase.com/receipts'; // Url of receipt page
				case 'account_dashboard' :
					return 'https://commerce.coinbase.com/dashboard'; // Url of account dashboard
			}
		}

		/**
		 * Handles gateway endpoints.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function endpoint() {
			$headers         = WC_Coinbase_Commerce_Utilities::getallheaders();
			$signature       = isset( $headers['X-Cc-Webhook-Signature'] ) ? $headers['X-Cc-Webhook-Signature'] : '';
			$response        = file_get_contents( 'php://input', true );
			$settings        = get_option( 'woocommerce_coinbase_commerce_settings', false );
			$hashed_response = hash_hmac( 'sha256', $response, $settings['webhook_shared_secret'] );

			// Decode the response.
			$response        = json_decode( $response, true );

			// Validate the signature.
			if ( $signature !== $hashed_response && '' !== $signature ) {
				update_option( 'wc_coinbase_commerce_show_webhook_notice', 'yes' );
			}

			/*
			 * Process response data sent from Coinbase Commerce to the endpoint.
			 */
			if ( isset( $response ) && isset( $response['event'] ) && $signature === $hashed_response ) {
				header( 'Content-Type: application/json' );
				
				$data       = $response['event']['data'];
				$payments   = $data['payments'];
				$timeline   = $data['timeline'];
				$metadata   = $data['metadata'];
				$code       = $data['code'];
				$hosted_url = $data['hosted_url'];
				$order_id   = $metadata['order_id'];
				$order      = wc_get_order( $order_id );
				$status     = end( $timeline );

				// Store payment details in the database.
				if ( ! empty( $payments ) ) {
					$last_payment = end( $payments );

					$last_payment_crypto = $last_payment['value']['crypto'];
					$amount              = $last_payment_crypto['amount'];
					$currency            = $last_payment_crypto['currency'];
					$transaction_id      = $last_payment['transaction_id'];
					
					update_post_meta( $order_id, 'wc_coinbase_commerce_digital_currency', $last_payment['value']['crypto']['currency'] );
					update_post_meta( $order_id, 'wc_coinbase_commerce_digital_amount', $last_payment['value']['crypto']['amount'] );
					update_post_meta( $order_id, 'wc_coinbase_commerce_transaction_id', $last_payment['transaction_id'] );
				}

				/*
				 * Handle payment statues.
				 */
				if ( 'NEW' === $status['status'] ) {
					// Order created.
					$order->add_order_note( sprintf( __( 'Order Code: <a href="%s" target="_blank">%s</a>.', 'woocommerce-coinbase-commerce' ), $hosted_url, $code ) );
				} elseif ( 'COMPLETED' === $status['status'] ) {
					// Payment received sucessfully.
					$order->payment_complete();
					$order->add_order_note( __( 'Payment recevied.', 'woocommerce-coinbase-commerce' ) );
				} elseif ( 'EXPIRED' === $status['status'] ) {
					// Payment delayed.
					$order->update_status( 'failed', __( 'Expired: transaction is not completed in time.', 'woocommerce-coinbase-commerce' ) );
				} elseif ( 'UNRESOLVED' === $status['status'] ) {
					// Payment has issues.
					$context        = $status['context'];
					$transaction_id = $status['payment']['transaction_id'];
					$payment        = end( array_map( function( $i ) use ( $transaction_id ) {
						if ( $transaction_id === $i['transaction_id'] ) {
							return $i;
						}
					}, $payments ) );
					$payment_crypto = $payment['value']['crypto'];
					$amount         = $payment_crypto['amount'];
					$currency       = $payment_crypto['currency'];

					switch ( $context ) {
						case 'UNDERPAID':
							$order->update_status( 'on-hold', __( 'The amount paid is less than the order total.', 'woocommerce-coinbase-commerce' ) );
							break;
						case 'OVERPAID':
							$order->update_status( 'on-hold', __( 'The amount paid is more than the order total.', 'woocommerce-coinbase-commerce' ) );
							break;
						case 'MULTIPLE':
							$order->update_status( 'on-hold', sprintf( __( 'Multiple payments received for this order. Please check your %1$sCoinbase Commerce account%2$s for more details.', 'woocommerce-coinbase-commerce' ), '<a href="' . self::variables( 'account_dashboard' ) . '" target="_blank"', '</a>' ) );
							break;
						case 'DELAYED':
							$order->update_status( 'on-hold', __( 'Payment delayed.', 'woocommerce-coinbase-commerce' ) );						
							break;
						case 'MANUAL':
							// Status code returned from Coinbase Commerce: UNRESOLVED - MANUAL.
							$order->update_status( 'on-hold', __( 'Status Code: MANUAL.', 'woocommerce-coinbase-commerce' ) );
							break;
						case 'OTHER':
							// Status code returned from Coinbase Commerce: UNRESOLVED - MANUAL.
							$order->update_status( 'on-hold', __( 'Status Code: OTHER.', 'woocommerce-coinbase-commerce' ) );
							break;
					}
				} elseif ( 'RESOLVED' === $status['status'] ) {
					// Payment issues resolved manually.
					$order->payment_complete();
					$order->add_order_note( 'Payment recieved: all issues are resolved manually from the Coinbase Commerce dashboard.', 'woocommerce-coinbase-commerce' );
				}

				if ( 'COMPLETED' === $status['status'] || 'UNRESOLVED' === $status['status'] ) {
					$order->add_order_note(
						/* translators: 1) currency name 2) br tag 3) amount 4) currency code 5) br tag 6) transaction 7) br tag 8) receipt */
						sprintf(
							__( 'Digital Currency: %1$s%2$sAmount: %3$s %4$s%5$sTransaction ID: %6$s%7$sPayment Receipt: %8$s' ),
							WC_Coinbase_Commerce_Utilities::get_currency_name( $currency ),
							'<br>',
							$amount,
							$currency,
							'<br>',
							'<a href="' . WC_Coinbase_Commerce_Utilities::get_explorer_url( $currency, $transaction_id ) . '" target="_blank">' . $transaction_id . '</a>',
							'<br>',
							'<a href="' . self::variables( 'receipts_url' ) . '/' . $code . '" target="_blank">' . $code . '</a>'
						)
					);
				}
			} else {
				echo -1;
			}
			die();
		}

		/**
		 * Adds payment details to the order.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $total_rows The original total rows.
		 * @param object $order      The order object.
		 *
		 * @return array The new total rows.
		 */
		public function set_order_details( $total_rows, $order ) {
			// The new rows.
			$digital_currency_symbol = $order->get_meta( 'wc_coinbase_commerce_digital_currency', true );
			$digital_amount          = $order->get_meta( 'wc_coinbase_commerce_digital_amount', true );
			$transaction_id          = $order->get_meta( 'wc_coinbase_commerce_transaction_id', true );

			if ( '' !== $digital_currency_symbol ) {
				switch( $digital_currency_symbol ) {
					case 'BTC' :
						$digital_currency = __( 'Bitcoin', 'woocommerce-coinbase-commerce' ); break;
					case 'LTC' :
						$digital_currency = __( 'Litecoin', 'woocommerce-coinbase-commerce' ); break;
					case 'ETH' :
						$digital_currency = __( 'Ethereum', 'woocommerce-coinbase-commerce' ); break;
					case 'BCH' :
						$digital_currency = __( 'Bitcoin Cash', 'woocommerce-coinbase-commerce' ); break;
					case 'USDC' :
						$digital_currency = __( 'USD Coin', 'woocommerce-coinbase-commerce' ); break;
				}

				$total_rows['digital_currency'] = array(
					'label' => __( 'Digital Currency:', 'woocommerce-coinbase-commerce' ),
					'value' => $digital_currency,
				);
			}

			if ( '' !== $digital_amount && '' !== $digital_currency_symbol ) {
				$total_rows['digital_amount'] = array(
					'label' => __( 'Amount:', 'woocommerce-coinbase-commerce' ),
					'value' => $digital_amount . ' ' . $digital_currency_symbol,
				);
			}

			if ( '' !== $transaction_id && '' !== $digital_currency_symbol ) {
				$explorer_url = WC_Coinbase_Commerce_Utilities::get_explorer_url( $digital_currency_symbol, $transaction_id );	

				$total_rows['transaction_id'] = array(
					'label' => __( 'Transaction ID:', 'woocommerce-coinbase-commerce' ),
					'value' => '<a href="' . $explorer_url . '" target="_blank">' . $transaction_id . '</a>',
				);
			}
			
			return $total_rows;
		}
	}

endif;