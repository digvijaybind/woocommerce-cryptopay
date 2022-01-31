<?php
/**
 * Coinbase Commerce Settings.
 * 
 * @package WooCommerce_Coinbase_Commerce/Admin
 * @author  Infinue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
} 

return apply_filters( 'wc_coinbase_commerce_settings',
	array (
		'enabled' => array (
			'title'   => __( 'Enable/Disable', 'woocommerce-coinbase-commerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Coinbase Commerce', 'woocommerce-coinbase-commerce' ),
			'default' => 'no',
		),
		'title' => array (
			'title'       => __( 'Title', 'woocommerce-coinbase-commerce' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
		),
		'description' => array (
			'title'       => __( 'Description', 'woocommerce-coinbase-commerce' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
			'default'     => __( 'Pay in digital currency.', 'woocommerce-coinbase-commerce' ),
		),
		'webhook_endpoints' => array (
			'title'       => __( 'Webhook Endpoints', 'woocommerce-coinbase-commerce' ),
			'type'        => 'title',
			/* translators: 1) webhook URL 2) opening anchor tag opening 3) closing anchor tag */
			'description' => sprintf( __( 'You must add the following webhook endpoint %1$s to your %2$sCoinbase Commerce account settings%3$s. This will enable you to receive notifications on the charge statuses.', 'woocommerce-coinbase-commerce' ), '<mark class="wc-coinbase-commerce-mark">' . WC_Gateway_Coinbase_Commerce::variables( 'webhook_url' ) . '</mark>',  '<a href="' . WC_Gateway_Coinbase_Commerce::variables( 'settings_page_url' ) . '" target="_blank">', '</a>' ),
		),
		'api_key' => array (
			'title'       => __( 'API Key', 'woocommerce-coinbase-commerce' ),
			'type'        => 'password',
			'description' => __( 'Get your API key from Coinbase Commerce.', 'woocommerce-coinbase-commerce' ),
			'placeholder' => __( 'Insert your Coinbase Commerce API key', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
		),
		'webhook_shared_secret' => array (
			'title'       => __( 'Webhook Shared Secret', 'woocommerce-coinbase-commerce' ),
			'type'        => 'password',
			'description' => __( 'Get your Webhook Shared Secret from Coinbase Commerce.', 'woocommerce-coinbase-commerce' ),
			'placeholder' => __( 'Insert your Coinbase Commerce Webhook Shared Secret', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
		),
		'display_prices_cryptocurrency' => array (
			'title'   => __( 'Display Product Prices in Cryptocurrency', 'woocommerce-coinbase-commerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Display product prices in cryptocurrency.', 'woocommerce-coinbase-commerce' ),
			'default' => __( 'yes' ),
		),
		'display_total_cryptocurrency' => array (
			'title'   => __( 'Display Order Total in Cryptocurrency', 'woocommerce-coinbase-commerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Display order total in cryptocurrency.', 'woocommerce-coinbase-commerce' ),
			'default' => __( 'yes' ),
		),
		'active_cryptocurrencies' => array(
			'title'       => __( 'Active Cryptocurrencies', 'woocommerce-coinbase-commerce' ),
			'type'        => 'multiselect',
			'description' => __( 'Select the cryptocurrencies you want to display prices in.', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
			'class'       => 'wc-enhanced-select',
			'options'     => array (
				'BTC'  => __( 'Bitcoin', 'woocommerce-coinbase-commerce' ),
				'BCH'  => __( 'Bitcoin Cash', 'woocommerce-coinbase-commerce' ),
				'ETH'  => __( 'Ethereum', 'woocommerce-coinbase-commerce' ),
				'LTC'  => __( 'Litecoin', 'woocommerce-coinbase-commerce' ),
				'USDC' => __( 'USD Coin', 'woocommerce-coinbase-commerce' ),
			),
			'default' => array( 'BTC', 'BCH', 'ETH', 'LTC', 'USDC' ),
		),
		'currency_column' => array (
			'title'   => __( 'Display Payment Currency', 'woocommerce-coinbase-commerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Display payment currency in orders page.', 'woocommerce-coinbase-commerce' ),
			'default' => __( 'yes', 'woocommerce-coinbase-commerce' ),
		),
		'form_title' => array (
			'title'       => __( 'Payment Form Title', 'woocommerce-coinbase-commerce' ),
			'type'        => 'text',
			'description' => __( 'Insert title you want to show in coinbase payment form', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
			'placeholder' => __( 'Insert title', 'woocommerce-coinbase-commerce' ),
			'default'     => get_bloginfo( 'name' ),
		),
		'form_description' => array (
			'title'       => __( 'Payment Form Description', 'woocommerce-coinbase-commerce' ),
			'type'        => 'text',
			'description' => __( 'Insert the description you want to show in coinbase payment form', 'woocommerce-coinbase-commerce' ),
			'desc_tip'    => true,
			'placeholder' => __( 'Insert description', 'woocommerce-coinbase-commerce' ),
			'default'     => get_bloginfo( 'description' ),
		),
	)
);