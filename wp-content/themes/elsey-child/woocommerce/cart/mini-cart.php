<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_mini_cart' ); ?>
<a href="javascript:void(0);" id="close-cart-trigger" class="minicart__link--close-cart cta cta--underlined txt--upper">Close</a>
<p class="minicart__header align--center heading poppins">Your Shopping Bag</p>
<?php if ( ! WC()->cart->is_empty() ) : ?>

	<div class="woocommerce-mini-cart cart_list product_list_widget minicart__products <?php echo esc_attr( $args['list_class'] ); ?>" data-cancel-scroll="true" data-initialized="true">
		<?php
			do_action( 'woocommerce_before_mini_cart_contents' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
					$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
					$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<div class="minicart__product mini-product--group woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
						
						<?php if ( ! $_product->is_visible() ) : ?>
							<?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ) . ''; ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $product_permalink ); ?>" class="mini-product__img">
								<?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ) . ''; ?>
							</a>
						<?php endif; ?>
						<div class="mini-product__info">
							<div class="mini-product__item mini-product__name p4"><?php echo $product_name ?></div>
						<?php
						echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
							'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
							esc_url( WC()->cart->get_remove_url( $cart_item_key ) ),
							__( 'Remove this item', 'woocommerce' ),
							esc_attr( $product_id ),
							esc_attr( $cart_item_key ),
							esc_attr( $_product->get_sku() )
						), $cart_item_key );
						?>
						<?php echo WC()->cart->get_item_data( $cart_item ); ?>
						
						<?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<div class="mini-product__item mini-product__attribute">' . sprintf( '<span class="label">数量: </span><span class="value">%s</span>', $cart_item['quantity'] ) . '</div>', $cart_item, $cart_item_key ); ?>

						<?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<div class="mini-product__item mini-product__price lato">' . sprintf( '%s', $product_price ) . '</div>', $cart_item, $cart_item_key ); ?>
						</div>
					</div>
					<?php
				}
			}

			do_action( 'woocommerce_mini_cart_contents' );
		?>
		
	</div>

	<div class="woocommerce-mini-cart__total total order__summary__row"><span class="label"><?php _e( 'Subtotal', 'woocommerce' ); ?>:</span><span class="value lato bigger"><?php echo WC()->cart->get_cart_subtotal(); ?></span></div>

	<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

	<div class="woocommerce-mini-cart__buttons buttons order__actions--bottom"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></div>

<?php else : ?>

	<p class="woocommerce-mini-cart__empty-message"><?php _e( 'No products in the cart.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
