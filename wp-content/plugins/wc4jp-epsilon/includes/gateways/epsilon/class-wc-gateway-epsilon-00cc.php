<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Epsilon Pro Payment Gateway
 *
 * Provides a Epsilon Credit Card Payment Gateway (Link Type).
 *
 * @class 		WC_Epsilon
 * @extends		WC_Gateway_Epsilon_Pro_CC
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_Epsilon_Pro_CC extends WC_Payment_Gateway {


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'epsilon_pro_cc';
		$this->has_fields        = false;
		$this->method_title      = __( 'Epsilon Credit Card', 'wc4jp-epsilon' );
		
        // When no save setting error at chackout page
		if(is_null($this->title)){
			$this->title = __( 'Please set this payment at Control Panel! ', 'wc4jp-epsilon' ).$this->method_title;
		}

		//Epsilon Setting IDs
		$this->contract_code = get_option('wc-epsilon-pro-cid');
		$this->contract_password = get_option('wc-epsilon-pro-cpass');
		$this->st_code = '10000-0000-00000-00000-00000-00000-00000';

        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'Epsilon Credit Card', 'wc4jp-epsilon' );
		$this->method_description = __( 'Allows payments by Epsilon Credit Card in Japan.', 'wc4jp-epsilon' );

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Actions
		add_action( 'woocommerce_receipt_epsilon_pro_cc',                              array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'wc4jp-epsilon' ),
				'label'       => __( 'Enable Epsilon Credit Card Payment', 'wc4jp-epsilon' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'wc4jp-epsilon' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc4jp-epsilon' ),
				'default'     => __( 'Credit Card (Epsilon)', 'wc4jp-epsilon' )
			),
			'description' => array(
				'title'       => __( 'Description', 'wc4jp-epsilon' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc4jp-epsilon' ),
				'default'     => __( 'Pay with your credit card via Epsilon.', 'wc4jp-epsilon' )
			),
			'order_button_text' => array(
				'title'       => __( 'Order Button Text', 'woo-paydesign' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-paydesign' ),
				'default'     => __( 'Proceed to Epsilon Credit Card', 'wc4jp-epsilon' )
			),
			'testmode' => array(
				'title'       => __( 'Test Mode', 'wc4jp-epsilon' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Epsilon Test mode For Credit Card', 'wc4jp-epsilon' ),
				'default'     => 'yes',
				'description' => sprintf( __( 'Please check you want to use Epsilon Test mode for Credit Card.', 'wc4jp-epsilon' )),
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 */
	function process_payment( $order_id ) {
		include_once( 'includes/class-wc-gateway-epsilon-request.php' );

		$order          = wc_get_order( $order_id );
		$epsilon_request = new WC_Gateway_Epsilon_Request( $this );
		$response_array = $epsilon_request->get_order_to_epsilon( $order ,$this->contract_code, $this->st_code, $this->testmode, $this->id);

		if($response_array['error_msg']){
			$order->add_order_note('Error : '.mb_convert_encoding($response_array['error_msg'],'UTF-8','auto'));
			if(is_checkout())wc_add_notice( mb_convert_encoding($response_array['error_msg'],'UTF-8','auto'), $notice_type = 'error' );
		}else{
			// Mark as pending
			$order->update_status( 'pending', __( 'Paydesign Payment Processing.', 'woo-paydesign' ) );
			
			update_post_meta($order_id, 'epsilon_response', $response_array);
			update_post_meta($order_id, 'epsilon_type', 'creditcard');
			
			return array(
				'result'   => 'success',
				'redirect' => $response_array['redirect_url']
			);
		}
	}

	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'wc4jp-epsilon' ) . '</p>';
	}

	/**
	 * Get post data if set
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $_POST[ $name ];
		}
		return null;
	}
}
/**
 * Add the gateway to woocommerce
 */
function add_wc_epsilon_pro_cc_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Epsilon_Pro_CC';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_epsilon_pro_cc_gateway' );
