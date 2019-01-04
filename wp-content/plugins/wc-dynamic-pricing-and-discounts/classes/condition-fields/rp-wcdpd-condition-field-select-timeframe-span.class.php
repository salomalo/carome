<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RP_WCDPD_Condition_Field_Select_Timeframe')) {
    require_once('rp-wcdpd-condition-field-select-timeframe.class.php');
}

/**
 * Condition Field: Select - Timeframe Span
 *
 * @class RP_WCDPD_Condition_Field_Select_Timeframe_Span
 * @package WooCommerce Dynamic Pricing & Discounts
 * @author RightPress
 */
if (!class_exists('RP_WCDPD_Condition_Field_Select_Timeframe_Span')) {

class RP_WCDPD_Condition_Field_Select_Timeframe_Span extends RP_WCDPD_Condition_Field_Select_Timeframe
{
    protected $key = 'timeframe_span';

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
        $this->hook();
    }





}

RP_WCDPD_Condition_Field_Select_Timeframe_Span::get_instance();

}
