<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RP_WCDPD_Condition_Product_Property')) {
    require_once('rp-wcdpd-condition-product-property.class.php');
}

/**
 * Condition: Product Property - Stock Quantity
 *
 * @class RP_WCDPD_Condition_Product_Property_Stock_Quantity
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Condition_Product_Property_Stock_Quantity')) {

class RP_WCDPD_Condition_Product_Property_Stock_Quantity extends RP_WCDPD_Condition_Product_Property
{
    protected $key          = 'stock_quantity';
    protected $contexts     = array('product_pricing_product', 'product_pricing_bogo_product', 'cart_discounts_product', 'checkout_fees_product');
    protected $method       = 'numeric';
    protected $fields       = array(
        'after'     => array('number'),
    );
    protected $position     = 30;

    // Singleton instance
    protected static $instance = false;

    /**
     * Singleton control
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->hook();
    }

    /**
     * Get label
     *
     * @access public
     * @return string
     */
    public function get_label()
    {
        return __('Product stock quantity', 'rp_wcdpd');
    }

    /**
     * Get value to compare against condition
     *
     * @access public
     * @param array $params
     * @return mixed
     */
    public function get_value($params)
    {
        if (!empty($params['item_id']) || !empty($params['child_id'])) {

            // Select correct product id
            $product_id = !empty($params['child_id']) ? $params['child_id'] : $params['item_id'];

            // Attempt to load product
            if ($product = wc_get_product($product_id)) {

                // Get product stock quantity
                return $product->get_stock_quantity();
            }
        }

        return null;
    }




}

RP_WCDPD_Condition_Product_Property_Stock_Quantity::get_instance();

}
