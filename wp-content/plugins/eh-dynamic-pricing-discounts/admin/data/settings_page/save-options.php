<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
if (!defined('WPINC')) {
    die;
}

if (isset($_REQUEST['submit'])) {
    $enabled_modes=!empty($_REQUEST['enabled_modes']) ? $_REQUEST['enabled_modes'] : array();
    $prev_data = array(
        'product_rules_on_off' => in_array('product_rules',$enabled_modes) ? 'enable':'disable',
        'combinational_rules_on_off' => in_array('combinational_rules',$enabled_modes) ? 'enable':'disable',
        'category_rules_on_off' => in_array('category_rules',$enabled_modes) ? 'enable':'disable',
        'cat_comb_rules_on_off' => in_array('cat_combinational_rules',$enabled_modes) ? 'enable':'disable',
        'cart_rules_on_off' => in_array('cart_rules_on_off',$enabled_modes) ? 'enable':'disable',
        'buy_and_get_free_rules_on_off' => in_array('buy_and_get_free_rules',$enabled_modes) ? 'enable':'disable',
        'BOGO_category_rules_on_off' => in_array('BOGO_category_rules',$enabled_modes) ? 'enable':'disable',
        'price_table_on_off' => !empty($_REQUEST['price_table_on_off']) ? $_REQUEST['price_table_on_off'] : 'disable',
        'xa_product_add_on_option' => !empty($_REQUEST['xa_product_add_on_option']) ? $_REQUEST['xa_product_add_on_option'] : 'disable',
        'offer_table_on_off' => !empty($_REQUEST['offer_table_on_off']) ? $_REQUEST['offer_table_on_off'] : 'disable',
        'auto_add_free_product_on_off' => !empty($_REQUEST['auto_add_free_product_on_off']) ? $_REQUEST['auto_add_free_product_on_off'] : 'enable',
        'pricing_table_qnty_shrtcode' => !empty($_REQUEST['pricing_table_qnty_shrtcode']) ? $_REQUEST['pricing_table_qnty_shrtcode'] : 'nos.',
        'pricing_table_position' => !empty($_REQUEST['pricing_table_position']) ? $_REQUEST['pricing_table_position'] : 'woocommerce_before_add_to_cart_button',
        'offer_table_position' => !empty($_REQUEST['offer_table_position']) ? $_REQUEST['offer_table_position'] : 'woocommerce_before_add_to_cart_button',
        'mode' => !empty($_REQUEST['mode']) ? $_REQUEST['mode'] : 'first_match',
        'execution_order' => $enabled_modes
    );


    update_option('xa_dynamic_pricing_setting', $prev_data);
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Saved Successfully', 'eh-dynamic-pricing-discounts'); ?></p>
    </div>
    <?php
    wp_safe_redirect(admin_url('admin.php?page=dynamic-pricing-main-page&tab=' . $active_tab));
} else {
    echo '<div class="notice notice-error is-dismissible">';
    echo '<p>' . _e('Please Enter All Fields!! Then Save', 'eh-dynamic-pricing-discounts') . '</p> </div>';
}
