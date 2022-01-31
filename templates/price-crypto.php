<?php if( ! empty( $prices ) ) : ?>
	<span class="crypto-price">
		<span class="crypto-price-inner">
			<span class="crypto-currencies">
				<?php foreach( $prices as $curr => $price ) : ?><span class="crypto-currency <?php echo strtolower( $curr ); ?><?php echo 'BTC' === $curr ? ' active' : ''; ?>" data-price="<?php echo $price ?>" data-currency="<?php _e( $curr, 'woocommerce-coinbase-commerce' ); ?>"></span><?php endforeach; ?>
			</span>
			<span class="crypto-amount-container">
				<span class="crypto-amount" id="crypto-amount"><?php echo $prices[ key( $prices ) ]; ?> <?php _e( key( $prices ), 'woocommerce-coinbase-commerce' ); ?></span>
			</span>
		</span>
	</span>
<?php endif; ?>